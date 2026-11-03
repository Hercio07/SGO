<?php
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'sgo'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS'));
define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));
define('DB_PORT', env('DB_PORT', '3306'));

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log('[SIGOP] DB connection error: ' . $e->getMessage());
    if (PHP_SAPI === 'cli') throw $e;
    die('Erro de ligação à base de dados. Contacte o administrador do sistema.');
}
