<?php
define('ROOT', dirname(__DIR__));
chdir(ROOT);

require_once ROOT . '/vendor/autoload.php';
require_once ROOT . '/config/config.php';
require_once ROOT . '/services/SessaoService.php';
SessaoService::iniciarPedido();
require_once ROOT . '/config/database.php';

header('Content-Type: text/html; charset=UTF-8');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store, private');

// Lista explícita das classes próprias. As bibliotecas continuam a usar o Composer.

// Models: consultas e gravações na base de dados.
require_once ROOT . '/models/Utilizador.php';
require_once ROOT . '/models/Esquadra.php';
require_once ROOT . '/models/TipoOcorrencia.php';
require_once ROOT . '/models/Bairro.php';
require_once ROOT . '/models/Ocorrencia.php';
require_once ROOT . '/models/Rascunho.php';
require_once ROOT . '/models/Relatorio.php';
require_once ROOT . '/models/Auditoria.php';
require_once ROOT . '/models/LogSistema.php';
require_once ROOT . '/models/Notificacao.php';

// Services: email, cache, exportação de documentos e cópias de segurança.
require_once ROOT . '/services/EmailService.php';
require_once ROOT . '/services/CacheService.php';
require_once ROOT . '/services/SenhaService.php';
require_once ROOT . '/services/OcorrenciaService.php';
require_once ROOT . '/services/DocumentoService.php';
require_once ROOT . '/services/RelatorioExcelService.php';
require_once ROOT . '/services/RelatorioPdfService.php';
require_once ROOT . '/services/OcorrenciaPdfService.php';
require_once ROOT . '/services/BackupService.php';

// Funções comuns: vistas, redirecionamento, autenticação e CSRF.
require_once ROOT . '/config/entrada.php';
// Funções comuns aos controllers e aos formulários.

// Apresentação segura em HTML, com UTF-8 explícito, como no gestao_escalas.
function e(mixed $valor): string {
    return htmlspecialchars((string)($valor ?? ''),ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8');
}

// Recebe os dados preparados pelo controller e disponibiliza-os na vista indicada.
function renderView(string $vista, array $variaveis = []): void {
    extract($variaveis, EXTR_SKIP);
    require ROOT . '/views/' . $vista . '.php';
}

// Formatação comum das datas e horas apresentadas nas vistas.
function dataOcorrenciaFormatada(?string $data): string {
    return $data ? date('d/m/Y', strtotime($data)) : '—';
}

function horaOcorrenciaFormatada(?string $hora): string {
    return $hora ? substr($hora, 0, 5) : '—';
}

// Termina o pedido com um redirecionamento para uma rota da aplicação.
function redirect(string $rota): void {
    header('Location: ' . BASE_URL . '/' . $rota);
    exit;
}

// Cria ou reutiliza o token que protege os formulários da sessão.
function gerarTokenCsrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function campoCsrf(): string {
    return '<input type="hidden" name="_csrf" value="' . e(gerarTokenCsrf()) . '">';
}

// Verifica o token recebido num formulário ou num pedido AJAX.
function verificarTokenCsrf(): bool {
    // Suporta envio via campo POST ou via cabeçalho HTTP (para AJAX)
    $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return !empty($token) && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

// Revalida a conta e limita o acesso aos perfis permitidos na rota.
function garantirAutenticacao($perfil = null): void {
    if (empty($_SESSION['user_id'])) {
        SessaoService::exigirLogin();
    }
    $conta = (new Utilizador())->estadoDaConta((int)$_SESSION['user_id']);
    if (!$conta || !$conta['ativo'] || $conta['perfil'] !== $_SESSION['perfil']
        || (int)$conta['esquadra_id'] !== (int)($_SESSION['esquadra_id'] ?? 0)
        || (isset($_SESSION['versao_sessao']) && (int)$_SESSION['versao_sessao'] !== (int)$conta['versao_sessao'])) {
        SessaoService::terminar();
        SessaoService::exigirLogin();
    }
    $_SESSION['versao_sessao'] = (int)$conta['versao_sessao'];
    $_SESSION['exigir_alteracao_senha'] = (bool)$conta['exigir_alteracao_senha'];
    $rotaAtual = explode('/',trim($_GET['url'] ?? '','/'))[0];
    if ($conta['exigir_alteracao_senha'] && !in_array($rotaAtual,['alterar-senha','logout','sessao'],true)) {
        if (SessaoService::pedidoJson()) {
            http_response_code(428);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['erro'=>'alteracao_senha_obrigatoria','login'=>BASE_URL.'/alterar-senha']);
            exit;
        }
        redirect('alterar-senha');
    }
    if ($perfil !== null) {
        $perfisPermitidos = is_array($perfil) ? $perfil : [$perfil];
        if (!in_array($_SESSION['perfil'], $perfisPermitidos)) {
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }
}

// Apresenta a página de registo ou rota inexistente e termina o pedido.
function mostrar404(): void {
    http_response_code(404);
    require_once ROOT . '/views/errors/404.php';
    exit;
}

// Controllers: recebem os pedidos e coordenam cada módulo.
require_once ROOT . '/controllers/AuthController.php';
require_once ROOT . '/controllers/SessaoController.php';
require_once ROOT . '/controllers/RascunhoController.php';
require_once ROOT . '/controllers/DashboardController.php';
require_once ROOT . '/controllers/OcorrenciaController.php';
require_once ROOT . '/controllers/UtilizadorController.php';
require_once ROOT . '/controllers/EsquadraController.php';
require_once ROOT . '/controllers/TipoController.php';
require_once ROOT . '/controllers/RelatorioController.php';
require_once ROOT . '/controllers/MapaController.php';
require_once ROOT . '/controllers/AuditoriaController.php';
require_once ROOT . '/controllers/ConfiguracaoController.php';
require_once ROOT . '/controllers/BackupController.php';
require_once ROOT . '/controllers/ApiController.php';

// Depois do arranque, as rotas escolhem o controller e o método a executar.
require ROOT . '/routes.php';
