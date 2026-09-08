<?php

declare(strict_types=1);

namespace App\Http;

final class JsonResponse
{
    public readonly string $body;

    public function __construct(
        array $payload,
        public readonly int $status = 200,
        int $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) {
        $this->body = (string)json_encode($payload, $flags);
    }

    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json; charset=UTF-8');
        echo $this->body;
    }
}
