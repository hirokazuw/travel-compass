<?php

return [
    'app' => [
        'name' => 'Travel Compass',
        'version' => '1.9.4',
        'timezone' => 'Asia/Tokyo',
    ],

    'seo' => [
        'title' => '航空券・ホテル比較とフェリー航路検索｜Travel Compass',
        'description' => 'Travel Compassは、航空券・ホテルの候補や予約サイトへのリンクをまとめて確認できる旅行検索サービスです。都市名・IATAコードでの航空券検索や、港・地図からのフェリー航路検索に対応しています。',
        'canonical_url' => 'https://hirokazu-watabe.jp/travel-compass/',
        'og_image_url' => 'https://hirokazu-watabe.jp/travel-compass/public/assets/og-travel-compass.png',
        'twitter_card' => 'summary_large_image',
    ],

    'db' => [
        'dsn' => getenv('DB_DSN') ?: '',
        'user' => getenv('DB_USER') ?: '',
        'password' => getenv('DB_PASSWORD') ?: '',
    ],

    'apify' => [
        'token' => getenv('APIFY_TOKEN') ?: '',
        'hotels_url' => 'https://api.apify.com/v2/acts/johnvc~google-hotels-search-scraper/run-sync-get-dataset-items',
        'places_url' => 'https://api.apify.com/v2/acts/xtracto~gmaps-suggestion/run-sync-get-dataset-items',
        'flights_url' => 'https://api.apify.com/v2/acts/johnvc~google-flights-data-scraper-flight-and-price-search/run-sync-get-dataset-items',
        'cache_ttl' => 3600,
        // Per cache directory; expired results are never served, including during API failures.
        'cache_max_bytes' => 104857600, // 100 MiB of JSON data (locks excluded).
        'cache_max_entries' => 1000, // 0 disables new cache writes.
        'hotel_cache_dir' => dirname(__DIR__) . '/storage/cache/apify/hotels',
        'places_cache_dir' => dirname(__DIR__) . '/storage/cache/apify/place-suggestions',
        'places_cache_ttl' => 900,
        'flight_cache_dir' => dirname(__DIR__) . '/storage/cache/apify/flights',
        'timeout' => 120,
        'connect_timeout' => 10,
        'max_pages' => 1,
        'max_place_suggestions' => 8,
    ],

    'rakuten' => [
        'application_id' => getenv('RAKUTEN_APPLICATION_ID') ?: '',
        'access_key' => getenv('RAKUTEN_ACCESS_KEY') ?: '',
        'affiliate_id' => getenv('RAKUTEN_AFFILIATE_ID') ?: '',
        'referer' => getenv('RAKUTEN_REFERER') ?: '',
    ],

];
