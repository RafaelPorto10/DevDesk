<?php
declare(strict_types=1);
namespace DevDesk\Domain;

use InvalidArgumentException;
use DomainException;

/** Entidade: guarda e protege as regras do chamado (SRP: nada de SQL ou HTTP aqui). */
final class Ticket
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $code,
        public readonly string $title,
        public readonly string $description,
        public readonly Category $category,
        public readonly Priority $priority,
        private Status $status,
        public readonly string $requesterEmail,
        public readonly string $createdAt,
        private ?string $resolvedAt = null,
    ) {
        if (trim($title) === '' || strlen($title) > 120) {
            throw new InvalidArgumentException('Título é obrigatório (máx. 120 caracteres).');
        }
        if (!filter_var($requesterEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-mail do solicitante inválido.');
        }
    }

    public function status(): Status { return $this->status; }
    public function resolvedAt(): ?string { return $this->resolvedAt; }

    public function moveTo(Status $next): void
    {
        if (!$this->status->canMoveTo($next)) {
            throw new DomainException("Transição inválida: {$this->status->value} → {$next->value}");
        }
        $this->status = $next;
        if ($next === Status::Resolvido) {
            $this->resolvedAt = date('Y-m-d H:i:s');
        }
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id, 'code' => $this->code, 'title' => $this->title,
            'description' => $this->description, 'category' => $this->category->value,
            'priority' => $this->priority->value, 'status' => $this->status->value,
            'requester_email' => $this->requesterEmail,
            'created_at' => $this->createdAt, 'resolved_at' => $this->resolvedAt,
        ];
    }
}
