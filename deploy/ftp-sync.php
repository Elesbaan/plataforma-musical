<?php

/**
 * Sincroniza una carpeta local con el servidor por FTP/FTPS (sin dependencias).
 *
 * Lo invoca deploy.ps1. Las credenciales llegan por variables de entorno
 * (FTP_HOST, FTP_PORT, FTP_USER, FTP_PASSWORD, FTP_REMOTE_PATH, FTP_SECURE)
 * y NUNCA se imprimen.
 *
 * - FTPS explícito (AUTH TLS) con ftp_ssl_connect cuando FTP_SECURE=true.
 * - Subida incremental: un manifiesto local (sha1 por archivo) evita volver a
 *   subir lo que no cambió. --full ignora el manifiesto.
 * - Escritura atómica: cada archivo se sube como "<nombre>.uploading" y luego se
 *   renombra, para que el sitio nunca sirva un PHP a medio subir.
 * - --dry-run lista lo que se subiría, sin conectarse.
 *
 * Uso: php deploy/ftp-sync.php <carpeta_local> <manifiesto.json> [--dry-run] [--full]
 */

const RETRIES = 3;
const SAVE_EVERY = 100;

[$script, $localRoot, $manifestPath] = $argv + [null, null, null];
$dryRun = in_array('--dry-run', $argv, true);
$full = in_array('--full', $argv, true);

if (! $localRoot || ! is_dir($localRoot) || ! $manifestPath) {
    fail('Uso: php deploy/ftp-sync.php <carpeta_local> <manifiesto.json> [--dry-run] [--full]');
}

$localRoot = rtrim(str_replace('\\', '/', realpath($localRoot)), '/');
$config = loadConfig($dryRun);
$manifest = (! $full && is_file($manifestPath)) ? (json_decode((string) file_get_contents($manifestPath), true) ?: []) : [];

// ----------------------------------------------------------------- Archivos a subir
$pending = [];
$totalBytes = 0;
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($localRoot, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if (! $file->isFile()) {
        continue;
    }
    $relative = ltrim(substr(str_replace('\\', '/', $file->getPathname()), strlen($localRoot)), '/');
    $hash = sha1_file($file->getPathname());
    if (($manifest[$relative] ?? null) !== $hash) {
        $pending[$relative] = $hash;
        $totalBytes += $file->getSize();
    }
}
ksort($pending);

out(sprintf('Archivos por subir: %d (%s). Sin cambios: %d.', count($pending), human($totalBytes), iterator_count($iterator) - count($pending)));

if ($dryRun) {
    foreach (array_slice(array_keys($pending), 0, 40) as $relative) {
        out("  + {$relative}");
    }
    if (count($pending) > 40) {
        out('  … y '.(count($pending) - 40).' más.');
    }
    out('Simulación terminada: no se subió nada.');
    exit(0);
}

if ($pending === []) {
    out('Todo está actualizado en el servidor.');
    exit(0);
}

// ----------------------------------------------------------------- Subida
$ftp = connect($config);
$knownDirs = [];
$done = 0;
$uploadedBytes = 0;

foreach ($pending as $relative => $hash) {
    $remote = $config['root'].'/'.$relative;
    $local = $localRoot.'/'.$relative;

    for ($attempt = 1; ; $attempt++) {
        try {
            ensureDir($ftp, dirname($remote), $knownDirs);
            upload($ftp, $local, $remote);
            break;
        } catch (RuntimeException $e) {
            if ($attempt >= RETRIES) {
                saveManifest($manifestPath, $manifest);
                fail("No se pudo subir {$relative}: {$e->getMessage()}");
            }
            out("  Reintentando {$relative} ({$attempt}/".RETRIES.')…');
            @ftp_close($ftp);
            sleep($attempt);
            $ftp = connect($config);
            $knownDirs = [];
        }
    }

    $manifest[$relative] = $hash;
    $done++;
    $uploadedBytes += filesize($local);

    if ($done % SAVE_EVERY === 0) {
        saveManifest($manifestPath, $manifest);
    }
    if ($done % 50 === 0 || $done === count($pending)) {
        out(sprintf('  %d/%d archivos (%s de %s)', $done, count($pending), human($uploadedBytes), human($totalBytes)));
    }
}

ftp_close($ftp);
saveManifest($manifestPath, $manifest);
out("Listo: {$done} archivo(s) subidos.");

// ===================================================================== Funciones

/**
 * @return array{host: string, port: int, user: string, password: string, root: string, secure: bool}
 */
function loadConfig(bool $dryRun): array
{
    $root = '/'.trim(str_replace('\\', '/', (string) getenv('FTP_REMOTE_PATH')), '/');
    $config = [
        'host' => (string) getenv('FTP_HOST'),
        'port' => (int) (getenv('FTP_PORT') ?: 21),
        'user' => (string) getenv('FTP_USER'),
        'password' => (string) getenv('FTP_PASSWORD'),
        'root' => $root === '/' ? '' : $root,
        'secure' => strtolower((string) getenv('FTP_SECURE')) !== 'false',
    ];

    if (! $dryRun) {
        foreach (['host' => 'FTP_HOST', 'user' => 'FTP_USER', 'password' => 'FTP_PASSWORD'] as $key => $name) {
            if ($config[$key] === '') {
                fail("Falta {$name} en .env.deploy.");
            }
        }
        if ($config['secure'] && ! function_exists('ftp_ssl_connect')) {
            fail('FTP_SECURE=true pero este PHP no tiene ftp_ssl_connect (habilita openssl y ftp en php.ini).');
        }
    }

    return $config;
}

function connect(array $config): FTP\Connection
{
    $ftp = $config['secure']
        ? @ftp_ssl_connect($config['host'], $config['port'], 30)
        : @ftp_connect($config['host'], $config['port'], 30);

    if (! $ftp) {
        fail("No se pudo conectar a {$config['host']}:{$config['port']}".($config['secure'] ? ' por FTPS.' : '.'));
    }
    if (! @ftp_login($ftp, $config['user'], $config['password'])) {
        fail('El servidor rechazó el usuario o la contraseña FTP.');
    }

    ftp_pasv($ftp, true);

    return $ftp;
}

/**
 * @param  array<string, true>  $known
 */
function ensureDir(FTP\Connection $ftp, string $dir, array &$known): void
{
    $dir = rtrim($dir, '/');
    if ($dir === '' || $dir === '.' || isset($known[$dir])) {
        return;
    }

    $path = '';
    foreach (explode('/', ltrim($dir, '/')) as $segment) {
        $path .= '/'.$segment;
        if (isset($known[$path])) {
            continue;
        }
        if (! @ftp_chdir($ftp, $path) && ! @ftp_mkdir($ftp, $path) && ! @ftp_chdir($ftp, $path)) {
            throw new RuntimeException("no se pudo crear la carpeta {$path}");
        }
        $known[$path] = true;
    }
}

function upload(FTP\Connection $ftp, string $local, string $remote): void
{
    $temporary = $remote.'.uploading';

    if (! @ftp_put($ftp, $temporary, $local, FTP_BINARY)) {
        throw new RuntimeException('falló la transferencia');
    }
    if (! @ftp_rename($ftp, $temporary, $remote)) {
        // Algunos servidores no sobrescriben al renombrar.
        @ftp_delete($ftp, $remote);
        if (! @ftp_rename($ftp, $temporary, $remote)) {
            @ftp_delete($ftp, $temporary);
            throw new RuntimeException('no se pudo reemplazar el archivo');
        }
    }
}

function saveManifest(string $path, array $manifest): void
{
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }
    ksort($manifest);
    file_put_contents($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function human(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $value = (float) $bytes;
    while ($value >= 1024 && $i < count($units) - 1) {
        $value /= 1024;
        $i++;
    }

    return number_format($value, $i === 0 ? 0 : 1).' '.$units[$i];
}

function out(string $message): void
{
    fwrite(STDOUT, $message.PHP_EOL);
}

function fail(string $message): never
{
    fwrite(STDERR, 'ERROR: '.$message.PHP_EOL);
    exit(1);
}
