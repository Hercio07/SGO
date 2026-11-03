<?php
// Model Bairro: consulta os bairros disponíveis para os formulários.
class Bairro {
    private PDO $pdo;

    // Obtém a ligação PDO criada em config/database.php.
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    // Consulta os registos, aplicando os filtros e a paginação recebidos.
    public function listarTodos(): array {
        $consulta = $this->pdo->prepare('SELECT id, nome, distrito FROM bairros ORDER BY distrito, nome');
        $consulta->execute();
        return $consulta->fetchAll();
    }
}
