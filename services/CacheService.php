<?php
// Serviço de cache: guarda resultados temporários e evita repetir consultas do painel.
class CacheService {
    private const EXT = '.cache';

    private static function diretorio(): string {
        static $resolved = null;
        if ($resolved === null) {
            $path = ROOT . '/storage/cache';
            if (!is_dir($path)) {
                @mkdir($path, 0755, true);
            }
            $resolved = rtrim(str_replace('/', DIRECTORY_SEPARATOR, realpath($path) ?: $path), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        }
        return $resolved;
    }

    public static function obter(string $key) {
        $file = self::caminho($key);
        if (!file_exists($file)) return null;
        $dados = @unserialize(file_get_contents($file));
        if (!$dados || time() > $dados['expires']) {
            @unlink($file);
            return null;
        }
        return $dados['value'];
    }

    public static function guardar(string $key, $value, int $ttl = 300): void {
        $dir = self::diretorio();
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        @file_put_contents(
            self::caminho($key),
            serialize(['expires' => time() + $ttl, 'value' => $value]),
            LOCK_EX
        );
    }

    public static function remover(string $key): void {
        $file = self::caminho($key);
        if (file_exists($file)) @unlink($file);
    }

    public static function limpar(): void {
        $files = glob(self::diretorio() . '*' . self::EXT) ?: [];
        foreach ($files as $f) @unlink($f);
    }

    private static function caminho(string $key): string {
        return self::diretorio() . md5($key) . self::EXT;
    }
}
