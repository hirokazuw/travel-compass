<?php

declare(strict_types=1);

namespace App\Http;

use JsonException;

final class JsonResponse
{
    public readonly string $body;
    public readonly int $status;

    public function __construct(
        array $payload,
        int $status = 200,
        int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) {
        // Never send a partial or lossy payload as a successful response.
        $flags = ($flags & ~(JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_IGNORE | JSON_INVALID_UTF8_SUBSTITUTE))
            | JSON_THROW_ON_ERROR;
        try {
            $body = json_encode($payload, $flags);
        } catch (JsonException $e) {
            // A literal fallback cannot fail encoding; do not expose the payload.
            $body = '{"message":"応答データを生成できませんでした。"}';
            $status = 500;
            \App\Core\RequestLog::failure('json.encode', $e);
        }
        $this->body = $body;
        $this->status = $status;
    }

    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=UTF-8');
        echo $this->body;
    }
}
