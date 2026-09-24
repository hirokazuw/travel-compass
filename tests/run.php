<?php

declare(strict_types=1);

$root = dirname(__DIR__);
spl_autoload_register(static function (string $class) use ($root): void {
    if (!str_starts_with($class, 'App\\')) return;
    $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) require $file;
});

final class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;

    public function test(string $name, callable $test): void
    {
        try {
            $test();
            $this->passed++;
            echo "PASS {$name}\n";
        } catch (Throwable $e) {
            $this->failed++;
            fwrite(STDERR, "FAIL {$name}: {$e->getMessage()}\n");
        }
    }

    public function same(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException(($message !== '' ? $message . ': ' : '')
                . 'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
        }
    }

    public function true(bool $condition, string $message): void
    {
        if (!$condition) throw new RuntimeException($message);
    }

    public function contains(string $needle, string $haystack, string $message): void
    {
        $this->true(str_contains($haystack, $needle), $message);
    }

    public function finish(): never
    {
        echo "\n{$this->passed} passed, {$this->failed} failed\n";
        exit($this->failed === 0 ? 0 : 1);
    }
}

function memoryDatabase(): PDO
{
    $db = new PDO('sqlite::memory:');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->sqliteCreateFunction('JSON_SEARCH', static function (?string $json, string $mode, string $needle): ?string {
        $values = json_decode((string)$json, true);
        return is_array($values) && in_array($needle, $values, true) ? '$[0]' : null;
    });
    return $db;
}

$t = new TestRunner();
$csrf = 'test-csrf-token';

$t->test('Flight request accepts valid round trip', function () use ($t, $csrf): void {
    $request = App\Requests\FlightSearchRequest::fromPost([
        'csrf' => $csrf, 'origin' => '東京', 'destination' => '大阪',
        'departure_date' => '2026-10-01', 'return_date' => '2026-10-03', 'travelers' => '2',
    ], $csrf);
    $t->same([], $request->errors);
    $t->same('2', $request->values['travelers']);
});

$t->test('Flight request rejects invalid date order and traveler count', function () use ($t, $csrf): void {
    $request = App\Requests\FlightSearchRequest::fromPost([
        'csrf' => $csrf, 'origin' => '東京', 'destination' => '大阪',
        'departure_date' => '2026-10-03', 'return_date' => '2026-10-01', 'travelers' => '10',
    ], $csrf);
    $t->true(count($request->errors) === 2, 'Expected return-date and traveler errors');
});

$t->test('Hotel request validates checkout and scope', function () use ($t, $csrf): void {
    $request = App\Requests\HotelSearchRequest::fromPost([
        'csrf' => $csrf, 'hotel_destination' => '札幌',
        'check_in_date' => '2026-10-01', 'check_out_date' => '2026-10-01',
        'hotel_adults' => '1', 'hotel_children' => '0', 'hotel_scope' => 'invalid',
    ], $csrf);
    $t->same('domestic', $request->scope);
    $t->true(count($request->errors) === 1, 'Expected checkout error');
});

$t->test('Destination request enforces CSRF and query length', function () use ($t, $csrf): void {
    $forbidden = App\Requests\DestinationSuggestionRequest::fromPost(['csrf' => 'wrong', 'query' => '東京'], $csrf);
    $short = App\Requests\DestinationSuggestionRequest::fromPost(['csrf' => $csrf, 'query' => '東'], $csrf);
    $t->same(403, $forbidden->status);
    $t->same(422, $short->status);
});

$t->test('Ferry request validates selected identifiers', function () use ($t, $csrf): void {
    $request = App\Requests\FerrySearchRequest::fromPost([
        'csrf' => $csrf, 'ferry_company_name' => '会社',
        'ferry_company_id' => '1', 'ferry_route_id' => '2', 'ferry_search_mode' => 'map',
    ], $csrf);
    $t->same([], $request->errors);
    $t->same('map', $request->values['ferry_search_mode']);
});

$normalizer = new App\Services\ApifyResponseNormalizer(['max_place_suggestions' => 1]);

$t->test('Hotel normalizer keeps safe URLs and normalized rates', function () use ($t, $normalizer): void {
    $hotels = $normalizer->normalizeHotels([['properties' => [[
        'name' => ' Test Hotel ', 'link' => 'javascript:alert(1)',
        'images' => ['http://invalid.example/a.jpg', ['original_image' => 'https://img.example/a.jpg']],
        'gps_coordinates' => ['lat' => '35.1', 'lng' => '139.2'],
        'rate_per_night' => ['extracted_lowest' => '￥12,345'],
    ]]]]);
    $t->same('Test Hotel', $hotels[0]['name']);
    $t->same('', $hotels[0]['official_url']);
    $t->same(['https://img.example/a.jpg'], $hotels[0]['image_urls']);
    $t->same(12345, $hotels[0]['price_per_night']);
});

$t->test('Destination normalizer applies configured limit', function () use ($t, $normalizer): void {
    $items = $normalizer->normalizeDestinationSuggestions([
        ['name' => '東京', 'coordinate' => [35.6, 139.7]],
        ['name' => '大阪', 'coordinate' => [34.6, 135.5]],
    ]);
    $t->same(1, count($items));
    $t->same('東京', $items[0]['name']);
});

$t->test('Flight normalizer sorts offers and derives carrier code', function () use ($t, $normalizer): void {
    $offers = $normalizer->normalizeFlights([['best_flights' => [
        ['price' => '20,000円', 'flights' => [['airline' => 'JAL', 'flight_number' => 'JL101']]],
        ['price' => 10000, 'flights' => [['airline' => 'ANA', 'flight_number' => 'NH100']]],
    ]]]);
    $t->same('NH', $offers[0]['carrier_code']);
    $t->same('10,000', $offers[0]['price']);
});

$t->test('Hotel URL builder preserves dates and guests', function () use ($t): void {
    $links = (new App\Services\HotelUrlBuilder())->buildHotelCardLinks(
        ['name' => 'テストホテル', 'address' => '東京都'], '東京',
        '2026-10-01', '2026-10-03', 2, 1, false
    );
    $t->contains('startDate=2026-10-01', $links['expedia'], 'Missing check-in date');
    $t->contains('adults=2', $links['hotels'], 'Missing adults');
    $t->contains('children=1', $links['hotels'], 'Missing children');
});

$db = memoryDatabase();
$db->exec('CREATE TABLE iata_cities (id INTEGER PRIMARY KEY, city TEXT, country TEXT, iata TEXT, code_type TEXT, airports TEXT, aliases TEXT)');
$db->exec("INSERT INTO iata_cities VALUES (1, 'Tokyo', 'JP', 'TYO', 'metropolitan', '[\"HND\",\"NRT\"]', '[\"東京\"]')");
$cities = new App\Models\FlightCity($db);
(new ReflectionProperty($cities, 'countryColumn'))->setValue($cities, 'country');

$t->test('FlightCity resolves alias, metropolitan airports and domestic flag', function () use ($t, $cities): void {
    $t->same('TYO', $cities->find('東京')['iata']);
    $t->same('HND,NRT', $cities->flightSearchCode('東京'));
    $t->same('TKY', $cities->airtripCode('東京'));
    $t->true($cities->isDomestic('東京'), 'Tokyo must be domestic');
});

$t->test('Flight URL builder returns only displayed providers', function () use ($t, $cities): void {
    $links = (new App\Services\FlightUrlBuilder($cities))->buildFlightLinks(
        '東京', '東京', '2026-10-01', '2026-10-03', 2, true
    );
    $t->same(['maps', 'expedia', 'agoda', 'airtrip', 'travelist', 'realticket'], array_keys($links));
    $t->contains('F1Year=2026', $links['airtrip'], 'Missing departure year');
});

$db->exec('CREATE TABLE airlines (iata_code TEXT, icao_code TEXT, name TEXT, callsign TEXT, alliance TEXT, ffp_name TEXT, ffp_currency TEXT, credits_json TEXT, official_url TEXT, active INTEGER)');
$db->exec("INSERT INTO airlines VALUES ('JL', 'JAL', 'Japan Airlines', NULL, 'ow', 'JAL Mileage Bank', 'miles', NULL, 'https://www.jal.co.jp/', 1)");
$aggregator = new App\Services\FlightOfferAggregator(new App\Models\Airline($db));

$t->test('Flight aggregator groups airline and keeps lowest price', function () use ($t, $aggregator): void {
    $groups = $aggregator->byAirline([
        ['carrier_code' => 'JL', 'carrier_name' => 'JAL', 'price' => '20,000', 'currency' => 'JPY', 'stops' => 0],
        ['carrier_code' => 'JL', 'carrier_name' => 'JAL', 'price' => '15,000', 'currency' => 'JPY', 'stops' => 1],
    ]);
    $t->same(1, count($groups));
    $t->same('15,000', $groups[0]['price']);
    $t->same(2, $groups[0]['flight_count']);
    $t->same('oneworld', $groups[0]['alliance']);
});

$db->exec('CREATE TABLE ferry_companies (id INTEGER PRIMARY KEY, name TEXT, name_ja TEXT, slug TEXT, logo_url TEXT, official_url TEXT, reservation_url TEXT, active INTEGER)');
$db->exec('CREATE TABLE ferry_routes (id INTEGER PRIMARY KEY, company_id INTEGER, route_name TEXT, departure_port TEXT, departure_prefecture TEXT, arrival_port TEXT, arrival_prefecture TEXT, duration_minutes INTEGER, fare_from INTEGER, fare_currency TEXT, fare_updated_at TEXT, vehicle_available INTEGER, overnight INTEGER, reservation_url TEXT, active INTEGER)');
$db->exec("INSERT INTO ferry_companies VALUES (1, 'Test Ferry', 'テストフェリー', 'test', NULL, 'https://company.example/', 'https://booking.example/', 1)");
$db->exec("INSERT INTO ferry_routes VALUES (2, 1, '東京～徳島', '東京港', '東京都', '徳島港', '徳島県', 600, 12000, 'JPY', '2026-08-01', 1, 1, 'https://route.example/', 1)");
$companies = new App\Models\FerryCompany($db);
$routes = new App\Models\FerryRoute($db);
$ferrySearch = new App\Services\FerrySearchService($routes);

$t->test('Ferry models constrain active company and route ownership', function () use ($t, $companies, $routes): void {
    $t->same('テストフェリー', $companies->suggestActive('テスト')[0]['name']);
    $t->same(1, count($routes->findActiveOptionsByCompany(1)));
    $t->same(null, $routes->findActiveByIdAndCompany(2, 999));
});

$t->test('Ferry service presents route and preferred URL', function () use ($t, $ferrySearch): void {
    $route = $ferrySearch->findRoute(1, 2);
    $t->same('約10時間', $route['duration']);
    $t->same('12,000', $route['fare_from']);
    $t->same('https://route.example/', $route['destination_url']);
});

$t->test('Ferry map projects registered route', function () use ($t, $routes, $ferrySearch): void {
    $data = (new App\Services\FerryMapService($routes, $ferrySearch))->data();
    $t->same(1, count($data['routes']));
    $t->same('kanto', $data['routes'][0]['departure']['region']);
    $t->same('shikoku', $data['routes'][0]['arrival']['region']);
});

$t->test('Hotel matching requires a unique safe match', function () use ($t): void {
    $service = (new ReflectionClass(App\Services\HotelSearchService::class))->newInstanceWithoutConstructor();
    $matches = $service->matchRakutenHotels(
        [['name' => 'ホテルテスト東京']],
        [['name' => 'ホテルテスト東京', 'url' => 'https://travel.example/hotel']]
    );
    $t->same('https://travel.example/hotel', $matches[0]['url']);
});

$t->test('Baseline contains all current tables and no search history rows', function () use ($t, $root): void {
    $schema = file_get_contents($root . '/database/schema.sql');
    foreach (['flight_searches', 'hotel_searches', 'iata_cities', 'airlines', 'ferry_companies', 'ferry_routes'] as $table) {
        $t->true(preg_match('/CREATE TABLE(?: IF NOT EXISTS)?\s+`?' . preg_quote($table, '/') . '`?/i', $schema) === 1, "Missing {$table}");
    }
    $t->true(!str_contains($schema, 'INSERT INTO `flight_searches`'), 'Flight history data must not be seeded');
    $t->true(!str_contains($schema, 'INSERT INTO `hotel_searches`'), 'Hotel history data must not be seeded');
});

require __DIR__ . '/request-validation.php';
require __DIR__ . '/json-response.php';
require __DIR__ . '/search-actions.php';
require __DIR__ . '/request-log.php';
require __DIR__ . '/hotel-card-links.php';
require __DIR__ . '/api-cache.php';
require __DIR__ . '/controller-factory.php';
require __DIR__ . '/flight-url-validation.php';
require __DIR__ . '/normalizer-contract.php';
require __DIR__ . '/ferry-map-master.php';
require __DIR__ . '/script-assets.php';
require __DIR__ . '/ferry-freshness.php';
require __DIR__ . '/flight-suggestions.php';
$t->finish();
