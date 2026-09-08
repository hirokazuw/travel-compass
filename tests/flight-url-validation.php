<?php

$t->test('Flight URL validation rejects unsafe URLs and preserves valid query bytes', function () use ($t): void {
    foreach (['javascript:alert(1)', 'http://example.com/', '//example.com/', '/relative',
        'https://user:password@example.com/', "https://example.com/\r\nHeader:value",
        'https://example.com/a b', 'https://example.com\\@other.example/', 'https://'] as $url) {
        $rejected = false;
        try { App\Services\FlightUrls\FlightUrl::validate($url); }
        catch (InvalidArgumentException $e) { $rejected = true; }
        $t->true($rejected, 'Unsafe URL accepted: ' . $url);
    }
    $url = 'https://example.com/?AgentCode=SGTOP&city=%E6%9D%B1%E4%BA%AC&dep_date%5B%5D=2026-01-02';
    $t->same($url, App\Services\FlightUrls\FlightUrl::validate($url));
});

$t->test('Shared flight query encoding keeps zero, ordering and RFC3986 encoding', function () use ($t): void {
    $t->same('adult=0&label=A%20%26%20B&dates%5B0%5D=2026-01-02',
        App\Services\FlightUrls\FlightUrl::query([
            'adult' => 0, 'empty' => '', 'label' => 'A & B', 'dates' => ['2026-01-02'],
        ]));
});
