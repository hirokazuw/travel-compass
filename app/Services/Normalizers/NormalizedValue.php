<?php

declare(strict_types=1);

namespace App\Services\Normalizers;

final class NormalizedValue
{
    public static function priceValue(mixed $value): int
    {
        if (is_numeric($value)) return max(0, (int)round((float)$value));
        $digits = preg_replace('/[^0-9.]/', '', (string)$value);
        return $digits !== '' ? max(0, (int)round((float)$digits)) : 0;
    }

    public static function httpsUrl(string $url): string
    {
        return filter_var($url, FILTER_VALIDATE_URL) && str_starts_with($url, 'https://') ? $url : '';
    }


}
