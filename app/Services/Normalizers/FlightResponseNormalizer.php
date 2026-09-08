<?php

declare(strict_types=1);

namespace App\Services\Normalizers;

final class FlightResponseNormalizer
{
    public function normalizeFlights(array $items): array
    {
        $offers = [];
        foreach ($items as $page) {
            if (!is_array($page)) continue;
            foreach (['best_flights' => 'best', 'other_flights' => 'other'] as $key => $group) {
                foreach ((array)($page[$key] ?? []) as $result) {
                    if (!is_array($result)) continue;
                    $segments = is_array($result['flights'] ?? null) ? $result['flights'] : [];
                    if ($segments === [] && isset($result['departure_airport'])) $segments = [$result];
                    $offer = $this->normalizeFlight($result, $segments, $group);
                    if ($offer !== null) $offers[] = $offer;
                }
            }
            // Some Actor versions expose only the flattened collection.
            if (!isset($page['best_flights'], $page['other_flights'])) {
                foreach ((array)($page['all_flights'] ?? []) as $result) {
                    if (!is_array($result)) continue;
                    $offer = $this->normalizeFlight($result, (array)($result['flights'] ?? [$result]), 'other');
                    if ($offer !== null) $offers[] = $offer;
                }
            }
        }
        usort($offers, static fn(array $a, array $b): int => $a['_price'] <=> $b['_price']);
        return array_map(static function (array $offer): array { unset($offer['_price']); return $offer; }, $offers);
    }

    private function normalizeFlight(array $result, array $segments, string $group): ?array
    {
        $segments = array_values(array_filter($segments, 'is_array'));
        if ($segments === []) return null;
        $first = $segments[0];
        $last = $segments[array_key_last($segments)];
        $price = NormalizedValue::priceValue($result['price'] ?? 0);
        if ($price <= 0) return null;
        $airlines = array_values(array_unique(array_filter(array_map(
            static fn(array $flight): string => trim((string)($flight['airline'] ?? $flight['airline_name'] ?? '')),
            $segments
        ))));
        $numbers = array_values(array_filter(array_map(
            static fn(array $flight): string => trim((string)($flight['flight_number'] ?? '')),
            $segments
        )));
        preg_match('/^[A-Z0-9]{2}/i', (string)($numbers[0] ?? ''), $carrier);
        $departureAirport = (array)($first['departure_airport'] ?? []);
        $arrivalAirport = (array)($last['arrival_airport'] ?? []);
        $minutes = (int)($result['total_duration'] ?? $result['duration'] ?? 0);
        $logo = (string)($result['airline_logo'] ?? $first['airline_logo'] ?? '');

        return [
            'carrier_code' => strtoupper((string)($carrier[0] ?? '')),
            'carrier_name' => implode(' / ', $airlines) ?: '航空会社',
            'airline' => implode(' / ', $airlines),
            'airline_logo' => NormalizedValue::httpsUrl($logo),
            'flight_number' => implode(' / ', $numbers),
            'departure_airport' => $departureAirport,
            'arrival_airport' => $arrivalAirport,
            'departure_time' => $this->time((string)($departureAirport['time'] ?? $first['departure_time'] ?? '')),
            'arrival_time' => $this->time((string)($arrivalAirport['time'] ?? $last['arrival_time'] ?? '')),
            'origin' => (string)($departureAirport['id'] ?? $departureAirport['code'] ?? ''),
            'destination' => (string)($arrivalAirport['id'] ?? $arrivalAirport['code'] ?? ''),
            'duration' => $this->duration($minutes),
            'duration_minutes' => $minutes,
            'stops' => isset($result['stops']) && is_numeric($result['stops']) ? max(0, (int)$result['stops']) : max(0, count($segments) - 1),
            'price' => number_format($price),
            'currency' => (string)($result['currency'] ?? 'JPY'),
            'travel_class' => (string)($first['travel_class'] ?? $result['travel_class'] ?? ''),
            'group' => $group,
            '_price' => $price,
        ];
    }

    private function time(string $value): string
    {
        return preg_match('/(\d{1,2}:\d{2})(?:\s|$)/', $value, $matches) ? $matches[1] : '--:--';
    }

    private function duration(int $minutes): string
    {
        if ($minutes <= 0) return '';
        return (intdiv($minutes, 60) > 0 ? intdiv($minutes, 60) . '時間' : '')
            . ($minutes % 60 > 0 ? $minutes % 60 . '分' : '');
    }
}
