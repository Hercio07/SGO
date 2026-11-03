<?php
// Model Utilizador: consultas e gravações das contas na base de dados.
class Utilizador {
    private PDO $pdo;

    // Obtém a ligação PDO criada em config/database.php.
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    // Login e recuperação usam o email; o valor nunca é concatenado no SQL.
    public function buscarPorEmail(string $email) {
        $consulta = $this->pdo->prepare(
            'SELECT u.*, e.nome AS esquadra_nome, e.tipo AS esquadra_tipo
             FROM utilizadores u
             LEFT JOIN esquadras e ON u.esquadra_id = e.id
             WHERE u.email = ?'
        );
        $consulta->execute([trim($email)]);
        return $consulta->fetch();
    }

    public function buscarParaLogin(string $identificador) {
        $q = $this->pdo->prepare('SELECT u.*,e.nome AS esquadra_nome,e.tipo AS esquadra_tipo FROM utilizadores u
            LEFT JOIN esquadras e ON e.id=u.esquadra_id WHERE u.email=? OR u.username=? LIMIT 1');
        $q->execute([trim($identificador),trim($identificador)]);
        return $q->fetch();
    }

    private function novoUsername(string $email): string {
        $base = substr(preg_replace('/[^a-z0-9_.-]/','',strtolower(explode('@',$email)[0])) ?: 'utilizador',0,35);
        $username = $base;
        $q = $this->pdo->prepare('SELECT COUNT(*) FROM utilizadores WHERE username=?');
        for (;;) {
            $q->execute([$username]);
            if (!(int)$q->fetchColumn()) return $username;
            $username = $base.'.'.bin2hex(random_bytes(3));
        }
    }

    // Procura um registo pelo seu identificador.
    public function buscarPorId($id) {
        $consulta = $this->pdo->prepare(
            'SELECT u.*, e.nome AS esquadra_nome, e.tipo AS esquadra_tipo
             FROM utilizadores u
             LEFT JOIN esquadras e ON u.esquadra_id = e.id
             WHERE u.id = ?'
        );
        $consulta->execute([$id]);
        return $consulta->fetch();
    }

    // Consulta os registos, aplicando os filtros e a paginação recebidos.
    public function listarTodos($filtros = [], ?int $offset = null, ?int $limit = null) {
        $sql = 'SELECT u.id, u.nome, u.email, u.patente, u.numero_mecanografico,
                       u.perfil, u.esquadra_id, u.telefone, u.ativo, u.ultimo_acesso,
                       e.nome AS esquadra_nome
                FROM utilizadores u
                LEFT JOIN esquadras e ON u.esquadra_id = e.id
                WHERE 1=1';
        $parametros = [];

        if (!empty($filtros['pesquisa'])) {
            $sql .= ' AND (u.nome LIKE ? OR u.email LIKE ? OR u.numero_mecanografico LIKE ?)';
            $p = '%' . $filtros['pesquisa'] . '%';
            $parametros = array_merge($parametros, [$p, $p, $p]);
        }
        if (!empty($filtros['perfil'])) {
            $sql .= ' AND u.perfil = ?';
            $parametros[] = $filtros['perfil'];
        }
        if (isset($filtros['ativo']) && $filtros['ativo'] !== '') {
            $sql .= ' AND u.ativo = ?';
            $parametros[] = $filtros['ativo'];
        }

        $sql .= ' ORDER BY u.nome ASC';
        if ($limit !== null) {
            $sql .= ' LIMIT ? OFFSET ?';
            $parametros[] = $limit;
            $parametros[] = max(0, (int)$offset);
        }
        $consulta = $this->pdo->prepare($sql);
        $consulta->execute($parametros);
        return $consulta->fetchAll();
    }

    // Conta os registos que correspondem aos filtros recebidos.
    public function contar(array $filtros = []): int {
        $sql = 'SELECT COUNT(*) FROM utilizadores u WHERE 1=1';
        $parametros = [];
        if (!empty($filtros['pesquisa'])) {
            $sql .= ' AND (u.nome LIKE ? OR u.email LIKE ? OR u.numero_mecanografico LIKE ?)';
            $p = '%' . $filtros['pesquisa'] . '%';
            $parametros = [$p, $p, $p];
        }
        if (!empty($filtros['perfil'])) {
            $sql .= ' AND u.perfil = ?';
            $parametros[] = $filtros['perfil'];
        }
        if (isset($filtros['ativo']) && $filtros['ativo'] !== '') {
            $sql .= ' AND u.ativo = ?';
            $parametros[] = $filtros['ativo'];
        }
        $consulta = $this->pdo->prepare($sql);
        $consulta->execute($parametros);
        return (int)$consulta->fetchColumn();
    }

    // Cria o registo com os dados recebidos.
    public function criar($dados) {
        $consulta = $this->pdo->prepare(
            'INSERT INTO utilizadores (nome, username, email, password, patente, numero_mecanografico, perfil, esquadra_id, telefone, exigir_alteracao_senha)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)'
        );
        $consulta->execute([
            $dados['nome'], $this->novoUsername($dados['email']), $dados['email'] ?: null,
            password_hash($dados['password'], PASSWORD_DEFAULT),
            ($dados['patente'] ?? '') ?: null, ($dados['numero_mecanografico'] ?? '') ?: null,
            $dados['perfil'], ($dados['esquadra_id'] ?? '') ?: null, ($dados['telefone'] ?? '') ?: null
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    // Grava as alterações de um registo existente.
    public function atualizar($id, $dados) {
        $campos = ['nome=?', 'email=?', 'patente=?', 'numero_mecanografico=?', 'perfil=?', 'esquadra_id=?', 'telefone=?'];
        $parametros = [$dados['nome'], $dados['email'] ?: null, ($dados['patente'] ?? '') ?: null,
                   ($dados['numero_mecanografico'] ?? '') ?: null, $dados['perfil'],
                   ($dados['esquadra_id'] ?? '') ?: null, ($dados['telefone'] ?? '') ?: null];

        if (!empty($dados['password'])) {
            $campos[] = 'password=?';
            $parametros[] = password_hash($dados['password'], PASSWORD_DEFAULT);
            $campos[] = 'exigir_alteracao_senha=1';
            $campos[] = 'versao_sessao=versao_sessao+1';
        }

        $parametros[] = $id;
        $consulta = $this->pdo->prepare('UPDATE utilizadores SET ' . implode(',', $campos) . ' WHERE id=?');
        $consulta->execute($parametros);
    }

    // Alterna o estado activo ou inactivo do registo.
    public function alternarAtivo($id) {
        $consulta = $this->pdo->prepare('UPDATE utilizadores SET ativo = NOT ativo WHERE id=?');
        $consulta->execute([$id]);
    }

    public function atualizarUltimoAcesso($id) {
        $consulta = $this->pdo->prepare('UPDATE utilizadores SET ultimo_acesso = NOW() WHERE id=?');
        $consulta->execute([$id]);
    }

    public function contarPorPerfil() {
        $consulta = $this->pdo->prepare('SELECT perfil, COUNT(*) AS total FROM utilizadores GROUP BY perfil');
        $consulta->execute();
        return $consulta->fetchAll();
    }

    public function emailExiste($email, $excluirId = 0) {
        $consulta = $this->pdo->prepare('SELECT COUNT(*) FROM utilizadores WHERE email=? AND id!=?');
        $consulta->execute([$email, $excluirId]);
        $result = $consulta->fetchColumn();
        return (int)$result > 0;
    }

    public function numeroMecanograficoExiste($mecan, $excluirId = 0) {
        $consulta = $this->pdo->prepare('SELECT COUNT(*) FROM utilizadores WHERE numero_mecanografico=? AND id!=?');
        $consulta->execute([$mecan, $excluirId]);
        $result = $consulta->fetchColumn();
        return (int)$result > 0;
    }

    public function atualizarSenha(int $id, string $senha, bool $limparToken = false): void {
        $sql = 'UPDATE utilizadores SET password=?, exigir_alteracao_senha=0, versao_sessao=versao_sessao+1';
        if ($limparToken) $sql .= ', reset_token=NULL, reset_token_expires=NULL';
        $consulta = $this->pdo->prepare($sql . ' WHERE id=?');
        $consulta->execute([password_hash($senha, PASSWORD_DEFAULT), $id]);
    }

    public function atualizarPerfil(int $id, string $nome, ?string $email, ?string $telefone): void {
        $consulta = $this->pdo->prepare('UPDATE utilizadores SET nome=?, email=?, telefone=? WHERE id=?');
        $consulta->execute([$nome, $email, $telefone, $id]);
    }

    public function guardarTokenRecuperacao(int $id, string $tokenHash, string $expiraEm): void {
        $consulta = $this->pdo->prepare('UPDATE utilizadores SET reset_token=?, reset_token_expires=? WHERE id=?');
        $consulta->execute([$tokenHash, $expiraEm, $id]);
    }

    public function buscarPorTokenRecuperacao(string $tokenHash) {
        $consulta = $this->pdo->prepare(
            'SELECT * FROM utilizadores WHERE reset_token=? AND reset_token_expires > NOW() AND ativo=1'
        );
        $consulta->execute([$tokenHash]);
        return $consulta->fetch();
    }

    public function estadoDaConta(int $id) {
        $consulta = $this->pdo->prepare('SELECT ativo, perfil, esquadra_id, exigir_alteracao_senha, versao_sessao FROM utilizadores WHERE id=?');
        $consulta->execute([$id]);
        return $consulta->fetch();
    }

    public function listarAgentesAtivos(): array {
        $consulta = $this->pdo->prepare(
            "SELECT u.id, u.nome, u.patente, e.nome AS esquadra_nome
             FROM utilizadores u
             LEFT JOIN esquadras e ON u.esquadra_id = e.id
             WHERE u.perfil = 'agente' AND u.ativo = 1
             ORDER BY e.nome, u.nome"
        );
        $consulta->execute();
        return $consulta->fetchAll();
    }

    public function investigadorAtivoDaEsquadra(int $id, int $esquadraId, bool $bloquear = false): bool {
        $consulta = $this->pdo->prepare(
            "SELECT id FROM utilizadores WHERE id=? AND perfil='investigador' AND esquadra_id=? AND ativo=1" . ($bloquear ? ' FOR UPDATE' : '')
        );
        $consulta->execute([$id, $esquadraId]);
        return (int)$consulta->fetchColumn() > 0;
    }

    public function contactoPorId(int $id) {
        $consulta = $this->pdo->prepare('SELECT nome, email FROM utilizadores WHERE id=?');
        $consulta->execute([$id]);
        return $consulta->fetch();
    }

    public function listarParaAuditoria(?int $esquadraId = null): array {
        $sql = 'SELECT id, nome, perfil FROM utilizadores';
        if ($esquadraId !== null) $sql .= ' WHERE esquadra_id=?';
        $sql .= ' ORDER BY nome';
        $consulta = $this->pdo->prepare($sql);
        $consulta->execute($esquadraId !== null ? [$esquadraId] : []);
        return $consulta->fetchAll();
    }
}
