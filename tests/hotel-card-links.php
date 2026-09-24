<?php

declare(strict_types=1);

$t->test('Hotel card link failures preserve cards and continue with later cards', function () use ($t): void {
    $service = new App\Services\HotelSearchService(
        new App\Services\ApifyHotelSearch(new App\Services\ApifyClient([]),
            new App\Services\ApiCache('', 0), new App\Services\Normalizers\HotelResponseNormalizer()),
        new App\Services\HotelUrlBuilder()
    );
    $hotels = [
        2 => ['name' => 'First Hotel', 'address' => 'Paris'],
        5 => ['name' => 'Broken Hotel', 'address' => new stdClass(), 'booking_links' => ['stale' => 'https://example.test/']],
        8 => ['name' => 'Last Hotel', 'address' => 'Paris'],
    ];
    $result = $service->addHotelCardLinks($hotels, 'Paris', '2026-10-01', '2026-10-03', 2, 1, false);
    $t->same([2, 5, 8], array_keys($result));
    $t->same([], $result[5]['booking_links']);
    $t->same($hotels[5]['address'], $result[5]['address']);
    $t->same('Broken Hotel', $result[5]['name']);
    foreach ([2, 8] as $index) {
        $t->same(['expedia', 'hotels'], array_keys($result[$index]['booking_links']));
        $t->contains('adults=2&children=1', $result[$index]['booking_links']['expedia'], 'Guest counts preserved');
    }
    $t->same(['stale' => 'https://example.test/'], $hotels[5]['booking_links'], 'Input remains unchanged');
});

$t->test('Domestic card links isolate matched-name failures and retain matching restrictions', function () use ($t): void {
    $service = new App\Services\HotelSearchService(
        new App\Services\ApifyHotelSearch(new App\Services\ApifyClient([]),
            new App\Services\ApiCache('', 0), new App\Services\Normalizers\HotelResponseNormalizer()),
        new App\Services\HotelUrlBuilder()
    );
    $hotels = [['name' => 'Broken Match'], ['name' => 'Unmatched'], ['name' => 'Original Name']];
    $matches = [0 => ['name' => new stdClass()], 2 => ['name' => '楽天ホテル']];
    $result = $service->addHotelCardLinks($hotels, '東京', '2026-10-01', '2026-10-03', 1, 0, true, $matches);
    $t->same([], $result[0]['booking_links']);
    $t->same([], $result[1]['booking_links']);
    $t->same('Original Name', $result[2]['name']);
    $t->same(['jalan', 'yahoo', 'ikyu', 'expedia'], array_keys($result[2]['booking_links']));
    $t->contains('kwd=' . rawurlencode('楽天ホテル'), $result[2]['booking_links']['yahoo'], 'Matched name used only for links');
    $restricted = $service->addHotelCardLinks($hotels, '東京', '2026-10-01', '2026-10-03', 1, 0, true, []);
    $t->same([[], [], []], array_column($restricted, 'booking_links'));
});

$t->test('Hotel action retains whole-search error status without external requests', function () use ($t, $db, $csrf): void {
    // A configured token with no endpoint fails locally before any HTTP call.
    $service = new App\Services\HotelSearchService(
        new App\Services\ApifyHotelSearch(new App\Services\ApifyClient(['token' => 'fixture-token']),
            new App\Services\ApiCache('', 0), new App\Services\Normalizers\HotelResponseNormalizer()),
        new App\Services\HotelUrlBuilder()
    );
    $action = new App\Actions\HotelSearchAction(new App\Models\SearchHistory($db), $service,
        new App\Services\RakutenTravelService([]), 'fixture-visitor');
    $state = $action->handle(['csrf' => $csrf, 'hotel_destination' => 'Paris', 'hotel_scope' => 'overseas',
        'check_in_date' => '2026-10-01', 'check_out_date' => '2026-10-03'], $csrf);
    $t->same('error', $state->hotelStatus);
    $t->same([], $state->hotels);
    $t->same('overseas', $state->activeHotelScope);
    $t->same('Paris', $state->hotelValues['hotel_destination']);
});
