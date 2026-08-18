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
    $db = App\Core\Database::connect($config['db']);
    $flightCity = new App\Models\FlightCity($db);
    $serpApiConfig = $config['serpapi'] ?? [];
    $serpApiCache = new App\Services\SerpApiCache(
        (string)($serpApiConfig['cache_dir'] ?? $root . '/storage/cache/serpapi'),
        max(0, (int)($serpApiConfig['cache_ttl'] ?? 3600)),
        max(0, (int)($serpApiConfig['monthly_limit'] ?? 225)),
        (string)($serpApiConfig['usage_file'] ?? $root . '/storage/cache/serpapi-usage.json')
    );
    (new App\Controllers\SearchController(
        new App\Models\TravelSearch($db),
        $flightCity,
        new App\Services\FlightSearchService(
            $flightCity,
            new App\Services\SerpApiFlightSearch($serpApiConfig, $serpApiCache)
        ),
        new App\Services\RakutenTravelService($config['rakuten'] ?? []),
        new App\Services\TravelLinkBuilder(
            $flightCity,
            $config
        ),
        $config
    ))->index();

} catch (Throwable $e) {

    error_log((string)$e);

    http_response_code(500);

    echo '設定またはデータベースをご確認ください。';
}
