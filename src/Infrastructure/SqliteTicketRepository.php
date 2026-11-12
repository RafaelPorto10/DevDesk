<?php
declare(strict_types=1);
namespace DevDesk\Infrastructure;

use DevDesk\Domain\{Ticket, TicketReader, TicketWriter, Status, Priority, Category};
use PDO;

final class SqliteTicketRepository implements TicketReader, TicketWriter
{
    public function __construct(private PDO $pdo) {}

    public static function connect(string $dsn): PDO
    {
        $pdo = new PDO($dsn, options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        $pdo->exec((string) file_get_contents(__DIR__ . '/../../sql/schema.sql'));
        return $pdo;
    }

    public function nextCode(): string
    {
        $max = (int) $this->pdo->query('SELECT COALESCE(MAX(id), 0) FROM tickets')->fetchColumn();
        return sprintf('CH-%04d', $max + 1);
    }

    public function find(int $id): ?Ticket
    {
        $st = $this->pdo->prepare('SELECT * FROM tickets WHERE id = :id');   // prepared statement: evita SQL Injection
        $st->execute(['id' => $id]);
        $row = $st->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function search(?Status $status = null, ?Priority $priority = null): array
    {
        $sql = 'SELECT * FROM tickets WHERE 1=1';
        $params = [];
        if ($status)   { $sql .= ' AND status = :status';     $params['status'] = $status->value; }
        if ($priority) { $sql .= ' AND priority = :priority'; $params['priority'] = $priority->value; }
        $sql .= ' ORDER BY id DESC';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return array_map(fn(array $r) => $this->hydrate($r), $st->fetchAll());
    }

    public function save(Ticket $t): Ticket
    {
        if ($t->id === null) {
            $st = $this->pdo->prepare('INSERT INTO tickets (code,title,description,category,priority,status,requester_email,created_at)
                VALUES (:code,:title,:description,:category,:priority,:status,:email,:created)');
            $st->execute(['code' => $t->code, 'title' => $t->title, 'description' => $t->description,
                'category' => $t->category->value, 'priority' => $t->priority->value,
                'status' => $t->status()->value, 'email' => $t->requesterEmail, 'created' => $t->createdAt]);
            return $this->find((int) $this->pdo->lastInsertId());
        }
        $st = $this->pdo->prepare('UPDATE tickets SET status = :status, resolved_at = :resolved WHERE id = :id');
        $st->execute(['status' => $t->status()->value, 'resolved' => $t->resolvedAt(), 'id' => $t->id]);
        return $t;
    }

    public function stats(): array
    {
        $by = fn(string $col) => $this->pdo->query("SELECT $col AS k, COUNT(*) AS total FROM tickets GROUP BY $col")
            ->fetchAll(PDO::FETCH_KEY_PAIR);
        return ['por_status' => array_map('intval', $by('status')), 'por_prioridade' => array_map('intval', $by('priority'))];
    }

    private function hydrate(array $r): Ticket
    {
        return new Ticket((int) $r['id'], $r['code'], $r['title'], $r['description'],
            Category::from($r['category']), Priority::from($r['priority']), Status::from($r['status']),
            $r['requester_email'], $r['created_at'], $r['resolved_at']);
    }
}
