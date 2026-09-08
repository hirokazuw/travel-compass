<?php

$t->test('Normalizer fixtures preserve complete output keys, values and types', function () use ($t): void {
    $normalizer = new App\Services\ApifyResponseNormalizer(['max_place_suggestions' => 2]);
    $domains = [
        'hotels' => new App\Services\Normalizers\HotelResponseNormalizer(),
        'flights' => new App\Services\Normalizers\FlightResponseNormalizer(),
        'destinations' => new App\Services\Normalizers\DestinationResponseNormalizer(['max_place_suggestions' => 2]),
    ];
    foreach (['hotels' => 'normalizeHotels', 'flights' => 'normalizeFlights', 'destinations' => 'normalizeDestinationSuggestions'] as $name => $method) {
        $path = __DIR__ . '/fixtures/normalizers/' . $name;
        $input = json_decode(file_get_contents($path . '-input.json'), true, 512, JSON_THROW_ON_ERROR);
        $expected = json_decode(file_get_contents($path . '-expected.json'), true, 512, JSON_THROW_ON_ERROR);
        $t->same($expected, $normalizer->$method($input), $name . ' output contract');
        $t->same($expected, $domains[$name]->$method($input), $name . ' domain contract');
        $t->same([], $normalizer->$method([]), $name . ' empty input');
        $t->same([], $domains[$name]->$method([]), $name . ' empty domain input');
    }
});
