<?php
// Autenticação: início de sessão, recuperação e redefinição de senha.
class AuthController {
    private Utilizador $utilizadores;

    // Prepara os models e a ligação utilizados por esta classe.
    public function __construct() {
        $this->utilizadores = new Utilizador();
    }

    // Valida as credenciais e inicia a sessão do utilizador.
    public function login(): void {
        if (!empty($_SESSION['user_id'])) {
            redirect('dashboard');
        }

        $erro = '';
        if (($_GET['motivo'] ?? '') === 'inatividade' && empty($_SESSION['toast'])) {
            $_SESSION['toast'] = ['tipo'=>'aviso','msg'=>'A sua sessão terminou por inactividade. Inicie sessão novamente.'];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ip = LogSistema::obterIp();
            if ($this->tentativasLoginExcedidas($ip)) {
                $erro = 'Muitas tentativas de acesso falhadas a partir deste endereço. Aguarde alguns minutos antes de tentar novamente.';
            } else {
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';

                if (empty($email) || empty($password)) {
                    $erro = 'Por favor, preencha todos os campos.';
                } elseif (strlen($email) > 150) {
                    $erro = 'Introduza o email ou nome de utilizador.';
                } else {
                    $utilizador = $this->utilizadores->buscarParaLogin($email);
                    if ($utilizador && password_verify($password, $utilizador['password'])) {
                        if (!$utilizador['ativo']) {
                            $erro = 'Conta desactivada. Contacte o administrador.';
                            LogSistema::registar('Login Bloqueado', 'Tentativa de acesso a uma conta inactiva.', 'acesso', $utilizador['id']);
                        } else {
                            $_SESSION['user_id']       = $utilizador['id'];
                            $_SESSION['nome']          = $utilizador['nome'];
                            $_SESSION['perfil']        = $utilizador['perfil'];
                            $_SESSION['versao_sessao'] = (int)$utilizador['versao_sessao'];
                            $_SESSION['exigir_alteracao_senha'] = (bool)$utilizador['exigir_alteracao_senha'];
                            $_SESSION['patente']       = $utilizador['patente'];
                            $_SESSION['esquadra_id']   = $utilizador['esquadra_id'];
                            $_SESSION['esquadra_nome'] = $utilizador['esquadra_nome'];
                            $_SESSION['esquadra_tipo'] = $utilizador['esquadra_tipo'];

                            $this->utilizadores->atualizarUltimoAcesso($utilizador['id']);
                            LogSistema::registar('Login', 'Sessão iniciada com sucesso.', 'acesso', $utilizador['id']);
                            session_regenerate_id(true);
                            SessaoService::iniciar();

                            redirect($utilizador['exigir_alteracao_senha'] ? 'alterar-senha' : 'dashboard');
                        }
                    } else {
                        $erro = 'Email, nome de utilizador ou senha incorrectos.';
                        LogSistema::registar('Login Falhado', 'Tentativa de autenticação por email recusada.', 'acesso');
                    }
                }
            }
        }

        renderView('auth/login', [
            'erro' => $erro,
        ]);
    }

    private function tentativasLoginExcedidas(string $ip): bool {
        return LogSistema::contarAcoesRecentes($ip, 'Login Falhado') >= 5;
    }

    private function pedidosRecentesExcedidos(string $ip): bool {
        return LogSistema::contarAcoesRecentes($ip, 'Recuperação Solicitada') >= 5;
    }

    // Recebe o pedido de recuperação e envia o link quando a conta é válida.
    public function recuperar(): void {
        if (!empty($_SESSION['user_id'])) {
            redirect('dashboard');
        }

        $msg  = '';
        $tipo = '';

        // Sem SMTP configurado, a funcionalidade está indisponível
        if (empty(MAIL_USER) || empty(MAIL_PASS)) {
            $msg  = 'A recuperação de senha por email não está disponível neste momento. Contacte o administrador do sistema para redefinir a sua senha.';
            $tipo = 'aviso';
            renderView('auth/recuperar', [
            'msg' => $msg,
            'tipo' => $tipo,
        ]);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $ip = LogSistema::obterIp();
            if ($this->pedidosRecentesExcedidos($ip)) {
                $msg  = 'Muitos pedidos recentes a partir deste endereço. Aguarde alguns minutos antes de tentar novamente.';
                $tipo = 'aviso';
                renderView('auth/recuperar', [
            'msg' => $msg,
            'tipo' => $tipo,
        ]);
                return;
            }

            $email = trim($_POST['email'] ?? '');
            if (empty($email)) {
                $msg  = 'Introduza o seu email.';
                $tipo = 'erro';
            } elseif (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $msg = 'Introduza um endereço de email válido.';
                $tipo = 'erro';
            } else {
                // Regista o pedido independentemente de o utilizador existir, para manter
                // a simetria anti-enumeração da mensagem de resposta e para efeitos de auditoria.
                LogSistema::registar('Recuperação Solicitada', 'Pedido de recuperação de senha por email.', 'acesso');

                // Testa o SMTP antes de consultar a conta: uma falha do serviço
                // apresenta o mesmo aviso para qualquer email.
                if (!EmailService::verificarLigacaoSMTP()) {
                    renderView('auth/recuperar', [
                        'msg' => 'Não foi possível contactar ou autenticar no serviço de email. Tente novamente mais tarde ou contacte o administrador do sistema.',
                        'tipo' => 'aviso',
                    ]);
                    return;
                }

                try {
                    $utilizador = $this->utilizadores->buscarPorEmail($email);
                    if ($utilizador && !empty($utilizador['email']) && $utilizador['ativo']) {
                        $tokenRaw  = bin2hex(random_bytes(32));
                        $tokenHash = hash('sha256', $tokenRaw);
                        $expires   = date('Y-m-d H:i:s', time() + 3600);
                        $this->utilizadores->guardarTokenRecuperacao((int)$utilizador['id'], $tokenHash, $expires);
                        $link = BASE_URL . '/redefinir-senha?token=' . $tokenRaw;
                        $sent = EmailService::enviarRecuperacaoSenha($utilizador['email'], $utilizador['nome'], $link);
                        if (!$sent) {
                            error_log('[SIGOP] Falha ao enviar email de recuperação de senha (conta: ' . (int)$utilizador['id'] . ').');
                        }
                    }
                } finally {
                    EmailService::fecharLigacaoSMTP();
                }
                // Mensagem genérica deliberada: não revela se o utilizador existe
                $msg  = 'Se o email corresponder a uma conta activa, receberá um link para redefinir a senha. Verifique a caixa de entrada e a pasta de spam.';
                $tipo = 'sucesso';
            }
        }

        renderView('auth/recuperar', [
            'msg' => $msg,
            'tipo' => $tipo,
        ]);
    }

    // Valida o token de recuperação e guarda a nova senha.
    public function redefinir(): void {
        if (!empty($_SESSION['user_id'])) {
            redirect('dashboard');
        }

        $token = $_SERVER['REQUEST_METHOD'] === 'POST'
            ? trim($_POST['token'] ?? '')
            : trim($_GET['token'] ?? '');
        $msg   = '';
        $tipo  = '';
        $utilizador  = null;

        if (!empty($token)) {
            $utilizador = $this->utilizadores->buscarPorTokenRecuperacao(hash('sha256', $token));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$utilizador) {
                $_SESSION['toast'] = ['tipo' => 'erro', 'msg' => 'Link inválido ou expirado. Solicite um novo.'];
                redirect('recuperar-senha');
            }
            $nova = $_POST['senha_nova'] ?? '';
            $conf = $_POST['senha_confirmacao'] ?? '';
            if ($erroSenha = SenhaService::validar($nova,$utilizador)) {
                $msg  = $erroSenha;
                $tipo = 'erro';
            } elseif ($nova !== $conf) {
                $msg  = 'As senhas não coincidem.';
                $tipo = 'erro';
            } else {
                $this->utilizadores->atualizarSenha((int)$utilizador['id'], $nova, true);
                LogSistema::registar('Senha Redefinida', 'Senha redefinida via link de recuperação.', 'acesso', $utilizador['id']);
                $_SESSION['toast'] = ['tipo' => 'sucesso', 'msg' => 'Senha redefinida com sucesso. Pode agora iniciar sessão.'];
                redirect('login');
            }
        }

        renderView('auth/redefinir', [
            'utilizador' => $utilizador,
            'msg' => $msg,
            'tipo' => $tipo,
            'token' => $token,
        ]);
    }

    // Regista a saída e termina a sessão actual.
    public function alterarSenha(): void {
        $utilizador = $this->utilizadores->buscarPorId((int)$_SESSION['user_id']);
        $erro = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $atual = $_POST['senha_atual'] ?? '';
            $nova = $_POST['senha_nova'] ?? '';
            if (!password_verify($atual,$utilizador['password'])) $erro = 'A senha actual está incorrecta.';
            elseif ($nova !== ($_POST['senha_confirmacao'] ?? '')) $erro = 'As senhas não coincidem.';
            elseif ($falha = SenhaService::validar($nova,$utilizador)) $erro = $falha;
            else {
                $this->utilizadores->atualizarSenha((int)$utilizador['id'],$nova,true);
                $atualizado = $this->utilizadores->estadoDaConta((int)$utilizador['id']);
                $_SESSION['versao_sessao'] = (int)$atualizado['versao_sessao'];
                $_SESSION['exigir_alteracao_senha'] = false;
                session_regenerate_id(true);
                SessaoService::iniciar();
                LogSistema::registar('Senha Alterada','Senha pessoal definida; as outras sessões foram invalidadas.','acesso');
                $_SESSION['toast'] = ['tipo'=>'sucesso','msg'=>'Senha pessoal guardada. Pode utilizar o sistema.'];
                redirect('dashboard');
            }
        }
        renderView('auth/alterar_senha',compact('utilizador','erro'));
    }

    public function logout(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('dashboard');
        }
        if (!empty($_SESSION['user_id'])) {
            LogSistema::registar('Logout', 'Sessão terminada.', 'acesso', $_SESSION['user_id']);
        }
        SessaoService::terminar();
        redirect('login');
    }
}
