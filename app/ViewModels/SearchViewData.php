<?php

declare(strict_types=1);

namespace App\ViewModels;

final class SearchViewData
{
    private const FLIGHT_MESSAGES = [
        'not_configured' => '現在、参考価格データを取得できません。',
        'unsupported_route' => '入力された都市の空港コードを確認できませんでした。',
        'empty' => '条件に一致する便が見つかりませんでした。',
        'error' => '参考便を取得できませんでした。',
    ];

    private const HOTEL_MESSAGES = [
        'not_configured' => 'ホテル検索を一時的に利用できません。',
        'empty' => '条件に一致するホテルが見つかりませんでした。',
        'error' => 'ホテル検索を一時的に利用できません。',
    ];

    public static function flightMessage(string $status): string
    {
        return self::FLIGHT_MESSAGES[$status] ?? '';
    }

    public static function hotelMessage(string $status): string
    {
        return self::HOTEL_MESSAGES[$status] ?? '';
    }
}
