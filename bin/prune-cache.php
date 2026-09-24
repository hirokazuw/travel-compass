<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (count($argv) > 2 || (isset($argv[1]) && !in_array($argv[1], ['--dry-run', '--apply'], true))) {
    fwrite(STDERR, "Usage: php bin/prune-cache.php [--dry-run|--apply]\n");
    exit(2);
}
$root = dirname(__DIR__);
spl_autoload_register(static function (string $class) use ($root): void {
    if (str_starts_with($class, 'App\\')) require $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
});
App\Core\Env::load($root . '/.env');
if (!is_file($root . '/config/config.php')) {
    fwrite(STDERR, "config/config.php is required.\n");
    exit(2);
}
$config = require $root . '/config/config.php';
$dryRun = ($argv[1] ?? '--dry-run') !== '--apply';
try {
    $reports = (new App\Factories\ApifySearchFactory($config['apify'] ?? [], $root))->pruneCaches($dryRun);
    echo json_encode(['dry_run' => $dryRun, 'caches' => $reports], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n";
    foreach ($reports as $report) {
        if ($report['busy'] || $report['skipped'] > 0) exit(1);
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Cache cleanup failed: " . $e->getMessage() . "\n");
    exit(1);
}
