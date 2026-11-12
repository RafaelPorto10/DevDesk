<?php
declare(strict_types=1);
namespace DevDesk\Application;

use DevDesk\Domain\{Ticket, TicketReader, TicketWriter, Notifier, SlaPolicy, Status, Priority, Category};
use DomainException;

/** Casos de uso. Só conhece interfaces (DIP); não sabe se o banco é SQLite ou MySQL. */
final class TicketService
{
    public function __construct(
        private TicketReader $reader,
        private TicketWriter $writer,
        private Notifier $notifier,
        private SlaPolicy $sla,
    ) {}

    public function open(array $in): Ticket
    {
        $ticket = new Ticket(
            null, $this->writer->nextCode(), trim((string) ($in['title'] ?? '')), trim((string) ($in['description'] ?? '')),
            Category::tryFrom($in['category'] ?? '') ?? throw new \InvalidArgumentException('Categoria inválida.'),
            Priority::tryFrom($in['priority'] ?? 'media') ?? Priority::Media,
            Status::Aberto, trim((string) ($in['requester_email'] ?? '')), date('Y-m-d H:i:s'),
        );
        $saved = $this->writer->save($ticket);
        $this->notifier->notify($saved->requesterEmail, "Chamado {$saved->code} aberto",
            "SLA de atendimento: {$this->sla->hoursFor($saved->priority)}h.");
        return $saved;
    }

    public function changeStatus(int $id, Status $next): Ticket
    {
        $ticket = $this->reader->find($id) ?? throw new DomainException('Chamado não encontrado.');
        $ticket->moveTo($next);
        $this->writer->save($ticket);
        $this->notifier->notify($ticket->requesterEmail, "Chamado {$ticket->code} atualizado", "Novo status: {$next->value}.");
        return $ticket;
    }

    public function list(?Status $s = null, ?Priority $p = null): array
    {
        return array_map(fn(Ticket $t) => $t->toArray() + ['sla_hours' => $this->sla->hoursFor($t->priority)], $this->reader->search($s, $p));
    }

    public function stats(): array { return $this->reader->stats(); }
}
