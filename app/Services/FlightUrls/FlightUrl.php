<?php

declare(strict_types=1);

namespace App\Services\FlightUrls;

final class FlightUrl
{
    public static function query(array $params): string
    {
        return http_build_query(array_filter($params, static fn($value) => $value !== ''), '', '&', PHP_QUERY_RFC3986);
    }

    public static function validate(string $url): string
    {
        $parts = parse_url($url);
        if ($parts === false || ($parts['scheme'] ?? '') !== 'https'
            || ($parts['host'] ?? '') === '' || isset($parts['user']) || isset($parts['pass'])
            || preg_match('/[\x00-\x20\x7f\\\\]/', $url)
            || filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Flight booking URL must be an absolute HTTPS URL without credentials or control characters');
        }
        return $url;
    }
}
