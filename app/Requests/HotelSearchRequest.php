<?php

declare(strict_types=1);

namespace App\Requests;

use DateTimeImmutable;

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
        if (!hash_equals($sessionToken, (string)($input['csrf'] ?? ''))) $errors[] = '送信内容を確認できませんでした。';
        if ($values['hotel_destination'] === '' || mb_strlen($values['hotel_destination']) > 100) $errors[] = '目的地を入力してください。';

        $checkIn = self::date($values['check_in_date']);
        $checkOut = self::date($values['check_out_date']);
        if (!$checkIn) $errors[] = '正しいチェックイン日を入力してください。';
        if (!$checkOut) $errors[] = '正しいチェックアウト日を入力してください。';
        if ($checkIn && $checkOut && $checkOut <= $checkIn) $errors[] = 'チェックアウト日はチェックイン日より後にしてください。';

        $adults = filter_var($values['hotel_adults'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 9]]);
        $children = filter_var($values['hotel_children'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 9]]);
        if ($adults === false) $errors[] = '大人人数は1〜9名です。';
        if ($children === false) $errors[] = '子供人数は0〜9名です。';

        return new self(
            $values,
            (string)($input['hotel_scope'] ?? 'domestic') === 'overseas' ? 'overseas' : 'domestic',
            $adults === false ? 0 : (int)$adults,
            $children === false ? 0 : (int)$children,
            $errors
        );
    }

    private static function date(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }
}
