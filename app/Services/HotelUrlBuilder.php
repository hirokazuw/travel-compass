<?php
declare(strict_types=1);
namespace App\Services;
final class HotelUrlBuilder
{
    public function buildHotelCardLinks(
        array $hotel,
        string $destination,
        string $checkIn,
        string $checkOut,
        int $adults,
        int $children,
        bool $domestic
    ): array {
        $name = trim((string)($hotel['name'] ?? ''));
        if ($name === '') return [];

        $searchParts = array_values(array_unique(array_filter([
            $name,
            trim($destination),
            trim((string)($hotel['address'] ?? '')),
        ])));
        $search = implode(' ', $searchParts);
        if ($search === '') return [];

        $params = [
            'destination' => $search,
            'startDate' => $checkIn,
            'endDate' => $checkOut,
            'adults' => max(1, $adults),
            'children' => max(0, $children),
        ];

        if (!$domestic) return [
            'expedia' => 'https://www.expedia.co.jp/Hotel-Search?' . $this->query($params),
            'hotels' => 'https://jp.hotels.com/Hotel-Search?' . $this->query($params),
        ];

        $arrival = new \DateTimeImmutable($checkIn);
        $departure = new \DateTimeImmutable($checkOut);
        $stayCount = max(1, (int)$arrival->diff($departure)->days);
        $commonDomestic = [
            'kwd' => $name,
            'cid' => $arrival->format('Y-m-d'),
            'lc' => $stayCount,
            'ppc' => max(1, $adults),
            'rc' => 1,
        ];
        $jalanParams = [
            'keyword' => mb_convert_encoding($name, 'SJIS-win', 'UTF-8'),
            'stayYear' => $arrival->format('Y'),
            'stayMonth' => $arrival->format('n'),
            'stayDay' => $arrival->format('j'),
            'stayCount' => $stayCount,
            'roomCount' => 1,
            'adultNum' => max(1, $adults),
            'dateUndecided' => 0,
        ];
        if ($children >= 1 && $children <= 5) $jalanParams['child1Num'] = $children;

        return [
            'jalan' => 'https://www.jalan.net/uw/uwp2011/uww2011init.do?'
                . http_build_query($jalanParams, '', '&', PHP_QUERY_RFC3986),
            'yahoo' => 'https://travel.yahoo.co.jp/search?' . $this->query($commonDomestic),
            'ikyu' => 'https://www.ikyu.com/search?' . $this->query($commonDomestic),
            'expedia' => 'https://www.expedia.co.jp/Hotel-Search?' . $this->query([
                'destination' => $name,
                'd1' => $checkIn,
                'startDate' => $checkIn,
                'd2' => $checkOut,
                'endDate' => $checkOut,
                'adults' => max(1, $adults),
                'children' => max(0, $children),
                'rooms' => 1,
                'sort' => 'RECOMMENDED',
            ]),
        ];
    }

    private function query(array $params): string { return http_build_query($params,'','&',PHP_QUERY_RFC3986); }
}
