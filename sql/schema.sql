-- DevDesk: esquema do banco (SQLite)
-- Constraints usadas: PRIMARY KEY, NOT NULL, UNIQUE, CHECK, DEFAULT
CREATE TABLE IF NOT EXISTS tickets (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    code            TEXT    NOT NULL UNIQUE,                       -- ex.: CH-0001 (sem repetição)
    title           TEXT    NOT NULL,
    description     TEXT    NOT NULL DEFAULT '',
    category        TEXT    NOT NULL CHECK (category IN ('rede','hardware','software','acesso')),
    priority        TEXT    NOT NULL DEFAULT 'media'  CHECK (priority IN ('baixa','media','alta','critica')),
    status          TEXT    NOT NULL DEFAULT 'aberto' CHECK (status   IN ('aberto','em_andamento','resolvido','fechado')),
    requester_email TEXT    NOT NULL,
    created_at      TEXT    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at     TEXT
);
CREATE INDEX IF NOT EXISTS idx_tickets_status ON tickets(status);
