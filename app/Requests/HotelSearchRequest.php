<?php

declare(strict_types=1);

namespace App\Requests;

final class HotelSearchRequest
{
    private function __construct(
        public readonly array $values,
        public readonly string $scope,
        public readonly int $adults,
        public readonly int $children,
        public readonly array $errors
    ) {}

    public static function fromPost(array $input, string $sessionToken): self
    {
        $values = [];
        foreach (['hotel_destination' => '', 'check_in_date' => '', 'check_out_date' => '', 'hotel_adults' => '1', 'hotel_children' => '0'] as $key => $default) {
            $values[$key] = trim((string)($input[$key] ?? $default));
        }

        $errors = [];
        if (!RequestValidation::csrfMatches($input, $sessionToken)) $errors[] = '送信内容を確認できませんでした。';
        if (!RequestValidation::lengthBetween($values['hotel_destination'], 1, 100)) $errors[] = '目的地を入力してください。';

        $checkIn = RequestValidation::date($values['check_in_date']);
        $checkOut = RequestValidation::date($values['check_out_date']);
        if (!$checkIn) $errors[] = '正しいチェックイン日を入力してください。';
        if (!$checkOut) $errors[] = '正しいチェックアウト日を入力してください。';
        if ($checkIn && $checkOut && $checkOut <= $checkIn) $errors[] = 'チェックアウト日はチェックイン日より後にしてください。';

        $adults = RequestValidation::integer($values['hotel_adults'], 1, 9);
        $children = RequestValidation::integer($values['hotel_children'], 0, 9);
        if ($adults === false) $errors[] = '大人人数は1〜9名です。';
        if ($children === false) $errors[] = '子供人数は0〜9名です。';

        return new self(
            $values,
            in_array((string)($input['hotel_scope'] ?? ''), ['domestic', 'korea', 'overseas'], true)
                ? (string)$input['hotel_scope']
                : 'domestic',
            $adults === false ? 0 : (int)$adults,
            $children === false ? 0 : (int)$children,
            $errors
        );
    }
}
