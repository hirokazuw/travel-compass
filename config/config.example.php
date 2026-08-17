<?php

return [
    'app' => [
        'name' => 'Travel Compass',
        'version' => '1.1.1',
        'timezone' => 'Asia/Tokyo',
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
