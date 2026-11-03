<?php
// Auditoria: consulta os históricos de ocorrências e acessos permitidos ao perfil.
class AuditoriaController {
    private Auditoria $auditoria;
    private Esquadra $esquadras;
    private Utilizador $utilizadores;

    // Prepara os models e a ligação utilizados por esta classe.
    public function __construct() {
        $this->auditoria = new Auditoria();
        $this->esquadras = new Esquadra();
        $this->utilizadores = new Utilizador();
    }

    private const PER_PAGE = 6;

    // Recolhe os filtros e os registos necessários para apresentar a listagem.
    public function listar(): void {
        $tab = $_GET['tab'] ?? 'ocorrencias';

        if ($tab === 'acessos') {
            $this->listarAcessos();
        } else {
            $this->listarOcorrencias();
        }
    }

    // ── Tab 1: histórico de ocorrências ──────────────────────────────────

    private function listarOcorrencias(): void {
        $souSupervisor  = $_SESSION['perfil'] === 'supervisor';
        $esquadraFixada = $souSupervisor ? (int)$_SESSION['esquadra_id'] : null;

        $filtros = [
            'data_inicio'   => $_GET['data_inicio']   ?? date('Y-m-01'),
            'data_fim'      => $_GET['data_fim']       ?? date('Y-m-d'),
            // Supervisor nunca escolhe a esquadra — é sempre a sua, ignorando qualquer valor vindo do pedido.
            'esquadra_id'   => $souSupervisor ? $esquadraFixada : ($_GET['esquadra_id'] ?? ''),
            'utilizador_id' => $_GET['utilizador_id']  ?? '',
            'pesquisa'      => trim($_GET['pesquisa']  ?? ''),
        ];
        $page   = max(1, (int)($_GET['pagina'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        [$where, $parametros] = $this->auditoria->filtrosHistorico($filtros);

        $registos = $this->auditoria->listarHistorico($where, $parametros, self::PER_PAGE, $offset);
        $total = $this->auditoria->contarHistorico($where, $parametros);

        $totalPaginas = (int)ceil($total / self::PER_PAGE);

        ['hoje' => $hoje, 'semana' => $semana, 'uniqueOc' => $uniqueOc] =
            $this->auditoria->estatisticasHistorico($esquadraFixada);

        $esquadras    = $this->esquadras->listarParaSelect();
        $utilizadores = $this->utilizadores->listarParaAuditoria($esquadraFixada);

        $tab            = 'ocorrencias';
        $titulo      = 'Log de Auditoria';
        $paginaAtiva     = 'auditoria';
        $esquadraFixada = $esquadraFixada ? $this->esquadras->buscarPorId($esquadraFixada) : null;
        renderView('auditoria/index', [
            'titulo' => $titulo,
            'paginaAtiva' => $paginaAtiva,
            'souSupervisor' => $souSupervisor,
            'esquadraFixada' => $esquadraFixada,
            'filtros' => $filtros,
            'page' => $page,
            'registos' => $registos,
            'total' => $total,
            'totalPaginas' => $totalPaginas,
            'hoje' => $hoje,
            'semana' => $semana,
            'uniqueOc' => $uniqueOc,
            'esquadras' => $esquadras,
            'utilizadores' => $utilizadores,
            'tab' => $tab,
        ]);
    }

    // ── Tab 2: acessos e gestão de utilizadores ──────────────────────────

    private function listarAcessos(): void {
        $souSupervisor  = $_SESSION['perfil'] === 'supervisor';
        $esquadraFixada = $souSupervisor ? (int)$_SESSION['esquadra_id'] : null;

        $filtros = [
            'data_inicio'   => $_GET['data_inicio']   ?? date('Y-m-01'),
            'data_fim'      => $_GET['data_fim']       ?? date('Y-m-d'),
            'utilizador_id' => $_GET['utilizador_id']  ?? '',
            'tipo'          => $_GET['tipo']            ?? '',
            'pesquisa'      => trim($_GET['pesquisa']  ?? ''),
            'esquadra_id'   => $esquadraFixada,
        ];
        $page   = max(1, (int)($_GET['pagina'] ?? 1));
        $offset = ($page - 1) * self::PER_PAGE;

        [$where, $parametros] = $this->auditoria->filtrosAcessos($filtros);

        $registos = $this->auditoria->listarAcessos($where, $parametros, self::PER_PAGE, $offset);
        $total = $this->auditoria->contarAcessos($where, $parametros);

        $totalPaginas = (int)ceil($total / self::PER_PAGE);

        ['hoje' => $hoje, 'logins' => $logins, 'falhas' => $falhas] =
            $this->auditoria->estatisticasAcessos($esquadraFixada);

        $utilizadores = $this->utilizadores->listarParaAuditoria($esquadraFixada);

        $tab            = 'acessos';
        $titulo      = 'Log de Auditoria';
        $paginaAtiva     = 'auditoria';
        $esquadraFixada = $esquadraFixada ? $this->esquadras->buscarPorId($esquadraFixada) : null;
        renderView('auditoria/index', [
            'titulo' => $titulo,
            'paginaAtiva' => $paginaAtiva,
            'souSupervisor' => $souSupervisor,
            'esquadraFixada' => $esquadraFixada,
            'filtros' => $filtros,
            'page' => $page,
            'registos' => $registos,
            'total' => $total,
            'totalPaginas' => $totalPaginas,
            'hoje' => $hoje,
            'logins' => $logins,
            'falhas' => $falhas,
            'utilizadores' => $utilizadores,
            'tab' => $tab,
        ]);
    }
}
