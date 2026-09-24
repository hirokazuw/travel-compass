<?php
declare(strict_types=1);
namespace App\Actions;

use App\Models\FlightCity;
use App\Http\JsonResponse;

final class FlightCitySuggestionsAction
{
    public function __construct(private FlightCity $cities) {}

    public function handle(array $input, string $sessionToken): JsonResponse
    {
        if ($sessionToken === '' || !hash_equals($sessionToken, (string)($input['csrf'] ?? ''))) {
            return new JsonResponse(['suggestions' => []], 403);
        }
        $query = trim((string)($input['query'] ?? ''));
        if (mb_strlen($query) < 2 || mb_strlen($query) > 100) return new JsonResponse(['suggestions' => []], 422);
        try {
            return new JsonResponse(['suggestions' => $this->cities->suggest($query)]);
        } catch (\Throwable $e) {
            \App\Core\RequestLog::failure('flight.suggestions', $e);
            return new JsonResponse(['suggestions' => [], 'message' => '候補を取得できませんでした。都市名またはIATAコードを直接入力できます。'], 503);
        }
    }
}
