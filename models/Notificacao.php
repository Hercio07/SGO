<?php
// Model Notificacao: guarda e consulta as notificações dos utilizadores.
class Notificacao {
    private PDO $pdo;

    // Obtém a ligação PDO criada em config/database.php.
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    // Cria o registo com os dados recebidos.
    public function criar(int $userId, string $titulo, string $mensagem, string $tipo = 'info', string $url = ''): void {
        $consulta = $this->pdo->prepare(
            'INSERT INTO notificacoes (utilizador_id, titulo, mensagem, tipo, url) VALUES (?,?,?,?,?)'
        );
        $consulta->execute([$userId, $titulo, $mensagem, $tipo, $url ?: null]);
    }

    public function criarParaSupervisores(int $esquadraId, string $titulo, string $mensagem, string $tipo = 'info', string $url = ''): void {
        $consulta = $this->pdo->prepare(
            "SELECT id FROM utilizadores WHERE perfil='supervisor' AND esquadra_id=? AND ativo=1"
        );
        $consulta->execute([$esquadraId]);
        $supervisores = $consulta->fetchAll();
        foreach ($supervisores as $sup) {
            $this->criar((int)$sup['id'], $titulo, $mensagem, $tipo, $url);
        }
    }

    public function contarNaoLidas(int $utilizadorId): int {
        $consulta = $this->pdo->prepare('SELECT COUNT(*) FROM notificacoes WHERE utilizador_id=? AND lida=0');
        $consulta->execute([$utilizadorId]);
        return (int)$consulta->fetchColumn();
    }

    public function listarRecentes(int $utilizadorId): array {
        $consulta = $this->pdo->prepare(
            'SELECT * FROM notificacoes WHERE utilizador_id=? ORDER BY created_at DESC LIMIT 10'
        );
        $consulta->execute([$utilizadorId]);
        return $consulta->fetchAll();
    }

    public function marcarLida(int $id, int $utilizadorId): void {
        $consulta = $this->pdo->prepare(
            'UPDATE notificacoes SET lida=1, lida_em=NOW() WHERE id=? AND utilizador_id=?'
        );
        $consulta->execute([$id, $utilizadorId]);
    }

    public function marcarTodasLidas(int $utilizadorId): void {
        $consulta = $this->pdo->prepare(
            'UPDATE notificacoes SET lida=1, lida_em=NOW() WHERE utilizador_id=?'
        );
        $consulta->execute([$utilizadorId]);
    }
}
