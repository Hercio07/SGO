<?php
// API: devolve JSON para notificações, pesquisa, mapas e gráficos da interface.
class ApiController {
    private Notificacao $notificacoes;
    private Ocorrencia $ocorrencias;
    private Relatorio $relatorios;

    // Prepara os models e a ligação utilizados por esta classe.
    public function __construct() {
        $this->notificacoes = new Notificacao();
        $this->ocorrencias = new Ocorrencia();
        $this->relatorios = new Relatorio();
        header('Content-Type: application/json; charset=UTF-8');
    }

    public function estatisticas() {
        $filtros = ['natureza_dados'=>isset(OcorrenciaService::NATUREZAS[$_GET['natureza_dados'] ?? '']) ? $_GET['natureza_dados'] : ''];
        if (in_array($_SESSION['perfil'], ['supervisor', 'investigador'])) {
            $filtros['esquadra_id'] = $_SESSION['esquadra_id'];
        } elseif ($_SESSION['perfil'] === 'agente') {
            $filtros['agente_id'] = $_SESSION['user_id'];
        }
        echo json_encode($this->relatorios->obterResumoMetricas($filtros));
    }

    public function notificacoes() {
        $total = $this->notificacoes->contarNaoLidas((int)$_SESSION['user_id']);
        $lista = $this->notificacoes->listarRecentes((int)$_SESSION['user_id']);

        $pendentes = null;
        if ($_SESSION['perfil'] === 'investigador' && !empty($_SESSION['esquadra_id'])) {
            $pendentes = $this->ocorrencias->contarPendentesEsquadra((int)$_SESSION['esquadra_id']);
        } elseif ($_SESSION['perfil'] === 'supervisor' && !empty($_SESSION['esquadra_id'])) {
            $pendentes = $this->ocorrencias->contarPorValidarEsquadra((int)$_SESSION['esquadra_id']);
        }

        echo json_encode(['total' => $total, 'lista' => $lista, 'pendentes' => $pendentes, 'conclusoes' => $_SESSION['perfil']==='supervisor' ? $this->ocorrencias->contar(['conclusao_estado'=>'pendente']) : null]);
    }

    public function marcarLida($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); return; }
        $this->notificacoes->marcarLida((int)$id, (int)$_SESSION['user_id']);
        echo json_encode(['ok' => true]);
    }

    public function marcarTodasLidas(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); return; }
        $this->notificacoes->marcarTodasLidas((int)$_SESSION['user_id']);
        echo json_encode(['ok' => true]);
    }

    public function mapa(): void {
        $filtros = ['natureza_dados'=>isset(OcorrenciaService::NATUREZAS[$_GET['natureza_dados'] ?? '']) ? $_GET['natureza_dados'] : ''];
        if (in_array($_SESSION['perfil'], ['supervisor', 'investigador'])) {
            $filtros['esquadra_id'] = $_SESSION['esquadra_id'];
        } elseif ($_SESSION['perfil'] === 'agente') {
            $filtros['agente_id'] = $_SESSION['user_id'];
        }

        echo json_encode($this->ocorrencias->obterMapa($filtros));
    }

    public function graficosOcorrencias() {
        $filtros = ['natureza_dados'=>isset(OcorrenciaService::NATUREZAS[$_GET['natureza_dados'] ?? '']) ? $_GET['natureza_dados'] : ''];
        if (in_array($_SESSION['perfil'], ['supervisor', 'investigador'])) {
            $filtros['esquadra_id'] = $_SESSION['esquadra_id'];
        } elseif ($_SESSION['perfil'] === 'agente') {
            $filtros['agente_id'] = $_SESSION['user_id'];
        }

        $tipo = $_GET['tipo'] ?? 'mensal';
        switch ($tipo) {
            case 'tipo':     $dados = $this->relatorios->obterPorTipo($filtros); break;
            case 'esquadra': $dados = $this->relatorios->obterPorEsquadra($filtros); break;
            case 'bairro':   $dados = $this->relatorios->obterPorBairro($filtros); break;
            case 'diaria':   $dados = $this->relatorios->obterTendenciaDiaria(30, $filtros); break;
            default:
                $dados = $this->relatorios->obterEvolucaoMensal(null, $filtros);
                break;
        }
        echo json_encode($dados);
    }

    public function pesquisa() {
        $q = trim($_GET['q'] ?? '');
        if (mb_strlen($q,'UTF-8') < 2) { echo json_encode([]); return; }

        $filtros = ['pesquisa' => $q];
        if (in_array($_SESSION['perfil'], ['supervisor', 'investigador'])) {
            $filtros['esquadra_id'] = $_SESSION['esquadra_id'];
        } elseif ($_SESSION['perfil'] === 'agente') {
            $filtros['agente_id'] = $_SESSION['user_id'];
        }

        $resultados = $this->ocorrencias->listarTodos($filtros, 0, 8);
        $saida = [];
        foreach ($resultados as $o) {
            $saida[] = [
                'id'              => $o['id'],
                'numero'          => $o['numero'],
                'tipo'            => $o['tipo_nome'],
                'bairro'          => $o['bairro'],
                'estado'          => $o['estado'],
                'url'             => BASE_URL . '/ocorrencias/ver/' . $o['id'],
            ];
        }
        echo json_encode($saida);
    }
}
