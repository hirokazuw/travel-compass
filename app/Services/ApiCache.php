<?php

declare(strict_types=1);

namespace App\Services;

final class ApiCache
{
    public function __construct(
        private string $directory,
        private int $ttl,
        private int $maxBytes = 104857600,
        private int $maxEntries = 1000
    ) {}

    public function remember(array $query, callable $fetch): array
    {
        if ($this->ttl <= 0 || !$this->ensureDirectory()) {
            return $fetch();
        }

        unset($query['api_key'], $query['token']);
        $this->sortRecursively($query);
        $key = hash('sha256', json_encode($query, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $cacheFile = $this->directory . DIRECTORY_SEPARATOR . $key . '.json';
        $lockFile = $this->directory . DIRECTORY_SEPARATOR . $key . '.lock';

        $cached = $this->read($cacheFile);
        if ($cached !== null) {
            return $cached;
        }

        $lock = @fopen($lockFile, 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            if (is_resource($lock)) fclose($lock);
            return $fetch();
        }

        try {
            // Another request may have populated the cache while this one waited.
            $cached = $this->read($cacheFile);
            if ($cached !== null) {
                return $cached;
            }

            $value = $fetch();
            $this->write($cacheFile, $value);
            return $value;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function ensureDirectory(): bool
    {
        return is_dir($this->directory)
            || (@mkdir($this->directory, 0775, true) && is_dir($this->directory));
    }

    private function read(string $file): ?array
    {
        if (!is_file($file)) return null;

        $raw = @file_get_contents($file);
        $entry = $raw === false ? null : json_decode($raw, true);
        if (!is_array($entry)
            || !isset($entry['expires_at'], $entry['data'])
            || !is_array($entry['data'])
            || (int)$entry['expires_at'] <= time()
        ) {
            return null;
        }

        return $entry['data'];
    }

    private function write(string $file, array $value): void
    {
        $encoded = json_encode([
            'expires_at' => time() + $this->ttl,
            'data' => $value,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($encoded === false || strlen($encoded) > $this->maxBytes || $this->maxEntries <= 0) return;
        $lock = $this->maintenanceLock();
        if ($lock === false) return; // Cache contention must not fail a successful API response.
        try {
            if (is_link($file)) return;
            $key = basename($file, '.json');
            // Reserve space before writing. The caller already holds this key's lock.
            $report = $this->pruneLocked(false, $key, strlen($encoded), 1);
            if ($report['bytes'] + strlen($encoded) > $this->maxBytes
                || $report['entries'] + 1 > $this->maxEntries) return;
            $temporary = $file . '.' . bin2hex(random_bytes(6)) . '.tmp';
            $written = @file_put_contents($temporary, $encoded, LOCK_EX);
            if ($written !== strlen($encoded)) {
                @unlink($temporary);
                return;
            }
            // Windows cannot atomically replace an existing file with rename().
            if (is_file($file)) @unlink($file);
            if (!@rename($temporary, $file)) @unlink($temporary);
        } catch (\Throwable $e) {
            \App\Core\RequestLog::failure('cache.maintenance', $e);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** Dry-run is the default. Only recognized cache files directly inside the directory are eligible. */
    public function prune(bool $dryRun = true): array
    {
        if (!is_dir($this->directory)) return $this->emptyReport();
        $lock = $this->maintenanceLock();
        if ($lock === false) return array_replace($this->emptyReport(), ['busy' => true]);
        try {
            return $this->pruneLocked($dryRun);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function emptyReport(): array
    {
        return ['removed' => 0, 'bytes' => 0, 'entries' => 0, 'skipped' => 0, 'busy' => false];
    }

    private function maintenanceLock()
    {
        $path = $this->directory . DIRECTORY_SEPARATOR . '.maintenance.lock';
        if (is_link($path)) return false;
        $lock = @fopen($path, 'c');
        if ($lock === false) return false;
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            return false;
        }
        return $lock;
    }

    private function pruneLocked(bool $dryRun, ?string $heldKey = null, int $reservedBytes = 0, int $reservedEntries = 0): array
    {
        $report = $this->emptyReport();
        $files = [];
        $now = time();
        foreach (new \DirectoryIterator($this->directory) as $entry) {
            if ($entry->isLink() || !$entry->isFile()
                || !preg_match('/^([a-f0-9]{64})\.json(?:\.[a-f0-9]{12}\.tmp)?$/D', $entry->getFilename(), $match)) continue;
            $path = $entry->getPathname();
            $temporary = str_ends_with($path, '.tmp');
            $expired = $temporary ? $entry->getMTime() <= $now - 3600 : false;
            if (!$temporary) {
                $raw = @file_get_contents($path);
                $stored = $raw === false ? null : json_decode($raw, true);
                $expired = !is_array($stored) || !isset($stored['expires_at'], $stored['data'])
                    || !is_array($stored['data']) || (int)$stored['expires_at'] <= $now;
                // The replacement is accounted for by reservedBytes/reservedEntries.
                if ($match[1] === $heldKey) continue;
                $report['bytes'] += $entry->getSize();
                $report['entries']++;
            }
            $files[] = ['path' => $path, 'key' => $match[1], 'size' => $entry->getSize(),
                'mtime' => $entry->getMTime(), 'temporary' => $temporary, 'expired' => $expired];
        }
        // Expired/corrupt entries first, then oldest writes (not access-based LRU).
        usort($files, static fn(array $a, array $b): int =>
            [$a['expired'] ? 0 : 1, $a['mtime'], $a['path']] <=> [$b['expired'] ? 0 : 1, $b['mtime'], $b['path']]);
        foreach ($files as $file) {
            $overLimit = $report['bytes'] + $reservedBytes > max(0, $this->maxBytes)
                || $report['entries'] + $reservedEntries > max(0, $this->maxEntries);
            if (!$file['expired'] && ($file['temporary'] || !$overLimit)) continue;
            $keyLock = null;
            if ($file['key'] !== $heldKey) {
                $lockPath = $this->directory . DIRECTORY_SEPARATOR . $file['key'] . '.lock';
                if (is_link($lockPath)) { $report['skipped']++; continue; }
                $keyLock = @fopen($lockPath, 'c');
                if ($keyLock === false || !flock($keyLock, LOCK_EX | LOCK_NB)) {
                    if (is_resource($keyLock)) fclose($keyLock);
                    $report['skipped']++;
                    continue;
                }
            }
            try {
                if (is_link($file['path']) || (!$dryRun && !@unlink($file['path']))) {
                    $report['skipped']++;
                    continue;
                }
                $report['removed']++;
                if (!$file['temporary']) {
                    $report['bytes'] -= $file['size'];
                    $report['entries']--;
                }
            } finally {
                if (is_resource($keyLock)) { flock($keyLock, LOCK_UN); fclose($keyLock); }
            }
        }
        return $report;
    }

    private function sortRecursively(array &$value): void
    {
        ksort($value);
        foreach ($value as &$item) {
            if (is_array($item)) $this->sortRecursively($item);
        }
    }
}
