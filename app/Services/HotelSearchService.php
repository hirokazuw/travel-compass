<?php

declare(strict_types=1);

namespace App\Services;

final class HotelSearchService
{
    public function __construct(private ApifyService $apify) {}

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
        return $this->apify->searchHotels($destination, $checkIn, $checkOut, $adults, $children);
    }

    public function bookingLinks(
        string $destination,
        string $checkIn,
        string $checkOut,
        int $adults,
        int $children,
        bool $domestic
    ): array {
        $common = [
            'destination' => $destination,
            'startDate' => $checkIn,
            'endDate' => $checkOut,
            'adults' => max(1, $adults),
            'children' => max(0, $children),
        ];

        if (!$domestic) {
            return [
                'expedia' => 'https://www.expedia.co.jp/Hotel-Search?' . http_build_query($common, '', '&', PHP_QUERY_RFC3986),
                'hotels' => 'https://jp.hotels.com/Hotel-Search?' . http_build_query($common, '', '&', PHP_QUERY_RFC3986),
                'jtb' => 'https://www.jtb.co.jp/kokunai-hotel/list/?' . http_build_query(['q' => $destination], '', '&', PHP_QUERY_RFC3986),
            ];
        }

        $keyword = rawurlencode($destination);
        return [
            'rakuten' => 'https://travel.rakuten.co.jp/yado/keyword/' . $keyword . '.html',
            'jalan' => 'https://www.jalan.net/uw/uwp2011/uww2011init.do?' . http_build_query(['keyword' => $destination], '', '&', PHP_QUERY_RFC3986),
            'yahoo' => 'https://travel.yahoo.co.jp/search/?' . http_build_query(['keyword' => $destination], '', '&', PHP_QUERY_RFC3986),
            'ikyu' => 'https://www.ikyu.com/search/?' . http_build_query(['keyword' => $destination], '', '&', PHP_QUERY_RFC3986),
            'expedia' => 'https://www.expedia.co.jp/Hotel-Search?' . http_build_query($common, '', '&', PHP_QUERY_RFC3986),
        ];
    }

    /** @return array<int, string> Apify result index => matched Rakuten affiliate URL */
    public function matchRakutenLinks(array $hotels, array $rakutenLinks): array
    {
        $candidates = [];
        foreach ($rakutenLinks as $link) {
            if (!is_array($link)) continue;
            $name = $this->matchName((string)($link['name'] ?? ''));
            $url = (string)($link['url'] ?? '');
            if ($name !== '' && filter_var($url, FILTER_VALIDATE_URL) && str_starts_with($url, 'https://')) {
                $candidates[] = ['name' => $name, 'url' => $url];
            }
        }

        $matches = [];
        foreach ($hotels as $index => $hotel) {
            if (!is_array($hotel)) continue;
            $name = $this->matchName((string)($hotel['name'] ?? ''));
            if ($name === '') continue;

            $exact = array_values(array_filter($candidates, static fn(array $candidate): bool => $candidate['name'] === $name));
            if (count($exact) === 1) {
                $matches[(int)$index] = $exact[0]['url'];
                continue;
            }

            // Accept a partial match only when it is sufficiently specific and unique.
            $partial = array_values(array_filter($candidates, static function (array $candidate) use ($name): bool {
                $shorter = mb_strlen($name) <= mb_strlen($candidate['name']) ? $name : $candidate['name'];
                return mb_strlen($shorter) >= 8
                    && (str_contains($name, $candidate['name']) || str_contains($candidate['name'], $name));
            }));
            if (count($partial) === 1) $matches[(int)$index] = $partial[0]['url'];
        }
        return $matches;
    }

    private function matchName(string $name): string
    {
        $name = mb_strtolower(mb_convert_kana(trim($name), 'asKV'));
        return preg_replace('/[\s　・･\-_（）()［］\[\]【】「」『』.,，．]+/u', '', $name) ?? '';
    }
}
