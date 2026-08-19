<?php

declare(strict_types=1);

namespace App\Requests;

use DateTimeImmutable;

final class FlightSearchRequest
{
    private function __construct(
        public readonly array $values,
        public readonly array $errors
    ) {}

    public static function fromPost(array $input, string $sessionToken): self
    {
        $values = [];
        foreach (['origin' => '', 'destination' => '', 'departure_date' => '', 'return_date' => '', 'travelers' => '1'] as $key => $default) {
            $values[$key] = trim((string)($input[$key] ?? $default));
        }

        $errors = [];
        if (!hash_equals($sessionToken, (string)($input['csrf'] ?? ''))) $errors[] = '送信内容を確認できませんでした。';
        if ($values['origin'] === '' || mb_strlen($values['origin']) > 100) $errors[] = '出発地を入力してください。';
        if ($values['destination'] === '' || mb_strlen($values['destination']) > 100) $errors[] = '目的地を入力してください。';

        $departure = self::date($values['departure_date']);
        $return = $values['return_date'] === '' ? null : self::date($values['return_date']);
        if (!$departure) $errors[] = '正しい出発日を入力してください。';
        if ($values['return_date'] !== '' && !$return) $errors[] = '正しい帰着日を入力してください。';
        if ($departure && $return && $return < $departure) $errors[] = '帰着日は出発日以降にしてください。';

        $travelers = filter_var($values['travelers'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 9]]);
        $values['travelers'] = (string)($travelers === false ? 0 : $travelers);
        if ($travelers === false) $errors[] = '人数は1〜9名です。';

        return new self($values, $errors);
    }

    private static function date(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }
}
