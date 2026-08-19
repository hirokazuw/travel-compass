<?php
declare(strict_types=1);
namespace App\Services;
use RuntimeException;
final class ApifyClient
{
    public function __construct(private array $config) {}
    public function isConfigured(): bool { return trim((string)($this->config['token'] ?? '')) !== ''; }
    public function maxPages(): int { return max(1, (int)($this->config['max_pages'] ?? 1)); }
    public function maxPlaceSuggestions(): int { return max(1, (int)($this->config['max_place_suggestions'] ?? 8)); }
    public function run(string $endpoint, array $input): array
    {
        $url = (string)($this->config[$endpoint] ?? '');
        if (!function_exists('curl_init')) throw new RuntimeException('PHP cURL extension is required.');
        if ($url === '') throw new RuntimeException('Apify endpoint is not configured.');
        $curl = curl_init($url);
        curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($input, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => max(1, (int)($this->config['timeout'] ?? 120)), CURLOPT_CONNECTTIMEOUT => max(1, (int)($this->config['connect_timeout'] ?? 10)), CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . (string)$this->config['token'], 'Content-Type: application/json', 'Accept: application/json']]);
        $raw = curl_exec($curl); $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE); $error = curl_error($curl); curl_close($curl);
        if ($raw === false || $status < 200 || $status >= 300) throw new RuntimeException('Apify request failed: HTTP ' . $status . ($error !== '' ? ' ' . $error : ''));
        if (trim((string)$raw) === '') return [];
        $decoded = json_decode((string)$raw, true);
        if (!is_array($decoded)) throw new RuntimeException('Apify returned invalid JSON.');
        if (isset($decoded['error'])) throw new RuntimeException('Apify returned an error response.');
        return $decoded;
    }
}
