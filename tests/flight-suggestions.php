<?php

$t->test('IATA suggestions cap Japanese English and code matches at eight', function () use ($t, $cities, $db): void {
    $db->beginTransaction();
    try {
        $insert = $db->prepare('INSERT INTO iata_cities (id, city, country, iata, code_type, airports, aliases) VALUES (?, ?, ?, ?, ?, ?, ?)');
        for ($i = 0; $i < 10; $i++) {
            $insert->execute([100 + $i, 'LimitCity' . $i, 'JP', 'QZ' . chr(65 + $i), 'airport', '[]', '["上限都市"]']);
        }
        foreach (['上限都市', 'LimitCity', 'QZ'] as $query) {
            $items = $cities->suggest($query);
            $t->same(8, count($items), $query . ' limit');
            $t->same(array_map(static fn($i) => 'QZ' . chr(65 + $i), range(0, 7)), array_column($items, 'iata'), $query . ' ordered subset');
        }
    } finally {
        $db->rollBack();
    }
});

$t->test('IATA suggestions search Japanese, English and codes with bounded escaped queries', function () use ($t, $cities, $db): void {
    $db->exec("INSERT INTO iata_cities VALUES (2, 'Seoul', 'KR', 'SEL', 'metropolitan', '[\"GMP\",\"ICN\"]', '[\"ソウル\"]')");
    foreach (['東京', 'Tok', 'tyo'] as $query) $t->same(['label' => '東京（TYO）', 'iata' => 'TYO'], $cities->suggest($query)[0]);
    foreach (['ソウル', 'Seo', 'SEL'] as $query) $t->same('SEL', $cities->suggest($query)[0]['iata']);
    foreach (['東', '%_', "' OR 1=1 --", 'unknown'] as $query) $t->same([], $cities->suggest($query));
    $action = new App\Actions\FlightCitySuggestionsAction($cities);
    $t->same(403, $action->handle(['query' => '東京'], 'token')->status);
    $t->same(422, $action->handle(['query' => '東', 'csrf' => 'token'], 'token')->status);
    $t->same(200, $action->handle(['query' => '東京', 'csrf' => 'token'], 'token')->status);
});

$t->test('Selected IATA codes use existing search conversion and history columns', function () use ($t, $db, $flight, $cities, $csrf): void {
    $db->exec('CREATE TABLE flight_searches (id INTEGER PRIMARY KEY, visitor_id TEXT, origin TEXT, destination TEXT, departure_date TEXT, return_date TEXT, travelers INTEGER, created_at TEXT)');
    $input = ['csrf' => $csrf, 'origin' => '東京（TYO）', 'origin_iata' => 'TYO',
        'destination' => 'ソウル（SEL）', 'destination_iata' => 'SEL', 'departure_date' => '2026-10-01', 'return_date' => '2026-10-03', 'travelers' => '2'];
    $state = $flight->handle($input, $csrf);
    $t->same([], $state->errors);
    $t->same('TYO', $state->values['origin']);
    $t->same('SEL', $state->values['destination']);
    $t->same('東京（TYO）', $state->cityLabels['origin']);
    $t->same('HND,NRT', $cities->flightSearchCode($state->values['origin']));
    $t->same('GMP,ICN', $cities->flightSearchCode($state->values['destination']));
    $saved = $db->query('SELECT origin, destination, return_date, travelers FROM flight_searches')->fetch();
    $t->same('東京（TYO）', $saved['origin']);
    $t->same('ソウル（SEL）', $saved['destination']);
    $t->same('2026-10-03', $saved['return_date']);
    $replay = $input;
    unset($replay['origin_iata'], $replay['destination_iata']);
    $replay['origin'] = $saved['origin'];
    $replay['destination'] = $saved['destination'];
    $replayed = $flight->handle($replay, $csrf);
    $t->same('TYO', $replayed->values['origin']);
    $t->same('SEL', $replayed->values['destination']);
    $t->same('ソウル（SEL）', $replayed->cityLabels['destination']);
    $input['origin'] = 'incorrect label';
    $t->same('東京（TYO）', $flight->handle($input, $csrf)->cityLabels['origin']);
    $input['origin_iata'] = 'ZZZ';
    $t->same('ZZZ', $flight->handle($input, $csrf)->cityLabels['origin']);
    $input['return_date'] = '';
    $t->same([], App\Requests\FlightSearchRequest::fromPost($input, $csrf)->errors);
    $input['origin_iata'] = 'invalid';
    $t->true(App\Requests\FlightSearchRequest::fromPost($input, $csrf)->errors !== [], 'Invalid code rejected');
});
