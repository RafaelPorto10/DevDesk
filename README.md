# DevDesk — Gerenciador de Chamados de TI

Sistema de chamados (service desk) feito em **PHP 8.1+ puro + SQLite**, com API REST e interface web.
Criado por um analista de infraestrutura que quer levar a vivência de suporte para o desenvolvimento.

## Como rodar

```bash
# requisitos: PHP 8.1+ com extensão pdo_sqlite
php -S localhost:8000 -t public public/index.php
# abra http://localhost:8000

php tests/run.php        # testes automatizados
```

## API REST

| Método | Rota | Descrição |
|---|---|---|
| GET | `/api/tickets?status=&priority=` | Lista chamados (com filtros) |
| POST | `/api/tickets` | Abre chamado (`title`, `category`, `priority`, `requester_email`, `description`) |
| PATCH | `/api/tickets/{id}/status` | Muda o status (`{"status":"em_andamento"}`) |
| GET | `/api/stats` | Totais por status e prioridade |

Códigos: `200` ok · `201` criado · `404` rota inexistente · `409` transição de status inválida · `422` dados inválidos.

```bash
curl -X POST localhost:8000/api/tickets -H 'Content-Type: application/json' \
  -d '{"title":"Sem internet no financeiro","category":"rede","priority":"alta","requester_email":"ana@empresa.com"}'
```

## Arquitetura e princípios SOLID

```
src/Domain          Ticket (entidade), Enums, Contracts (interfaces)
src/Application     TicketService (casos de uso)
src/Infrastructure  SqliteTicketRepository, LogNotifier, DefaultSlaPolicy
src/Http            Api (requisição -> caso de uso -> JSON)
src/bootstrap.php   composition root (único lugar que faz o "new" das implementações)
```

- **SRP** — cada classe tem um único motivo para mudar: `Ticket` (regras), `SqliteTicketRepository` (SQL), `Api` (HTTP), `TicketService` (orquestração).
- **OCP** — novas regras de SLA = nova classe que implementa `SlaPolicy`, sem editar o serviço.
- **LSP** — `LogNotifier` pode ser trocado por um `SmtpNotifier` sem quebrar quem usa `Notifier`.
- **ISP** — `TicketReader` e `TicketWriter` separados, em vez de uma interface gigante.
- **DIP** — `TicketService` depende de interfaces; as implementações são injetadas no `bootstrap.php`.

## Banco de dados

`sql/schema.sql` usa `PRIMARY KEY`, `NOT NULL`, `UNIQUE` (código do chamado), `CHECK` (categoria, prioridade, status) e `DEFAULT`.
`sql/reports.sql` traz consultas com `GROUP BY`, `HAVING`, `AVG`, `NOT IN` e combinações de `AND/OR` com parênteses.

## Boas práticas aplicadas

- `declare(strict_types=1)`, enums e `readonly` do PHP 8.1
- Máquina de estados: `aberto → em_andamento → resolvido → fechado` (transições inválidas retornam 409)
- Prepared statements (contra SQL Injection) e escape de HTML no front (contra XSS)
- Testes automatizados sem framework (`tests/run.php`)

## Próximos passos (ideias)

Autenticação com perfis (usuário/técnico), comentários e anexos no chamado, e-mail real via SMTP, Docker, pipeline de CI (GitHub Actions) e deploy — um caminho natural rumo a DevOps.
