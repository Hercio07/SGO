<?php
// Model LogSistema: regista acessos e operações de gestão para auditoria.
class LogSistema {
    public static function registar(
        string  $acao,
        string  $descricao    = '',
        string  $tipo         = 'acesso',
        ?int    $utilizadorId = null,
        ?string $identificadorTentativa = null
    ): void {
        $uid = $utilizadorId ?? ($_SESSION['user_id'] ?? null);
        $ip  = self::obterIp();
        // Best-effort: uma falha ao gravar o log de auditoria (ex: utilizador_id
        // referente a uma conta entretanto apagada) nunca deve impedir a acção
        // real (login/logout/etc) que despoletou o registo.
        try {
            global $pdo;
            $consulta = $pdo->prepare(
                'INSERT INTO log_sistema (utilizador_id, identificador_tentativa, tipo, acao, descricao, ip)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $consulta->execute([$uid, $identificadorTentativa, $tipo, $acao, $descricao ?: null, $ip]);
        } catch (\Throwable $e) {
            error_log('[SIGOP] Falha ao gravar log_sistema: ' . $e->getMessage());
        }
    }

    public static function obterIp(): string {
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return '—';
    }

    public static function contarAcoesRecentes(string $ip, string $acao): int {
        global $pdo;
        $consulta = $pdo->prepare(
            'SELECT COUNT(*) FROM log_sistema WHERE ip=? AND acao=? AND created_at > (NOW() - INTERVAL 15 MINUTE)'
        );
        $consulta->execute([$ip, $acao]);
        return (int)$consulta->fetchColumn();
    }
}
