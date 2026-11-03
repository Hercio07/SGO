<?php
// Model Esquadra: consultas e gravações das esquadras e postos.
class Esquadra {
    private PDO $pdo;

    // Obtém a ligação PDO criada em config/database.php.
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    // Consulta os registos, aplicando os filtros e a paginação recebidos.
    public function listarTodos($apenasAtivas = false, ?int $offset = null, ?int $limit = null) {
        $parametros = [];
        $validacao = OcorrenciaService::condicaoValidacao([],$parametros);
        $sql = 'SELECT e.*, COUNT(u.id) AS total_agentes,
                (SELECT COUNT(*) FROM ocorrencias o WHERE o.esquadra_id=e.id AND '.$validacao.') AS total_ocorrencias
                FROM esquadras e
                LEFT JOIN utilizadores u ON u.esquadra_id=e.id AND u.perfil=\'agente\'';
        if ($apenasAtivas) $sql .= ' WHERE e.ativo=1';
        $sql .= ' GROUP BY e.id ORDER BY e.nome ASC';
        if ($limit !== null) $sql .= ' LIMIT ' . (int)$limit . ' OFFSET ' . max(0, (int)$offset);
        $consulta = $this->pdo->prepare($sql);
        $consulta->execute($parametros);
        return $consulta->fetchAll();
    }

    // Conta os registos que correspondem aos filtros recebidos.
    public function contar(bool $apenasAtivas = false): int {
        $sql = 'SELECT COUNT(*) FROM esquadras' . ($apenasAtivas ? ' WHERE ativo=1' : '');
        return (int)$this->pdo->query($sql)->fetchColumn();
    }

    // Procura um registo pelo seu identificador.
    public function buscarPorId($id) {
        $consulta = $this->pdo->prepare('SELECT * FROM esquadras WHERE id=?');
        $consulta->execute([$id]);
        return $consulta->fetch();
    }

    // Cria o registo com os dados recebidos.
    public function criar($dados) {
        $consulta = $this->pdo->prepare(
            'INSERT INTO esquadras (nome, codigo, tipo, distrito, bairro, endereco, telefone, email, comandante_nome)
             VALUES (?,?,?,?,?,?,?,?,?)'
        );
        $consulta->execute([$dados['nome'], $dados['codigo'] ?? null, $dados['tipo'] ?? 'esquadra',
            $dados['distrito'] ?? null, $dados['bairro'] ?? null, $dados['endereco'] ?? null,
            $dados['telefone'] ?? null, $dados['email'] ?? null, $dados['comandante_nome'] ?? null]);
        return (int)$this->pdo->lastInsertId();
    }

    // Grava as alterações de um registo existente.
    public function atualizar($id, $dados) {
        $consulta = $this->pdo->prepare(
            'UPDATE esquadras SET nome=?, codigo=?, tipo=?, distrito=?, bairro=?, endereco=?, telefone=?, email=?, comandante_nome=? WHERE id=?'
        );
        $consulta->execute([$dados['nome'], $dados['codigo'] ?? null, $dados['tipo'] ?? 'esquadra',
            $dados['distrito'] ?? null, $dados['bairro'] ?? null, $dados['endereco'] ?? null,
            $dados['telefone'] ?? null, $dados['email'] ?? null, $dados['comandante_nome'] ?? null, $id]);
    }

    public function listarParaSelect() {
        $consulta = $this->pdo->prepare('SELECT id, nome, tipo FROM esquadras WHERE ativo=1 ORDER BY nome ASC');
        $consulta->execute();
        return $consulta->fetchAll();
    }
}
