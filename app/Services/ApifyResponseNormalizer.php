<?php

declare(strict_types=1);

namespace App\Services;

final class ApifyResponseNormalizer
{
    public function __construct(private array $config) {}

    public function normalizeHotels(array $items): array
    {
        $properties = [];
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            if (is_array($item['properties'] ?? null)) {
                array_push($properties, ...$item['properties']);
            } elseif (isset($item['name'])) {
                $properties[] = $item;
            }
        }

        $hotels = [];
        foreach ($properties as $hotel) {
            if (!is_array($hotel)) continue;
            $name = trim((string)($hotel['name'] ?? ''));
            if ($name === '') continue;
            $images = [];
            foreach ((array)($hotel['images'] ?? []) as $image) {
                $url = is_array($image) ? (string)($image['original_image'] ?? '') : (string)$image;
                if (($url = $this->httpsUrl($url)) !== '') $images[] = $url;
            }
            $hotels[] = [
                'name' => $name,
                'description' => trim((string)($hotel['description'] ?? '')),
                'official_url' => $this->httpsUrl((string)($hotel['link'] ?? '')),
                'property_token' => trim((string)($hotel['property_token'] ?? '')),
                'google_place_id' => trim((string)($hotel['place_id'] ?? $hotel['placeId'] ?? '')),
                'address' => trim((string)($hotel['address'] ?? '')),
                'latitude' => $this->coordinate($hotel['gps_coordinates'] ?? [], 'latitude'),
                'longitude' => $this->coordinate($hotel['gps_coordinates'] ?? [], 'longitude'),
                'hotel_class' => $this->nullableText($hotel['hotel_class'] ?? $hotel['extracted_hotel_class'] ?? null),
                'rating' => isset($hotel['overall_rating']) && is_numeric($hotel['overall_rating']) ? (float)$hotel['overall_rating'] : null,
                'reviews' => isset($hotel['reviews']) && is_numeric($hotel['reviews']) ? max(0, (int)$hotel['reviews']) : null,
                'price_per_night' => ($price = $this->rateValue($hotel['rate_per_night'] ?? 0)) > 0 ? $price : null,
                'total_price' => ($total = $this->rateValue($hotel['total_rate'] ?? 0)) > 0 ? $total : null,
                'check_in_time' => $this->nullableText($hotel['check_in_time'] ?? null),
                'check_out_time' => $this->nullableText($hotel['check_out_time'] ?? null),
                'image_urls' => array_values(array_unique($images)),
                'amenities' => array_values(array_filter(array_map('strval', (array)($hotel['amenities'] ?? [])))),
                'deal' => $hotel['deal'] ?? null,
            ];
        }
        return $hotels;
    }

    public function normalizeDestinationSuggestions(array $items): array
    {
        $suggestions = [];
        foreach ($items as $place) {
            if (!is_array($place)) continue;
            $name = trim((string)($place['name'] ?? $place['keyword'] ?? ''));
            if ($name === '') continue;
            $coordinate = is_array($place['coordinate'] ?? null) ? array_values($place['coordinate']) : [];
            $suggestions[] = [
                'name' => $name,
                'category' => trim((string)($place['type'] ?? $place['category'] ?? '')),
                'address' => trim((string)($place['address'] ?? '')),
                'place_id' => trim((string)($place['item_id'] ?? $place['place_id'] ?? '')),
                'country_code' => strtoupper(trim((string)($place['country_code'] ?? ''))),
                'latitude' => isset($coordinate[0]) && is_numeric($coordinate[0]) ? (float)$coordinate[0] : null,
                'longitude' => isset($coordinate[1]) && is_numeric($coordinate[1]) ? (float)$coordinate[1] : null,
            ];
        }
        return array_slice($suggestions, 0, max(1, (int)($this->config['max_place_suggestions'] ?? 8)));
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string)$value);
        return $value !== '' ? $value : null;
    }

    private function coordinate(mixed $coordinates, string $axis): ?float
    {
        if (!is_array($coordinates)) return null;
        $aliases = $axis === 'latitude' ? ['latitude', 'lat'] : ['longitude', 'lng', 'lon'];
        foreach ($aliases as $key) {
            if (isset($coordinates[$key]) && is_numeric($coordinates[$key])) return (float)$coordinates[$key];
        }
        return null;
    }

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
        $price = $this->priceValue($result['price'] ?? 0);
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
            'airline_logo' => $this->httpsUrl($logo),
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

    private function rateValue(mixed $rate): int
    {
        if (!is_array($rate)) return $this->priceValue($rate);
        foreach (['extracted_lowest', 'extracted_price', 'extracted', 'extracted_value', 'lowest', 'price', 'value', 'amount'] as $key) {
            if (!array_key_exists($key, $rate)) continue;
            $value = $this->rateValue($rate[$key]);
            if ($value > 0) return $value;
        }
        return 0;
    }

    private function priceValue(mixed $value): int
    {
        if (is_numeric($value)) return max(0, (int)round((float)$value));
        $digits = preg_replace('/[^0-9.]/', '', (string)$value);
        return $digits !== '' ? max(0, (int)round((float)$digits)) : 0;
    }

    private function httpsUrl(string $url): string
    {
        return filter_var($url, FILTER_VALIDATE_URL) && str_starts_with($url, 'https://') ? $url : '';
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
