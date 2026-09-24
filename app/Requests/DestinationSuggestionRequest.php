<?php

declare(strict_types=1);

namespace App\Requests;

final class DestinationSuggestionRequest
{
    private function __construct(
        public readonly string $query,
        public readonly ?string $error,
        public readonly int $status
    ) {}

    public static function fromPost(array $input, string $sessionToken): self
    {
        if (!RequestValidation::csrfMatches($input, $sessionToken)) {
            return new self('', '送信内容を確認できませんでした。', 403);
        }
        $query = trim((string)($input['query'] ?? ''));
        if (!RequestValidation::lengthBetween($query, 2, 100)) {
            return new self($query, '目的地を2〜100文字で入力してください。', 422);
        }
        return new self($query, null, 200);
    }
}
