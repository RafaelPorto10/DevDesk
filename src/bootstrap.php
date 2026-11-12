<?php
declare(strict_types=1);

// Autoloader PSR-4 simples (sem Composer): DevDesk\Domain\Ticket -> src/Domain/Ticket.php
// Contracts/Enums/Adapters têm várias declarações por arquivo, então carregamos explicitamente.
require_once __DIR__ . '/Domain/Enums.php';
require_once __DIR__ . '/Domain/Contracts.php';
require_once __DIR__ . '/Infrastructure/Adapters.php';
spl_autoload_register(function (string $class): void {
    $file = __DIR__ . '/' . str_replace(['DevDesk\\', '\\'], ['', '/'], $class) . '.php';
    if (is_file($file)) require $file;
});

use DevDesk\Application\TicketService;
use DevDesk\Infrastructure\{SqliteTicketRepository, LogNotifier, DefaultSlaPolicy};

/** Composition root: único lugar que conhece as implementações concretas. */
function makeService(string $dsn, string $logFile): TicketService
{
    $repo = new SqliteTicketRepository(SqliteTicketRepository::connect($dsn));
    return new TicketService($repo, $repo, new LogNotifier($logFile), new DefaultSlaPolicy());
}
