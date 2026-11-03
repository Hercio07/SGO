<?php
// Regras comuns à primeira alteração, recuperação e gestão de senhas.
class SenhaService {
    public const MINIMO = 6;

    public static function validar(string $senha, array $utilizador = []): ?string {
        if (!mb_check_encoding($senha,'UTF-8') || str_contains($senha,"\0")) return 'A senha contém caracteres inválidos.';
        if (mb_strlen($senha,'UTF-8') < self::MINIMO) return 'Use uma senha com pelo menos '.self::MINIMO.' caracteres.';
        if (strlen($senha) > 72) return 'A senha é demasiado longa. Use uma frase mais curta.';
        if (preg_match('/^(.)\1+$/u',$senha) || preg_match('/^(123456|password|senha|qwerty)/i',$senha)) return 'Escolha uma senha menos previsível, por exemplo uma frase pessoal.';
        if (!empty($utilizador['password']) && password_verify($senha,$utilizador['password'])) return 'A nova senha deve ser diferente da senha actual.';
        global $pdo;
        $consulta = $pdo->prepare('SELECT password FROM utilizadores WHERE id<>?');
        $consulta->execute([(int)($utilizador['id'] ?? 0)]);
        foreach ($consulta->fetchAll(PDO::FETCH_COLUMN) as $hash) {
            if (password_verify($senha,$hash)) return 'Esta senha não está disponível. Escolha uma senha pessoal diferente.';
        }
        return null;
    }
}
