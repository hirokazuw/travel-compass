<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class ScrapeDoService
{
    public function __construct(
        private array $config,
        private SerpApiCache $flightCache,
        private SerpApiCache $hotelCache
    ) {}

    public function isConfigured(): bool
    {
        return trim((string)($this->config['token'] ?? '')) !== '';
    }

    public function searchFlights(
        string $departureId,
        string $arrivalId,
        string $outboundDate,
        string $returnDate,
        int $adults = 1
    ): array {
        if (!$this->isConfigured()) return [];

        $query = [
            'token' => (string)$this->config['token'],
            'departure_id' => strtoupper($departureId),
            'arrival_id' => strtoupper($arrivalId),
            'outbound_date' => $outboundDate,
            'type' => $returnDate !== '' ? 1 : 2,
            'travel_class' => 1,
            'adults' => $adults,
            'currency' => 'JPY',
            'hl' => 'ja',
            'gl' => 'jp',
        ];
        if ($returnDate !== '') $query['return_date'] = $returnDate;

        $response = $this->flightCache->remember(
            $query,
            fn(): array => $this->request((string)($this->config['flights_url'] ?? ''), $query)
        );
        return $this->normalizeFlights($response);
    }

    public function searchHotels(
        string $queryText,
        string $checkIn,
        string $checkOut,
        int $adults = 1,
        int $children = 0
    ): array
    {
        if (!$this->isConfigured()) return [];

        $query = [
            'token' => (string)$this->config['token'],
            'q' => $queryText,
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'hl' => 'ja',
            'gl' => 'jp',
            'currency' => 'JPY',
        ];
        $response = $this->hotelCache->remember(
            $query,
            fn(): array => $this->request((string)($this->config['hotels_url'] ?? ''), $query)
        );
        $hotels = $this->normalizeHotels($response);
        foreach ($hotels as &$hotel) {
            $hotel['booking_links'] = $this->buildHotelBookingLinks(
                (string)$hotel['name'],
                $checkIn,
                $checkOut,
                $adults,
                $children
            );
        }
        unset($hotel);
        return $hotels;
    }

    private function request(string $url, array $query): array
    {
        if (!function_exists('curl_init')) throw new RuntimeException('PHP cURL extension is required.');
        if ($url === '') throw new RuntimeException('Scrape.do endpoint is not configured.');

        $curl = curl_init($url . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986));
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => max(1, (int)($this->config['timeout'] ?? 20)),
            CURLOPT_CONNECTTIMEOUT => max(1, (int)($this->config['connect_timeout'] ?? 5)),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $raw = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($raw === false || $status < 200 || $status >= 300) {
            throw new RuntimeException('Scrape.do request failed: HTTP ' . $status . ' ' . $error);
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) throw new RuntimeException('Scrape.do returned invalid JSON.');
        if (isset($decoded['error'])) {
            $message = is_array($decoded['error']) ? json_encode($decoded['error'], JSON_UNESCAPED_UNICODE) : $decoded['error'];
            throw new RuntimeException('Scrape.do: ' . (string)$message);
        }
        return $decoded;
    }

    private function normalizeFlights(array $response): array
    {
        $offers = [];
        foreach (['best_flights' => 'best', 'other_flights' => 'other'] as $key => $group) {
            foreach (is_array($response[$key] ?? null) ? $response[$key] : [] as $result) {
                $segments = is_array($result['flights'] ?? null) ? $result['flights'] : [];
                if ($segments === []) continue;
                $first = $segments[0];
                $last = $segments[array_key_last($segments)];
                $price = (float)($result['price'] ?? 0);
                if ($price <= 0) continue;
                $airlines = array_values(array_unique(array_filter(array_map(
                    static fn(array $flight): string => trim((string)($flight['airline'] ?? '')),
                    $segments
                ))));
                $flightNumbers = array_values(array_filter(array_map(
                    static fn(array $flight): string => trim((string)($flight['flight_number'] ?? '')),
                    $segments
                )));
                preg_match('/^[A-Z0-9]{2}/', (string)($flightNumbers[0] ?? ''), $carrierMatch);
                $logo = (string)($result['airline_logo'] ?? $first['airline_logo'] ?? '');
                $offers[] = [
                    'carrier_code' => (string)($carrierMatch[0] ?? ''),
                    'carrier_name' => implode(' / ', $airlines) ?: '航空会社',
                    'airline' => implode(' / ', $airlines),
                    'airline_logo' => $this->httpsUrl($logo),
                    'flight_number' => implode(' / ', $flightNumbers),
                    'departure_airport' => (array)($first['departure_airport'] ?? []),
                    'arrival_airport' => (array)($last['arrival_airport'] ?? []),
                    'departure_time' => $this->time((string)($first['departure_airport']['time'] ?? '')),
                    'arrival_time' => $this->time((string)($last['arrival_airport']['time'] ?? '')),
                    'origin' => (string)($first['departure_airport']['id'] ?? ''),
                    'destination' => (string)($last['arrival_airport']['id'] ?? ''),
                    'duration' => $this->duration((int)($result['total_duration'] ?? 0)),
                    'duration_minutes' => (int)($result['total_duration'] ?? 0),
                    'stops' => max(0, count($segments) - 1),
                    'price' => number_format($price),
                    'price_value' => $price,
                    'currency' => 'JPY',
                    'travel_class' => (string)($first['travel_class'] ?? ''),
                    'group' => $group,
                ];
            }
        }
        usort($offers, static fn(array $a, array $b): int => $a['price_value'] <=> $b['price_value']);
        return array_map(static function (array $offer): array {
            unset($offer['price_value']);
            return $offer;
        }, $offers);
    }

    private function normalizeHotels(array $response): array
    {
        $properties = $response['properties'] ?? $response['hotels'] ?? [];
        if (!is_array($properties)) return [];
        $hotels = [];
        foreach ($properties as $hotel) {
            if (!is_array($hotel)) continue;
            $name = trim((string)($hotel['name'] ?? ''));
            if ($name === '') continue;
            $rate = $hotel['rate_per_night'] ?? $hotel['price'] ?? $hotel['nightly_price'] ?? [];
            $total = $hotel['total_rate'] ?? $hotel['total_price'] ?? $hotel['total'] ?? [];
            $images = [];
            foreach ((array)($hotel['images'] ?? []) as $image) {
                $url = is_array($image) ? (string)($image['original_image'] ?? $image['thumbnail'] ?? '') : (string)$image;
                $url = $this->httpsUrl($url);
                if ($url !== '') $images[] = $url;
            }
            $hotels[] = [
                'name' => $name,
                'description' => trim((string)($hotel['description'] ?? '')),
                'hotel_class' => (string)($hotel['hotel_class'] ?? ''),
                'overall_rating' => isset($hotel['overall_rating']) ? (string)$hotel['overall_rating'] : '',
                'reviews' => max(0, (int)($hotel['reviews'] ?? 0)),
                'price' => $this->rateValue($rate),
                'total_rate' => $this->rateValue($total),
                'check_in_time' => trim((string)($hotel['check_in_time'] ?? '')),
                'check_out_time' => trim((string)($hotel['check_out_time'] ?? '')),
                'gps_coordinates' => (array)($hotel['gps_coordinates'] ?? []),
                'images' => array_values(array_unique($images)),
                'link' => $this->httpsUrl((string)($hotel['link'] ?? $hotel['property_token'] ?? '')),
            ];
        }
        return $hotels;
    }

    public function buildHotelBookingLinks(
        string $hotelName,
        string $checkIn,
        string $checkOut,
        int $adults,
        int $children
    ): array {
        $common = [
            'destination' => $hotelName,
            'startDate' => $checkIn,
            'endDate' => $checkOut,
            'adults' => max(1, $adults),
            'children' => max(0, $children),
            'rooms' => 1,
        ];

        $links = [
            // These links are converted by the ValueCommerce vcdal script.
            'expedia' => 'https://www.expedia.co.jp/Hotel-Search?' . http_build_query($common + [
                'd1' => $checkIn,
                'd2' => $checkOut,
                'sort' => 'RECOMMENDED',
            ], '', '&', PHP_QUERY_RFC3986),
            'hotels' => 'https://jp.hotels.com/Hotel-Search?' . http_build_query($common, '', '&', PHP_QUERY_RFC3986),
            'jtb' => 'https://www.jtb.co.jp/ovs_htl/search/search_result/?' . http_build_query([
                'freeword' => $hotelName,
                'chckInDt' => $checkIn,
                'chckOutDt' => $checkOut,
                'useRoomCnt' => 1,
                'userCnt' => max(1, $adults) + max(0, $children),
                'roomCnt' => 1,
                'adult1' => max(1, $adults),
                'adult2' => 1,
                'adult3' => 1,
                'adult4' => 1,
                'child1' => max(0, $children),
                'child2' => 0,
                'child3' => 0,
                'child4' => 0,
                'childAge1' => '0,0,0',
                'childAge2' => '0,0,0',
                'childAge3' => '0,0,0',
                'childAge4' => '0,0,0',
                'sugItemTypeCd' => '',
                'sugItemId' => '',
            ], '', '&', PHP_QUERY_RFC3986),
        ];

        return $links;
    }

    private function priceValue(mixed $value): int
    {
        if (is_numeric($value)) return max(0, (int)round((float)$value));
        $digits = preg_replace('/[^0-9.]/', '', (string)$value);
        return $digits !== '' ? max(0, (int)round((float)$digits)) : 0;
    }

    private function rateValue(mixed $rate): int
    {
        if (!is_array($rate)) return $this->priceValue($rate);
        foreach (['extracted_lowest', 'extracted_price', 'extracted_value', 'lowest', 'price', 'value', 'amount'] as $key) {
            if (!array_key_exists($key, $rate)) continue;
            $value = is_array($rate[$key])
                ? $this->rateValue($rate[$key])
                : $this->priceValue($rate[$key]);
            if ($value > 0) return $value;
        }
        foreach ($rate as $value) {
            if (!is_array($value)) continue;
            $price = $this->rateValue($value);
            if ($price > 0) return $price;
        }
        return 0;
    }

    private function httpsUrl(string $url): string
    {
        return filter_var($url, FILTER_VALIDATE_URL) && str_starts_with($url, 'https://') ? $url : '';
    }

    private function time(string $value): string
    {
        return preg_match('/(\d{1,2}:\d{2})$/', $value, $matches) ? $matches[1] : '--:--';
    }

    private function duration(int $minutes): string
    {
        if ($minutes <= 0) return '';
        return (intdiv($minutes, 60) > 0 ? intdiv($minutes, 60) . '時間' : '')
            . ($minutes % 60 > 0 ? $minutes % 60 . '分' : '');
    }
}
