<?php

declare(strict_types=1);

function captureRequestLogs(callable $work): array
{
    $path = tempnam(sys_get_temp_dir(), 'travel-log-');
    $previous = ini_get('error_log');
    ini_set('error_log', $path);
    try {
        $work();
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_map(static fn(string $line): array => json_decode(substr($line, strpos($line, '{')), true, 512, JSON_THROW_ON_ERROR), $lines);
    } finally {
        ini_set('error_log', $previous);
        unlink($path);
    }
}

$t->test('Controller correlates failures and response without logging sensitive input', function () use ($t, $page): void {
    $controller = new App\Controllers\SearchController([], [
        'hotel_destination_suggestions' => static function (): App\Http\JsonResponse {
            App\Core\RequestLog::failure('history.read', new RuntimeException('secret SQL and visitor'));
            App\Core\RequestLog::failure('hotel.destination_suggestions', new App\Http\ExternalServiceException('secret token and response', 'apify', 429));
            return new App\Http\JsonResponse(['suggestions' => []], 502);
        },
    ], $page);
    $logs = captureRequestLogs(function () use ($t, $controller): void {
        for ($i = 0; $i < 2; $i++) {
            $response = $controller->handle('POST', ['search_type' => 'hotel_destination_suggestions', 'query' => 'secret destination'], 'secret csrf');
            $t->same(502, $response->status);
        }
    });
    $t->same(6, count($logs));
    $t->same(1, preg_match('/^[a-f0-9]{32}$/D', $logs[0]['request_id']));
    $t->same($logs[0]['request_id'], $logs[1]['request_id']);
    $t->same($logs[0]['request_id'], $logs[2]['request_id']);
    $t->true($logs[0]['request_id'] !== $logs[3]['request_id'], 'Each handle gets a new ID');
    $t->same('hotel_destination_suggestions', $logs[0]['feature']);
    $t->same(null, $logs[0]['http_status']);
    $t->same('apify', $logs[1]['external_service']);
    $t->same(429, $logs[1]['http_status']);
    $t->same(App\Http\ExternalServiceException::class, $logs[1]['exception_class']);
    $t->same(502, $logs[2]['response_status']);
    $t->true(!str_contains(json_encode($logs), 'secret'), 'No exception message or request data in logs');
});

$t->test('Request log scope resets after exceptions and JSON encoding failures stay correlated', function () use ($t): void {
    $error = new RuntimeException('private detail');
    $logs = captureRequestLogs(function () use ($t, $error): void {
        try {
            App\Core\RequestLog::scope('flight', static function () use ($error): never { throw $error; });
        } catch (RuntimeException $caught) { $t->same($error, $caught); }
        App\Core\RequestLog::scope('hotel', static fn() => new App\Http\JsonResponse(['bad' => "\xB1"]));
        App\Core\RequestLog::failure('cache.maintenance', new RuntimeException('private path'));
    });
    $t->same(5, count($logs));
    $t->same(500, $logs[1]['response_status']);
    $t->same('hotel', $logs[2]['feature']);
    $t->same('json.encode', $logs[2]['event']);
    $t->same($logs[2]['request_id'], $logs[3]['request_id']);
    $t->same(500, $logs[3]['response_status']);
    $t->same('standalone', $logs[4]['feature']);
    $t->true($logs[0]['request_id'] !== $logs[2]['request_id'], 'Failed scope must not leak');
});
