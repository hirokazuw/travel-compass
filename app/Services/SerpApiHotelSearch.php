<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class SerpApiHotelSearch
{
    public function __construct(
        private array $config,
        private HotelBookingLinks $bookingLinks,
        private SerpApiCache $cache
    ) {}

    public function isConfigured(): bool
    {
        return ($this->config['api_key'] ?? '') !== '';
    }

    public function search(
        string $destination,
        string $checkIn,
        string $checkOut,
        int $adults,
        int $children,
        bool $domestic
    ): array {
        if (!$this->isConfigured()) {
            return [];
        }

        $query = [
            'engine' => 'google_hotels',
            'api_key' => $this->config['api_key'],
            'q' => $destination . ' ホテル',
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'adults' => $adults,
            'children' => $children,
            'currency' => 'JPY',
            'hl' => 'ja',
            'gl' => 'jp',
        ];
        $response = $this->cache->remember($query, fn(): array => $this->request($query));

        $properties = is_array($response['properties'] ?? null) ? $response['properties'] : [];
        foreach (array_slice($properties, 0, 5, true) as $index => $property) {
            $token = trim((string)($property['property_token'] ?? ''));
            if ($token === '') continue;
            try {
                $details = $this->propertyDetails($token, $destination, $checkIn, $checkOut, $adults, $children);
                $detailPrices = array_merge(
                    is_array($details['featured_prices'] ?? null) ? $details['featured_prices'] : [],
                    is_array($details['prices'] ?? null) ? $details['prices'] : []
                );
                if ($detailPrices !== []) $properties[$index]['prices'] = $detailPrices;
            } catch (\Throwable $e) {
                error_log('SerpApi hotel property details: ' . $e->getMessage());
            }
        }

        return $this->normalize(
            $properties,
            $destination,
            $checkIn,
            $checkOut,
            $adults,
            $children,
            $domestic
        );
    }

    public function detailOffers(
        string $propertyToken,
        string $destination,
        string $checkIn,
        string $checkOut,
        int $adults,
        int $children,
        bool $domestic
    ): array {
        $details = $this->propertyDetails($propertyToken, $destination, $checkIn, $checkOut, $adults, $children);
        $prices = array_merge(
            is_array($details['featured_prices'] ?? null) ? $details['featured_prices'] : [],
            is_array($details['prices'] ?? null) ? $details['prices'] : []
        );
        $sites = $this->bookingLinks->sites($destination, $checkIn, $checkOut, $adults, $children, $domestic);
        $nights = max(1, (new \DateTimeImmutable($checkIn))->diff(new \DateTimeImmutable($checkOut))->days);
        return $this->offers(['prices' => $prices], $sites, $nights);
    }

    private function propertyDetails(
        string $propertyToken,
        string $destination,
        string $checkIn,
        string $checkOut,
        int $adults,
        int $children
    ): array {
        $query = [
            'engine' => 'google_hotels',
            'api_key' => $this->config['api_key'],
            'q' => $destination . ' ホテル',
            'property_token' => $propertyToken,
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'adults' => $adults,
            'children' => $children,
            'currency' => 'JPY',
            'hl' => 'ja',
            'gl' => 'jp',
        ];

        return $this->cache->remember($query, fn(): array => $this->request($query));
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
            throw new RuntimeException('SerpApi hotel request failed: HTTP ' . $status . ' ' . $error);
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('SerpApi returned invalid hotel JSON.');
        }
        if (isset($decoded['error'])) {
            throw new RuntimeException('SerpApi: ' . (string)$decoded['error']);
        }

        return $decoded;
    }

    private function normalize(
        array $properties,
        string $destination,
        string $checkIn,
        string $checkOut,
        int $adults,
        int $children,
        bool $domestic
    ): array {
        $nights = max(1, (new \DateTimeImmutable($checkIn))->diff(new \DateTimeImmutable($checkOut))->days);
        $siteDefinitions = $this->bookingLinks->sites($destination, $checkIn, $checkOut, $adults, $children, $domestic);
        $hotels = [];

        foreach ($properties as $property) {
            if (!is_array($property) || trim((string)($property['name'] ?? '')) === '') {
                continue;
            }

            $offers = $this->offers($property, $siteDefinitions, $nights);

            $images = is_array($property['images'] ?? null) ? $property['images'] : [];
            $firstImage = is_array($images[0] ?? null) ? $images[0] : [];
            $amenities = array_values(array_slice(array_filter(array_map('strval', (array)($property['amenities'] ?? []))), 0, 6));
            $tags = $this->tags($property, $amenities);
            $nearby = is_array($property['nearby_places'][0] ?? null) ? $property['nearby_places'][0] : [];

            $hotels[] = [
                'name' => trim((string)$property['name']),
                'property_token' => trim((string)($property['property_token'] ?? '')),
                'image' => $this->httpsUrl((string)($firstImage['original_image'] ?? $firstImage['thumbnail'] ?? '')),
                'image_count' => count($images),
                'type' => trim((string)($property['type'] ?? 'ホテル')),
                'stars' => max(0, min(5, (int)($property['hotel_class'] ?? 0))),
                'area' => trim((string)($property['neighborhood'] ?? $property['location'] ?? $destination)),
                'access' => trim((string)($nearby['name'] ?? $property['description'] ?? '')),
                'rating' => isset($property['overall_rating']) ? number_format((float)$property['overall_rating'], 1) : '',
                'rating_10' => isset($property['overall_rating']) ? number_format(min(10, (float)$property['overall_rating'] * 2), 1) : '',
                'rating_label' => $this->ratingLabel(isset($property['overall_rating']) ? (float)$property['overall_rating'] * 2 : 0),
                'reviews' => max(0, (int)($property['reviews'] ?? 0)),
                'amenities' => $amenities,
                'tags' => $tags,
                'offers' => $offers,
                'lowest' => $offers[0] ?? null,
                'search_sites' => $this->bookingLinks->sites(
                    $destination,
                    $checkIn,
                    $checkOut,
                    $adults,
                    $children,
                    $domestic,
                    trim((string)$property['name'])
                ),
                'nights' => $nights,
            ];
        }

        return $hotels;
    }

    private function offers(array $property, array $sites, int $nights): array
    {
        $rawPrices = is_array($property['prices'] ?? null) ? $property['prices'] : [];
        $offers = [];

        foreach ($rawPrices as $price) {
            if (!is_array($price)) continue;
            $source = trim((string)($price['source'] ?? ''));
            $siteKey = $this->siteKey($source, $sites);
            if ($siteKey === null || isset($offers[$siteKey])) continue;

            $nightly = $this->money($price['rate_per_night']['extracted_lowest'] ?? $price['extracted_rate_per_night'] ?? null);
            $total = $this->money($price['total_rate']['extracted_lowest'] ?? $price['extracted_total_rate'] ?? null);
            if ($nightly <= 0 && $total > 0) $nightly = (int)round($total / $nights);
            if ($nightly <= 0) continue;
            if ($total <= 0) $total = $nightly * $nights;

            $offers[$siteKey] = [
                'key' => $siteKey,
                'site' => $sites[$siteKey]['name'],
                'nightly' => $nightly,
                'total' => $total,
                'free_cancellation' => (bool)($price['free_cancellation'] ?? $price['cancellation']['free_cancellation'] ?? false),
                'url' => $this->httpsUrl((string)($price['link'] ?? '')) ?: $sites[$siteKey]['url'],
            ];
        }

        uasort($offers, static fn(array $a, array $b): int => $a['nightly'] <=> $b['nightly']);
        return array_values($offers);
    }

    private function siteKey(string $source, array $sites): ?string
    {
        $source = mb_strtolower($source);
        foreach ($sites as $key => $site) {
            foreach ($site['aliases'] as $alias) {
                if (str_contains($source, mb_strtolower($alias))) return $key;
            }
        }
        return null;
    }

    private function money(mixed $value): int
    {
        if (is_numeric($value)) return max(0, (int)round((float)$value));
        $digits = preg_replace('/[^0-9.]/', '', (string)$value);
        return $digits === '' ? 0 : max(0, (int)round((float)$digits));
    }

    private function httpsUrl(string $url): string
    {
        return filter_var($url, FILTER_VALIDATE_URL) && str_starts_with($url, 'https://') ? $url : '';
    }

    private function tags(array $property, array $amenities): array
    {
        $haystack = mb_strtolower(implode(' ', array_merge($amenities, [
            (string)($property['description'] ?? ''),
            (string)($property['deal'] ?? ''),
        ])));
        $rules = [
            'キャンセル無料' => ['キャンセル無料', 'free cancellation'],
            '朝食付き' => ['朝食付き', 'breakfast included'],
            '現地払い' => ['現地払い', 'pay at property'],
            '返金可能' => ['返金可能', 'refundable'],
            'Wi-Fi無料' => ['wi-fi 無料', '無料 wi-fi', 'free wi-fi', 'free wifi'],
        ];
        $tags = [];
        foreach ($rules as $label => $needles) {
            foreach ($needles as $needle) {
                if (str_contains($haystack, $needle)) { $tags[] = $label; break; }
            }
        }
        return array_slice($tags, 0, 5);
    }

    private function ratingLabel(float $rating): string
    {
        return match (true) {
            $rating >= 9 => '最高',
            $rating >= 8 => '大満足',
            $rating >= 7 => '満足',
            $rating > 0 => '良い',
            default => '',
        };
    }
}
