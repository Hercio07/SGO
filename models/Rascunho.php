<?php
// Rascunhos privados de cada conta, conservados durante sete dias.
class Rascunho {
    private PDO $pdo;
    public function __construct() { global $pdo; $this->pdo = $pdo; }

    public function obter(int $utilizador, string $contexto): ?array {
        $limpar = $this->pdo->prepare('DELETE FROM rascunhos_ocorrencia WHERE utilizador_id=? AND contexto=? AND expira_em<=NOW()');
        $limpar->execute([$utilizador,$contexto]);
        $consulta = $this->pdo->prepare('SELECT dados,versao,updated_at FROM rascunhos_ocorrencia WHERE utilizador_id=? AND contexto=? AND expira_em>NOW()');
        $consulta->execute([$utilizador,$contexto]);
        $linha = $consulta->fetch();
        if (!$linha) return null;
        $linha['dados'] = json_decode($linha['dados'],true,512,JSON_THROW_ON_ERROR);
        return $linha;
    }

    public function guardar(int $utilizador, string $contexto, array $dados, int $versao): array {
        $this->pdo->beginTransaction();
        try {
            $consulta = $this->pdo->prepare('SELECT versao FROM rascunhos_ocorrencia WHERE utilizador_id=? AND contexto=? FOR UPDATE');
            $consulta->execute([$utilizador,$contexto]);
            $existente = $consulta->fetchColumn();
            if ((int)$existente !== $versao) throw new DomainException('Este rascunho foi alterado noutro separador. Recarregue a página para recuperar a versão mais recente.');
            $consulta = $this->pdo->prepare('INSERT INTO rascunhos_ocorrencia (utilizador_id,contexto,dados,versao,expira_em) VALUES (?,?,?,1,DATE_ADD(NOW(),INTERVAL 7 DAY)) ON DUPLICATE KEY UPDATE dados=VALUES(dados),versao=versao+1,updated_at=NOW(),expira_em=VALUES(expira_em)');
            $consulta->execute([$utilizador,$contexto,json_encode($dados,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
            $this->pdo->commit();
            return $this->obter($utilizador,$contexto);
        } catch (Throwable $e) { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); throw $e; }
    }

    public function eliminar(int $utilizador, string $contexto, ?int $versao = null): int {
        // Conserva apenas a versão vazia: um pedido automático atrasado não pode recriar o rascunho depois da submissão.
        if ($versao !== null) {
            $consulta = $this->pdo->prepare("UPDATE rascunhos_ocorrencia SET dados='[]',versao=versao+1,updated_at=NOW(),expira_em=DATE_ADD(NOW(),INTERVAL 7 DAY) WHERE utilizador_id=? AND contexto=? AND versao=?");
            $consulta->execute([$utilizador,$contexto,$versao]);
            if (!$consulta->rowCount()) throw new DomainException('O rascunho mudou noutro separador. Recarregue antes de o descartar.');
            return $versao+1;
        }
        $consulta = $this->pdo->prepare("INSERT INTO rascunhos_ocorrencia (utilizador_id,contexto,dados,versao,expira_em) VALUES (?,?,'[]',1,DATE_ADD(NOW(),INTERVAL 7 DAY)) ON DUPLICATE KEY UPDATE dados='[]',versao=versao+1,updated_at=NOW(),expira_em=VALUES(expira_em)");
        $consulta->execute([$utilizador,$contexto]);
        return (int)$this->obter($utilizador,$contexto)['versao'];
    }
}
