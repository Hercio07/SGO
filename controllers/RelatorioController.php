<?php
// Relatórios: valida os filtros, consulta os models e solicita as exportações aos serviços.
class RelatorioController {
    private Esquadra $esquadras;
    private Ocorrencia $ocorrencias;
    private Relatorio $relatorios;
    private TipoOcorrencia $tipos;

    // Prepara os models e a ligação utilizados por esta classe.
    public function __construct() {
        $this->esquadras = new Esquadra();
        $this->ocorrencias = new Ocorrencia();
        $this->relatorios = new Relatorio();
        $this->tipos = new TipoOcorrencia();
    }

    private const LIMITE_EXPORTACAO = 5000;

    private function dataValida(string $data): bool {
        $dataConvertida = \DateTime::createFromFormat('Y-m-d', $data);
        return $dataConvertida && $dataConvertida->format('Y-m-d') === $data;
    }

    // Lê as datas e aplica a esquadra permitida ao utilizador em sessão.
    private function obterFiltros(): array {
        $restritoAEsquadra = in_array($_SESSION['perfil'], ['supervisor', 'investigador']);

        $inicio = $_GET['data_inicio'] ?? date('Y-m-01');
        $fim    = $_GET['data_fim']    ?? date('Y-m-d');
        if (!$this->dataValida($inicio)) { $inicio = date('Y-m-01'); }
        if (!$this->dataValida($fim))    { $fim    = date('Y-m-d');  }
        if ($fim < $inicio)              { $fim    = $inicio;         }

        $filtros = [
            'data_inicio' => $inicio,
            'conclusao_estado' => isset(OcorrenciaService::CONCLUSOES[$_GET['conclusao_estado'] ?? '']) ? $_GET['conclusao_estado'] : '',
            'natureza_dados' => isset(OcorrenciaService::NATUREZAS[$_GET['natureza_dados'] ?? '']) ? $_GET['natureza_dados'] : '',
            'data_fim'    => $fim,
            'tipo_id'     => $_GET['tipo'] ?? $_GET['tipo_id'] ?? '',
            'estado'      => $_GET['estado']      ?? '',
            'prioridade'  => $_GET['prioridade']  ?? '',
        ];
        if ($restritoAEsquadra) {
            $filtros['esquadra_id'] = (int)$_SESSION['esquadra_id'];
        } else {
            // aceita 'esquadra' (formulário) e 'esquadra_id' (link de exportação)
            $filtros['esquadra_id'] = $_GET['esquadra'] ?? $_GET['esquadra_id'] ?? '';
            $filtros['tipo_id']     = $_GET['tipo']     ?? $_GET['tipo_id']     ?? '';
        }
        return $filtros;
    }

    // Prepara os dados e apresenta a página principal do módulo.
    public function index(): void {
        $filtros     = $this->obterFiltros();

        $total       = $this->ocorrencias->contar($filtros);
        $pagina      = max(1, (int)($_GET['pagina'] ?? 1));
        $totalPgs    = max(1, (int)ceil($total / ITEMS_PER_PAGE));
        $pagina      = min($pagina, $totalPgs);
        $ocorrencias = $this->ocorrencias->listarTodos($filtros, ($pagina - 1) * ITEMS_PER_PAGE, ITEMS_PER_PAGE);
        $stats       = $this->relatorios->obterResumoMetricas($filtros);
        $porTipo     = $this->relatorios->obterPorTipo($filtros);
        $porEsquadra = $this->relatorios->obterPorEsquadra($filtros);
        $porBairro   = $this->relatorios->obterPorBairro($filtros);
        $evolucao    = $this->relatorios->obterEvolucaoPeriodo($filtros);
        $esquadras   = $this->esquadras->listarParaSelect();
        $tipos       = $this->tipos->listarTodos(true);

        $titulo  = 'Relatórios e Estatísticas';
        $paginaAtiva = 'relatorios';
        renderView('relatorios/index', [
            'titulo' => $titulo,
            'paginaAtiva' => $paginaAtiva,
            'filtros' => $filtros,
            'total' => $total,
            'pagina' => $pagina,
            'totalPgs' => $totalPgs,
            'ocorrencias' => $ocorrencias,
            'stats' => $stats,
            'porTipo' => $porTipo,
            'porEsquadra' => $porEsquadra,
            'porBairro' => $porBairro,
            'evolucao' => $evolucao,
            'esquadras' => $esquadras,
            'tipos' => $tipos,
        ]);
    }

    private function gerarReferencia(): string {
        return 'REL-' . date('Ymd-His');
    }

    /** Recusa a exportação (em vez de gerar um ficheiro cortado silenciosamente) quando o período filtrado excede o limite seguro. */
    private function bloquearExportacaoSeExcedida(array $filtros, int $total): void {
        if ($total <= self::LIMITE_EXPORTACAO) return;

        $_SESSION['toast'] = [
            'tipo' => 'aviso',
            'msg'  => 'O período seleccionado tem ' . number_format($total) . ' ocorrências, acima do limite de '
                . number_format(self::LIMITE_EXPORTACAO) . ' por exportação. Reduza o período ou aplique mais filtros antes de exportar.',
        ];
        header('Location: ' . BASE_URL . '/relatorios?' . http_build_query(array_filter($filtros)));
        exit;
    }

    private function sanitizarImagemGrafico(?string $imagem): ?string {
        if (!$imagem) return null;
        if (!preg_match('/^data:image\/png;base64,[A-Za-z0-9+\/=]+$/', $imagem)) return null;
        return $imagem;
    }

    // Recolhe os dados autorizados e solicita o documento ao serviço de PDF.
    public function exportarPDF(): void {
        $filtros = $this->obterFiltros();
        $total   = $this->ocorrencias->contar($filtros);
        $this->bloquearExportacaoSeExcedida($filtros, $total);

        $ocorrencias = $this->ocorrencias->listarTodos($filtros, 0, max($total, 1));
        $stats       = $this->relatorios->obterResumoMetricas($filtros);
        $porTipo     = $this->relatorios->obterPorTipo($filtros);
        $porEsquadra = $this->relatorios->obterPorEsquadra($filtros);
        $porBairro   = $this->relatorios->obterPorBairro($filtros);
        $evolucao    = $this->relatorios->obterEvolucaoPeriodo($filtros);

        $graficos = [
            'evolucao'  => $this->sanitizarImagemGrafico($_POST['chart_evolucao'] ?? null),
            'tipo'      => $this->sanitizarImagemGrafico($_POST['chart_tipo'] ?? null),
            'esquadra'  => $this->sanitizarImagemGrafico($_POST['chart_esquadra'] ?? null),
            'bairro'    => $this->sanitizarImagemGrafico($_POST['chart_bairro'] ?? null),
        ];

        (new RelatorioPdfService())->descarregar([
            'filtros' => $filtros,
            'ocorrencias' => $ocorrencias,
            'stats' => $stats,
            'porTipo' => $porTipo,
            'porEsquadra' => $porEsquadra,
            'porBairro' => $porBairro,
            'evolucao' => $evolucao,
            'graficos' => $graficos,
            'referencia' => $this->gerarReferencia(),
        ]);
    }

    // Recolhe os dados autorizados e envia o Excel criado pelo serviço.
    public function exportarExcel(): void {
        $filtros = $this->obterFiltros();
        $total   = $this->ocorrencias->contar($filtros);
        $this->bloquearExportacaoSeExcedida($filtros, $total);

        $ocorrencias = $this->ocorrencias->listarTodos($filtros, 0, max($total, 1));
        $stats       = $this->relatorios->obterResumoMetricas($filtros);
        $porTipo     = $this->relatorios->obterPorTipo($filtros);
        $porEsquadra = $this->relatorios->obterPorEsquadra($filtros);
        $porBairro   = $this->relatorios->obterPorBairro($filtros);
        $evolucao    = $this->relatorios->obterEvolucaoPeriodo($filtros);

        $logoPath = ROOT . '/public/assets/img/logo.png';
        if (!file_exists($logoPath)) { $logoPath = null; }

        $caminho = (new RelatorioExcelService())->gerar([
            'filtros' => $filtros,
            'ocorrencias' => $ocorrencias,
            'stats' => $stats,
            'porTipo' => $porTipo,
            'porEsquadra' => $porEsquadra,
            'porBairro' => $porBairro,
            'evolucao' => $evolucao,
            'logoPath' => $logoPath,
            'referencia' => $this->gerarReferencia(),
        ]);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="SIGOP_Relatorio_' . date('Y-m-d') . '.xlsx"');
        header('Cache-Control: max-age=0');
        try {
            readfile($caminho);
        } finally {
            unlink($caminho);
        }
        exit;
    }
}
