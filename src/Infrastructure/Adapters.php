<?php
declare(strict_types=1);
namespace DevDesk\Infrastructure;

use DevDesk\Domain\{Notifier, SlaPolicy, Priority};

/** Troque por um SmtpNotifier sem tocar no TicketService (LSP + DIP). */
final class LogNotifier implements Notifier
{
    public function __construct(private string $file) {}
    public function notify(string $to, string $subject, string $message): void
    {
        file_put_contents($this->file, sprintf("[%s] para=%s | %s | %s\n", date('c'), $to, $subject, $message), FILE_APPEND);
    }
}

final class DefaultSlaPolicy implements SlaPolicy
{
    public function hoursFor(Priority $priority): int
    {
        return match ($priority) { Priority::Critica => 2, Priority::Alta => 8, Priority::Media => 24, Priority::Baixa => 72 };
    }
}
