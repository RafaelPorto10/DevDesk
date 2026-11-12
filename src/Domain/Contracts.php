<?php
declare(strict_types=1);
namespace DevDesk\Domain;

/** ISP: interfaces pequenas e focadas. DIP: o serviço depende delas, não de implementações. */
interface TicketReader
{
    public function find(int $id): ?Ticket;
    /** @return Ticket[] */
    public function search(?Status $status = null, ?Priority $priority = null): array;
    public function stats(): array;
}

interface TicketWriter
{
    public function nextCode(): string;
    public function save(Ticket $ticket): Ticket;
}

interface Notifier
{
    public function notify(string $to, string $subject, string $message): void;
}

/** OCP: para criar uma nova regra de SLA, basta uma nova classe que implemente isto. */
interface SlaPolicy
{
    public function hoursFor(Priority $priority): int;
}
