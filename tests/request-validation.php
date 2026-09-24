<?php

declare(strict_types=1);

$t->test('Request validation preserves error text and ordering', function () use ($t): void {
    $flight = App\Requests\FlightSearchRequest::fromPost(['origin_iata' => 'invalid', 'travelers' => '01'], 'token');
    $t->same([
        '正しいIATAコードを選択してください。', '送信内容を確認できませんでした。',
        '出発地を入力してください。', '目的地を入力してください。',
        '正しい出発日を入力してください。', '人数は1〜9名です。',
    ], $flight->errors);
    $t->same('0', $flight->values['travelers']);
    $hotel = App\Requests\HotelSearchRequest::fromPost(['hotel_adults' => '01', 'hotel_children' => '10'], 'token');
    $t->same([
        '送信内容を確認できませんでした。', '目的地を入力してください。',
        '正しいチェックイン日を入力してください。', '正しいチェックアウト日を入力してください。',
        '大人人数は1〜9名です。', '子供人数は0〜9名です。',
    ], $hotel->errors);
    $t->same('01', $hotel->values['hotel_adults']);
    $t->same(0, $hotel->adults);
    $t->same(0, $hotel->children);
    $ferry = App\Requests\FerrySearchRequest::fromPost([
        'ferry_company_id' => '01', 'ferry_company_name' => str_repeat('あ', 151),
    ], 'token');
    $t->same([
        '送信内容を確認できませんでした。', 'フェリー会社の選択内容が無効です。',
        '航路を選択してください。', 'フェリー会社名が長すぎます。',
    ], $ferry->errors);
});

$t->test('Suggestion request preserves CSRF precedence and Unicode length boundaries', function () use ($t, $csrf): void {
    $request = App\Requests\DestinationSuggestionRequest::fromPost(['query' => '東'], $csrf);
    $t->same(['query' => '', 'error' => '送信内容を確認できませんでした。', 'status' => 403], get_object_vars($request));
    foreach ([0, 1, 2, 100, 101] as $length) {
        $query = str_repeat('東', $length);
        $request = App\Requests\DestinationSuggestionRequest::fromPost(['csrf' => $csrf, 'query' => ' ' . $query . ' '], $csrf);
        $valid = $length >= 2 && $length <= 100;
        $t->same($query, $request->query);
        $t->same($valid ? 200 : 422, $request->status);
        $t->same($valid ? null : '目的地を2〜100文字で入力してください。', $request->error);
    }
});

$t->test('Search requests preserve strict dates and different same-day rules', function () use ($t, $csrf): void {
    foreach (['2028-02-29' => true, '2026-02-29' => false, '2026-04-31' => false, '2026-2-01' => false, '2026-10-01extra' => false] as $date => $valid) {
        $flight = App\Requests\FlightSearchRequest::fromPost([
            'csrf' => $csrf, 'origin' => '東京', 'destination' => '大阪',
            'departure_date' => $date, 'return_date' => $date,
        ], $csrf);
        $t->same($valid ? [] : ['正しい出発日を入力してください。', '正しい帰着日を入力してください。'], $flight->errors);
        $hotel = App\Requests\HotelSearchRequest::fromPost([
            'csrf' => $csrf, 'hotel_destination' => '大阪', 'check_in_date' => $date, 'check_out_date' => $date,
        ], $csrf);
        $t->same($valid ? ['チェックアウト日はチェックイン日より後にしてください。']
            : ['正しいチェックイン日を入力してください。', '正しいチェックアウト日を入力してください。'], $hotel->errors);
    }
});

$t->test('Integer validation preserves zero, bounds and filter syntax across requests', function () use ($t, $csrf): void {
    foreach (['0', '1', '9', '10', '-1', '01', '1.0', '1e0', '+1', ' 2 ', '999999999999999999999999'] as $value) {
        $positive = in_array($value, ['1', '9', '10', '+1', ' 2 '], true);
        $adult = $positive && $value !== '10';
        $child = $adult || $value === '0';
        $flight = App\Requests\FlightSearchRequest::fromPost([
            'csrf' => $csrf, 'origin' => '東京', 'destination' => '大阪', 'departure_date' => '2026-10-01', 'travelers' => $value,
        ], $csrf);
        $t->same($adult ? [] : ['人数は1〜9名です。'], $flight->errors);
        $t->same($adult ? (string)(int)$value : '0', $flight->values['travelers']);
        $hotel = App\Requests\HotelSearchRequest::fromPost([
            'csrf' => $csrf, 'hotel_destination' => '大阪', 'check_in_date' => '2026-10-01', 'check_out_date' => '2026-10-02',
            'hotel_adults' => $value, 'hotel_children' => $value,
        ], $csrf);
        $t->same(array_merge($adult ? [] : ['大人人数は1〜9名です。'], $child ? [] : ['子供人数は0〜9名です。']), $hotel->errors);
        $t->same(trim($value), $hotel->values['hotel_children']);
        $t->same($child ? (int)$value : 0, $hotel->children);
        $ferry = App\Requests\FerrySearchRequest::fromPost(['csrf' => $csrf, 'ferry_company_id' => $value, 'ferry_route_id' => $value], $csrf);
        $t->same($positive ? [] : ['フェリー会社の選択内容が無効です。', '航路の選択内容が無効です。'], $ferry->errors);
    }
});
