<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Airline;
use RuntimeException;

final class AeroDataBoxRouteSearch
{
    public function __construct(
        private array $config,
        private SerpApiCache $cache,
        private Airline $airlines
    ) {}

    public function isConfigured(): bool
    {
        return trim((string)($this->config['api_key'] ?? '')) !== '';
    }

    /** @param list<array{iata: string, name: string}> $origins
     *  @param list<array{iata: string, name: string}> $destinations */
    public function search(array $origins, array $destinations): array
    {
        if (!$this->isConfigured()) return [];
        $destinationCodes = array_fill_keys(array_map(
            static fn(array $airport): string => strtoupper($airport['iata']),
            $destinations
        ), true);
        $operators = [];

        foreach ($origins as $origin) {
            $originCode = strtoupper($origin['iata']);
            $response = $this->cache->remember(
                ['origin' => $originCode],
                fn(): array => $this->request($originCode)
            );
            foreach ((array)($response['routes'] ?? []) as $route) {
                if (!is_array($route)) continue;
                $destination = is_array($route['destination'] ?? null) ? $route['destination'] : [];
                $destinationCode = strtoupper(trim((string)($destination['iata'] ?? '')));
                if ($destinationCode === '' || !isset($destinationCodes[$destinationCode])) continue;

                foreach ((array)($route['operators'] ?? []) as $operator) {
                    if (!is_array($operator)) continue;
                    $iata = strtoupper(trim((string)($operator['iata'] ?? '')));
                    $apiName = trim((string)($operator['name'] ?? ''));
                    if ($iata === '' && $apiName === '') continue;
                    $key = $iata !== '' ? 'iata:' . $iata : 'name:' . mb_strtolower($apiName);
                    if (isset($operators[$key])) continue;

                    $destinationName = trim((string)($destination['shortName'] ?? $destination['name'] ?? ''));
                    $operators[$key] = [
                        'carrier_name' => $iata !== '' ? $this->airlines->displayName($iata) : $apiName,
                        'carrier_code' => $iata !== '' ? $iata : strtoupper(trim((string)($operator['icao'] ?? ''))),
                        'carrier_iata' => $iata,
                        'logo_url' => $iata !== '' ? 'https://pics.avs.io/120/40/' . rawurlencode($iata) . '.png' : '',
                        'origin_name' => trim((string)$origin['name']) ?: $originCode,
                        'origin_iata' => $originCode,
                        'destination_name' => $destinationName !== '' ? $destinationName : $destinationCode,
                        'destination_iata' => $destinationCode,
                    ];
                }
            }
        }

        uasort($operators, static fn(array $a, array $b): int => strcasecmp($a['carrier_name'], $b['carrier_name']));
        return array_values($operators);
    }

    private function request(string $origin): array
    {
        if (!function_exists('curl_init')) throw new RuntimeException('PHP cURL extension is required.');
        $baseUrl = rtrim((string)($this->config['base_url'] ?? 'https://aerodatabox.p.rapidapi.com'), '/');
        $url = $baseUrl . '/airports/iata/' . rawurlencode($origin) . '/stats/routes/daily';
        $headers = [
            'Accept: application/json',
            'X-RapidAPI-Key: ' . trim((string)$this->config['api_key']),
            'X-RapidAPI-Host: ' . (string)($this->config['rapidapi_host'] ?? 'aerodatabox.p.rapidapi.com'),
        ];
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => $headers,
        ]);
        $raw = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($status === 204) return ['routes' => []];
        if ($raw === false || $status < 200 || $status >= 300) {
            throw new RuntimeException('AeroDataBox request failed: HTTP ' . $status . ' ' . $error);
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) throw new RuntimeException('AeroDataBox returned invalid JSON.');
        return $decoded;
    }
}
