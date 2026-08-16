<?php

return [
    'app' => [
        'name' => 'Travel Compass',
        'timezone' => 'Asia/Tokyo',
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
];
