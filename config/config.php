<?php

return [
    'app' => [
        'name' => 'Travel Compass',
        'version' => '1.2.0',
        'timezone' => 'Asia/Tokyo',
    ],

    'seo' => [
        'title' => 'Travel Compass｜航空券・ホテルをまとめて比較',
        'description' => 'Travel Compassは、航空券とホテルを一つの画面から検索し、複数の旅行予約サイトを比較できる旅行検索サービスです。',
        'canonical_url' => 'https://hirokazu-watabe.jp/travel-compass/',
        'og_image_url' => 'https://hirokazu-watabe.jp/travel-compass/public/assets/og-travel-compass.png',
        'twitter_card' => 'summary_large_image',
    ],

    'db' => [
        'dsn' => getenv('DB_DSN') ?: '',
        'user' => getenv('DB_USER') ?: '',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],

    'trip' => [
        'flight_search_url' => 'https://jp.trip.com/flights/showfarefirst',
        'alliance_id' => '6026343',
        'sid' => '169869421',
    ],


    'serpapi' => [
        'base_url' => 'https://serpapi.com/search',
        'api_key' => getenv('SERPAPI_API_KEY') ?: '',
        'cache_ttl' => 3600,
        'cache_dir' => dirname(__DIR__) . '/storage/cache/serpapi',
    ],

    'rakuten' => [
        'application_id' => getenv('RAKUTEN_APPLICATION_ID') ?: '',
        'access_key' => getenv('RAKUTEN_ACCESS_KEY') ?: '',
        'affiliate_id' => getenv('RAKUTEN_AFFILIATE_ID') ?: '',
        'referer' => getenv('RAKUTEN_REFERER') ?: '',
    ],

    'affiliate' => [
        'hotel_url'  => 'https://www.trip.com/t/d1vKvIKRwV2',
        'flight_url' => 'https://www.trip.com/t/E631zVIRwV2',
    ],

    'widgets' => [
        'trip_hotel_id' => 'S19265451',
        'expedia_program' => 'jp-expedia',
        'expedia_camref' => '1011l5PxNZ',
    ],
];
