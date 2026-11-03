<?php
// Esquadras: coordena a listagem, criação e edição de esquadras e postos.
class EsquadraController {
    private Esquadra $esquadras;

    // Prepara os models e a ligação utilizados por esta classe.
    public function __construct() {
        $this->esquadras = new Esquadra();
    }

    // Recolhe os filtros e os registos necessários para apresentar a listagem.
    public function listar(): void {
        $pagina = max(1, (int)($_GET['pagina'] ?? 1));
        $total = $this->esquadras->contar();
        $totalPgs = max(1, (int)ceil($total / ITEMS_PER_PAGE));
        $pagina = min($pagina, $totalPgs);
        $esquadras = $this->esquadras->listarTodos(false, ($pagina - 1) * ITEMS_PER_PAGE, ITEMS_PER_PAGE);
        $titulo  = 'Gestão de Esquadras e Postos';
        $paginaAtiva = 'esquadras';
        renderView('esquadras/index', [
            'titulo' => $titulo,
            'paginaAtiva' => $paginaAtiva,
            'pagina' => $pagina,
            'total' => $total,
            'totalPgs' => $totalPgs,
            'esquadras' => $esquadras,
        ]);
    }

    // Cria o registo com os dados recebidos.
    public function criar(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $_POST;
            $erros = $this->validar($dados);
            if (empty($erros)) {
                try {
                    $this->esquadras->criar($dados);
                    $_SESSION['toast'] = ['tipo' => 'sucesso', 'msg' => 'Esquadra/Posto criado com sucesso!'];
                } catch (PDOException $e) {
                    error_log('[SIGOP] Erro ao criar esquadra: ' . $e->getMessage());
                    $_SESSION['toast'] = ['tipo' => 'erro', 'msg' => 'Não foi possível guardar a esquadra. Verifique se o código já está em uso.'];
                }
            } else {
                $_SESSION['toast'] = ['tipo' => 'erro', 'msg' => implode(' ', $erros)];
            }
        }
        redirect('esquadras');
    }

    // Carrega o registo, valida o formulário e encaminha a atualização.
    public function editar(int $id): void {
        $esquadra = $this->esquadras->buscarPorId($id);
        if (!$esquadra) { mostrar404(); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $_POST;
            $erros = $this->validar($dados);
            if (empty($erros)) {
                try {
                    $this->esquadras->atualizar($id, $dados);
                    $_SESSION['toast'] = ['tipo' => 'sucesso', 'msg' => 'Esquadra/Posto actualizado com sucesso!'];
                } catch (PDOException $e) {
                    error_log('[SIGOP] Erro ao editar esquadra: ' . $e->getMessage());
                    $_SESSION['toast'] = ['tipo' => 'erro', 'msg' => 'Não foi possível actualizar a esquadra. Verifique se o código já está em uso.'];
                }
            } else {
                $_SESSION['toast'] = ['tipo' => 'erro', 'msg' => implode(' ', $erros)];
            }
        }
        redirect('esquadras');
    }

    // Verifica os campos do formulário e devolve as mensagens de erro.
    private function validar(array $dados): array {
        $erros = [];
        if (empty($dados['nome'])) $erros[] = 'O nome é obrigatório.';
        if (!in_array($dados['tipo'] ?? '', ['esquadra', 'posto'], true)) $erros[] = 'Seleccione um tipo válido.';
        if (!empty($dados['email']) && !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) $erros[] = 'Endereço de email inválido.';
        return $erros;
    }
}
