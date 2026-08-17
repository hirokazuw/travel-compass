<?php

declare(strict_types=1);

namespace App\Services;

final class SerpApiCache
{
    public function __construct(
        private string $directory,
        private int $ttl
    ) {}

    public function remember(array $query, callable $fetch): array
    {
        if ($this->ttl <= 0 || !$this->ensureDirectory()) {
            return $fetch();
        }

        unset($query['api_key']);
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

        if ($encoded === false) return;

        $temporary = $file . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (@file_put_contents($temporary, $encoded, LOCK_EX) === false) return;

        // Windows cannot atomically replace an existing file with rename().
        if (is_file($file)) @unlink($file);
        if (!@rename($temporary, $file)) {
            @unlink($temporary);
        }
    }

    private function sortRecursively(array &$value): void
    {
        ksort($value);
        foreach ($value as &$item) {
            if (is_array($item)) $this->sortRecursively($item);
        }
    }
}
