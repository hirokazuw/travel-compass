<?php

declare(strict_types=1);

namespace App\Services;

final class HotelBookingLinks
{
    private const INTERNATIONAL_SITES = ['booking', 'expedia', 'ena'];
    private const DOMESTIC_SITES = ['rakuten', 'jalan', 'yahoo'];

    public function sites(
        string $destination,
        string $checkIn,
        string $checkOut,
        int $adults,
        int $children,
        bool $domestic,
        string $hotelName = ''
    ): array {
        $siteKeys = self::INTERNATIONAL_SITES;
        if ($domestic) {
            $siteKeys = array_merge($siteKeys, self::DOMESTIC_SITES);
        }

        $all = $this->all($destination, $checkIn, $checkOut, $adults, $children, $hotelName);
        return array_intersect_key($all, array_flip($siteKeys));
    }

    private function all(
        string $destination,
        string $checkIn,
        string $checkOut,
        int $adults,
        int $children,
        string $hotelName
    ): array {
        $hotelQuery = trim($hotelName . ' ' . $destination);

        return [
            'booking' => [
                'name' => 'Booking.com',
                'aliases' => ['booking.com', 'booking'],
                'url' => $this->bookingHotelUrl($hotelQuery, $checkIn, $checkOut, $adults, $children),
            ],
            'expedia' => [
                'name' => 'Expedia',
                'aliases' => ['expedia'],
                'url' => $this->expediaHotelUrl($destination, $checkIn, $checkOut, $adults, $children),
            ],
            'ena' => [
                'name' => 'ena',
                'aliases' => ['ena', 'イーナ'],
                'url' => $this->enaHotelUrl($hotelQuery, $checkIn, $checkOut, $adults, $children),
            ],
            'rakuten' => [
                'name' => '楽天トラベル',
                'aliases' => ['楽天トラベル', 'rakuten travel', 'rakuten'],
                'url' => $this->rakutenHotelUrl($hotelQuery, $checkIn, $checkOut, $adults, $children),
            ],
            'jalan' => [
                'name' => 'じゃらん',
                'aliases' => ['じゃらん', 'jalan'],
                'url' => $this->jalanHotelUrl($hotelQuery, $checkIn, $checkOut, $adults, $children),
            ],
            'yahoo' => [
                'name' => 'Yahoo!トラベル',
                'aliases' => ['yahoo!トラベル', 'yahoo travel', 'yahoo'],
                'url' => $this->yahooHotelUrl($hotelQuery, $checkIn, $checkOut, $adults, $children),
            ],
        ];
    }

    private function bookingHotelUrl(string $query, string $in, string $out, int $adults, int $children): string
    {
        return 'https://www.booking.com/searchresults.ja.html?' . $this->query(['ss' => $query, 'checkin' => $in, 'checkout' => $out, 'group_adults' => $adults, 'group_children' => $children, 'no_rooms' => 1]);
    }

    private function expediaHotelUrl(
        string $destination,
        string $in,
        string $out,
        int $adults,
        int $children
    ): string
    {
        $expediaChildren = min($children, 6);
        $params = [
            'CityName' => $destination,
            'InDate' => $in,
            'OutDate' => $out,
            'SortBy' => 0,
            'NumRooms' => 1,
            'NumAdult-Room1' => $adults,
            'NumChild-Room1' => $expediaChildren,
        ];

        return 'https://www.expedia.co.jp/go/hotel/search/Destination/'
            . rawurlencode($in)
            . '/'
            . rawurlencode($out)
            . '?'
            . $this->query($params);
    }

    private function enaHotelUrl(string $query, string $in, string $out, int $adults, int $children): string
    {
        return 'https://www.ena.travel/hotel/search?' . $this->query(['destination' => $query, 'checkIn' => $in, 'checkOut' => $out, 'adult' => $adults, 'child' => $children]);
    }

    private function rakutenHotelUrl(string $query, string $in, string $out, int $adults, int $children): string
    {
        return 'https://search.travel.rakuten.co.jp/ds/hotellist/Japan?' . $this->query(['f_query' => $query, 'f_cd' => '03', 'f_dai' => 'japan', 'f_chkin' => $in, 'f_chkout' => $out, 'f_adult_su' => $adults, 'f_child' => $children, 'f_heya_su' => 1]);
    }

    private function jalanHotelUrl(string $query, string $in, string $out, int $adults, int $children): string
    {
        [$year, $month, $day] = explode('-', $in);
        return 'https://www.jalan.net/uw/uwp2011/uww2011init.do?' . $this->query(['keyword' => $query, 'stayYear' => $year, 'stayMonth' => (int)$month, 'stayDay' => (int)$day, 'stayCount' => max(1, (new \DateTimeImmutable($in))->diff(new \DateTimeImmutable($out))->days), 'adultNum' => $adults, 'childNum' => $children, 'roomCount' => 1]);
    }

    private function yahooHotelUrl(string $query, string $in, string $out, int $adults, int $children): string
    {
        return 'https://travel.yahoo.co.jp/search/?' . $this->query(['keyword' => $query, 'checkin' => $in, 'checkout' => $out, 'adult' => $adults, 'child' => $children, 'room' => 1]);
    }

    private function query(array $params): string
    {
        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
