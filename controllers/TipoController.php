<?php
// Tipos de ocorrência: valida os formulários e coordena a manutenção dos tipos.
class TipoController {
    private TipoOcorrencia $tipos;

    // Prepara os models e a ligação utilizados por esta classe.
    public function __construct() {
        $this->tipos = new TipoOcorrencia();
    }

    // Cria o registo com os dados recebidos.
    public function criar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('configuracoes');
        }
        $nome = trim($_POST['nome'] ?? '');
        if (empty($nome) || !in_array($_POST['prioridade_default'] ?? 'media', ['baixa', 'media', 'alta', 'critica'], true)) {
            $_SESSION['toast'] = ['tipo' => 'erro', 'msg' => 'Indique um nome e uma prioridade válidos.'];
            redirect('configuracoes');
        }
        try {
            $this->tipos->criar($_POST);
            $_SESSION['toast'] = ['tipo' => 'sucesso', 'msg' => 'Tipo de ocorrência criado.'];
        } catch (PDOException $e) {
            error_log('[SIGOP] Erro ao criar tipo de ocorrência: ' . $e->getMessage());
            $_SESSION['toast'] = ['tipo' => 'erro', 'msg' => 'Não foi possível guardar o tipo. Verifique se o código já está em uso.'];
        }
        redirect('configuracoes');
    }

    // Carrega o registo, valida o formulário e encaminha a atualização.
    public function editar(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('configuracoes');
        }
        $tipo = $this->tipos->buscarPorId($id);
        if (!$tipo) { mostrar404(); return; }

        $nome = trim($_POST['nome'] ?? '');
        if (empty($nome) || !in_array($_POST['prioridade_default'] ?? 'media', ['baixa', 'media', 'alta', 'critica'], true)) {
            $_SESSION['toast'] = ['tipo' => 'erro', 'msg' => 'Indique um nome e uma prioridade válidos.'];
            redirect('configuracoes');
        }
        try {
            $this->tipos->atualizar($id, $_POST);
            $_SESSION['toast'] = ['tipo' => 'sucesso', 'msg' => 'Tipo de ocorrência actualizado.'];
        } catch (PDOException $e) {
            error_log('[SIGOP] Erro ao editar tipo de ocorrência: ' . $e->getMessage());
            $_SESSION['toast'] = ['tipo' => 'erro', 'msg' => 'Não foi possível actualizar o tipo. Verifique se o código já está em uso.'];
        }
        redirect('configuracoes');
    }

    public function alternarEstado(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('configuracoes');
        }
        $tipo = $this->tipos->buscarPorId($id);
        if (!$tipo) { mostrar404(); return; }
        $this->tipos->alternarAtivo($id);
        $msg = $tipo['ativo'] ? 'Tipo desactivado.' : 'Tipo activado.';
        $_SESSION['toast'] = ['tipo' => 'info', 'msg' => $msg];
        redirect('configuracoes');
    }
}
