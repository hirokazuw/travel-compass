<?php

// Reuse the isolated city and ferry fixtures from run.php.
$client = new App\Services\ApifyClient([]);
$cache = new App\Services\ApiCache(sys_get_temp_dir() . '/travel-compass-action-tests', 0);
$history = new App\Models\SearchHistory($db);
$page = new App\ViewModels\SearchPageBuilder($history, [], 'test-visitor');
$flight = new App\Actions\FlightSearchAction($history, $cities,
    new App\Services\FlightSearchService($cities, new App\Services\ApifyFlightSearch($client, $cache, new App\Services\Normalizers\FlightResponseNormalizer()), $aggregator),
    new App\Services\FlightUrlBuilder($cities), 'test-visitor');
$hotel = new App\Actions\HotelSearchAction($history,
    new App\Services\HotelSearchService(new App\Services\ApifyHotelSearch($client, $cache, new App\Services\Normalizers\HotelResponseNormalizer()), new App\Services\HotelUrlBuilder()),
    new App\Services\RakutenTravelService([]), 'test-visitor');
$ferry = new App\Actions\FerrySearchAction($companies, $routes, $ferrySearch, new App\Services\FerryMapService($routes, $ferrySearch));

$t->test('Invalid actions preserve tab, values, scope and historical status', function () use ($t, $page, $flight, $hotel, $ferry, $csrf): void {
    foreach ([[$flight, 'flight', 'errors', 'flightOffersStatus', 'idle'],
        [$hotel, 'hotel', 'hotelErrors', 'hotelStatus', 'idle'],
        [$ferry, 'ferry', 'ferryErrors', 'ferryStatus', 'invalid']] as [$action, $tab, $errorKey, $statusKey, $status]) {
        $state = $action->handle(['hotel_scope' => 'overseas', 'origin' => '<Tokyo>'], $csrf);
        $t->same($tab, $page->build($state, true, $csrf)->activeTab);
        $t->same($status, $state->$statusKey);
        $t->same('送信内容を確認できませんでした。', $state->$errorKey[0]);
        if ($tab === 'hotel') $t->same('overseas', $state->activeHotelScope);
        if ($tab === 'flight') $t->same('<Tokyo>', $state->values['origin']);
    }
});

$t->test('Valid flight and hotel survive history failure and retain unavailable status', function () use ($t, $flight, $hotel, $csrf): void {
    $state = $flight->handle(['csrf' => $csrf, 'origin' => '東京', 'destination' => '東京', 'departure_date' => '2026-10-01'], $csrf);
    $t->same([], $state->errors);
    $t->same('not_configured', $state->flightOffersStatus);
    $t->same('domestic', $state->activeFlightScope);
    $t->true(isset($state->result['expedia']), 'Flight links remain available');
    $state = $hotel->handle(['csrf' => $csrf, 'hotel_destination' => 'Paris', 'hotel_scope' => 'overseas',
        'check_in_date' => '2026-10-01', 'check_out_date' => '2026-10-03'], $csrf);
    $t->same([], $state->hotelErrors);
    $t->same('not_configured', $state->hotelStatus);
    $t->same('overseas', $state->activeHotelScope);
});

$t->test('Ferry action preserves success and invalid route status', function () use ($t, $ferry, $page, $csrf): void {
    $input = ['csrf' => $csrf, 'ferry_company_id' => '1', 'ferry_route_id' => '2'];
    $state = $ferry->handle($input, $csrf);
    $t->same('success', $state->ferryStatus);
    $t->same('ferry', $page->build($state, true, $csrf)->activeTab);
    $t->same(1, count($state->ferryRoutes));
    $state = $ferry->handle(array_replace($input, ['ferry_route_id' => '999']), $csrf);
    $t->same('invalid', $state->ferryStatus);
    $t->same(1, count($state->ferryErrors));
});

$t->test('Page assembly tolerates history failure and derives SEO and messages', function () use ($t, $page): void {
    $data = $page->build(new App\ViewModels\HotelSearchViewData(hotelStatus: 'not_configured'), true, 'test-token');
    $t->same([], $data->recent);
    $t->same('ホテル検索を一時的に利用できません。', $data->hotel->message());
    $t->same('noindex,follow', str_replace(' ', '', $data->seo['robots']));
    $t->true($data->cssVersion !== '', 'Asset version available');
});

$t->test('Ferry JSON actions return successful payloads and missing-company status without output', function () use ($t, $ferry, $csrf): void {
    $response = $ferry->companySuggestions(['csrf' => $csrf, 'query' => 'テスト'], $csrf);
    $t->same(200, $response->status);
    $t->same('テストフェリー', json_decode($response->body, true)['suggestions'][0]['name']);
    $response = $ferry->companyRoutes(['csrf' => $csrf, 'company_id' => '999'], $csrf);
    $t->same(404, $response->status);
    $t->same(['routes' => []], json_decode($response->body, true));
    $response = $ferry->companyRoutes(['csrf' => $csrf, 'company_id' => '1'], $csrf);
    $t->same(200, $response->status);
    $t->same(1, count(json_decode($response->body, true)['routes']));
    $response = $ferry->mapData(['csrf' => $csrf], $csrf);
    $t->same(200, $response->status);
    $t->same(1, count(json_decode($response->body, true)['routes']));
});

$t->test('View models reject misspelled fields and mutation including array elements', function () use ($t, $page): void {
    $data = $page->build(null, false, 'model-token');
    $cases = [
        static fn() => new App\ViewModels\FlightSearchViewData(flightOfferStatus: 'error'),
        static fn() => new App\ViewModels\HotelSearchViewData(hotelStats: 'error'),
        static fn() => new App\ViewModels\FerrySearchViewData(ferryStats: 'error'),
        static fn() => $data->hotel->hotelStats,
        static function () use ($data): void { $data->activeTab = 'hotel'; },
        static function () use ($data): void { $data->flight->values['origin'] = 'changed'; },
        static function () use ($data): void { $data->hotel->hotelErrors[] = 'changed'; },
        static function () use ($data): void { $data->ferry->extraField = 'changed'; },
        static fn() => $page->build(['hotelStatus' => 'error'], true, 'token'),
    ];
    foreach ($cases as $case) {
        $rejected = false;
        try { $case(); } catch (Error $e) { $rejected = true; }
        $t->true($rejected, 'Invalid field, state type or mutation must fail');
    }
});

$t->test('Partial rendering depends only on its explicit page model', function () use ($t, $page): void {
    $data = $page->build(new App\ViewModels\HotelSearchViewData(hotelErrors: ['<Error>']), true, 'model-token');
    $oldServer = $_SERVER;
    $oldSession = $_SESSION ?? [];
    try {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SESSION['csrf'] = 'global-token';
        $html = App\Views\SearchView::render($data, 'partials/search-panel');
        $t->contains('id="hotel-tab" role="tab" aria-selected="true"', $html, 'Model controls active tab');
        $t->contains('value="model-token"', $html, 'Model supplies CSRF');
        $t->contains('&lt;Error&gt;', $html, 'Errors stay escaped');
        $t->true(!str_contains($html, 'global-token'), 'Session does not leak into template');
        $html = App\Views\SearchView::render($data, 'partials/flight-search-form');
        $t->true(!str_contains($html, 'value="roundtrip" checked'), 'Model controls POST trip state');
    } finally {
        $_SERVER = $oldServer;
        $_SESSION = $oldSession;
    }
});
