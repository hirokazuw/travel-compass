<?php

return [
    'app' => [
        'name' => 'Travel Compass',
        'version' => '1.2.2',
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
        'alliance_id' => 'YOUR_ALLIANCE_ID',
        'sid' => 'YOUR_SID',
    ],

    'serpapi' => [
        'base_url' => 'https://serpapi.com/search',
        'api_key' => getenv('SERPAPI_API_KEY') ?: '',
        'cache_ttl' => 3600,
        'hotel_cache_ttl' => 21600,
        'cache_dir' => dirname(__DIR__) . '/storage/cache/serpapi',
        'monthly_limit' => 225,
        'usage_file' => dirname(__DIR__) . '/storage/cache/serpapi-usage.json',
        'hotel_min_rakuten_results' => 3,
    ],

    'rakuten' => [
        'application_id' => getenv('RAKUTEN_APPLICATION_ID') ?: '',
        'access_key' => getenv('RAKUTEN_ACCESS_KEY') ?: '',
        'affiliate_id' => getenv('RAKUTEN_AFFILIATE_ID') ?: '',
        'referer' => getenv('RAKUTEN_REFERER') ?: '',
    ],

    'affiliate' => [
        'hotel_url' => 'https://www.trip.com/hotels/',
        'flight_url' => 'https://www.trip.com/flights/',
    ],

    'widgets' => [
        'trip_hotel_id' => 'YOUR_TRIP_WIDGET_ID',
        'expedia_program' => 'jp-expedia',
        'expedia_camref' => 'YOUR_EXPEDIA_CAMREF',
    ],
];
