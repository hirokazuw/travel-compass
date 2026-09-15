<?php
declare(strict_types=1);
spl_autoload_register(static function ($class) {
    if (str_starts_with($class, 'App\\')) require dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
});
require __DIR__ . '/view-fixtures.php';
$fixtures = viewFixtures();
$fixtures['flight-domestic'][1]['values']['origin'] = 'TYO';
$fixtures['flight-domestic'][1]['values']['destination'] = 'CTS';
$fixtures['flight-domestic'][1]['cityLabels'] = ['origin' => '東京（TYO）', 'destination' => '札幌（CTS）'];
$hotel = $fixtures['hotel-success'][1];
unset($hotel['activeTab']);
$page = new App\ViewModels\SearchPageViewModel(
    new App\ViewModels\FlightSearchViewData(...$fixtures['flight-domestic'][1]),
    new App\ViewModels\HotelSearchViewData(...$hotel), new App\ViewModels\FerrySearchViewData(),
    'hotel', false, 'browser-token', [
        ['search_type' => 'flight', 'origin' => '東京（TYO）', 'destination' => 'ソウル（SEL）', 'departure_date' => '2026-10-01', 'return_date' => '', 'travelers' => '3'],
        ['search_type' => 'hotel', 'destination' => '札幌', 'check_in' => '2026-10-01', 'check_out' => '2026-10-03', 'adults' => 2, 'children' => 1],
    ], 'Fixture', '1', '1', '1', '1', App\ViewModels\SeoViewData::create([], false)
);
$html = App\Views\SearchView::render($page);
// Keep the real application entry script; remove advertising scripts in this isolated fixture.
$html = preg_replace_callback('~<script\b[^>]*>.*?</script>~s', static fn($m) => str_contains($m[0], 'public/assets/app.js') ? $m[0] : '', $html);
$html = str_replace('</head>', '<script src="/tests/browser-tests.js"></script></head>', $html);
echo $html;
