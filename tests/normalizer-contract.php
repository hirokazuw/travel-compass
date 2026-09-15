<?php

$t->test('Captured hotel destinations preserve keyword and place output contracts', function () use ($t): void {
    $path = __DIR__ . '/fixtures/normalizers/destinations-real';
    $input = json_decode(file_get_contents($path . '-input.json'), true, 512, JSON_THROW_ON_ERROR);
    $expected = json_decode(file_get_contents($path . '-expected.json'), true, 512, JSON_THROW_ON_ERROR);
    foreach ([new App\Services\Normalizers\DestinationResponseNormalizer([]), new App\Services\ApifyResponseNormalizer([])] as $normalizer) {
        $t->same($expected, $normalizer->normalizeDestinationSuggestions($input));
        $t->same([], $normalizer->normalizeDestinationSuggestions([]));
        $augmented = $input;
        foreach ($augmented as &$item) $item['unknown_metadata'] = ['nested' => ['ignored']];
        unset($item);
        $t->same($expected, $normalizer->normalizeDestinationSuggestions($augmented), 'Unknown fields ignored');
        $t->same([], $normalizer->normalizeDestinationSuggestions([null, false, 'unexpected', [], ['name' => ' ']]));
        foreach ([[], null, 'unexpected', [null, null]] as $coordinate) {
            $t->same([$expected[0]], $normalizer->normalizeDestinationSuggestions([
                ['keyword' => 'Fixture Restaurant', 'coordinate' => $coordinate, 'address' => null, 'item_id' => null, 'country_code' => null, 'type' => null],
            ]), 'Missing optional values');
        }
        $t->same([], $normalizer->normalizeDestinationSuggestions([['name' => '', 'keyword' => 'Fallback']]), 'Empty name retains existing precedence');
        $t->same([$expected[0]], $normalizer->normalizeDestinationSuggestions([['name' => null, 'keyword' => 'Fixture Restaurant']]), 'Null name uses keyword');
    }
    $limited = new App\Services\Normalizers\DestinationResponseNormalizer(['max_place_suggestions' => 2]);
    $t->same(array_slice($expected, 0, 2), $limited->normalizeDestinationSuggestions($input), 'Limit preserves keyword-first order');
});

$t->test('Captured flight response preserves view contract and avoids flattened duplicates', function () use ($t): void {
    $path = __DIR__ . '/fixtures/normalizers/flights-real';
    $input = json_decode(file_get_contents($path . '-input.json'), true, 512, JSON_THROW_ON_ERROR);
    $expected = json_decode(file_get_contents($path . '-expected.json'), true, 512, JSON_THROW_ON_ERROR);
    $t->same(3, count($expected));
    $t->same($expected, (new App\Services\Normalizers\FlightResponseNormalizer())->normalizeFlights($input));
    $t->same($expected, (new App\Services\ApifyResponseNormalizer([]))->normalizeFlights($input));
});

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
