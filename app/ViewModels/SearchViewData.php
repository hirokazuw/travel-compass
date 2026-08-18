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

    private const RAKUTEN_MESSAGES = [
        'not_configured' => '.envに楽天APIのApplication IDとAccess Keyを設定してください。',
        'empty' => '条件に一致するホテルが見つかりませんでした。',
        'error' => '楽天トラベルのホテル情報を取得できませんでした。時間を置いて再度お試しください。',
    ];

    private const OVERSEAS_HOTEL_MESSAGES = [
        'not_configured' => '現在、海外ホテル情報を取得できません。',
        'empty' => '条件に一致する海外ホテルが見つかりませんでした。',
        'error' => '海外ホテル情報を取得できませんでした。時間を置いて再度お試しください。',
    ];

    public static function flightMessage(string $status): string
    {
        return self::FLIGHT_MESSAGES[$status] ?? '';
    }

    public static function rakutenMessage(string $status): string
    {
        return self::RAKUTEN_MESSAGES[$status] ?? '';
    }

    public static function overseasHotelMessage(string $status): string
    {
        return self::OVERSEAS_HOTEL_MESSAGES[$status] ?? '';
    }
}
