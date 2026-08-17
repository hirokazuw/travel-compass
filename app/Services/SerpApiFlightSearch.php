<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class SerpApiFlightSearch
{
    private const AIRPORT_GROUPS = [
        'TYO' => 'HND,NRT',
        'OSA' => 'KIX,ITM,UKB',
        'SPK' => 'CTS,OKD',
        'NGO' => 'NGO,NKM',
        'SEL' => 'ICN,GMP',
        'BKK' => 'BKK,DMK',
        'NYC' => 'JFK,LGA,EWR',
        'LON' => 'LHR,LGW,LCY,LTN,STN',
        'PAR' => 'CDG,ORY',
    ];
    public function __construct(
        private array $config,
        private SerpApiCache $cache
    )
    {
    }

    public function isConfigured(): bool
    {
        return ($this->config['api_key'] ?? '') !== '';
    }

    public function search(
        string $origin,
        string $destination,
        string $departureDate,
        string $returnDate,
        int $adults
    ): array {
        if (!$this->isConfigured()) {
            return [];
        }

        $query = [
            'engine' => 'google_flights',
            'api_key' => $this->config['api_key'],
            'departure_id' => $this->airportIds($origin),
            'arrival_id' => $this->airportIds($destination),
            'outbound_date' => $departureDate,
            'type' => $returnDate !== '' ? 1 : 2,
            'travel_class' => 1,
            'adults' => $adults,
            'currency' => 'JPY',
            'hl' => 'ja',
            'gl' => 'jp',
            'sort_by' => 1,
        ];

        if ($returnDate !== '') {
            $query['return_date'] = $returnDate;
        }

        $response = $this->cache->remember($query, fn(): array => $this->request($query));
        return $this->normalize($response);
    }

    private function airportIds(string $code): string
    {
        $code = strtoupper(trim($code));
        return self::AIRPORT_GROUPS[$code] ?? $code;
    }

    private function request(array $query): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension is required.');
        }

        $baseUrl = (string)($this->config['base_url'] ?? 'https://serpapi.com/search');
        $curl = curl_init($baseUrl . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986));
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);

        $raw = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($raw === false || $status < 200 || $status >= 300) {
            throw new RuntimeException('SerpApi request failed: HTTP ' . $status . ' ' . $error);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('SerpApi returned invalid JSON.');
        }

        if (isset($decoded['error'])) {
            throw new RuntimeException('SerpApi: ' . (string)$decoded['error']);
        }

        return $decoded;
    }

    private function normalize(array $response): array
    {
        $results = array_merge(
            is_array($response['best_flights'] ?? null) ? $response['best_flights'] : [],
            is_array($response['other_flights'] ?? null) ? $response['other_flights'] : []
        );
        $offersByCarrier = [];

        foreach ($results as $result) {
            $flights = $result['flights'] ?? [];
            if (!is_array($flights) || $flights === []) {
                continue;
            }

            $first = $flights[0];
            $last = $flights[array_key_last($flights)];
            $airlines = array_values(array_unique(array_filter(array_map(
                static fn(array $flight): string => (string)($flight['airline'] ?? ''),
                $flights
            ))));
            $flightNumber = (string)($first['flight_number'] ?? '');
            preg_match('/^[A-Z0-9]{2}/', $flightNumber, $carrierMatch);
            $carrierCode = (string)($carrierMatch[0] ?? '');
            $carrierName = implode(' / ', $airlines) ?: '航空会社';
            $carrierKey = mb_strtolower(
                $carrierName !== '航空会社' ? $carrierName : $carrierCode
            );
            $logo = (string)($result['airline_logo'] ?? $first['airline_logo'] ?? '');
            $price = (float)($result['price'] ?? 0);

            if ($price <= 0) {
                continue;
            }

            $offer = [
                'carrier_code' => $carrierCode,
                'carrier_name' => $carrierName,
                'airline_logo' => filter_var($logo, FILTER_VALIDATE_URL) && str_starts_with($logo, 'https://') ? $logo : '',
                'departure_time' => $this->time((string)($first['departure_airport']['time'] ?? '')),
                'arrival_time' => $this->time((string)($last['arrival_airport']['time'] ?? '')),
                'origin' => (string)($first['departure_airport']['id'] ?? ''),
                'destination' => (string)($last['arrival_airport']['id'] ?? ''),
                'duration' => $this->duration((int)($result['total_duration'] ?? 0)),
                'stops' => max(0, count($flights) - 1),
                'price' => number_format($price),
                'price_value' => $price,
                'currency' => 'JPY',
            ];

            if (
                !isset($offersByCarrier[$carrierKey]) ||
                $price < $offersByCarrier[$carrierKey]['price_value']
            ) {
                $offersByCarrier[$carrierKey] = $offer;
            }
        }

        uasort(
            $offersByCarrier,
            static fn(array $a, array $b): int =>
                $a['price_value'] <=> $b['price_value']
        );

        return array_map(
            static function (array $offer): array {
                unset($offer['price_value']);
                return $offer;
            },
            array_values($offersByCarrier)
        );
    }

    private function time(string $value): string
    {
        return preg_match('/(\d{2}:\d{2})$/', $value, $matches) ? $matches[1] : '--:--';
    }

    private function duration(int $minutes): string
    {
        if ($minutes <= 0) {
            return '';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;
        return ($hours > 0 ? $hours . '時間' : '')
            . ($remainingMinutes > 0 ? $remainingMinutes . '分' : '');
    }
}
