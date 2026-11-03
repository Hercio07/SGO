<?php
/**
 * Geração e retenção de cópias comprimidas da base de dados.
 * Os ficheiros ficam em storage/backups, que não é servido pelo Apache.
 */
class BackupService {
    private const RETENTION_DAYS = 30;
    private const PREFIX = 'sgo_';

    public static function gerarDiario(?int $utilizadorId = null): array {
        $lock = null;
        try {
            $lock = self::obterLock();
            $dados = date('Y-m-d');
            $destino = self::diretorio() . DIRECTORY_SEPARATOR . self::PREFIX . 'completo_' . $dados . '.zip';

            if (is_file($destino) && filesize($destino) > 0) {
                self::verificarCompleto($destino);
                self::replicar($destino);
                $removidos = self::rotacionar();
                if ($removidos > 0) {
                    LogSistema::registar('Rotação de Backups', "Foram removidos {$removidos} backup(s) com mais de 30 dias.", 'gestao', $utilizadorId);
                }
                self::registarResultado('existente',basename($destino));
                return ['estado' => 'existente'];
            }

            $tamanho = self::gerarFicheiro($destino);
            $removidos = self::rotacionar();
            $descricao = "Backup diário criado: " . basename($destino) . " ({$tamanho} bytes).";
            if ($removidos > 0) {
                $descricao .= " Rotação: {$removidos} backup(s) antigo(s) removido(s).";
            }
            LogSistema::registar('Backup Automático', $descricao, 'gestao', $utilizadorId);

            self::registarResultado('criado',basename($destino));
            return ['estado' => 'criado', 'ficheiro' => basename($destino), 'tamanho' => $tamanho];
        } catch (Throwable $e) {
            error_log('[SIGOP] Falha no backup diário: ' . $e->getMessage());
            self::registarResultado('falhado');
            LogSistema::registar('Backup Falhado', 'Não foi possível gerar o backup diário. Consulte o log do servidor.', 'gestao', $utilizadorId);
            return ['estado' => 'falhado'];
        } finally {
            self::libertarLock($lock);
        }
    }

    public static function gerarManual(?int $utilizadorId = null): array {
        $lock = null;
        try {
            $lock = self::obterLock();
            $nome = self::PREFIX . 'completo_manual_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.zip';
            $destino = self::diretorio() . DIRECTORY_SEPARATOR . $nome;
            $tamanho = self::gerarFicheiro($destino);
            $removidos = self::rotacionar();
            $descricao = "Backup manual criado: {$nome} ({$tamanho} bytes).";
            if ($removidos > 0) {
                $descricao .= " Rotação: {$removidos} backup(s) antigo(s) removido(s).";
            }
            LogSistema::registar('Backup Manual', $descricao, 'gestao', $utilizadorId);

            self::registarResultado('criado',$nome);
            return ['estado' => 'criado', 'ficheiro' => $nome, 'tamanho' => $tamanho];
        } catch (Throwable $e) {
            error_log('[SIGOP] Falha no backup manual: ' . $e->getMessage());
            self::registarResultado('falhado');
            LogSistema::registar('Backup Falhado', 'Não foi possível gerar o backup manual. Consulte o log do servidor.', 'gestao', $utilizadorId);
            return ['estado' => 'falhado'];
        } finally {
            self::libertarLock($lock);
        }
    }

    /** @return array<int, array{nome:string,data:int,tamanho:int}> */
    // Recolhe os filtros e os registos necessários para apresentar a listagem.
    public static function listar(): array {
        $dir = self::diretorio();
        if (!is_dir($dir)) {
            return [];
        }

        $ficheiros = [];
        foreach (new DirectoryIterator($dir) as $item) {
            if ($item->isDot() || !$item->isFile() || $item->isLink()) {
                continue;
            }
            $nome = $item->getFilename();
            if (!self::nomeValido($nome)) {
                continue;
            }
            $ficheiros[] = [
                'nome' => $nome,
                'data' => $item->getMTime(),
                'tamanho' => $item->getSize(),
            ];
        }

        usort($ficheiros, static fn(array $a, array $b): int => $b['data'] <=> $a['data']);
        return $ficheiros;
    }

    public static function caminhoDoFicheiro(string $nome): ?string {
        if (!self::nomeValido($nome)) {
            return null;
        }
        $dir = realpath(self::diretorio());
        $ficheiro = realpath(self::diretorio() . DIRECTORY_SEPARATOR . $nome);
        if ($dir === false || $ficheiro === false || dirname($ficheiro) !== $dir || !is_file($ficheiro) || is_link($ficheiro)) {
            return null;
        }
        return $ficheiro;
    }

    private static function nomeValido(string $nome): bool {
        return (bool)preg_match('/^sgo_(?:completo_)?(?:\d{4}-\d{2}-\d{2}|manual_\d{8}_\d{6}_[a-f0-9]{6})\.(?:sql\.gz|zip)$/', $nome);
    }

    private static function diretorio(): string {
        return ROOT . '/storage/backups';
    }

    public static function ultimoResultado(): ?array {
        $caminho = self::diretorio().'/ultima_execucao.json';
        return is_file($caminho) ? json_decode(file_get_contents($caminho),true) : null;
    }

    private static function registarResultado(string $estado, ?string $ficheiro = null): void {
        $dados = ['estado'=>$estado,'data'=>date('d/m/Y H:i:s'),'ficheiro'=>$ficheiro,
            'copia_externa'=>$estado === 'falhado' ? 'Não foi possível concluir todas as cópias.' : (env('BACKUP_EXTERNAL_DIR') !== '' ? 'Cópia adicional verificada.' : 'Cópia adicional não configurada.')];
        $caminho = self::diretorio().'/ultima_execucao.json';
        if (file_put_contents($caminho,json_encode($dados,JSON_UNESCAPED_UNICODE),LOCK_EX) === false) error_log('[SIGOP] Não foi possível guardar o resultado do backup.');
    }

    /** Abre um lock partilhado entre pedidos PHP deste servidor. */
    private static function obterLock() {
        $dir = self::diretorio();
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new RuntimeException('Não foi possível criar a pasta de backups.');
        }
        $lock = fopen($dir . DIRECTORY_SEPARATOR . '.backup.lock', 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            if (is_resource($lock)) {
                fclose($lock);
            }
            throw new RuntimeException('Não foi possível bloquear a pasta de backups.');
        }
        return $lock;
    }

    private static function libertarLock($lock): void {
        if (is_resource($lock)) {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private static function gerarFicheiro(string $destino): int {
        global $pdo;
        $sqlGzip = tempnam(sys_get_temp_dir(),'sigop_backup_');
        $sql = tempnam(sys_get_temp_dir(),'sigop_sql_');
        $temporario = $destino.'.'.bin2hex(random_bytes(6)).'.tmp';
        if ($sqlGzip === false || $sql === false) throw new RuntimeException('Não foi possível preparar o backup.');
        try {
            self::gerarSql($sqlGzip);
            $entrada = gzopen($sqlGzip,'rb'); $saida = fopen($sql,'wb');
            if (!$entrada || !$saida) throw new RuntimeException('Não foi possível preparar o SQL.');
            try { if (stream_copy_to_stream($entrada,$saida) === false) throw new RuntimeException('Falha ao ler o SQL.'); }
            finally { fclose($entrada); fclose($saida); }

            $zip = new ZipArchive();
            if ($zip->open($temporario,ZipArchive::CREATE|ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Não foi possível criar o ZIP.');
            try {
                $manifesto = ['versao'=>1,'gerado_em'=>date(DATE_ATOM),'ficheiros'=>[]];
                $adicionar = static function(string $origem,string $nome) use ($zip,&$manifesto): void {
                    if (is_link($origem) || !is_file($origem) || !$zip->addFile($origem,$nome)) throw new RuntimeException('Não foi possível copiar um ficheiro do backup.');
                    $manifesto['ficheiros'][$nome] = ['sha256'=>hash_file('sha256',$origem),'bytes'=>filesize($origem)];
                };
                $adicionar($sql,'database.sql');
                // Recusa backups incompletos quando um anexo registado já não existe no disco.
                foreach ($pdo->query('SELECT nome_arquivo FROM anexos_ocorrencia')->fetchAll(PDO::FETCH_COLUMN) as $nome) {
                    if ($nome !== basename($nome) || !is_file(UPLOAD_DIR.$nome) || is_link(UPLOAD_DIR.$nome)) throw new RuntimeException('Existe um anexo registado que não está disponível no disco.');
                }
                if (is_dir(UPLOAD_DIR)) {
                    $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(UPLOAD_DIR,FilesystemIterator::SKIP_DOTS));
                    foreach ($iterador as $item) {
                        if ($item->isLink()) throw new RuntimeException('Não são permitidos atalhos na pasta de anexos.');
                        if ($item->isFile()) $adicionar($item->getPathname(),'uploads/'.str_replace('\\','/',substr($item->getPathname(),strlen(UPLOAD_DIR))));
                    }
                }
                // O código e a configuração permitem repor esta versão da aplicação.
                foreach (['controllers','models','services','views','config','public','database'] as $pasta) {
                    $base = ROOT.'/'.$pasta;
                    if (!is_dir($base)) continue;
                    $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS));
                    foreach ($iterador as $item) {
                        if ($item->isLink()) throw new RuntimeException('Não são permitidos atalhos nos ficheiros da aplicação.');
                        if ($item->isFile()) $adicionar($item->getPathname(),'aplicacao/'.$pasta.'/'.str_replace('\\','/',substr($item->getPathname(),strlen($base)+1)));
                    }
                }
                foreach (['index.php','routes.php','composer.json','composer.lock','.htaccess','.env'] as $nome) {
                    if (is_file(ROOT.'/'.$nome)) $adicionar(ROOT.'/'.$nome,'aplicacao/'.$nome);
                }
                $manifesto['dependencias'] = 'As bibliotecas vendor são reinstaladas com composer install, utilizando o composer.lock incluído.';
                if (!$zip->addFromString('manifesto.json',json_encode($manifesto,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))) throw new RuntimeException('Falha ao escrever o manifesto.');
            } finally { if (!$zip->close()) throw new RuntimeException('Não foi possível concluir o ZIP.'); }
            self::verificarCompleto($temporario);
            if (!rename($temporario,$destino)) throw new RuntimeException('Não foi possível finalizar o backup.');
            @chmod($destino,0600);
            self::replicar($destino);
            return (int)filesize($destino);
        } finally {
            foreach ([$sqlGzip,$sql,$temporario] as $ficheiro) if (is_file($ficheiro)) unlink($ficheiro);
        }
    }

    public static function verificarCompleto(string $caminho): array {
        $zip = new ZipArchive();
        if ($zip->open($caminho,ZipArchive::CHECKCONS) !== true) throw new RuntimeException('Backup ZIP inválido.');
        try {
            $manifesto = json_decode($zip->getFromName('manifesto.json') ?: '',true,512,JSON_THROW_ON_ERROR);
            if (($manifesto['versao'] ?? 0) !== 1 || empty($manifesto['ficheiros']['database.sql'])) throw new RuntimeException('O backup não contém o manifesto esperado.');
            if ($zip->numFiles !== count($manifesto['ficheiros'])+1) throw new RuntimeException('O conteúdo do backup difere do manifesto.');
            foreach ($manifesto['ficheiros'] as $nome=>$dados) {
                if ($nome !== 'database.sql' && (!preg_match('~^(?:uploads|aplicacao)/[^:]+$~',$nome)
                    || preg_match('~(^|/)\.\.?(/|$)~',$nome) || str_contains($nome,'\\') || str_contains($nome,"\0"))) throw new RuntimeException('Nome inválido no backup.');
                $ficheiro = $zip->getStream($nome);
                if (!$ficheiro) throw new RuntimeException('Ficheiro ausente do backup.');
                $hash = hash_init('sha256');
                $bytes = hash_update_stream($hash,$ficheiro); fclose($ficheiro);
                if ($bytes !== $dados['bytes'] || !hash_equals($dados['sha256'],hash_final($hash))) throw new RuntimeException('A integridade do backup não foi confirmada.');
            }
            return $manifesto;
        } finally { $zip->close(); }
    }

    private static function replicar(string $caminho): void {
        $destino = trim(env('BACKUP_EXTERNAL_DIR'));
        if ($destino === '') return;
        $pasta = realpath($destino);
        $raiz = realpath(ROOT);
        if ($pasta === false || !is_dir($pasta) || !is_writable($pasta) || str_starts_with(strtolower($pasta).DIRECTORY_SEPARATOR,strtolower($raiz).DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('A pasta externa de backups não está disponível ou pertence à aplicação.');
        }
        $final = $pasta.DIRECTORY_SEPARATOR.basename($caminho);
        $temporario = $final.'.'.bin2hex(random_bytes(4)).'.tmp';
        try {
            if (!copy($caminho,$temporario) || !hash_equals(hash_file('sha256',$caminho),hash_file('sha256',$temporario))) throw new RuntimeException('Falha ao verificar a cópia externa.');
            if (!rename($temporario,$final)) throw new RuntimeException('Não foi possível concluir a cópia externa.');
        } finally { if (is_file($temporario)) unlink($temporario); }
    }

    private static function gerarSql(string $destino): int {
        global $pdo;
        if (!$pdo instanceof PDO) {
            throw new RuntimeException('Ligação à base de dados indisponível.');
        }

        $temporario = $destino . '.' . bin2hex(random_bytes(6)) . '.tmp';
        $gzip = gzopen($temporario, 'wb6');
        if ($gzip === false) {
            throw new RuntimeException('Não foi possível criar o ficheiro temporário.');
        }

        try {
            $pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $pdo->beginTransaction();
            $tabelas = self::tabelas($pdo);
            $esquemas = [];
            foreach ($tabelas as $tabela) {
                $consulta = $pdo->query('SHOW CREATE TABLE ' . self::identificador($tabela));
                $linha = $consulta->fetch(PDO::FETCH_NUM);
                if (!$linha || !isset($linha[1])) {
                    throw new RuntimeException('Não foi possível ler o esquema de uma tabela.');
                }
                $esquemas[$tabela] = $linha[1];
            }

            $ordem = self::ordenarTabelas($pdo, $tabelas);
            self::escrever($gzip, "-- Backup SIGOP-PRM gerado em " . date('Y-m-d H:i:s') . " (Africa/Maputo)\n");
            self::escrever($gzip, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

            foreach (array_reverse($ordem) as $tabela) {
                self::escrever($gzip, 'DROP TABLE IF EXISTS ' . self::identificador($tabela) . ";\n");
            }
            self::escrever($gzip, "\n");
            foreach ($ordem as $tabela) {
                self::escrever($gzip, $esquemas[$tabela] . ";\n\n");
            }

            self::escrever($gzip, "START TRANSACTION;\n");
            foreach ($ordem as $tabela) {
                $colunas = self::colunas($pdo, $tabela);
                if ($colunas === []) {
                    continue;
                }
                $consulta = $pdo->query('SELECT * FROM ' . self::identificador($tabela));
                $prefixo = 'INSERT INTO ' . self::identificador($tabela) . ' ('
                    . implode(', ', array_map([self::class, 'identificador'], array_keys($colunas))) . ') VALUES (';
                while ($linha = $consulta->fetch(PDO::FETCH_ASSOC)) {
                    $valores = [];
                    foreach ($colunas as $coluna => $tipo) {
                        $valores[] = self::valorSql($pdo, $linha[$coluna] ?? null, $tipo);
                    }
                    self::escrever($gzip, $prefixo . implode(', ', $valores) . ");\n");
                }
            }
            self::escrever($gzip, "COMMIT;\nSET FOREIGN_KEY_CHECKS=1;\n");
            $pdo->commit();
            $compressaoConcluida = gzclose($gzip);
            $gzip = false;
            if (!$compressaoConcluida) {
                throw new RuntimeException('Não foi possível concluir a compressão do backup.');
            }

            if (!is_file($temporario) || filesize($temporario) === 0 || !rename($temporario, $destino)) {
                throw new RuntimeException('Não foi possível finalizar o ficheiro de backup.');
            }
            @chmod($destino, 0600);
            return (int)filesize($destino);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (is_resource($gzip)) {
                gzclose($gzip);
            }
            if (is_file($temporario)) {
                @unlink($temporario);
            }
            throw $e;
        }
    }

    private static function tabelas(PDO $pdo): array {
        $tabelas = [];
        foreach ($pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as $linha) {
            if (isset($linha[1]) && strtoupper((string)$linha[1]) === 'BASE TABLE') {
                $tabelas[] = (string)$linha[0];
            }
        }
        sort($tabelas, SORT_STRING);
        return $tabelas;
    }

    private static function ordenarTabelas(PDO $pdo, array $tabelas): array {
        $disponiveis = array_fill_keys($tabelas, true);
        $dependencias = array_fill_keys($tabelas, []);
        $consulta = $pdo->prepare(
            'SELECT TABLE_NAME, REFERENCED_TABLE_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND REFERENCED_TABLE_NAME IS NOT NULL'
        );
        $consulta->execute([DB_NAME]);
        foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $tabela = (string)$linha['TABLE_NAME'];
            $referenciada = (string)$linha['REFERENCED_TABLE_NAME'];
            if (isset($disponiveis[$tabela], $disponiveis[$referenciada])) {
                $dependencias[$tabela][$referenciada] = true;
            }
        }

        $ordem = [];
        $visitadas = [];
        $visita = static function (string $tabela) use (&$visita, &$ordem, &$visitadas, $dependencias): void {
            if (isset($visitadas[$tabela])) {
                return;
            }
            $visitadas[$tabela] = true;
            foreach (array_keys($dependencias[$tabela] ?? []) as $dependencia) {
                $visita($dependencia);
            }
            $ordem[] = $tabela;
        };
        foreach ($tabelas as $tabela) {
            $visita($tabela);
        }
        return $ordem;
    }

    private static function colunas(PDO $pdo, string $tabela): array {
        $resultado = [];
        foreach ($pdo->query('SHOW COLUMNS FROM ' . self::identificador($tabela))->fetchAll(PDO::FETCH_ASSOC) as $linha) {
            $resultado[(string)$linha['Field']] = strtolower((string)$linha['Type']);
        }
        return $resultado;
    }

    private static function valorSql(PDO $pdo, $valor, string $tipo): string {
        if ($valor === null) {
            return 'NULL';
        }
        if (preg_match('/^(?:tinyblob|blob|mediumblob|longblob|binary|varbinary|bit)(?:\b|\()/i', $tipo)) {
            return '0x' . bin2hex((string)$valor);
        }
        $quoted = $pdo->quote((string)$valor);
        if ($quoted === false) {
            throw new RuntimeException('Não foi possível codificar um valor da base de dados.');
        }
        return $quoted;
    }

    private static function identificador(string $valor): string {
        return '`' . str_replace('`', '``', $valor) . '`';
    }

    private static function escrever($gzip, string $texto): void {
        $gravados = gzwrite($gzip, $texto);
        if ($gravados === false || $gravados !== strlen($texto)) {
            throw new RuntimeException('Erro ao escrever dados do backup.');
        }
    }

    private static function rotacionar(): int {
        $dir = self::diretorio();
        if (!is_dir($dir)) {
            return 0;
        }
        $limite = time() - (self::RETENTION_DAYS * 86400);
        $removidos = 0;
        foreach (new DirectoryIterator($dir) as $item) {
            if ($item->isDot() || !$item->isFile() || $item->isLink() || !self::nomeValido($item->getFilename())) {
                continue;
            }
            if ($item->getMTime() < $limite) {
                if (@unlink($item->getPathname())) {
                    $removidos++;
                } else {
                    error_log('[SIGOP] Não foi possível remover o backup antigo: ' . $item->getFilename());
                }
            }
        }
        return $removidos;
    }
}

// Executa o backup quando este serviço é chamado directamente pelo Agendador de Tarefas.
if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    define('ROOT',dirname(__DIR__));
    $_SESSION = [];
    require ROOT.'/vendor/autoload.php';
    require ROOT.'/config/config.php';
    if (!is_dir(ROOT.'/storage/backups')) mkdir(ROOT.'/storage/backups',0700,true);
    ini_set('log_errors','1');
    ini_set('error_log',ROOT.'/storage/backups/execucao.log');
    try {
        require ROOT.'/config/database.php';
        require_once ROOT.'/models/LogSistema.php';
        $resultado = BackupService::gerarDiario();
    } catch (Throwable $e) {
        error_log('[SIGOP] Não foi possível executar o backup: '.$e->getMessage());
        $resultado = ['estado'=>'falhado'];
        file_put_contents(ROOT.'/storage/backups/ultima_execucao.json',json_encode([
            'estado'=>'falhado','data'=>date('d/m/Y H:i:s'),'ficheiro'=>null,'copia_externa'=>'Não foi possível iniciar ou concluir o backup. Consulte o log de execução.'
        ],JSON_UNESCAPED_UNICODE),LOCK_EX);
    }
    echo json_encode($resultado,JSON_UNESCAPED_UNICODE).PHP_EOL;
    exit($resultado['estado'] === 'falhado' ? 1 : 0);
}
