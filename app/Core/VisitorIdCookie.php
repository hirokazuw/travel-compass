<?php

namespace App\Core;

final class VisitorIdCookie
{
    public const NAME = 'travel_compass_visitor_id';
    private const MAX_AGE = 60 * 60 * 24 * 90;
    private const PATTERN = '/\A[a-f0-9]{64}\z/D';

    public static function resolve(): string
    {
        $current = strtolower((string)($_COOKIE[self::NAME] ?? ''));
        if (preg_match(self::PATTERN, $current) === 1) {
            return $current;
        }

        $visitorId = bin2hex(random_bytes(32));
        $secure = self::isHttpsRequest();
        $expires = time() + self::MAX_AGE;

        if (!setcookie(self::NAME, $visitorId, [
            'expires' => $expires,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ])) {
            throw new \RuntimeException('visitor_id Cookieを設定できませんでした。');
        }

        // The response Cookie is not reflected in $_COOKIE until the next request.
        $_COOKIE[self::NAME] = $visitorId;

        return $visitorId;
    }

    private static function isHttpsRequest(): bool
    {
        if ((string)($_SERVER['HTTPS'] ?? '') !== '' && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
            return true;
        }

        if ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) {
            return true;
        }

        $forwardedProto = strtolower(trim(explode(',', (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
        return $forwardedProto === 'https';
    }
}
