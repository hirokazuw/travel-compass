<?php
declare(strict_types=1);

require_once __DIR__ . '/view-fixtures.php';
spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) require dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
});
set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$snapshots = [];
foreach (viewFixtures() as $name => [$isPost, $state]) {
    $_SERVER['REQUEST_METHOD'] = $isPost ? 'POST' : 'GET';
    $_SESSION['csrf'] = 'fixture-csrf';
    // Keep the recorded display version stable; release bumps are not DOM changes.
    $builder = new App\ViewModels\SearchPageBuilder(new App\Models\SearchHistory(new PDO('sqlite::memory:')), ['app' => ['version' => '1.9.0']], 'fixture');
    $activeTab = $state['activeTab'] ?? 'flight';
    unset($state['activeTab']);
    $searchData = match ($activeTab) {
        'hotel' => new App\ViewModels\HotelSearchViewData(...$state),
        'ferry' => new App\ViewModels\FerrySearchViewData(...$state),
        default => new App\ViewModels\FlightSearchViewData(...$state),
    };
    $data = $builder->build($searchData, $isPost, 'fixture-csrf');
    ob_start();
    (new App\Http\SearchHtmlResponse($data))->send();
    $html = ob_get_clean();
    // P1-7 intentionally changes only the application script loading mode.
    if (!str_contains($html, '<script type="module" src="public/assets/app.js?v=')) {
        throw new RuntimeException('Application entry must load as an ES module');
    }
    $html = str_replace('<script type="module" src="public/assets/app.js?v=', '<script src="public/assets/app.js?v=', $html);
    $html = preg_replace('/(public\/assets\/[^"?]+\?v=)[^"<]+/', '$1VERSION', $html);
    $html = preg_replace('/© \d{4}/u', '© YEAR', $html);
    $html = str_replace("\r\n", "\n", $html);
    $snapshots[$name] = hash('sha256', $html);
}
$file = __DIR__ . '/fixtures/search-html.json';
if (in_array('--record', $argv, true)) {
    if (!is_dir(dirname($file))) mkdir(dirname($file));
    file_put_contents($file, json_encode($snapshots, JSON_PRETTY_PRINT) . "\n");
} else {
    $expected = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    if (array_keys($expected) !== array_keys($snapshots)) throw new RuntimeException('HTML contract cases changed');
    foreach ($snapshots as $name => $hash) {
        if (($expected[$name] ?? null) !== $hash) throw new RuntimeException("HTML contract changed: $name");
    }
    echo count($snapshots) . " HTML contracts passed\n";
}
