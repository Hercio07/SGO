<?php
// Model Auditoria: consultas dos históricos e respetivos filtros.
class Auditoria {
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

    public function listarHistorico(string $where, array $parametros, int $limit, int $offset): array {
        return $this->consultar(
            "SELECT h.id, h.acao, h.descricao, h.estado_anterior, h.estado_novo, h.created_at,
                    o.id AS ocorrencia_id, o.numero AS ocorrencia_numero,
                    e.nome AS esquadra_nome,
                    COALESCE(u.nome,'Sistema') AS utilizador_nome, u.perfil AS utilizador_perfil, u.patente AS utilizador_patente
             FROM historico_ocorrencia h
             JOIN ocorrencias o ON h.ocorrencia_id = o.id
             LEFT JOIN utilizadores u ON h.utilizador_id = u.id
             JOIN esquadras e ON o.esquadra_id = e.id
             WHERE {$where}
             ORDER BY h.created_at DESC LIMIT ? OFFSET ?",
            [...$parametros, $limit, $offset]
        )->fetchAll();
    }

    public function contarHistorico(string $where, array $parametros): int {
        return (int)$this->consultar(
            "SELECT COUNT(*) FROM historico_ocorrencia h
             JOIN ocorrencias o ON h.ocorrencia_id = o.id
             LEFT JOIN utilizadores u ON h.utilizador_id = u.id
             JOIN esquadras e ON o.esquadra_id = e.id
             WHERE {$where}",
            $parametros
        )->fetchColumn();
    }

    public function estatisticasHistorico(?int $esquadraId): array {
        $where = $esquadraId !== null ? 'o.esquadra_id = ?' : '1=1';
        $parametros = $esquadraId !== null ? [$esquadraId] : [];
        $where .= ' AND '.OcorrenciaService::condicaoValidacao([],$parametros);
        $base = 'FROM historico_ocorrencia h JOIN ocorrencias o ON h.ocorrencia_id=o.id';
        return [
            'hoje' => (int)$this->consultar("SELECT COUNT(*) {$base} WHERE DATE(h.created_at)=CURDATE() AND {$where}", $parametros)->fetchColumn(),
            'semana' => (int)$this->consultar("SELECT COUNT(*) {$base} WHERE h.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND {$where}", $parametros)->fetchColumn(),
            'uniqueOc' => (int)$this->consultar("SELECT COUNT(DISTINCT h.ocorrencia_id) {$base} WHERE {$where}", $parametros)->fetchColumn(),
        ];
    }

    public function listarAcessos(string $where, array $parametros, int $limit, int $offset): array {
        return $this->consultar(
            "SELECT l.id, l.tipo, l.acao, l.descricao, l.ip, l.created_at,
                    l.identificador_tentativa,
                    COALESCE(u.nome,'Sistema') AS utilizador_nome, u.perfil AS utilizador_perfil, u.patente AS utilizador_patente
             FROM log_sistema l
             LEFT JOIN utilizadores u ON l.utilizador_id = u.id
             WHERE {$where}
             ORDER BY l.created_at DESC LIMIT ? OFFSET ?",
            [...$parametros, $limit, $offset]
        )->fetchAll();
    }

    public function contarAcessos(string $where, array $parametros): int {
        return (int)$this->consultar(
            "SELECT COUNT(*) FROM log_sistema l
             LEFT JOIN utilizadores u ON l.utilizador_id = u.id
             WHERE {$where}",
            $parametros
        )->fetchColumn();
    }

    public function estatisticasAcessos(?int $esquadraId): array {
        $join = $esquadraId !== null ? 'JOIN utilizadores u ON l.utilizador_id=u.id' : '';
        $where = $esquadraId !== null ? 'u.esquadra_id = ?' : '1=1';
        $parametros = $esquadraId !== null ? [$esquadraId] : [];
        $base = "FROM log_sistema l {$join}";
        return [
            'hoje' => (int)$this->consultar("SELECT COUNT(*) {$base} WHERE DATE(l.created_at)=CURDATE() AND {$where}", $parametros)->fetchColumn(),
            'logins' => (int)$this->consultar("SELECT COUNT(*) {$base} WHERE l.acao='Login' AND DATE(l.created_at)=CURDATE() AND {$where}", $parametros)->fetchColumn(),
            'falhas' => (int)$this->consultar("SELECT COUNT(*) {$base} WHERE l.acao='Login Falhado' AND l.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND {$where}", $parametros)->fetchColumn(),
        ];
    }

    private function dataValida(string $dados): bool {
        $dt = DateTime::createFromFormat('Y-m-d', $dados);
        return $dt && $dt->format('Y-m-d') === $dados;
    }

    public function filtrosHistorico(array $filtros): array {
        $inicio = $this->dataValida($filtros['data_inicio']) ? $filtros['data_inicio'] : date('Y-m-01');
        $fim = $this->dataValida($filtros['data_fim']) ? $filtros['data_fim'] : date('Y-m-d');
        if ($fim < $inicio) $fim = $inicio;

        $condicoes = ['h.created_at >= ?', 'h.created_at <= ?'];
        $parametros = [$inicio . ' 00:00:00', $fim . ' 23:59:59'];
        $condicoes[] = OcorrenciaService::condicaoValidacao([],$parametros);
        if (!empty($filtros['esquadra_id'])) {
            $condicoes[] = 'o.esquadra_id = ?';
            $parametros[] = $filtros['esquadra_id'];
        }
        if (!empty($filtros['utilizador_id'])) {
            $condicoes[] = 'h.utilizador_id = ?';
            $parametros[] = $filtros['utilizador_id'];
        }
        if (!empty($filtros['pesquisa'])) {
            $condicoes[] = '(h.acao LIKE ? OR h.descricao LIKE ? OR o.numero LIKE ?)';
            $pesquisa = '%' . $filtros['pesquisa'] . '%';
            array_push($parametros, $pesquisa, $pesquisa, $pesquisa);
        }
        return [implode(' AND ', $condicoes), $parametros];
    }

    public function filtrosAcessos(array $filtros): array {
        $inicio = $this->dataValida($filtros['data_inicio']) ? $filtros['data_inicio'] : date('Y-m-01');
        $fim = $this->dataValida($filtros['data_fim']) ? $filtros['data_fim'] : date('Y-m-d');
        if ($fim < $inicio) $fim = $inicio;

        $condicoes = ['l.created_at >= ?', 'l.created_at <= ?'];
        $parametros = [$inicio . ' 00:00:00', $fim . ' 23:59:59'];
        if (!empty($filtros['esquadra_id'])) {
            $condicoes[] = 'u.esquadra_id = ?';
            $parametros[] = $filtros['esquadra_id'];
        }
        if (!empty($filtros['utilizador_id'])) {
            $condicoes[] = 'l.utilizador_id = ?';
            $parametros[] = $filtros['utilizador_id'];
        }
        if (!empty($filtros['tipo'])) {
            $condicoes[] = 'l.tipo = ?';
            $parametros[] = $filtros['tipo'];
        }
        if (!empty($filtros['pesquisa'])) {
            $condicoes[] = '(l.acao LIKE ? OR l.descricao LIKE ? OR l.identificador_tentativa LIKE ? OR l.ip LIKE ?)';
            $pesquisa = '%' . $filtros['pesquisa'] . '%';
            array_push($parametros, $pesquisa, $pesquisa, $pesquisa, $pesquisa);
        }
        return [implode(' AND ', $condicoes), $parametros];
    }
}
