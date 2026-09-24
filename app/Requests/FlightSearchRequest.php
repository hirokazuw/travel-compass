<?php

declare(strict_types=1);

namespace App\Requests;

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
        foreach (['origin', 'destination'] as $field) {
            $code = strtoupper(trim((string)($input[$field . '_iata'] ?? '')));
            // History stores the same label as the autocomplete; recover its search code.
            if ($code === '' && preg_match('/^.+（([A-Z]{3})）$/uD', $values[$field], $match)) {
                $code = $match[1];
            }
            if ($code !== '') {
                if (preg_match('/^[A-Z]{3}$/D', $code)) $values[$field] = $code;
                else $errors[] = '正しいIATAコードを選択してください。';
            }
        }
        if (!RequestValidation::csrfMatches($input, $sessionToken)) $errors[] = '送信内容を確認できませんでした。';
        if (!RequestValidation::lengthBetween($values['origin'], 1, 100)) $errors[] = '出発地を入力してください。';
        if (!RequestValidation::lengthBetween($values['destination'], 1, 100)) $errors[] = '目的地を入力してください。';

        $departure = RequestValidation::date($values['departure_date']);
        $return = $values['return_date'] === '' ? null : RequestValidation::date($values['return_date']);
        if (!$departure) $errors[] = '正しい出発日を入力してください。';
        if ($values['return_date'] !== '' && !$return) $errors[] = '正しい帰着日を入力してください。';
        if ($departure && $return && $return < $departure) $errors[] = '帰着日は出発日以降にしてください。';

        $travelers = RequestValidation::integer($values['travelers'], 1, 9);
        $values['travelers'] = (string)($travelers === false ? 0 : $travelers);
        if ($travelers === false) $errors[] = '人数は1〜9名です。';

        return new self($values, $errors);
    }
}
