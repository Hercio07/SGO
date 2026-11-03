<?php
// Model Relatorio: consultas agregadas para relatórios, gráficos e painel inicial.
class Relatorio {
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

    private function obterValor(string $sql, array $parametros = []) {
        return $this->consultar($sql, $parametros)->fetchColumn();
    }

    // Calcula os totais de ocorrências para os filtros recebidos.
    public function obterResumoMetricas(array $filtros = []): array {
        $parametros = [];
        $where  = $this->montarFiltroEstatisticas($filtros, $parametros);

        $base = "SELECT COUNT(*) FROM ocorrencias WHERE {$where}";
        return [
            'total'           => (int)$this->obterValor($base, $parametros),
            'hoje'            => (int)$this->obterValor("SELECT COUNT(*) FROM ocorrencias WHERE DATE(data_ocorrencia)=CURDATE() AND {$where}", $parametros),
            'abertas'         => (int)$this->obterValor("SELECT COUNT(*) FROM ocorrencias WHERE estado='aberta' AND {$where}", $parametros),
            'em_investigacao' => (int)$this->obterValor("SELECT COUNT(*) FROM ocorrencias WHERE estado='em_investigacao' AND {$where}", $parametros),
            'resolvidas'      => (int)$this->obterValor("SELECT COUNT(*) FROM ocorrencias WHERE estado='resolvida' AND {$where}", $parametros),
            'arquivadas'      => (int)$this->obterValor("SELECT COUNT(*) FROM ocorrencias WHERE estado='arquivada' AND {$where}", $parametros),
            'criticas'        => (int)$this->obterValor("SELECT COUNT(*) FROM ocorrencias WHERE prioridade='critica' AND estado NOT IN ('resolvida','arquivada') AND {$where}", $parametros),
        ];
    }

    private function montarFiltroEstatisticas(array $filtros, array &$parametros, string $prefix = ''): string {
        $conds = [OcorrenciaService::condicaoValidacao($filtros,$parametros,$prefix)];
        if (!empty($filtros['conclusao_estado'])) { $conds[] = $prefix.'conclusao_estado=?'; $parametros[] = $filtros['conclusao_estado']; }
        if (!empty($filtros['esquadra_id']))      { $conds[] = $prefix . 'esquadra_id=?';      $parametros[] = $filtros['esquadra_id']; }
        if (!empty($filtros['agente_id']))        { $conds[] = $prefix . 'agente_id=?';        $parametros[] = $filtros['agente_id']; }
        if (!empty($filtros['data_inicio']))      { $conds[] = $prefix . 'data_ocorrencia>=?'; $parametros[] = $filtros['data_inicio']; }
        if (!empty($filtros['data_fim']))         { $conds[] = $prefix . 'data_ocorrencia<=?'; $parametros[] = $filtros['data_fim']; }
        if (!empty($filtros['tipo_id']))          { $conds[] = $prefix . 'tipo_id=?';          $parametros[] = $filtros['tipo_id']; }
        if (!empty($filtros['estado']))           { $conds[] = $prefix . 'estado=?';           $parametros[] = $filtros['estado']; }
        if (!empty($filtros['prioridade']))       { $conds[] = $prefix . 'prioridade=?';       $parametros[] = $filtros['prioridade']; }
        if (!empty($filtros['natureza_dados'])) { $conds[] = $prefix . 'natureza_dados=?'; $parametros[] = $filtros['natureza_dados']; }
        return implode(' AND ', $conds);
    }

    private function montarFiltroGraficos(array $filtros, string $prefix = 'o.'): array {
        $conds  = ['1=1'];
        $parametros = [];
        $conds[] = OcorrenciaService::condicaoValidacao($filtros,$parametros,$prefix);
        if (!empty($filtros['conclusao_estado'])) { $conds[] = $prefix.'conclusao_estado=?'; $parametros[] = $filtros['conclusao_estado']; }
        if (!empty($filtros['esquadra_id'])) { $conds[] = $prefix . 'esquadra_id=?';      $parametros[] = (int)$filtros['esquadra_id']; }
        if (!empty($filtros['agente_id']))   { $conds[] = $prefix . 'agente_id=?';        $parametros[] = (int)$filtros['agente_id']; }
        if (!empty($filtros['data_inicio']))  { $conds[] = $prefix . 'data_ocorrencia>=?'; $parametros[] = $filtros['data_inicio']; }
        if (!empty($filtros['data_fim']))     { $conds[] = $prefix . 'data_ocorrencia<=?'; $parametros[] = $filtros['data_fim']; }
        if (!empty($filtros['tipo_id']))      { $conds[] = $prefix . 'tipo_id=?';          $parametros[] = (int)$filtros['tipo_id']; }
        if (!empty($filtros['estado']))       { $conds[] = $prefix . 'estado=?';           $parametros[] = $filtros['estado']; }
        if (!empty($filtros['prioridade']))   { $conds[] = $prefix . 'prioridade=?';       $parametros[] = $filtros['prioridade']; }
        if (!empty($filtros['natureza_dados'])) { $conds[] = $prefix . 'natureza_dados=?'; $parametros[] = $filtros['natureza_dados']; }
        return [implode(' AND ', $conds), $parametros];
    }

    // Agrupa as ocorrências por tipo para os gráficos e relatórios.
    public function obterPorTipo(array $filtros = []) {
        [$where, $parametros] = $this->montarFiltroGraficos($filtros);
        return $this->listarResultados(
            "SELECT t.nome, COUNT(o.id) AS total
             FROM ocorrencias o JOIN tipos_ocorrencia t ON o.tipo_id=t.id
             WHERE {$where}
             GROUP BY t.id ORDER BY total DESC LIMIT 10",
            $parametros
        );
    }

    // Agrupa as ocorrências por esquadra para os gráficos e relatórios.
    public function obterPorEsquadra(array $filtros = []) {
        [$where, $parametros] = $this->montarFiltroGraficos($filtros);
        return $this->listarResultados(
            "SELECT e.nome, COUNT(o.id) AS total
             FROM ocorrencias o JOIN esquadras e ON o.esquadra_id=e.id
             WHERE {$where}
             GROUP BY e.id ORDER BY total DESC",
            $parametros
        );
    }

    // Consulta os bairros com maior número de ocorrências.
    public function obterPorBairro(array $filtros = []) {
        [$where, $parametros] = $this->montarFiltroGraficos($filtros);
        return $this->listarResultados(
            "SELECT o.bairro, COUNT(*) AS total
             FROM ocorrencias o
             WHERE {$where} AND o.bairro IS NOT NULL
             GROUP BY o.bairro ORDER BY total DESC LIMIT 10",
            $parametros
        );
    }

    // Conta as ocorrências de cada mês do ano indicado.
    public function obterEvolucaoMensal($ano = null, array $filtros = []) {
        if (!$ano) $ano = date('Y');
        $parametros = [];
        $where = $this->montarFiltroEstatisticas($filtros, $parametros);
        return $this->listarResultados(
            "SELECT MONTH(data_ocorrencia) AS mes, COUNT(*) AS total
             FROM ocorrencias WHERE YEAR(data_ocorrencia)=? AND {$where}
             GROUP BY mes ORDER BY mes",
            array_merge([$ano], $parametros)
        );
    }

    // Conta as ocorrências de cada mês do período selecionado.
    public function obterEvolucaoPeriodo(array $filtros): array {
        [$where, $parametros] = $this->montarFiltroGraficos($filtros);
        return $this->listarResultados(
            "SELECT DATE_FORMAT(o.data_ocorrencia, '%Y-%m') AS periodo,
                    DATE_FORMAT(o.data_ocorrencia, '%m/%Y') AS label,
                    COUNT(*) AS total
             FROM ocorrencias o
             WHERE {$where}
             GROUP BY periodo
             ORDER BY periodo
             LIMIT 24",
            $parametros
        );
    }

    // Conta as ocorrências de cada dia no intervalo indicado.
    public function obterTendenciaDiaria($dias = 30, array $filtros = []) {
        $parametros = [];
        $where = $this->montarFiltroEstatisticas($filtros, $parametros);
        return $this->listarResultados(
            "SELECT DATE(data_ocorrencia) AS dia, COUNT(*) AS total
             FROM ocorrencias WHERE data_ocorrencia >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND {$where}
             GROUP BY dia ORDER BY dia",
            array_merge([(int)$dias], $parametros)
        );
    }

}
