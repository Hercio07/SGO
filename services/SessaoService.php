<?php
// A inactividade é validada no servidor; pedidos automáticos não renovam a sessão.
class SessaoService {
    // Tal como no gestao_escalas, o cookie pertence apenas ao caminho da aplicação.
    public static function iniciarPedido(): void {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        $url = parse_url(BASE_URL);
        $caminho = !empty($url['path']) ? rtrim($url['path'], '/') . '/' : '/';
        session_name('SGO_GESTAO_V2');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $caminho,
            'secure' => strtolower($url['scheme'] ?? '') === 'https',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function iniciar(): void {
        $_SESSION['last_activity'] = time();
        $_SESSION['sessao_identificador'] = bin2hex(random_bytes(16));
        unset($_SESSION['csrf_token']);
    }

    public static function preparar(): void {
        if (!isset($_SESSION['last_activity'])) $_SESSION['last_activity'] = time();
        if (empty($_SESSION['sessao_identificador'])) $_SESSION['sessao_identificador'] = bin2hex(random_bytes(16));
    }

    public static function expirada(): bool {
        return !empty($_SESSION['user_id']) && isset($_SESSION['last_activity'])
            && time() - (int)$_SESSION['last_activity'] >= SESSION_TIMEOUT;
    }

    public static function registarAtividade(int $atraso = 0): void {
        // O atraso corresponde ao tempo desde a última interacção, evitando prolongar a sessão por causa da rede.
        $_SESSION['last_activity'] = max((int)($_SESSION['last_activity'] ?? 0),time() - $atraso);
    }

    public static function estado(): array {
        return ['ativo'=>true,'exigir_senha'=>!empty($_SESSION['exigir_alteracao_senha']),'atividade'=>(int)$_SESSION['last_activity'],'restante'=>max(0,SESSION_TIMEOUT - (time() - (int)$_SESSION['last_activity'])),
            'limite'=>SESSION_TIMEOUT,'aviso'=>SESSION_WARNING];
    }

    public static function terminar(bool $inatividade = false): void {
        if ($inatividade && !empty($_SESSION['user_id'])) {
            LogSistema::registar('Sessão Expirada','Sessão terminada após '.(int)(SESSION_TIMEOUT/60).' minutos de inactividade.','acesso');
        }
        $_SESSION = [];
        session_regenerate_id(true);
        if ($inatividade) $_SESSION['toast'] = ['tipo'=>'aviso','msg'=>'A sua sessão terminou após '.(int)(SESSION_TIMEOUT/60).' minutos de inactividade. Inicie sessão novamente.'];
    }

    public static function pedidoJson(): bool {
        $rota = explode('/',trim($_GET['url'] ?? '','/'))[0];
        return in_array($rota,['api','sessao','rascunhos']) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '','application/json');
    }

    public static function exigirLogin(bool $inatividade = false): void {
        $url = BASE_URL.'/login'.($inatividade ? '?motivo=inatividade' : '');
        if (self::pedidoJson()) {
            http_response_code(401);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ativo'=>false,'erro'=>$inatividade?'sessao_expirada':'sessao_terminada','login'=>$url]);
            exit;
        }
        header('Location: '.$url, true, ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? 303 : 302);
        exit;
    }
}
