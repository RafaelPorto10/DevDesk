-- Consultas de relatório (o dashboard usa as duas primeiras via TicketReader::stats)

-- Total por status (GROUP BY + COUNT)
SELECT status, COUNT(*) AS total FROM tickets GROUP BY status;

-- Chamados críticos ou altos ainda em aberto (AND/OR com parênteses)
SELECT code, title FROM tickets
WHERE status NOT IN ('resolvido','fechado') AND (priority = 'critica' OR priority = 'alta');

-- Categorias com mais de 2 chamados (GROUP BY + HAVING)
SELECT category, COUNT(*) AS total FROM tickets GROUP BY category HAVING COUNT(*) > 2;

-- Tempo médio de resolução em horas (AVG)
SELECT ROUND(AVG((julianday(resolved_at) - julianday(created_at)) * 24), 2) AS horas
FROM tickets WHERE resolved_at IS NOT NULL;
