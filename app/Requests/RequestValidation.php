<?php

declare(strict_types=1);

namespace App\Requests;

use DateTimeImmutable;

/** Shared predicates and parsers; messages, ordering and status belong to each request. */
final class RequestValidation
{
    public static function csrfMatches(array $input, string $sessionToken): bool
    {
        return hash_equals($sessionToken, (string)($input['csrf'] ?? ''));
    }

    public static function lengthBetween(string $value, int $min, int $max): bool
    {
        $length = mb_strlen($value);
        return $length >= $min && $length <= $max;
    }

    public static function integer(string $value, int $min, ?int $max = null): int|false
    {
        $options = ['min_range' => $min];
        if ($max !== null) $options['max_range'] = $max;
        return filter_var($value, FILTER_VALIDATE_INT, ['options' => $options]);
    }

    public static function date(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $date : null;
    }
}
