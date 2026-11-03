<?php
// SMTP: configuração local do servidor; não editável pela interface web.
if (is_file(ROOT . '/.env')) {
    foreach (file(ROOT . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) continue;
        $value = trim($value);
        if (str_starts_with($value, '"')) {
            $decoded = json_decode($value, true);
            if (is_string($decoded)) $value = $decoded;
        }
        $_ENV[$key] = $value;
    }
}

function env(string $key, string $default = ''): string {
    return (string)($_ENV[$key] ?? getenv($key) ?: $default);
}

define('BASE_URL', env('BASE_URL','http://localhost/SGO'));
define('APP_NAME', 'SIGOP-PRM');
define('APP_FULL_NAME', 'Sistema Integrado de Gestão de Ocorrências Policiais');
define('APP_VERSION', '1.0.0');
define('APP_YEAR', '2026');
define('ENTITY_NAME', 'Polícia da República de Moçambique');
define('COMMAND_NAME', 'Comando da Cidade de Maputo');

define('UPLOAD_DIR', dirname(__DIR__) . '/uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024);

define('ITEMS_PER_PAGE', 6);
define('SESSION_TIMEOUT', 15 * 60);
define('SESSION_WARNING', 60);

define('MAIL_USER', env('MAIL_USER'));
define('MAIL_PASS', env('MAIL_PASS'));
define('MAIL_HOST', env('MAIL_HOST', 'smtp.gmail.com'));
define('MAIL_PORT', (int)env('MAIL_PORT', '465'));
define('MAIL_ENCRYPTION', env('MAIL_ENCRYPTION', 'ssl'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', APP_NAME . ' - ' . COMMAND_NAME));

date_default_timezone_set('Africa/Maputo');
