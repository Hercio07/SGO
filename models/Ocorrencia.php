<?php
// Model Ocorrencia: registos, participantes, anexos e histórico das ocorrências.
class Ocorrencia {
    private PDO $pdo;

    // Obtém a ligação PDO criada em config/database.php.
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    private function consultar(string $sql, array $parametros = []): PDOStatement {
        $consulta = $this->pdo->prepare($sql);
        $consulta->execute($parametros);
        return $consulta;
    }

    private function listarResultados(string $sql, array $parametros = []): array {
        return $this->consultar($sql, $parametros)->fetchAll();
    }

    private function obterResultado(string $sql, array $parametros = []) {
        return $this->consultar($sql, $parametros)->fetch();
    }

    private function obterValor(string $sql, array $parametros = []) {
        return $this->consultar($sql, $parametros)->fetchColumn();
    }

    public function obterMapa(array $filtros = []): array {
        $where = 'o.latitude IS NOT NULL AND o.longitude IS NOT NULL';
        $parametros = [];
        $where .= ' AND '.OcorrenciaService::condicaoValidacao($filtros,$parametros);
        if (!empty($filtros['natureza_dados'])) { $where .= ' AND o.natureza_dados=?'; $parametros[] = $filtros['natureza_dados']; }
        if (!empty($filtros['esquadra_id'])) {
            $where .= ' AND o.esquadra_id=?';
            $parametros[] = $filtros['esquadra_id'];
        }
        if (!empty($filtros['agente_id'])) {
            $where .= ' AND o.agente_id=?';
            $parametros[] = $filtros['agente_id'];
        }
        return $this->listarResultados(
            "SELECT o.id, o.numero, o.latitude, o.longitude, o.bairro, o.prioridade, o.estado,
                    o.data_ocorrencia, o.hora_ocorrencia, o.esquadra_id,
                    t.nome AS tipo_nome, e.nome AS esquadra_nome
             FROM ocorrencias o
             JOIN tipos_ocorrencia t ON o.tipo_id=t.id
             JOIN esquadras e ON o.esquadra_id=e.id
             WHERE {$where}
             ORDER BY o.created_at DESC LIMIT 500",
            $parametros
        );
    }

    public function guardarAnexo(int $ocorrenciaId, string $nome, string $arquivo, string $mime, int $tamanho, int $uploaderId): void {
        $this->consultar(
            'INSERT INTO anexos_ocorrencia (ocorrencia_id, nome_original, nome_arquivo, tipo_mime, tamanho, uploader_id)
             VALUES (?,?,?,?,?,?)',
            [$ocorrenciaId, $nome, $arquivo, $mime, $tamanho, $uploaderId]
        );
    }

    public function anexoParaExcluir(int $id) {
        return $this->obterResultado(
            'SELECT a.*, o.agente_id, o.esquadra_id, o.estado, o.validacao_estado, o.investigador_id
             FROM anexos_ocorrencia a
             JOIN ocorrencias o ON a.ocorrencia_id = o.id
             WHERE a.id = ?',
            [$id]
        );
    }

    public function excluirAnexo(int $id): void {
        $this->consultar('DELETE FROM anexos_ocorrencia WHERE id = ?', [$id]);
    }

    public function anexoParaAbrir(int $id) {
        return $this->obterResultado(
            'SELECT a.nome_original, a.nome_arquivo, a.tipo_mime, o.agente_id, o.esquadra_id, o.validacao_estado, o.investigador_id
             FROM anexos_ocorrencia a
             JOIN ocorrencias o ON a.ocorrencia_id = o.id
             WHERE a.id=?',
            [$id]
        );
    }

    // Consulta os registos, aplicando os filtros e a paginação recebidos.
    public function listarTodos($filtros = [], $offset = 0, $limit = 15) {
        $sql = 'SELECT o.*, t.nome AS tipo_nome, e.nome AS esquadra_nome, u.nome AS agente_nome, u.patente
                FROM ocorrencias o
                JOIN tipos_ocorrencia t ON o.tipo_id = t.id
                JOIN esquadras e ON o.esquadra_id = e.id
                JOIN utilizadores u ON o.agente_id = u.id
                WHERE 1=1';
        $parametros = [];

        $this->aplicarFiltros($sql, $parametros, $filtros);

        $sql .= ' ORDER BY o.created_at DESC LIMIT ? OFFSET ?';
        $parametros[] = (int)$limit;
        $parametros[] = (int)$offset;

        return $this->listarResultados($sql, $parametros);
    }

    // Conta os registos que correspondem aos filtros recebidos.
    public function contar($filtros = []) {
        $sql = 'SELECT COUNT(*) FROM ocorrencias o WHERE 1=1';
        $parametros = [];
        $this->aplicarFiltros($sql, $parametros, $filtros);
        return (int)$this->obterValor($sql, $parametros);
    }

    private function aplicarFiltros(string &$sql, array &$parametros, array $filtros): void {
        $sql .= ' AND '.OcorrenciaService::condicaoValidacao($filtros,$parametros);
        if (!empty($filtros['conclusao_estado'])) { $sql .= ' AND o.conclusao_estado=?'; $parametros[] = $filtros['conclusao_estado']; }
        if (!empty($filtros['em_curso'])) $sql .= " AND o.estado IN ('aberta','em_investigacao')";
        if (!empty($filtros['pesquisa'])) {
            $sql .= ' AND (o.numero LIKE ? OR o.bairro LIKE ? OR o.descricao_factos LIKE ?
                          OR EXISTS (SELECT 1 FROM participantes_ocorrencia p
                                     WHERE p.ocorrencia_id = o.id AND p.nome LIKE ?))';
            $p = '%' . $filtros['pesquisa'] . '%';
            $parametros = array_merge($parametros, [$p, $p, $p, $p]);
        }
        if (!empty($filtros['tipo_id']))          { $sql .= ' AND o.tipo_id=?';          $parametros[] = $filtros['tipo_id']; }
        if (!empty($filtros['estado']))           { $sql .= ' AND o.estado=?';           $parametros[] = $filtros['estado']; }
        if (!empty($filtros['prioridade']))       { $sql .= ' AND o.prioridade=?';       $parametros[] = $filtros['prioridade']; }
        if (!empty($filtros['natureza_dados'])) { $sql .= ' AND o.natureza_dados=?'; $parametros[] = $filtros['natureza_dados']; }
        if (!empty($filtros['esquadra_id']))      { $sql .= ' AND o.esquadra_id=?';      $parametros[] = $filtros['esquadra_id']; }
        if (!empty($filtros['agente_id']))        { $sql .= ' AND o.agente_id=?';        $parametros[] = $filtros['agente_id']; }
        if (!empty($filtros['data_inicio']))      { $sql .= ' AND o.data_ocorrencia>=?'; $parametros[] = $filtros['data_inicio']; }
        if (!empty($filtros['data_fim']))         { $sql .= ' AND o.data_ocorrencia<=?'; $parametros[] = $filtros['data_fim']; }
    }

    // Procura um registo pelo seu identificador.
    public function buscarPorId($id) {
        return $this->obterResultado(
            'SELECT o.*, t.nome AS tipo_nome, e.nome AS esquadra_nome, e.tipo AS esquadra_tipo,
                    e.codigo AS esquadra_codigo, e.distrito AS esquadra_distrito,
                    u.nome AS agente_nome, u.patente AS agente_patente, u.numero_mecanografico,
                    i.nome AS investigador_nome, i.email AS investigador_email, cp.nome AS conclusao_proponente_nome, cd.nome AS conclusao_decisor_nome
             FROM ocorrencias o
             JOIN tipos_ocorrencia t ON o.tipo_id = t.id
             JOIN esquadras e ON o.esquadra_id = e.id
             JOIN utilizadores u ON o.agente_id = u.id
             LEFT JOIN utilizadores i ON o.investigador_id = i.id
             LEFT JOIN utilizadores cp ON o.conclusao_proposta_por=cp.id
             LEFT JOIN utilizadores cd ON o.conclusao_decidida_por=cd.id
             WHERE o.id = ?',
            [$id]
        );
    }

    // Gera o nº formal do Auto (ex: "0147/21ª ESQ/2026") na primeira vez que
    // o documento é impresso, e persiste-o — mantém o mesmo número em
    // reimpressões e cobre ocorrências antigas criadas antes desta coluna existir.
    public function garantirNumeroAuto(array $ocorrencia): array {
        if (!empty($ocorrencia['numero_auto'])) {
            return $ocorrencia;
        }

        // Mesmo problema de corrida que gerarNumero(): duas impressões do Auto
        // em simultâneo, para a mesma esquadra/tipo de peça, podem calcular o
        // mesmo número_auto. Tenta de novo se colidir com a chave única.
        for ($tentativa = 1; ; $tentativa++) {
            $numeroAuto = $this->gerarNumeroAuto((int)$ocorrencia['esquadra_id'], $ocorrencia['tipo_peca'], $ocorrencia['esquadra_nome']);
            try {
                $this->consultar('UPDATE ocorrencias SET numero_auto=? WHERE id=? AND numero_auto IS NULL', [$numeroAuto, $ocorrencia['id']]);
                break;
            } catch (\PDOException $e) {
                if ($tentativa >= 5 || !$this->chaveDuplicada($e, 'numero_auto')) {
                    throw $e;
                }
            }
        }

        $atual = $this->obterValor('SELECT numero_auto FROM ocorrencias WHERE id=?', [$ocorrencia['id']]);
        $ocorrencia['numero_auto'] = $atual ?: $numeroAuto;
        return $ocorrencia;
    }

    private function gerarNumeroAuto(int $esquadraId, string $tipoPeca, string $esquadraNome): string {
        $ano = date('Y');
        $seq = 1 + (int)$this->obterValor(
            "SELECT COUNT(*) FROM ocorrencias
             WHERE esquadra_id=? AND tipo_peca=? AND numero_auto IS NOT NULL AND YEAR(created_at)=?",
            [$esquadraId, $tipoPeca, $ano]
        );

        $ordinal = '—';
        if (preg_match('/(\d+)/', $esquadraNome, $m)) {
            $ordinal = $m[1] . 'ª ESQ';
        }

        return sprintf('%04d/%s/%s', $seq, $ordinal, $ano);
    }

    // Cria o registo com os dados recebidos.
    public function criar($dados) {
        $lat = isset($dados['latitude']) && $dados['latitude'] !== '' ? (float)$dados['latitude'] : null;
        $lng = isset($dados['longitude']) && $dados['longitude'] !== '' ? (float)$dados['longitude'] : null;

        // gerarNumero() lê o último número e incrementa em PHP, sem lock — dois
        // registos concorrentes podem calcular o mesmo número. Em vez de
        // bloquear a tabela, tenta de novo com um número recalculado se o
        // INSERT colidir com a chave única "numero".
        for ($tentativa = 1; ; $tentativa++) {
            $numero = $this->gerarNumero();
            try {
                $this->consultar(
                    'INSERT INTO ocorrencias (numero, tipo_id, tipo_peca, esquadra_id, agente_id, data_ocorrencia, hora_ocorrencia,
                     bairro, rua, referencia_local, descricao_factos,
                     objetos_recuperados, observacoes, prioridade, estado, validacao_estado, investigador_id, latitude, longitude)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
                    [
                        $numero, $dados['tipo_id'], $dados['tipo_peca'] ?? 'auto_noticia', $dados['esquadra_id'], $dados['agente_id'],
                        $dados['data_ocorrencia'], $dados['hora_ocorrencia'],
                        $dados['bairro'] ?? null, $dados['rua'] ?? null,
                        $dados['referencia_local'] ?? null, $dados['descricao_factos'],
                        $dados['objetos_recuperados'] ?? null, $dados['observacoes'] ?? null,
                        $dados['prioridade'] ?? 'media', 'aberta', 'pendente', null, $lat, $lng
                    ]
                );
                return (int)$this->pdo->lastInsertId();
            } catch (\PDOException $e) {
                if ($tentativa >= 5 || !$this->chaveDuplicada($e, 'numero')) {
                    throw $e;
                }
            }
        }
    }

    private function chaveDuplicada(\PDOException $e, string $keyName): bool {
        if ($e->getCode() !== '23000') return false;
        return (bool)preg_match("/for key '(?:[\w.]+\.)?" . preg_quote($keyName, '/') . "'/", $e->getMessage());
    }

    // Grava as alterações de um registo existente.
    public function atualizar($id, $dados) {
        $lat = isset($dados['latitude']) && $dados['latitude'] !== '' ? (float)$dados['latitude'] : null;
        $lng = isset($dados['longitude']) && $dados['longitude'] !== '' ? (float)$dados['longitude'] : null;
        $this->consultar(
            'UPDATE ocorrencias SET tipo_id=?, tipo_peca=?, data_ocorrencia=?, hora_ocorrencia=?, bairro=?, rua=?,
             referencia_local=?, descricao_factos=?,
             objetos_recuperados=?, observacoes=?, prioridade=?, latitude=?, longitude=?
             WHERE id=?',
            [
                $dados['tipo_id'], $dados['tipo_peca'] ?? 'auto_noticia', $dados['data_ocorrencia'], $dados['hora_ocorrencia'],
                $dados['bairro'] ?? null, $dados['rua'] ?? null,
                $dados['referencia_local'] ?? null, $dados['descricao_factos'],
                $dados['objetos_recuperados'] ?? null, $dados['observacoes'] ?? null,
                $dados['prioridade'] ?? 'media', $lat, $lng, $id
            ]
        );
    }

    public function atualizarEstado($id, $estado) {
        $this->consultar('UPDATE ocorrencias SET estado=? WHERE id=?', [$estado, $id]);
    }

    public function guardarNatureza(int $id, array $dados): void {
        $this->consultar('UPDATE ocorrencias SET natureza_dados=?, fonte_informacao=? WHERE id=?',
            [$dados['natureza_dados'] ?? 'por_validar',trim($dados['fonte_informacao'] ?? '') ?: null,$id]);
    }

    public function validar(int $id, int $supervisorId, string $decisao, string $motivo): void {
        $this->consultar('UPDATE ocorrencias SET validacao_estado=?, supervisor_id=?, data_validacao=NOW(), motivo_rejeicao=? WHERE id=?',
            [$decisao,$supervisorId,$decisao === 'rejeitada' ? trim($motivo) : null,$id]);
    }

    public function resubmeter(int $id): void {
        $this->consultar("UPDATE ocorrencias SET validacao_estado='pendente', supervisor_id=NULL, data_validacao=NULL, motivo_rejeicao=NULL WHERE id=?",[$id]);
    }

    public function contarPorValidarEsquadra(int $esquadraId): int {
        return (int)$this->obterValor("SELECT COUNT(*) FROM ocorrencias WHERE esquadra_id=? AND validacao_estado='pendente' AND estado<>'arquivada'",[$esquadraId]);
    }

    public function atribuirInvestigador(int $id, int $investigadorId): void {
        $this->consultar('UPDATE ocorrencias SET investigador_id=? WHERE id=?', [$investigadorId, $id]);
    }

    // Investigadores activos elegíveis para receber ocorrências desta esquadra.
    public function listarInvestigadoresEsquadra(int $esquadraId): array {
        return $this->listarResultados(
            "SELECT u.id, u.nome, u.patente,
                    (SELECT COUNT(*) FROM ocorrencias o WHERE o.investigador_id=u.id AND o.esquadra_id=u.esquadra_id
                     AND o.validacao_estado='aprovada' AND o.estado IN ('aberta','em_investigacao')) AS casos_activos
             FROM utilizadores u
             WHERE u.perfil='investigador' AND u.esquadra_id=? AND u.ativo=1
             ORDER BY casos_activos, u.nome",
            [$esquadraId]
        );
    }

    public function limparAtribuicao(int $id): void {
        $this->consultar('UPDATE ocorrencias SET investigador_id=NULL WHERE id=?',[$id]);
    }

    public function proporConclusao(int $id, int $autor, string $texto, string $destino): void {
        $this->consultar("UPDATE ocorrencias SET conclusao_estado='pendente',conclusao_proposta=?,conclusao_destino=?,
            conclusao_proposta_por=?,conclusao_proposta_em=NOW(),conclusao_decidida_por=NULL,conclusao_decidida_em=NULL,conclusao_parecer=NULL WHERE id=?",
            [trim($texto),$destino,$autor,$id]);
    }

    public function decidirConclusao(int $id, int $supervisor, string $estado, string $parecer): void {
        $this->consultar('UPDATE ocorrencias SET conclusao_estado=?,conclusao_decidida_por=?,conclusao_decidida_em=NOW(),conclusao_parecer=? WHERE id=?',
            [$estado,$supervisor,trim($parecer),$id]);
    }

    public function contarPendentesEsquadra(int $esquadraId): int {
        $parametros = [$esquadraId];
        $escopo = OcorrenciaService::condicaoValidacao([],$parametros,'');
        return (int)$this->obterValor("SELECT COUNT(*) FROM ocorrencias WHERE esquadra_id=? AND estado NOT IN ('resolvida','arquivada') AND {$escopo}",$parametros);
    }

    public function listarParticipantes($ocorrenciaId) {
        return $this->listarResultados(
            'SELECT * FROM participantes_ocorrencia WHERE ocorrencia_id=? ORDER BY tipo, nome',
            [$ocorrenciaId]
        );
    }

    public function eliminarParticipante(int $participanteId, int $ocorrenciaId): void {
        $this->consultar('DELETE FROM participantes_ocorrencia WHERE id=? AND ocorrencia_id=?', [$participanteId, $ocorrenciaId]);
    }

    public function adicionarParticipante($ocorrenciaId, $dados) {
        // Campos opcionais vazios ficam sem valor, em vez de serem convertidos em zero.
        foreach (['idade', 'sexo', 'estado_civil', 'data_nascimento'] as $campo) {
            if (($dados[$campo] ?? '') === '') {
                $dados[$campo] = null;
            }
        }
        $this->consultar(
            'INSERT INTO participantes_ocorrencia (ocorrencia_id, tipo, nome, idade, sexo, estado_civil, data_nascimento,
             nome_pai, nome_mae, naturalidade, nacionalidade, profissao, documento, telefone, email, endereco,
             quarteirao, numero_casa, observacoes)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [$ocorrenciaId, $dados['tipo'], $dados['nome'] ?? null, $dados['idade'] ?? null,
             $dados['sexo'] ?? null, $dados['estado_civil'] ?? null, !empty($dados['data_nascimento']) ? $dados['data_nascimento'] : null,
             $dados['nome_pai'] ?? null, $dados['nome_mae'] ?? null, $dados['naturalidade'] ?? null,
             !empty($dados['nacionalidade']) ? $dados['nacionalidade'] : 'Moçambicana', $dados['profissao'] ?? null,
             $dados['documento'] ?? null, $dados['telefone'] ?? null,
             !empty($dados['email']) ? $dados['email'] : null,
             $dados['endereco'] ?? null, $dados['quarteirao'] ?? null, $dados['numero_casa'] ?? null,
             $dados['observacoes'] ?? null]
        );
    }

    public function notificarVitimas(int $ocorrenciaId, string $evento): void {
        $vitimas = $this->listarResultados(
            "SELECT nome, email FROM participantes_ocorrencia
             WHERE ocorrencia_id=? AND tipo='vitima' AND email IS NOT NULL AND email != ''",
            [$ocorrenciaId]
        );
        if (empty($vitimas)) return;

        $oc = $this->obterResultado(
            'SELECT o.numero, e.nome AS esquadra_nome
             FROM ocorrencias o JOIN esquadras e ON o.esquadra_id=e.id
             WHERE o.id=?',
            [$ocorrenciaId]
        );
        if (!$oc) return;

        foreach ($vitimas as $v) {
            $ok = EmailService::enviarAtualizacao(
                $v['email'],
                $v['nome'] ?? 'Cidadão',
                $oc['numero'],
                $evento,
                $oc['esquadra_nome']
            );
            if (!$ok) {
                error_log('[SIGOP] Falha ao enviar email para ' . $v['email'] . ' — ocorrência ' . $oc['numero'] . ' evento=' . $evento);
            }
        }
    }

    public function listarAnexos($ocorrenciaId) {
        return $this->listarResultados(
            'SELECT * FROM anexos_ocorrencia WHERE ocorrencia_id=? ORDER BY created_at DESC',
            [$ocorrenciaId]
        );
    }

    public function listarHistorico($ocorrenciaId) {
        return $this->listarResultados(
            'SELECT h.*, COALESCE(u.nome,\'Sistema\') AS utilizador_nome, u.patente
             FROM historico_ocorrencia h
             LEFT JOIN utilizadores u ON h.utilizador_id = u.id
             WHERE h.ocorrencia_id=? ORDER BY h.created_at ASC',
            [$ocorrenciaId]
        );
    }

    public function registarHistorico($ocorrenciaId, $userId, $acao, $descricao = '', $estadoAnterior = null, $estadoNovo = null) {
        $this->consultar(
            'INSERT INTO historico_ocorrencia (ocorrencia_id, utilizador_id, acao, descricao, estado_anterior, estado_novo)
             VALUES (?,?,?,?,?,?)',
            [$ocorrenciaId, $userId, $acao, $descricao, $estadoAnterior, $estadoNovo]
        );
    }

    private function montarFiltroEstatisticas(array $filtros, array &$parametros, string $prefix = ''): string {
        $conds = [OcorrenciaService::condicaoValidacao($filtros,$parametros,$prefix)];
        if (!empty($filtros['natureza_dados'])) { $conds[] = $prefix.'natureza_dados=?'; $parametros[] = $filtros['natureza_dados']; }
        if (!empty($filtros['esquadra_id']))      { $conds[] = $prefix . 'esquadra_id=?';      $parametros[] = $filtros['esquadra_id']; }
        if (!empty($filtros['agente_id']))        { $conds[] = $prefix . 'agente_id=?';        $parametros[] = $filtros['agente_id']; }
        if (!empty($filtros['data_inicio']))      { $conds[] = $prefix . 'data_ocorrencia>=?'; $parametros[] = $filtros['data_inicio']; }
        if (!empty($filtros['data_fim']))         { $conds[] = $prefix . 'data_ocorrencia<=?'; $parametros[] = $filtros['data_fim']; }
        if (!empty($filtros['tipo_id']))          { $conds[] = $prefix . 'tipo_id=?';          $parametros[] = $filtros['tipo_id']; }
        if (!empty($filtros['estado']))           { $conds[] = $prefix . 'estado=?';           $parametros[] = $filtros['estado']; }
        return implode(' AND ', $conds);
    }

    public function listarRecentes($limit = 10, array $filtros = []) {
        $parametros = [];
        $where  = $this->montarFiltroEstatisticas($filtros, $parametros, 'o.');
        $parametros[] = (int)$limit;
        return $this->listarResultados(
            "SELECT o.*, t.nome AS tipo_nome, e.nome AS esquadra_nome, u.nome AS agente_nome
             FROM ocorrencias o
             JOIN tipos_ocorrencia t ON o.tipo_id=t.id
             JOIN esquadras e ON o.esquadra_id=e.id
             JOIN utilizadores u ON o.agente_id=u.id
             WHERE {$where}
             ORDER BY o.created_at DESC LIMIT ?",
            $parametros
        );
    }

    public function listarCriticas(array $filtros = []) {
        $parametros = [];
        $where  = $this->montarFiltroEstatisticas($filtros, $parametros, 'o.');
        return $this->listarResultados(
            "SELECT o.*, t.nome AS tipo_nome, e.nome AS esquadra_nome
             FROM ocorrencias o
             JOIN tipos_ocorrencia t ON o.tipo_id=t.id
             JOIN esquadras e ON o.esquadra_id=e.id
             WHERE o.prioridade='critica' AND o.estado NOT IN ('resolvida','arquivada') AND {$where}
             ORDER BY o.created_at DESC",
            $parametros
        );
    }

    private function gerarNumero() {
        $ano  = date('Y');
        $last = $this->obterValor(
            "SELECT numero FROM ocorrencias WHERE numero LIKE ? ORDER BY id DESC LIMIT 1",
            ["OC-{$ano}-%"]
        );
        $seq = 1;
        if ($last) {
            $parts = explode('-', $last);
            $seq = (int)end($parts) + 1;
        }
        return sprintf('OC-%s-%06d', $ano, $seq);
    }
}
