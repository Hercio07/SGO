-- Executar na base de dados existente antes de usar o fluxo de aprovação.
-- Não elimina registos nem altera decisões de validação já guardadas.
-- Registos sem validação guardada ficam pendentes para análise do supervisor.
-- Compatível com MariaDB (XAMPP); pode ser executada novamente.
SET @sigop_recuperar_validacao = NOT EXISTS (
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ocorrencias' AND COLUMN_NAME='validacao_estado'
);

ALTER TABLE ocorrencias
    ADD COLUMN IF NOT EXISTS validacao_estado ENUM('pendente','aprovada','rejeitada') NOT NULL DEFAULT 'pendente',
    ADD COLUMN IF NOT EXISTS supervisor_id INT(11) NULL,
    ADD COLUMN IF NOT EXISTS data_validacao DATETIME NULL,
    ADD COLUMN IF NOT EXISTS motivo_rejeicao TEXT NULL,
    ADD INDEX IF NOT EXISTS idx_validacao_esquadra (validacao_estado,esquadra_id),
    ADD INDEX IF NOT EXISTS idx_validacao_supervisor (supervisor_id);

-- Recupera apenas decisões explícitas do último evento de validação no histórico.
-- Uma resubmissão posterior conserva a ocorrência pendente. Na repetição, não
-- modifica decisões nem resubmissões guardadas pelo fluxo actual.
UPDATE ocorrencias o
JOIN (
    SELECT ocorrencia_id,MAX(id) AS ultimo_id FROM historico_ocorrencia
    WHERE acao IN ('Ocorrência Aprovada','Ocorrência Rejeitada','Ocorrência Resubmetida','Ocorrência Reesubmetida')
    GROUP BY ocorrencia_id
) ultima ON ultima.ocorrencia_id=o.id
JOIN historico_ocorrencia h ON h.id=ultima.ultimo_id
SET o.validacao_estado=CASE WHEN h.acao='Ocorrência Aprovada' THEN 'aprovada' ELSE 'rejeitada' END,
    o.supervisor_id=h.utilizador_id,o.data_validacao=h.created_at,
    o.motivo_rejeicao=CASE WHEN h.acao='Ocorrência Rejeitada' THEN h.descricao ELSE NULL END
WHERE @sigop_recuperar_validacao=1 AND h.acao IN ('Ocorrência Aprovada','Ocorrência Rejeitada');
