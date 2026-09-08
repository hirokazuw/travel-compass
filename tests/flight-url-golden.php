<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) require dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
});
set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->sqliteCreateFunction('JSON_SEARCH', static function ($json, $mode, $needle) {
    return in_array($needle, json_decode($json, true), true) ? '$[0]' : null;
});
$db->exec('CREATE TABLE iata_cities (id INTEGER PRIMARY KEY, city TEXT, country TEXT, iata TEXT, code_type TEXT, airports TEXT, aliases TEXT)');
$insert = $db->prepare('INSERT INTO iata_cities VALUES (?, ?, ?, ?, ?, ?, ?)');
foreach ([
    [1, 'Tokyo', 'JP', 'TYO', 'metropolitan', '["HND","NRT"]', '["東京"]'],
    [2, 'Osaka', 'JP', 'OSA', 'metropolitan', '["ITM","KIX"]', '["大阪"]'],
    [3, 'Seoul', 'KR', 'SEL', 'metropolitan', '["ICN","GMP"]', '["ソウル"]'],
    [4, 'Haneda', 'JP', 'HND', 'airport', '[]', '["羽田"]'],
    [5, 'New York & Queens', 'US', 'JFK', 'airport', '[]', '["NY & Queens"]'],
] as $row) $insert->execute($row);
$cities = new App\Models\FlightCity($db);
(new ReflectionProperty($cities, 'countryColumn'))->setValue($cities, 'country');
$builder = new App\Services\FlightUrlBuilder($cities);
$cases = [
    'domestic-roundtrip' => ['東京', '大阪', '2026-01-02', '2026-01-09', 2, true],
    'domestic-oneway' => ['羽田', '大阪', '2026-12-31', '', 1, true],
    'overseas-roundtrip' => ['東京', 'ソウル', '2026-12-31', '2027-01-02', 9, false],
    'overseas-oneway' => ['ソウル', '羽田', '2026-02-03', '', 3, false],
    'encoding-airport' => ['羽田', 'NY & Queens', '2026-03-04', '2026-03-05', 2, false],
    'unknown-origin-domestic' => ['Unknown', '東京', '2026-01-02', '', 1, true],
    'unknown-destination-overseas' => ['東京', '<Unknown & city>', '2026-01-02', '', 1, false],
    'alias-domestic' => ['Tokyo', 'Osaka', '2026-01-02', '2026-01-09', 2, true],
    'zero-travelers-domestic' => ['東京', '大阪', '2026-01-02', '', 0, true],
    'zero-travelers-overseas' => ['東京', 'ソウル', '2026-01-02', '', 0, false],
];
$actual = [];
$uuidKeys = [];
foreach ($cases as $name => $args) {
    $links = $builder->buildFlightLinks(...$args);
    if (isset($links['skygate']) && str_contains($links['skygate'], 'serviceWorkerKey=')) {
        parse_str(parse_url($links['skygate'], PHP_URL_QUERY), $query);
        $uuid = $query['serviceWorkerKey'];
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D', $uuid)) {
            throw new RuntimeException('Invalid UUID v4');
        }
        if (in_array($uuid, $uuidKeys, true)) throw new RuntimeException('Reused UUID');
        $uuidKeys[] = $uuid;
        $links['skygate'] = str_replace($uuid, 'UUID-V4', $links['skygate']);
    }
    $actual[$name] = $links;
}
$file = __DIR__ . '/fixtures/flight-urls.json';
if (in_array('--record', $argv, true)) {
    file_put_contents($file, json_encode($actual, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
} else {
    $expected = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    if ($expected !== $actual) throw new RuntimeException('Flight URL golden output changed');
    echo count($cases) . " flight URL golden cases passed\n";
}
