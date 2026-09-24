<?php

declare(strict_types=1);

function withCacheDirectory(callable $test): void
{
    $directory = sys_get_temp_dir() . '/travel-compass-cache-' . bin2hex(random_bytes(8));
    mkdir($directory);
    try { $test($directory); }
    finally {
        foreach (new DirectoryIterator($directory) as $entry) {
            if (!$entry->isDot() && ($entry->isFile() || $entry->isLink())) unlink($entry->getPathname());
        }
        rmdir($directory);
    }
}

function cacheFixture(string $directory, string $key, int $expires, array $data = []): string
{
    $path = $directory . '/' . hash('sha256', $key) . '.json';
    file_put_contents($path, json_encode(['expires_at' => $expires, 'data' => $data]));
    return $path;
}

$t->test('Cache preserves hits, canonical keys and refuses stale data on failure', function () use ($t): void {
    withCacheDirectory(function (string $directory) use ($t): void {
        $cache = new App\Services\ApiCache($directory, 3600);
        $calls = 0;
        $fetch = static function () use (&$calls): array { $calls++; return ['value' => 1]; };
        $t->same(['value' => 1], $cache->remember(['b' => 2, 'a' => 1, 'token' => 'secret'], $fetch));
        $t->same(['value' => 1], $cache->remember(['a' => 1, 'b' => 2], $fetch));
        $t->same(1, $calls);
        $path = glob($directory . '/*.json')[0];
        file_put_contents($path, json_encode(['expires_at' => time() - 1, 'data' => ['old' => true]]));
        $failed = false;
        try {
            $cache->remember(['a' => 1, 'b' => 2], static function (): array { throw new RuntimeException('API unavailable'); });
        } catch (RuntimeException $e) { $failed = $e->getMessage() === 'API unavailable'; }
        $t->true($failed, 'Stale data must not mask the API exception');
        $t->same(['value' => 1], $cache->remember(['b' => 2, 'a' => 1], $fetch));
        $t->same(2, $calls);
    });
});

$t->test('Cache cleanup removes only owned expired, corrupt and abandoned files', function () use ($t): void {
    withCacheDirectory(function (string $directory) use ($t): void {
        $expired = cacheFixture($directory, 'expired', time() - 1);
        $fresh = cacheFixture($directory, 'fresh', time() + 3600);
        $corrupt = cacheFixture($directory, 'corrupt', time() + 3600);
        file_put_contents($corrupt, '{bad json');
        $oldTemporary = $expired . '.012345abcdef.tmp';
        $newTemporary = $fresh . '.012345abcdef.tmp';
        file_put_contents($oldTemporary, 'incomplete');
        touch($oldTemporary, time() - 3601);
        file_put_contents($newTemporary, 'in progress');
        file_put_contents($directory . '/usage.json', '{}');
        file_put_contents($directory . '/other.tmp', 'keep');
        $cache = new App\Services\ApiCache($directory, 3600);
        $report = $cache->prune();
        $t->same(3, $report['removed']);
        $t->true(is_file($expired) && is_file($corrupt) && is_file($oldTemporary), 'Dry-run preserves data');
        $report = $cache->prune(false);
        $t->same(3, $report['removed']);
        foreach ([$expired, $corrupt, $oldTemporary] as $path) $t->true(!is_file($path), 'Eligible file removed');
        foreach ([$fresh, $newTemporary, $directory . '/usage.json', $directory . '/other.tmp', $directory . '/' . hash('sha256', 'expired') . '.lock'] as $path) {
            $t->true(is_file($path), 'Live data, unrelated files and locks preserved');
        }
    });
});

$t->test('Cache enforces entry and byte budgets without failing fetched results', function () use ($t): void {
    withCacheDirectory(function (string $directory) use ($t): void {
        $old = cacheFixture($directory, 'old', time() + 3600);
        touch($old, time() - 100);
        $recent = cacheFixture($directory, 'recent', time() + 3600);
        $cache = new App\Services\ApiCache($directory, 3600, maxBytes: 1000, maxEntries: 2);
        $cache->remember(['new' => 1], static fn(): array => ['new']);
        $t->true(!is_file($old) && is_file($recent), 'Oldest write evicted first');
        $t->same(2, count(glob($directory . '/*.json')));
        $large = [str_repeat('x', 2000)];
        $t->same($large, $cache->remember(['large' => 1], static fn(): array => $large));
        $t->same(2, count(glob($directory . '/*.json')), 'Oversized result not cached');
        $report = (new App\Services\ApiCache($directory, 3600, maxBytes: 1))->prune(false);
        $t->same(0, $report['entries']);
        $t->same(0, $report['bytes']);
        $disabled = new App\Services\ApiCache($directory, 3600, maxEntries: 0);
        $t->same(['ok'], $disabled->remember([], static fn(): array => ['ok']));
        $t->same([], glob($directory . '/*.json'));
    });
});

$t->test('Cache cleanup skips active keys and writers respect maintenance contention', function () use ($t): void {
    withCacheDirectory(function (string $directory) use ($t): void {
        $expired = cacheFixture($directory, 'busy', time() - 1);
        $temporary = $expired . '.012345abcdef.tmp';
        file_put_contents($temporary, 'active');
        touch($temporary, time() - 3601);
        $keyLock = fopen(substr($expired, 0, -5) . '.lock', 'c');
        flock($keyLock, LOCK_EX);
        try {
            $cache = new App\Services\ApiCache($directory, 3600, maxEntries: 1);
            $report = $cache->prune(false);
            $t->same(2, $report['skipped']);
            $t->true(is_file($expired) && is_file($temporary), 'Active key and temporary protected');
            $t->same(['ok'], $cache->remember(['other' => 1], static fn(): array => ['ok']));
            $t->same(1, count(glob($directory . '/*.json')), 'No new entry when a locked entry consumes capacity');
        } finally { flock($keyLock, LOCK_UN); fclose($keyLock); }
        $maintenance = fopen($directory . '/.maintenance.lock', 'c');
        flock($maintenance, LOCK_EX);
        try {
            $t->same(true, $cache->prune(false)['busy']);
            $t->same(['ok'], $cache->remember(['other' => 1], static fn(): array => ['ok']));
            $t->same(1, count(glob($directory . '/*.json')));
        } finally { flock($maintenance, LOCK_UN); fclose($maintenance); }
        $t->same(2, $cache->prune(false)['removed']);
    });
});

$t->test('Concurrent cache misses share one fetch and leave complete JSON', function () use ($t, $root): void {
    withCacheDirectory(function (string $directory) use ($t, $root): void {
        $code = <<<'PHP'
require $argv[1] . '/app/Services/ApiCache.php';
$cache = new App\Services\ApiCache($argv[2], 3600);
$result = $cache->remember(['same' => 'query'], static function () use ($argv): array {
    file_put_contents($argv[2] . '/fetch-count', '1', FILE_APPEND | LOCK_EX);
    usleep(250000);
    return ['complete' => true];
});

echo json_encode($result);
PHP;
        $processes = [];
        try {
            for ($i = 0; $i < 2; $i++) {
                $process = proc_open([PHP_BINARY, '-r', $code, $root, $directory],
                    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
                if (!is_resource($process)) throw new RuntimeException('Cannot start cache test worker');
                fclose($pipes[0]);
                $processes[] = [$process, $pipes];
            }
            foreach ($processes as [$process, $pipes]) {
                $t->same('{"complete":true}', stream_get_contents($pipes[1]));
                $t->same('', stream_get_contents($pipes[2]));
            }
            $t->same('1', file_get_contents($directory . '/fetch-count'));
            $t->same(1, count(glob($directory . '/*.json')));
            $t->same([], glob($directory . '/*.tmp'));
        } finally {
            foreach ($processes as [$process, $pipes]) {
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_terminate($process);
                proc_close($process);
            }
        }
    });
});

$t->test('Cache cleanup CLI defaults to dry-run and applies only configured directories', function () use ($t, $root): void {
    withCacheDirectory(function (string $directory) use ($t, $root): void {
        $folders = ['bin', 'config', 'app', 'app/Core', 'app/Services', 'app/Factories'];
        $files = ['bin/prune-cache.php', 'app/Core/Env.php', 'app/Services/ApiCache.php',
            'app/Services/ApifyClient.php', 'app/Factories/ApifySearchFactory.php'];
        foreach ($folders as $folder) mkdir($directory . '/' . $folder);
        try {
            foreach ($files as $file) copy($root . '/' . $file, $directory . '/' . $file);
            file_put_contents($directory . '/config/config.php', '<?php return ' . var_export(['apify' => [
                'flight_cache_dir' => $directory, 'hotel_cache_dir' => $directory . '/absent-hotels',
                'places_cache_dir' => $directory . '/absent-places',
            ]], true) . ';');
            $expired = cacheFixture($directory, 'cli-expired', time() - 1);
            foreach ([null, '--apply'] as $mode) {
                $command = [PHP_BINARY, $directory . '/bin/prune-cache.php'];
                if ($mode !== null) $command[] = $mode;
                $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
                if (!is_resource($process)) throw new RuntimeException('Cannot start cleanup CLI');
                fclose($pipes[0]);
                $output = stream_get_contents($pipes[1]);
                $errors = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $t->same(0, proc_close($process));
                $t->same('', $errors);
                $report = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
                $t->same($mode === null, $report['dry_run']);
                $t->same(1, $report['caches']['flights']['removed']);
                clearstatcache();
                $t->same($mode === null, is_file($expired));
            }
            $t->true(!is_dir($directory . '/absent-hotels'), 'Missing cache directory is not created');
        } finally {
            foreach ($files as $file) if (is_file($directory . '/' . $file)) unlink($directory . '/' . $file);
            if (is_file($directory . '/config/config.php')) unlink($directory . '/config/config.php');
            foreach (array_reverse($folders) as $folder) rmdir($directory . '/' . $folder);
        }
    });
});
