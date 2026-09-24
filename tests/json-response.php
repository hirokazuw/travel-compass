<?php

declare(strict_types=1);

$t->test('JSON response preserves successful bodies, flags and statuses', function () use ($t): void {
    $payload = ['message' => '東京', 'url' => 'https://example.test/', 'items' => []];
    foreach ([200, 403, 422, 502, 503] as $status) {
        $response = new App\Http\JsonResponse($payload, $status);
        $t->same($status, $response->status);
        $t->same('{"message":"東京","url":"https://example.test/","items":[]}', $response->body);
    }
    $response = new App\Http\JsonResponse($payload, flags: JSON_UNESCAPED_UNICODE);
    $t->same('{"message":"東京","url":"https:\/\/example.test\/","items":[]}', $response->body);
});

$t->test('JSON response replaces encoding failures with a fixed 500 response', function () use ($t): void {
    $recursive = [];
    $recursive['self'] = &$recursive;
    $deep = [];
    for ($i = 0; $i < 513; $i++) $deep = [$deep];
    $resource = fopen('php://memory', 'r+');
    try {
        foreach ([['value' => "\xB1\x31"], ["\xB1" => 'value'], ['value' => INF], ['value' => NAN], $recursive, $deep, ['value' => $resource]] as $payload) {
            foreach ([0, JSON_THROW_ON_ERROR, JSON_PARTIAL_OUTPUT_ON_ERROR, JSON_INVALID_UTF8_IGNORE, JSON_INVALID_UTF8_SUBSTITUTE] as $flags) {
                $response = new App\Http\JsonResponse($payload, 200, $flags);
                $t->same(500, $response->status);
                $t->same('{"message":"応答データを生成できませんでした。"}', $response->body);
                $t->same(['message' => '応答データを生成できませんでした。'], json_decode($response->body, true, 512, JSON_THROW_ON_ERROR));
            }
        }
        $t->same(500, (new App\Http\JsonResponse(['message' => "\xB1"], 422))->status);
    } finally {
        fclose($resource);
    }
});
