<?php

declare(strict_types=1);

session_start();

$root = dirname(__DIR__);
$file = $root . '/config/config.php';

spl_autoload_register(function ($class) use ($root) {
    if (str_starts_with($class, 'App\\')) {
        $f = $root
            . '/app/'
            . str_replace('\\', '/', substr($class, 4))
            . '.php';

        if (is_file($f)) {
            require $f;
        }
    }
});

App\Core\Env::load($root . '/.env');

if (!is_file($file)) {
    http_response_code(503);
    exit('config.example.php を config.php にコピーしてください。');
}

$config = require $file;

date_default_timezone_set(
    $config['app']['timezone'] ?? 'Asia/Tokyo'
);

try {
    $visitorId = App\Core\VisitorIdCookie::resolve();
    $db = App\Core\Database::connect($config['db']);
    $flightCity = new App\Models\FlightCity($db);
    $apifyConfig = $config['apify'] ?? [];
    $apifyTtl = max(0, (int)($apifyConfig['cache_ttl'] ?? 3600));
    $apifyClient = new App\Services\ApifyClient($apifyConfig);
    $apifyNormalizer = new App\Services\ApifyResponseNormalizer($apifyConfig);
    $apifyFlight = new App\Services\ApifyFlightSearch(
        $apifyClient,
        new App\Services\ApiCache(
            (string)($apifyConfig['flight_cache_dir'] ?? $root . '/storage/cache/apify/flights'),
            $apifyTtl
        ),
        $apifyNormalizer
    );
    $apifyHotel = new App\Services\ApifyHotelSearch(
        $apifyClient,
        new App\Services\ApiCache(
            (string)($apifyConfig['hotel_cache_dir'] ?? $root . '/storage/cache/apify/hotels'),
            $apifyTtl
        ),
        $apifyNormalizer
    );
    $apifyDestination = new App\Services\ApifyDestinationSearch(
        $apifyClient,
        new App\Services\ApiCache(
            (string)($apifyConfig['places_cache_dir'] ?? $root . '/storage/cache/apify/place-suggestions'),
            max(0, (int)($apifyConfig['places_cache_ttl'] ?? 900))
        ),
        $apifyNormalizer
    );
    $ferryRoute = new App\Models\FerryRoute($db);
    (new App\Controllers\SearchController(
        new App\Models\SearchHistory($db),
        $flightCity,
        new App\Services\FlightSearchService(
            $flightCity,
            $apifyFlight,
            new App\Services\FlightOfferAggregator(new App\Models\Airline($db))
        ),
        new App\Services\RakutenTravelService($config['rakuten'] ?? []),
        new App\Services\HotelSearchService($apifyHotel, new App\Services\HotelUrlBuilder()),
        $apifyDestination,
        new App\Services\FlightUrlBuilder(
            $flightCity,
            $config
        ),
        new App\Controllers\FerryController(
            new App\Models\FerryCompany($db),
            $ferryRoute,
            new App\Services\FerrySearchService($ferryRoute),
            new App\Services\FerryMapService(
                $ferryRoute,
                new App\Services\FerrySearchService($ferryRoute)
            )
        ),
        $config,
        $visitorId
    ))->index();

} catch (Throwable $e) {

    error_log((string)$e);

    http_response_code(500);

    echo '設定またはデータベースをご確認ください。';
}
