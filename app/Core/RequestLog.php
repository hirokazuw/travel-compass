<?php

declare(strict_types=1);

namespace App\Core;

use App\Http\ExternalServiceException;
use Throwable;

final class RequestLog
{
    private static ?string $requestId = null;
    private static string $feature = 'standalone';

    public static function scope(string $feature, callable $work): mixed
    {
        $previous = [self::$requestId, self::$feature];
        self::$requestId = bin2hex(random_bytes(16));
        self::$feature = $feature;
        try {
            $response = $work();
            self::write('response', null, null, $response instanceof \App\Http\JsonResponse ? $response->status : 200);
            return $response;
        } catch (Throwable $e) {
            self::failure('request.failed', $e);
            self::write('response', null, null, 500);
            throw $e;
        } finally {
            [self::$requestId, self::$feature] = $previous;
        }
    }

    public static function failure(string $event, Throwable $error, ?string $service = null): void
    {
        self::write($event, $error, $service, null);
    }

    private static function write(string $event, ?Throwable $error, ?string $service, ?int $responseStatus): void
    {
        // Only fixed identifiers and typed status metadata; never messages, URLs, input or traces.
        error_log((string)json_encode([
            'request_id' => self::$requestId ?? bin2hex(random_bytes(16)),
            'feature' => self::$feature,
            'event' => $event,
            'external_service' => $error instanceof ExternalServiceException ? $error->service : $service,
            'http_status' => $error instanceof ExternalServiceException ? $error->httpStatus : null,
            'response_status' => $responseStatus,
            'exception_class' => $error === null ? null : get_class($error),
        ], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE));
    }
}
