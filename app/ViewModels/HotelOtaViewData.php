<?php

declare(strict_types=1);

namespace App\ViewModels;

final class HotelOtaViewData
{
    public static function create(string $scope, array $values): ?array
    {
        $guides = [
            'domestic' => [
                'message' => '',
                'label' => '',
                'base_url' => '',
                'params' => [],
                'hidden_booking_links' => ['expedia'],
                'show_guide' => false,
            ],
            'korea' => [
                'message' => '韓国のホテルはNOL WORLDもチェック',
                'label' => 'NOL WORLDで料金を見る',
                'base_url' => 'https://world.nol.com/ja/stay/accommodations',
                'params' => [
                    'keyword' => (string)($values['hotel_destination'] ?? ''),
                    'checkInDate' => (string)($values['check_in_date'] ?? ''),
                    'checkOutDate' => (string)($values['check_out_date'] ?? ''),
                    'adultPax' => (int)($values['hotel_adults'] ?? 1),
                ],
                'hidden_booking_links' => ['expedia', 'hotels'],
                'show_guide' => true,
            ],
            'overseas' => [
                'message' => 'その他海外ホテルはExpediaもチェック',
                'label' => 'Expediaで料金を見る',
                'base_url' => 'https://www.expedia.co.jp/Hotel-Search',
                'params' => [
                    'destination' => (string)($values['hotel_destination'] ?? ''),
                    'startDate' => (string)($values['check_in_date'] ?? ''),
                    'endDate' => (string)($values['check_out_date'] ?? ''),
                    'adults' => max(1, (int)($values['hotel_adults'] ?? 1)),
                    'children' => max(0, (int)($values['hotel_children'] ?? 0)),
                ],
                'hidden_booking_links' => ['expedia', 'hotels'],
                'show_guide' => true,
            ],
        ];
        $guide = $guides[$scope] ?? null;
        if ($guide === null) return null;

        $query = http_build_query($guide['params'], '', '&', PHP_QUERY_RFC3986);

        return [
            'message' => $guide['message'],
            'label' => $guide['label'],
            'url' => $guide['base_url'] . ($query !== '' ? '?' . $query : ''),
            'hidden_booking_links' => $guide['hidden_booking_links'],
            'show_guide' => $guide['show_guide'],
        ];
    }
}
