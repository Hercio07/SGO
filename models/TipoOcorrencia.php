<?php
// Model TipoOcorrencia: consultas e gravações dos tipos de ocorrência.
class TipoOcorrencia {
    private PDO $pdo;

    // Obtém a ligação PDO criada em config/database.php.
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    // Consulta os registos, aplicando os filtros e a paginação recebidos.
    public function listarTodos($apenasAtivos = false, ?int $offset = null, ?int $limit = null) {
        $sql = 'SELECT * FROM tipos_ocorrencia';
        if ($apenasAtivos) $sql .= ' WHERE ativo=1';
        $sql .= ' ORDER BY nome ASC';
        if ($limit !== null) $sql .= ' LIMIT ' . (int)$limit . ' OFFSET ' . max(0, (int)$offset);
        $consulta = $this->pdo->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll();
    }

    // Conta os registos que correspondem aos filtros recebidos.
    public function contar(bool $apenasAtivos = false): int {
        $sql = 'SELECT COUNT(*) FROM tipos_ocorrencia' . ($apenasAtivos ? ' WHERE ativo=1' : '');
        return (int)$this->pdo->query($sql)->fetchColumn();
    }

    // Procura um registo pelo seu identificador.
    public function buscarPorId($id) {
        $consulta = $this->pdo->prepare('SELECT * FROM tipos_ocorrencia WHERE id=?');
        $consulta->execute([$id]);
        return $consulta->fetch();
    }

    // Cria o registo com os dados recebidos.
    public function criar($dados) {
        $consulta = $this->pdo->prepare(
            'INSERT INTO tipos_ocorrencia (nome, codigo, descricao, prioridade_default) VALUES (?,?,?,?)'
        );
        $consulta->execute([$dados['nome'], $dados['codigo'] ?? null, $dados['descricao'] ?? null,
            $dados['prioridade_default'] ?? 'media']);
        return (int)$this->pdo->lastInsertId();
    }

    // Grava as alterações de um registo existente.
    public function atualizar(int $id, array $dados): void {
        $consulta = $this->pdo->prepare(
            'UPDATE tipos_ocorrencia SET nome=?, codigo=?, descricao=?, prioridade_default=? WHERE id=?'
        );
        $consulta->execute([$dados['nome'], $dados['codigo'] ?? null, $dados['descricao'] ?? null,
            $dados['prioridade_default'] ?? 'media', $id]);
    }

    // Alterna o estado activo ou inactivo do registo.
    public function alternarAtivo(int $id): void {
        $consulta = $this->pdo->prepare('UPDATE tipos_ocorrencia SET ativo = NOT ativo WHERE id=?');
        $consulta->execute([$id]);
    }
}
