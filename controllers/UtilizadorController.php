<?php
// Utilizadores: lista, valida e encaminha as operações de gestão de contas.
class UtilizadorController {
    private Esquadra $esquadras;
    private Utilizador $utilizadores;

    // Prepara os models e a ligação utilizados por esta classe.
    public function __construct() {
        $this->esquadras = new Esquadra();
        $this->utilizadores = new Utilizador();
    }

    // Recolhe os filtros e os registos necessários para apresentar a listagem.
    public function listar(): void {
        $filtros  = ['pesquisa' => $_GET['q'] ?? '', 'perfil' => $_GET['perfil'] ?? ''];
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $total = $this->utilizadores->contar($filtros);
        $totalPgs = max(1, (int)ceil($total / ITEMS_PER_PAGE));
        $pagina = min($pagina, $totalPgs);
        $utilizadores = $this->utilizadores->listarTodos($filtros, ($pagina - 1) * ITEMS_PER_PAGE, ITEMS_PER_PAGE);
        $esquadras    = $this->esquadras->listarParaSelect();

        $titulo  = 'Gestão de Utilizadores';
        $paginaAtiva = 'utilizadores';
        renderView('utilizadores/index', [
            'titulo' => $titulo,
            'paginaAtiva' => $paginaAtiva,
            'filtros' => $filtros,
            'pagina' => $pagina,
            'total' => $total,
            'totalPgs' => $totalPgs,
            'utilizadores' => $utilizadores,
            'esquadras' => $esquadras,
        ]);
    }

    // Cria o registo com os dados recebidos.
    public function criar(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $_POST;
            $dados['email'] = trim($dados['email'] ?? '');
            $erros = $this->validar($dados, 0);

            if (empty($erros)) {
                try {
                    $novoId = $this->utilizadores->criar($dados);
                    LogSistema::registar(
                        'Utilizador Criado',
                        'Criado: ' . ($dados['nome'] ?? '') . ' (ID ' . $novoId . ') — perfil: ' . ($dados['perfil'] ?? ''),
                        'gestao'
                    );
                    $_SESSION['toast'] = ['tipo' => 'sucesso', 'msg' => 'Utilizador criado com sucesso!'];
                } catch (PDOException $e) {
                    error_log('[SIGOP] Erro ao criar utilizador: ' . $e->getMessage());
                    $_SESSION['toast'] = ['tipo' => 'erro', 'msg' => 'Erro ao guardar. Verifique se o email ou NIP já existem.'];
                }
            } else {
                $_SESSION['toast'] = ['tipo' => 'erro', 'msg' => implode(' ', $erros)];
            }
            redirect('utilizadores');
        }

        redirect('utilizadores');
    }

    // Carrega o registo, valida o formulário e encaminha a atualização.
    public function editar(int $id): void {
        $utilizador = $this->utilizadores->buscarPorId($id);
        if (!$utilizador) { mostrar404(); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $_POST;
            $dados['email'] = trim($dados['email'] ?? '');
            $erros = $this->validar($dados, $id);

            if (empty($erros)) {
                try {
                    $this->utilizadores->atualizar($id, $dados);
                    LogSistema::registar(
                        'Utilizador Editado',
                        'Editado: ' . $utilizador['nome'] . ' (ID ' . $id . ')',
                        'gestao'
                    );
                    $_SESSION['toast'] = ['tipo' => 'sucesso', 'msg' => 'Utilizador actualizado com sucesso!'];
                } catch (PDOException $e) {
                    error_log('[SIGOP] Erro ao editar utilizador: ' . $e->getMessage());
                    $_SESSION['toast'] = ['tipo' => 'erro', 'msg' => 'Erro ao guardar. Verifique se o email ou NIP já estão em uso.'];
                }
            } else {
                $_SESSION['toast'] = ['tipo' => 'erro', 'msg' => implode(' ', $erros)];
            }
            redirect('utilizadores');
        }

        redirect('utilizadores');
    }

    // Alterna o estado activo ou inactivo do registo.
    public function alternarAtivo(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('utilizadores');
        }
        if ($id === (int)$_SESSION['user_id']) {
            $_SESSION['toast'] = ['tipo' => 'erro', 'msg' => 'Não pode desactivar a sua própria conta.'];
        } else {
            $alvo = $this->utilizadores->buscarPorId($id);
            $this->utilizadores->alternarAtivo($id);
            if ($alvo) {
                $acao = $alvo['ativo'] ? 'Utilizador Desactivado' : 'Utilizador Activado';
                LogSistema::registar($acao, 'Utilizador: ' . $alvo['nome'] . ' (ID ' . $id . ')', 'gestao');
            }
            $_SESSION['toast'] = ['tipo' => 'sucesso', 'msg' => 'Estado do utilizador actualizado.'];
        }
        redirect('utilizadores');
    }

    // Verifica os campos do formulário e devolve as mensagens de erro.
    private function validar(array $dados, int $id): array {
        $erros = [];
        if (empty($dados['nome']))     $erros[] = 'O nome é obrigatório.';
        elseif (!textoNoIntervalo($dados['nome'],3,100)) $erros[] = 'O nome deve ter entre 3 e 100 caracteres.';
        if (!in_array($dados['perfil'] ?? '', ['admin', 'supervisor', 'investigador', 'agente'], true))
            $erros[] = 'Seleccione um perfil válido.';
        if (empty($dados['email'])) $erros[] = 'O email de acesso é obrigatório.';
        elseif (strlen($dados['email']) > 150 || !filter_var($dados['email'], FILTER_VALIDATE_EMAIL))
            $erros[] = 'Endereço de email inválido.';
        if ($id === 0 && empty($dados['password'])) $erros[] = 'A senha é obrigatória.';
        if (!empty($dados['password'])) {
            $conta = $id ? $this->utilizadores->buscarPorId($id) : [];
            if ($falha = SenhaService::validar($dados['password'],$conta ?: [])) $erros[] = $falha;
        }
        if (!empty($dados['email']) && $this->utilizadores->emailExiste($dados['email'], $id)) $erros[] = 'Este email já está em uso.';
        if (!empty($dados['numero_mecanografico']) && !preg_match('/^(19|20)\d{2}(0[1-9]|1[0-2])\d{2}$/', $dados['numero_mecanografico'])) $erros[] = 'O NIP deve ter exactamente 8 dígitos numéricos no formato AAAAMMSS (ex: 20180347).';
        if (!empty($dados['numero_mecanografico']) && $this->utilizadores->numeroMecanograficoExiste($dados['numero_mecanografico'], $id)) $erros[] = 'Este NIP já está em uso.';
        if (in_array($dados['perfil'] ?? '', ['agente','supervisor','investigador'], true) && empty($dados['esquadra_id']))
            $erros[] = 'Seleccione a esquadra.';
        if (!empty($dados['esquadra_id'])) {
            $esquadra = filter_var($dados['esquadra_id'], FILTER_VALIDATE_INT)
                ? $this->esquadras->buscarPorId((int)$dados['esquadra_id']) : null;
            if (!$esquadra || !$esquadra['ativo']) $erros[] = 'Seleccione uma esquadra activa.';
        }
        return $erros;
    }
}
