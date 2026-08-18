<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Airline;
use DateTimeImmutable;
use RuntimeException;

final class AviasalesPriceSearch
{
    public function __construct(
        private array $config,
        private SerpApiCache $cache,
        private Airline $airlines
    ) {}

    public function isConfigured(): bool
    {
        return trim((string)($this->config['token'] ?? '')) !== '';
    }

    public function search(
        string $origin,
        string $destination,
        string $departureDate,
        string $returnDate
    ): array {
        if (!$this->isConfigured()) return [];

        $query = [
            'origin' => strtoupper($origin),
            'destination' => strtoupper($destination),
            'departure_date' => substr($departureDate, 0, 7),
            'calendar_type' => 'departure_date',
            'currency' => 'JPY',
        ];
        if ($returnDate !== '') $query['return_date'] = substr($returnDate, 0, 7);

        $response = $this->cache->remember($query, fn(): array => $this->request($query));
        return $this->closestPrice($response, $departureDate, $returnDate);
    }

    private function request(array $query): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is required.');
        }

        $baseUrl = (string)($this->config['base_url'] ?? 'https://api.travelpayouts.com/v1/prices/calendar');
        $curl = curl_init($baseUrl . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986));
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Accept-Encoding: gzip, deflate',
                'X-Access-Token: ' . trim((string)$this->config['token']),
            ],
        ]);

        $raw = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($raw === false || $status < 200 || $status >= 300) {
            throw new RuntimeException('Aviasales request failed: HTTP ' . $status . ' ' . $error);
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) throw new RuntimeException('Aviasales returned invalid JSON.');
        if (($decoded['success'] ?? false) !== true) {
            throw new RuntimeException('Aviasales: ' . (string)($decoded['error'] ?? 'unknown error'));
        }
        return $decoded;
    }

    private function closestPrice(array $response, string $departureDate, string $returnDate): array
    {
        $data = $response['data'] ?? [];
        if (!is_array($data)) return [];

        $wantedDeparture = new DateTimeImmutable($departureDate);
        $wantedReturn = $returnDate !== '' ? new DateTimeImmutable($returnDate) : null;
        $bestByCarrier = [];

        foreach ($data as $dateKey => $item) {
            if (!is_array($item) || !is_numeric($item['price'] ?? null) || (float)$item['price'] <= 0) continue;
            $actualDeparture = $this->date((string)($item['departure_at'] ?? $dateKey));
            if ($actualDeparture === null) continue;
            $score = abs($actualDeparture->getTimestamp() - $wantedDeparture->getTimestamp());
            if ($wantedReturn !== null) {
                $actualReturn = $this->date((string)($item['return_at'] ?? ''));
                if ($actualReturn === null) continue;
                $score += abs($actualReturn->getTimestamp() - $wantedReturn->getTimestamp());
            }
            $carrier = strtoupper(trim((string)($item['airline'] ?? '')));
            if ($carrier === '') $carrier = 'OTHER';
            $current = $bestByCarrier[$carrier] ?? null;
            if (
                $current === null
                || $score < $current['score']
                || ($score === $current['score'] && (float)$item['price'] < $current['price'])
            ) {
                $bestByCarrier[$carrier] = [
                    'item' => $item,
                    'score' => $score,
                    'price' => (float)$item['price'],
                ];
            }
        }

        uasort($bestByCarrier, static fn(array $a, array $b): int => $a['price'] <=> $b['price']);

        $offers = [];
        foreach ($bestByCarrier as $carrier => $selected) {
            $item = $selected['item'];
            $offers[] = [
                'carrier_code' => $carrier === 'OTHER' ? '' : $carrier,
                'carrier_name' => $this->airlines->displayName($carrier === 'OTHER' ? '' : $carrier),
                'airline_logo' => $carrier === 'OTHER'
                    ? ''
                    : 'https://pics.avs.io/120/40/' . rawurlencode($carrier) . '.png',
                'departure_time' => '',
                'arrival_time' => '',
                'origin' => strtoupper((string)($item['origin'] ?? '')),
                'destination' => strtoupper((string)($item['destination'] ?? '')),
                'duration' => '',
                'stops' => max(0, (int)($item['transfers'] ?? 0)),
                'price' => number_format($selected['price']),
                'currency' => 'JPY',
            ];
        }

        return $offers;
    }

    private function date(string $value): ?DateTimeImmutable
    {
        if ($value === '') return null;
        try {
            return new DateTimeImmutable(substr($value, 0, 10));
        } catch (\Throwable) {
            return null;
        }
    }
}
