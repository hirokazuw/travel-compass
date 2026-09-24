<?php

namespace App\Actions;

use App\Http\JsonResponse;
use App\Requests\DestinationSuggestionRequest;
use App\Services\ApifyDestinationSearch;

final class DestinationSuggestionsAction
{
    public function __construct(private ApifyDestinationSearch $apify) {}

    public function handle(array $input, string $sessionToken): JsonResponse
    {
        $request = DestinationSuggestionRequest::fromPost($input, $sessionToken);
        if ($request->error !== null) {
            return new JsonResponse(['suggestions' => [], 'message' => $request->error], $request->status, JSON_UNESCAPED_UNICODE);
        }
        if (!$this->apify->isConfigured()) {
            return new JsonResponse(['suggestions' => [], 'message' => '候補検索を現在利用できません。手入力で検索できます。'], 503, JSON_UNESCAPED_UNICODE);
        }

        try {
            $suggestions = $this->apify->search($request->query);
            return new JsonResponse([
                'suggestions' => $suggestions,
                'message' => $suggestions === [] ? '候補が見つかりませんでした。手入力で検索できます。' : '',
            ], 200, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            \App\Core\RequestLog::failure('hotel.destination_suggestions', $e, 'apify');
            return new JsonResponse(['suggestions' => [], 'message' => '候補を取得できませんでした。手入力で検索できます。'], 502, JSON_UNESCAPED_UNICODE);
        }
    }

}
