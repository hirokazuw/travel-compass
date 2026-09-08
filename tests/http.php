<?php
declare(strict_types=1);

// Run against the PHP HTTP SAPI so headers and status codes are observable.
$socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
$address = stream_socket_get_name($socket, false);
fclose($socket);
$log = tmpfile();
$process = proc_open([PHP_BINARY, '-S', $address, __DIR__ . '/http-fixture.php'],
    [0 => ['pipe', 'r'], 1 => $log, 2 => $log], $pipes, dirname(__DIR__));
if (!is_resource($process)) throw new RuntimeException('Cannot start HTTP fixture');
function request(string $method, array $input = []): array {
    global $address;
    $context = stream_context_create(['http' => ['method' => $method, 'ignore_errors' => true,
        'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => http_build_query($input)]]);
    $body = file_get_contents('http://' . $address, false, $context);
    return [implode("\n", $http_response_header), $body];
}
function check(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
try {
    for ($i = 0; $i < 100; $i++) {
        $connection = @stream_socket_client('tcp://' . $address, $errno, $error, 0.1);
        if ($connection) { fclose($connection); break; }
        usleep(20000);
    }
    foreach (['GET' => [''], 'POST' => ['flight', 'hotel', 'ferry', 'unknown', '']] as $method => $types) {
        foreach ($types as $type) {
            $input = $type === '' ? [] : ['search_type' => $type];
            [$headers, $body] = request($method, $input);
            check(str_contains($headers, '200 OK'), "$method $type HTTP status");
            check(str_contains(strtolower($headers), 'x-robots-tag: noindex, follow') === ($method === 'POST'), "$method $type robots");
            $tab = in_array($type, ['hotel', 'ferry']) ? $type : 'flight';
            check(str_contains($body, 'id="' . $tab . '-tab" role="tab" aria-selected="true"'), "$method $type active tab");
            $invalid = $method === 'POST' && $type !== 'unknown';
            check(str_contains($body, 'data-search-outcome="' . ($invalid ? 'error' : ($method === 'POST' ? 'success' : 'idle')) . '"'), "$method $type outcome");
            check(str_contains($body, '送信内容を確認できませんでした。') === $invalid, "$method $type validation");
            check(!str_contains($body, 'content="test-token"'), "$method $type token rotation");
        }
    }
    foreach (['hotel_destination_suggestions' => 403, 'ferry_company_suggestions' => 422, 'ferry_company_routes' => 422, 'ferry_map_data' => 422] as $type => $status) {
        [$headers, $body] = request('POST', ['search_type' => $type]);
        check(str_contains($headers, (string)$status), "$type status");
        check(str_contains($headers, 'application/json'), "$type content type");
        check(!str_contains(strtolower($headers), 'x-robots-tag'), "$type robots");
        check(str_contains($headers, 'X-Fixture-Csrf: test-token'), "$type preserves CSRF");
        check(is_array(json_decode($body, true, 512, JSON_THROW_ON_ERROR)), "$type JSON");
    }
    foreach (['東' => 422, '東京' => 503] as $query => $status) {
        [$headers, $body] = request('POST', ['search_type' => 'hotel_destination_suggestions', 'csrf' => 'test-token', 'query' => $query]);
        check(str_contains($headers, (string)$status), "Destination $query status");
        check(json_decode($body, true, 512, JSON_THROW_ON_ERROR)['suggestions'] === [], 'Destination empty suggestions');
    }
    [$headers, $body] = request('POST', ['search_type' => 'hotel', 'csrf' => 'test-token',
        'hotel_destination' => 'Paris', 'hotel_scope' => 'overseas',
        'check_in_date' => '2026-10-01', 'check_out_date' => '2026-10-03']);
    check(str_contains($headers, '200 OK'), 'Unconfigured hotel HTTP status');
    check(str_contains($body, 'ホテル検索を一時的に利用できません。'), 'Unconfigured hotel message');
    check(str_contains($body, 'id="hotel-tab" role="tab" aria-selected="true"'), 'Unconfigured hotel active tab');
    echo "HTTP characterization passed\n";
} finally {
    proc_terminate($process);
    fclose($pipes[0]);
    proc_close($process);
    fclose($log);
}
