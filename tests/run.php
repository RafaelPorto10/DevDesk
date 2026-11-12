<?php
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

use DevDesk\Domain\Status;

$fails = 0;
function check(string $name, bool $ok): void { global $fails; echo ($ok ? '  ✔ ' : '  ✘ ') . $name . "\n"; if (!$ok) $fails++; }
function throws(callable $fn, string $class): bool { try { $fn(); } catch (Throwable $e) { return $e instanceof $class; } return false; }

$svc = makeService('sqlite::memory:', sys_get_temp_dir() . '/devdesk-test.log');
$new = ['title' => 'Sem internet no setor', 'category' => 'rede', 'priority' => 'alta', 'requester_email' => 'ana@empresa.com'];

echo "Chamados\n";
$t = $svc->open($new);
check('abre chamado com código sequencial', $t->code === 'CH-0001');
check('status inicial é aberto', $t->status() === Status::Aberto);
check('rejeita e-mail inválido', throws(fn() => $svc->open(['requester_email' => 'x'] + $new), InvalidArgumentException::class));
check('rejeita categoria inválida', throws(fn() => $svc->open(['category' => 'xyz'] + $new), InvalidArgumentException::class));

echo "Máquina de estados\n";
check('aberto -> resolvido é proibido', throws(fn() => $svc->changeStatus($t->id, Status::Resolvido), DomainException::class));
$svc->changeStatus($t->id, Status::EmAndamento);
$r = $svc->changeStatus($t->id, Status::Resolvido);
check('em andamento -> resolvido grava resolved_at', $r->resolvedAt() !== null);
$svc->changeStatus($t->id, Status::Fechado);
check('fechado é estado final', throws(fn() => $svc->changeStatus($t->id, Status::Aberto), DomainException::class));

echo "Relatórios (GROUP BY)\n";
$svc->open(['priority' => 'baixa'] + $new);
check('stats por status', $svc->stats()['por_status'] === ['aberto' => 1, 'fechado' => 1]);
check('filtro por prioridade', count($svc->list(null, DevDesk\Domain\Priority::Baixa)) === 1);

echo $fails === 0 ? "\nTudo certo ✔\n" : "\n$fails falha(s)\n";
exit($fails === 0 ? 0 : 1);
