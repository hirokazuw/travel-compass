<?php

$t->test('Ferry master preserves legacy port projection including all prefectures and fallbacks', function () use ($t): void {
    $master = App\Models\FerryMapMaster::load();
    $cases = json_decode(file_get_contents(__DIR__ . '/fixtures/ferry-map-ports.json'), true, 512, JSON_THROW_ON_ERROR);
    foreach ($cases as $case) {
        $t->same($case['expected'], $master->project($case['name'], $case['prefecture']), $case['name'] . ':' . $case['prefecture']);
    }
    $t->same('苫小牧東', $master->match('苫小牧東港')['name']);
    $t->same('大阪港国際', $master->match('大阪港国際ターミナル')['name']);
});

$t->test('Ferry master validates coordinates, aliases, region references and priorities', function () use ($t, $root): void {
    $data = json_decode(file_get_contents($root . '/database/ferry-map.json'), true, 512, JSON_THROW_ON_ERROR);
    $t->same(47, count(array_merge(...array_column(array_values($data['regions']), 'prefectures'))));
    $mutations = [
        static function (&$d) { $d['ports'][0]['position'] = [101, 0]; },
        static function (&$d) { $d['ports'][0]['position'] = ['75', 0]; },
        static function (&$d) { $d['regions']['kanto']['center'] = [1]; },
        static function (&$d) { $d['ports'][0]['region'] = 'missing'; },
        static function (&$d) { $d['ports'][1]['aliases'] = $d['ports'][0]['aliases']; },
        static function (&$d) { $d['ports'][1]['priority'] = $d['ports'][0]['priority']; },
        static function (&$d) { $d['ports'][1]['name'] = $d['ports'][0]['name']; },
        static function (&$d) { $d['ports'][0]['aliases'] = ['']; },
        static function (&$d) { $d['regions']['kanto']['prefectures'][] = '北海道'; },
        static function (&$d) { unset($d['regions']['overseas']); },
    ];
    foreach ($mutations as $mutate) {
        $invalid = $data;
        $mutate($invalid);
        $rejected = false;
        try { new App\Models\FerryMapMaster($invalid); } catch (InvalidArgumentException $e) { $rejected = true; }
        $t->true($rejected, 'Invalid master must fail before projection');
    }
    $reversed = $data;
    $reversed['ports'] = array_reverse($reversed['ports']);
    $t->same('苫小牧東', (new App\Models\FerryMapMaster($reversed))->match('苫小牧東港')['name']);
});

$t->test('Ferry map service uses injected master while preserving route presentation and endpoint keys', function () use ($t, $root, $routes, $ferrySearch): void {
    $data = json_decode(file_get_contents($root . '/database/ferry-map.json'), true, 512, JSON_THROW_ON_ERROR);
    foreach ($data['ports'] as &$port) if ($port['name'] === '東京') $port['position'] = [12, 34];
    unset($port);
    $projection = (new App\Services\FerryMapService($routes, $ferrySearch, new App\Models\FerryMapMaster($data)))->data();
    $t->same(['routes'], array_keys($projection));
    $route = $projection['routes'][0];
    $t->same(['name' => '東京港', 'region' => 'kanto', 'x' => 12, 'y' => 34], $route['departure']);
    $t->same(['name' => '徳島港', 'region' => 'shikoku', 'x' => 39, 'y' => 76], $route['arrival']);
    $t->same('東京港 → 徳島港', $route['label']);
    $t->same(2, $route['id']);
    $t->same(1, $route['company_id']);
    $t->same('https://route.example/', $route['destination_url']);
});
