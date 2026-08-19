<?php

declare(strict_types=1);

namespace App\Services;

final class HotelSearchService
{
    public function __construct(private ApifyHotelSearch $apify, private HotelUrlBuilder $urls) {}

    public function isConfigured(): bool
    {
        return $this->apify->isConfigured();
    }

    public function search(
        string $destination,
        string $checkIn,
        string $checkOut,
        int $adults,
        int $children
    ): array {
        return $this->apify->search($destination, $checkIn, $checkOut, $adults, $children);
    }

    public function addHotelCardLinks(
        array $hotels,
        string $destination,
        string $checkIn,
        string $checkOut,
        int $adults,
        int $children,
        bool $domestic,
        ?array $allowedIndexes = null
    ): array {
        foreach ($hotels as $index => $hotel) {
            if (!is_array($hotel)) continue;
            if ($allowedIndexes !== null && !array_key_exists($index, $allowedIndexes)) {
                $hotels[$index]['booking_links'] = [];
                continue;
            }
            try {
                $hotelForLinks = $hotel;
                if (is_array($allowedIndexes[$index] ?? null)) {
                    $rakutenName = trim((string)($allowedIndexes[$index]['name'] ?? ''));
                    if ($rakutenName !== '') $hotelForLinks['name'] = $rakutenName;
                }
                $hotels[$index]['booking_links'] = $this->urls->buildHotelCardLinks(
                    $hotelForLinks, $destination, $checkIn, $checkOut, $adults, $children, $domestic
                );
            } catch (\Throwable $e) {
                error_log('Hotel card link generation: ' . $e->getMessage());
                $hotels[$index]['booking_links'] = [];
            }
        }
        return $hotels;
    }

    /** @return array<int, array{name: string, url: string}> Apify result index => matched Rakuten hotel */
    public function matchRakutenHotels(array $hotels, array $rakutenLinks): array
    {
        $candidates = [];
        foreach ($rakutenLinks as $link) {
            if (!is_array($link)) continue;
            $hotelName = trim((string)($link['name'] ?? ''));
            $name = $this->matchName($hotelName);
            $url = (string)($link['url'] ?? '');
            if ($name !== '' && filter_var($url, FILTER_VALIDATE_URL) && str_starts_with($url, 'https://')) {
                $candidates[] = ['match_name' => $name, 'name' => $hotelName, 'url' => $url];
            }
        }

        $matches = [];
        foreach ($hotels as $index => $hotel) {
            if (!is_array($hotel)) continue;
            $name = $this->matchName((string)($hotel['name'] ?? ''));
            if ($name === '') continue;

            $exact = array_values(array_filter($candidates, static fn(array $candidate): bool => $candidate['match_name'] === $name));
            if (count($exact) === 1) {
                $matches[(int)$index] = ['name' => $exact[0]['name'], 'url' => $exact[0]['url']];
                continue;
            }

            // Accept a partial match only when it is sufficiently specific and unique.
            $partial = array_values(array_filter($candidates, static function (array $candidate) use ($name): bool {
                $candidateName = $candidate['match_name'];
                $shorter = mb_strlen($name) <= mb_strlen($candidateName) ? $name : $candidateName;
                return mb_strlen($shorter) >= 8
                    && (str_contains($name, $candidateName) || str_contains($candidateName, $name));
            }));
            if (count($partial) === 1) {
                $matches[(int)$index] = ['name' => $partial[0]['name'], 'url' => $partial[0]['url']];
            }
        }
        return $matches;
    }

    private function matchName(string $name): string
    {
        $name = mb_strtolower(mb_convert_kana(trim($name), 'asKV'));
        return preg_replace('/[\s　・･\-_（）()［］\[\]【】「」『』.,，．]+/u', '', $name) ?? '';
    }
}
