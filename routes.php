<?php
// Rotas HTTP da aplicação. O arranque e as dependências ficam em public/index.php.
validarEntradaHttp();
$url = $_GET['url'] ?? '';
$url = trim($url, '/');
$url = filter_var($url, FILTER_SANITIZE_URL);
$partes = array_filter(explode('/', $url));
$partes = array_values($partes);

$controlador = $partes[0] ?? '';
$accao       = $partes[1] ?? 'index';
$parametro   = $partes[2] ?? null;
$extra       = $partes[3] ?? null;

// Recusa uma sessão expirada antes de qualquer operação, incluindo POST e pedidos AJAX.
$rotasPublicas = ['login', 'install', '', 'recuperar-senha', 'redefinir-senha'];
if (SessaoService::expirada()) {
    SessaoService::terminar(true);
    // Termina também o POST do login: o formulário antigo pertence à sessão
    // encerrada. Um novo GET apresenta o formulário com um token válido.
    SessaoService::exigirLogin(true);
}
if (!in_array($controlador, $rotasPublicas)) {
    if (empty($_SESSION['user_id'])) SessaoService::exigirLogin();
    SessaoService::preparar();
}

// CSRF: verificar em todos os pedidos POST (formulários e AJAX).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarTokenCsrf()) {
        if (SessaoService::pedidoJson()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ativo'=>false,'erro'=>'csrf_invalido','login'=>BASE_URL.'/login']);
            exit;
        }
        // Como no sistema de escalas, volta ao formulário em vez de mostrar
        // uma página de erro sem a interface da aplicação.
        $_SESSION['toast'] = ['tipo'=>'aviso','msg'=>'O formulário expirou ou já não é válido. Por favor, tente novamente.'];
        header('Location: '.BASE_URL.'/login', true, 303);
        exit;
    }
}

if (!empty($_SESSION['user_id'])) {
    $automatico = in_array($controlador,['sessao','rascunhos'],true)
        || ($controlador === 'api' && $_SERVER['REQUEST_METHOD'] === 'GET')
        || ($controlador === 'backups' && $accao === 'verificar-diario');
    if (!$automatico) SessaoService::registarAtividade();
}

// Redirecionar raiz
if ($controlador === '') {
    if (!empty($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/dashboard');
    } else {
        header('Location: ' . BASE_URL . '/login');
    }
    exit;
}

// Roteamento principal
switch ($controlador) {
    case 'login':
        $auth = new AuthController();
        $auth->login();
        break;

    case 'logout':
        $auth = new AuthController();
        $auth->logout();
        break;

    case 'alterar-senha':
        garantirAutenticacao();
        (new AuthController())->alterarSenha();
        break;

    case 'sessao':
        garantirAutenticacao();
        $sessao = new SessaoController();
        switch ($accao) {
            case 'estado': $sessao->estado(); break;
            case 'atividade': $sessao->atividade(); break;
            default: http_response_code(404); break;
        }
        break;

    case 'recuperar-senha':
        $auth = new AuthController();
        $auth->recuperar();
        break;

    case 'redefinir-senha':
        $auth = new AuthController();
        $auth->redefinir();
        break;

    case 'dashboard':
        garantirAutenticacao();
        $dashboard = new DashboardController();
        $dashboard->index();
        break;

    case 'ocorrencias':
        garantirAutenticacao();
        $ocorrencia = new OcorrenciaController();
        switch ($accao) {
            case 'diligencia':
                $ocorrencia->registarDiligencia((int)$parametro);
                break;
            case 'propor-conclusao':
                $ocorrencia->proporConclusao((int)$parametro);
                break;
            case 'decidir-conclusao':
                $ocorrencia->decidirConclusao((int)$parametro);
                break;
            case 'criar':
                $ocorrencia->criar();
                break;
            case 'ver':
                $ocorrencia->exibir((int)$parametro);
                break;
            case 'editar':
                $ocorrencia->editar((int)$parametro);
                break;
            case 'arquivar':
                $ocorrencia->arquivar((int)$parametro);
                break;
            case 'aprovar':
                $ocorrencia->aprovar((int)$parametro);
                break;
            case 'rejeitar':
                $ocorrencia->rejeitar((int)$parametro);
                break;
            case 'estado':
                $ocorrencia->atualizarEstado((int)$parametro);
                break;
            case 'imprimir':
                $ocorrencia->imprimir((int)$parametro);
                break;
            case 'auto':
                $ocorrencia->gerarAuto((int)$parametro);
                break;
            case 'anexo-excluir':
                $ocorrencia->excluirAnexo((int)$parametro);
                break;
            case 'anexo':
                $ocorrencia->abrirAnexo((int)$parametro);
                break;
            case 'atribuir-investigador':
                $ocorrencia->atribuirInvestigador((int)$parametro);
                break;
            default:
                $ocorrencia->listar();
        }
        break;

    case 'rascunhos':
        garantirAutenticacao();
        (new RascunhoController())->executar($accao);
        break;

    case 'utilizadores':
        garantirAutenticacao('admin');
        $utilizador = new UtilizadorController();
        switch ($accao) {
            case 'criar':
                $utilizador->criar();
                break;
            case 'editar':
                $utilizador->editar((int)$parametro);
                break;
            case 'toggle':
                $utilizador->alternarAtivo((int)$parametro);
                break;
            default:
                $utilizador->listar();
        }
        break;

    case 'esquadras':
        garantirAutenticacao('admin');
        $esquadra = new EsquadraController();
        switch ($accao) {
            case 'criar':
                $esquadra->criar();
                break;
            case 'editar':
                $esquadra->editar((int)$parametro);
                break;
            default:
                $esquadra->listar();
        }
        break;

    case 'relatorios':
        garantirAutenticacao(['admin', 'supervisor', 'investigador']);
        $relatorio = new RelatorioController();
        switch ($accao) {
            case 'exportar-pdf':
                $relatorio->exportarPDF();
                break;
            case 'exportar-excel':
                $relatorio->exportarExcel();
                break;
            default:
                $relatorio->index();
        }
        break;

    case 'mapa':
        garantirAutenticacao();
        $mapa = new MapaController();
        $mapa->index();
        break;

    case 'auditoria':
        garantirAutenticacao(['admin', 'supervisor']);
        $auditoria = new AuditoriaController();
        $auditoria->listar();
        break;

    case 'backups':
        garantirAutenticacao('admin');
        $backup = new BackupController();
        switch ($accao) {
            case 'criar':
                $backup->criar();
                break;
            case 'verificar-diario':
                $backup->verificarDiario();
                break;
            case 'download':
                $backup->descarregar($parametro);
                break;
            case 'verificar':
                $backup->verificar($parametro);
                break;
            default:
                $backup->index();
        }
        break;

    case 'configuracoes':
        garantirAutenticacao();
        $configuracao = new ConfiguracaoController();
        $configuracao->index();
        break;

    case 'api':
        garantirAutenticacao();
        $api = new ApiController();
        switch ($accao) {
            case 'stats':
                $api->estatisticas();
                break;
            case 'notificacoes':
                $api->notificacoes();
                break;
            case 'marcar-lida':
                $api->marcarLida((int)$parametro);
                break;
            case 'marcar-todas-lidas':
                $api->marcarTodasLidas();
                break;
            case 'ocorrencias-chart':
                $api->graficosOcorrencias();
                break;
            case 'pesquisa':
                $api->pesquisa();
                break;
            case 'mapa':
                $api->mapa();
                break;
            default:
                http_response_code(404);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['erro' => 'Endpoint não encontrado.']);
                break;
        }
        break;

    case 'tipos':
        garantirAutenticacao('admin');
        $tipo = new TipoController();
        switch ($accao) {
            case 'criar':
                $tipo->criar();
                break;
            case 'editar':
                $tipo->editar((int)$parametro);
                break;
            case 'toggle':
                $tipo->alternarEstado((int)$parametro);
                break;
            default:
                header('Location: ' . BASE_URL . '/configuracoes');
                exit;
        }
        break;

    case 'install':
        http_response_code(403);
        die('<div style="font-family:sans-serif;text-align:center;padding:60px;background:#0F172A;color:#F1F5F9;min-height:100vh;"><h2 style="color:#EF4444;">403 — Acesso Negado</h2><p style="color:#94A3B8;margin-top:12px;">O instalador não está disponível.</p></div>');

    default:
        mostrar404();
        break;
}
