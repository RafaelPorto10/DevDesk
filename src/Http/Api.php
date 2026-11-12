<?php
declare(strict_types=1);
namespace DevDesk\Http;

use DevDesk\Application\TicketService;
use DevDesk\Domain\{Status, Priority};
use DomainException;
use InvalidArgumentException;
use Throwable;

/** Camada HTTP: traduz requisição -> caso de uso -> JSON. Não contém regra de negócio. */
final class Api
{
    public function __construct(private TicketService $service) {}

    /** @return array{0:int,1:array} [status HTTP, corpo] */
    public function handle(string $method, string $path, array $query, array $body): array
    {
        try {
            if ($path === '/api/tickets' && $method === 'GET') {
                $s = isset($query['status']) ? Status::tryFrom($query['status']) : null;
                $p = isset($query['priority']) ? Priority::tryFrom($query['priority']) : null;
                return [200, $this->service->list($s, $p)];
            }
            if ($path === '/api/tickets' && $method === 'POST') {
                return [201, $this->service->open($body)->toArray()];
            }
            if ($path === '/api/stats' && $method === 'GET') {
                return [200, $this->service->stats()];
            }
            if ($method === 'PATCH' && preg_match('#^/api/tickets/(\d+)/status$#', $path, $m)) {
                $next = Status::tryFrom($body['status'] ?? '') ?? throw new InvalidArgumentException('Status inválido.');
                return [200, $this->service->changeStatus((int) $m[1], $next)->toArray()];
            }
            return [404, ['error' => 'Rota não encontrada.']];
        } catch (InvalidArgumentException $e) {
            return [422, ['error' => $e->getMessage()]];       // dados inválidos
        } catch (DomainException $e) {
            return [409, ['error' => $e->getMessage()]];       // regra de negócio violada
        } catch (Throwable $e) {
            return [500, ['error' => 'Erro interno.']];
        }
    }
}
