<?php

return [
    'app' => [
        'name' => 'Travel Compass',
        'version' => '1.7.1',
        'timezone' => 'Asia/Tokyo',
    ],

    'seo' => [
        'title' => 'Travel Compass（トラベルコンパス）｜航空券・ホテル比較',
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


    'apify' => [
        'token' => getenv('APIFY_TOKEN') ?: '',
        'hotels_url' => 'https://api.apify.com/v2/acts/johnvc~google-hotels-search-scraper/run-sync-get-dataset-items',
        'places_url' => 'https://api.apify.com/v2/acts/xtracto~gmaps-suggestion/run-sync-get-dataset-items',
        'flights_url' => 'https://api.apify.com/v2/acts/johnvc~google-flights-data-scraper-flight-and-price-search/run-sync-get-dataset-items',
        'cache_ttl' => 3600,
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

    'affiliate' => [
        'hotel_url'  => 'https://www.trip.com/t/d1vKvIKRwV2',
        'flight_url' => 'https://www.trip.com/t/E631zVIRwV2',
    ],

];
