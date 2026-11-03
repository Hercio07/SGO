<?php
// Painel inicial: reúne métricas e ocorrências de acordo com o perfil.
class DashboardController {
    private Ocorrencia $ocorrencias;
    private Relatorio $relatorios;

    // Prepara os models e a ligação utilizados por esta classe.
    public function __construct() {
        $this->ocorrencias = new Ocorrencia();
        $this->relatorios = new Relatorio();
    }

    // Prepara os dados e apresenta a página principal do módulo.
    public function index(): void {
        $filtros = ['natureza_dados'=>isset(OcorrenciaService::NATUREZAS[$_GET['natureza_dados'] ?? '']) ? $_GET['natureza_dados'] : ''];
        if (in_array($_SESSION['perfil'], ['supervisor', 'investigador'])) {
            $filtros['esquadra_id'] = $_SESSION['esquadra_id'];
        } elseif ($_SESSION['perfil'] === 'agente') {
            $filtros['agente_id'] = $_SESSION['user_id'];
        }

        $esquadraFiltro = in_array($_SESSION['perfil'], ['supervisor', 'investigador']) ? (int)($_SESSION['esquadra_id'] ?? 0) : 0;
        $cacheKey = 'sgo_dash_v2_'.($filtros['natureza_dados'] ?: 'todas').'_'.$_SESSION['perfil'].'_'.(int)$_SESSION['user_id'].'_'.$esquadraFiltro;

        $cached = CacheService::obter($cacheKey);
        if ($cached !== null) {
            extract($cached);
        } else {
            $stats        = $this->relatorios->obterResumoMetricas($filtros);
            $recentes     = $this->ocorrencias->listarRecentes(8, $filtros);
            $criticas     = $this->ocorrencias->listarCriticas($filtros);
            $evolucaoData = $this->relatorios->obterEvolucaoMensal(null, $filtros);
            $porTipoData  = $this->relatorios->obterPorTipo($filtros);
            $porEsquadra  = $this->relatorios->obterPorEsquadra($filtros);
            $conclusoesPendentes = $_SESSION['perfil'] === 'supervisor' ? $this->ocorrencias->contar(['conclusao_estado'=>'pendente']) : 0;
            $pendentes    = ($_SESSION['perfil'] === 'investigador')
                          ? $this->ocorrencias->contarPendentesEsquadra((int)$_SESSION['esquadra_id'])
                          : ($_SESSION['perfil'] === 'supervisor' ? $this->ocorrencias->contarPorValidarEsquadra((int)$_SESSION['esquadra_id']) : 0);

            CacheService::guardar($cacheKey, compact(
                'stats', 'recentes', 'criticas',
                'evolucaoData', 'porTipoData', 'porEsquadra', 'pendentes', 'conclusoesPendentes'
            ));
        }

        $titulo  = 'Dashboard — Centro de Comando';
        $paginaAtiva = 'dashboard';
        renderView('dashboard/index', [
            'titulo' => $titulo,
            'paginaAtiva' => $paginaAtiva,
            'stats' => $stats,
            'filtros' => $filtros,
            'recentes' => $recentes,
            'criticas' => $criticas,
            'evolucaoData' => $evolucaoData,
            'porTipoData' => $porTipoData,
            'porEsquadra' => $porEsquadra,
            'pendentes' => $pendentes,
            'conclusoesPendentes' => $conclusoesPendentes,
        ]);
    }
}
