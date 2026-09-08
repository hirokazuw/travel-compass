<?php

$t->test('Ferry fare confirmation shows full date and omits missing or invalid dates', function () use ($t, $routes, $ferrySearch): void {
    $raw = $routes->findActiveByIdAndCompany(2, 1);
    foreach (['2026-08-22' => '料金確認日：2026/08/22', '' => '', '2026-02-30' => ''] as $date => $expected) {
        $raw['fare_updated_at'] = $date;
        $raw['fare_from'] = null;
        $t->same($expected, $ferrySearch->presentRoute($raw)['fare_updated']);
    }
    $raw['fare_updated_at'] = null;
    $t->same('', $ferrySearch->presentRoute($raw)['fare_updated']);
});

$t->test('Inactive ferry routes disappear from search, options and map and can be reactivated', function () use ($t, $db, $routes, $ferrySearch): void {
    try {
        $db->exec('UPDATE ferry_routes SET active = 0 WHERE id = 2');
        $t->same(null, $ferrySearch->findRoute(1, 2));
        $t->same([], $routes->findActiveOptionsByCompany(1));
        $t->same([], $routes->findAllActiveForMap());
        $t->same(1, (int)$db->query('SELECT COUNT(*) FROM ferry_routes WHERE id = 2')->fetchColumn());
    } finally {
        $db->exec('UPDATE ferry_routes SET active = 1 WHERE id = 2');
    }
    $t->true($ferrySearch->findRoute(1, 2) !== null, 'Reactivated route must return');
});
