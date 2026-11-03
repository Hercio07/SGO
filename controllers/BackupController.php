<?php
// Backups: recebe os pedidos e delega os ficheiros ao BackupService.
class BackupController {
    // Prepara os dados e apresenta a página principal do módulo.
    public function index(): void {
        $backups = BackupService::listar();
        $ultimoResultado = BackupService::ultimoResultado();
        $titulo = 'Backups do Sistema';
        $paginaAtiva = 'backups';
        renderView('backups/index', [
            'titulo' => $titulo,
            'paginaAtiva' => $paginaAtiva,
            'backups' => $backups,
            'ultimoResultado' => $ultimoResultado,
        ]);
    }

    // Cria o registo com os dados recebidos.
    public function criar(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('backups');
        }

        $resultado = BackupService::gerarManual((int)$_SESSION['user_id']);
        if ($resultado['estado'] === 'criado') {
            $_SESSION['toast'] = ['tipo' => 'sucesso', 'msg' => 'Backup criado com sucesso.'];
        } else {
            $_SESSION['toast'] = ['tipo' => 'erro', 'msg' => 'Não foi possível gerar o backup. Consulte o log do servidor.'];
        }
        redirect('backups');
    }

    public function verificarDiario(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit;
        }

        $utilizadorId = (int)$_SESSION['user_id'];
        session_write_close();
        $resultado = BackupService::gerarDiario($utilizadorId);
        if ($resultado['estado'] === 'falhado') http_response_code(503);

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode(['estado' => $resultado['estado']], JSON_UNESCAPED_UNICODE);
    }

    public function descarregar(?string $nome): void {
        $caminho = BackupService::caminhoDoFicheiro((string)$nome);
        if ($caminho === null) {
            http_response_code(404);
            echo 'Backup não encontrado.';
            return;
        }

        header('Content-Type: '.(str_ends_with($caminho,'.zip') ? 'application/zip' : 'application/gzip'));
        header('Content-Disposition: attachment; filename="' . basename($caminho) . '"');
        header('Content-Length: ' . filesize($caminho));
        header('Cache-Control: private, no-store, no-cache, must-revalidate');
        header('X-Content-Type-Options: nosniff');
        session_write_close();
        readfile($caminho);
        exit;
    }

    public function verificar(?string $nome): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); return; }
        try {
            $caminho = BackupService::caminhoDoFicheiro((string)$nome);
            if (!$caminho || !str_ends_with($caminho,'.zip')) throw new DomainException('Seleccione um backup completo em ZIP.');
            $manifesto = BackupService::verificarCompleto($caminho);
            $_SESSION['toast'] = ['tipo'=>'sucesso','msg'=>'Integridade confirmada: '.count($manifesto['ficheiros']).' ficheiros verificados.'];
            LogSistema::registar('Backup Verificado','Integridade verificada: '.basename($caminho).'.','gestao');
        } catch (Throwable $e) {
            error_log('[SIGOP] Verificação do backup: '.$e->getMessage());
            $_SESSION['toast'] = ['tipo'=>'erro','msg'=>'Não foi possível confirmar a integridade deste backup. Consulte o log do servidor.'];
        }
        redirect('backups');
    }
}
