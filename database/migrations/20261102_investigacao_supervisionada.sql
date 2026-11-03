-- Evolução aditiva do SGO. Executar apenas na base sgo ou numa cópia de teste.
-- Conserva as contas, senhas, ocorrências e históricos existentes.
ALTER TABLE utilizadores
    ADD COLUMN IF NOT EXISTS username VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS exigir_alteracao_senha TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS versao_sessao INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE utilizadores ADD UNIQUE INDEX IF NOT EXISTS uq_sgo_username (username);
ALTER TABLE log_sistema ADD COLUMN IF NOT EXISTS identificador_tentativa VARCHAR(150) NULL;
UPDATE log_sistema SET identificador_tentativa=username_tentativa WHERE identificador_tentativa IS NULL AND username_tentativa IS NOT NULL;
ALTER TABLE ocorrencias
    ADD COLUMN IF NOT EXISTS versao INT UNSIGNED NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS natureza_dados ENUM('por_validar','confirmados','simulados') NOT NULL DEFAULT 'por_validar',
    ADD COLUMN IF NOT EXISTS fonte_informacao VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS conclusao_estado ENUM('sem_proposta','pendente','aprovada','devolvida') NOT NULL DEFAULT 'sem_proposta',
    ADD COLUMN IF NOT EXISTS conclusao_proposta TEXT NULL,
    ADD COLUMN IF NOT EXISTS conclusao_destino ENUM('resolvida','arquivada') NULL,
    ADD COLUMN IF NOT EXISTS conclusao_proposta_por INT NULL,
    ADD COLUMN IF NOT EXISTS conclusao_proposta_em DATETIME NULL,
    ADD COLUMN IF NOT EXISTS conclusao_decidida_por INT NULL,
    ADD COLUMN IF NOT EXISTS conclusao_decidida_em DATETIME NULL,
    ADD COLUMN IF NOT EXISTS conclusao_parecer TEXT NULL,
    ADD INDEX IF NOT EXISTS idx_sgo_encerramento (esquadra_id,conclusao_estado),
    ADD INDEX IF NOT EXISTS idx_sgo_investigador (investigador_id,validacao_estado,estado);
CREATE TABLE IF NOT EXISTS rascunhos_ocorrencia (
    utilizador_id INT NOT NULL,
    contexto VARCHAR(40) NOT NULL,
    dados MEDIUMTEXT NOT NULL,
    versao INT UNSIGNED NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expira_em DATETIME NOT NULL,
    PRIMARY KEY (utilizador_id,contexto),
    CONSTRAINT fk_sgo_rascunho_utilizador FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- Corrige apenas os links locais copiados da instalação registo.
UPDATE notificacoes SET url=REPLACE(url,'http://localhost/registo/','http://localhost/SGO/') WHERE url LIKE 'http://localhost/registo/%';
