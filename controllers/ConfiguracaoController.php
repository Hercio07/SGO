<?php
// Configurações: gere o perfil, a senha e a listagem dos tipos de ocorrência.
class ConfiguracaoController {
    private TipoOcorrencia $tipos;
    private Utilizador $utilizadores;

    // Prepara os models e a ligação utilizados por esta classe.
    public function __construct() {
        $this->tipos = new TipoOcorrencia();
        $this->utilizadores = new Utilizador();
    }

    // Prepara os dados e apresenta a página principal do módulo.
    public function index(): void {
        $utilizador = $this->utilizadores->buscarPorId($_SESSION['user_id']);
        $paginaTipos = max(1, (int)($_GET['pagina_tipos'] ?? 1));
        $totalTipos = $this->tipos->contar();
        $totalPgsTipos = max(1, (int)ceil($totalTipos / ITEMS_PER_PAGE));
        $paginaTipos = min($paginaTipos, $totalPgsTipos);
        $tipos = $this->tipos->listarTodos(false, ($paginaTipos - 1) * ITEMS_PER_PAGE, ITEMS_PER_PAGE);
        $erros      = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $acao = $_POST['acao'] ?? '';

            if ($acao === 'senha') {
                $atual = $_POST['senha_atual'] ?? '';
                $nova  = $_POST['senha_nova'] ?? '';
                $conf  = $_POST['senha_confirmacao'] ?? '';

                if (!password_verify($atual, $utilizador['password'])) {
                    $erros[] = 'A senha actual está incorrecta.';
                } elseif ($falha = SenhaService::validar($nova,$utilizador)) {
                    $erros[] = $falha;
                } elseif ($nova !== $conf) {
                    $erros[] = 'As senhas não coincidem.';
                } else {
                    $this->utilizadores->atualizarSenha((int)$_SESSION['user_id'], $nova);
                    $contaAtual = $this->utilizadores->estadoDaConta((int)$_SESSION['user_id']);
                    $_SESSION['versao_sessao'] = (int)$contaAtual['versao_sessao'];
                    $_SESSION['exigir_alteracao_senha'] = false;
                    session_regenerate_id(true);
                    SessaoService::iniciar();
                    LogSistema::registar('Senha Alterada','Senha alterada pelo próprio utilizador; outras sessões invalidadas.','acesso');
                    $_SESSION['toast'] = ['tipo' => 'sucesso', 'msg' => 'Senha alterada com sucesso!'];
                    redirect('configuracoes');
                }
            }

            if ($acao === 'perfil') {
                $nome     = trim($_POST['nome'] ?? '');
                $email    = trim($_POST['email'] ?? '');
                $telefone = trim($_POST['telefone'] ?? '') ?: null;

                if (empty($nome)) {
                    $erros[] = 'O nome é obrigatório.';
                } elseif (!textoNoIntervalo($nome,3,100)) {
                    $erros[] = 'O nome deve ter entre 3 e 100 caracteres.';
                }
                if ($email === '') {
                    $erros[] = 'O email de acesso é obrigatório.';
                } elseif (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $erros[] = 'Endereço de email inválido.';
                }
                if ($email !== '' && $this->utilizadores->emailExiste($email, $_SESSION['user_id'])) {
                    $erros[] = 'Este email já está em uso por outro utilizador.';
                }
                if ($telefone !== null && !textoNoIntervalo($telefone,1,20)) {
                    $erros[] = 'O telefone deve ter até 20 caracteres.';
                }

                if (empty($erros)) {
                    $this->utilizadores->atualizarPerfil((int)$_SESSION['user_id'], $nome, $email, $telefone);
                    $_SESSION['nome'] = $nome;
                    $_SESSION['toast'] = ['tipo' => 'sucesso', 'msg' => 'Perfil actualizado!'];
                    redirect('configuracoes');
                }
            }
        }

        $titulo  = 'Configurações';
        $paginaAtiva = 'configuracoes';
        renderView('configuracoes/index', [
            'titulo' => $titulo,
            'paginaAtiva' => $paginaAtiva,
            'utilizador' => $utilizador,
            'paginaTipos' => $paginaTipos,
            'totalTipos' => $totalTipos,
            'totalPgsTipos' => $totalPgsTipos,
            'tipos' => $tipos,
            'erros' => $erros,
        ]);
    }
}
