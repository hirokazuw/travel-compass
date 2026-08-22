<?php

declare(strict_types=1);

namespace App\Requests;

final class FerrySearchRequest
{
    private function __construct(
        public readonly array $values,
        public readonly array $errors
    ) {}

    public static function fromPost(array $input, string $sessionToken): self
    {
        $values = [
            'ferry_company_name' => trim((string)($input['ferry_company_name'] ?? '')),
            'ferry_company_id' => trim((string)($input['ferry_company_id'] ?? '')),
            'ferry_route_id' => trim((string)($input['ferry_route_id'] ?? '')),
            'ferry_search_mode' => (string)($input['ferry_search_mode'] ?? '') === 'map' ? 'map' : 'conditions',
        ];
        $errors = [];
        if (!hash_equals($sessionToken, (string)($input['csrf'] ?? ''))) {
            $errors[] = '送信内容を確認できませんでした。';
        }
        if ($values['ferry_company_id'] === '') {
            $errors[] = 'フェリー会社を候補から選択してください。';
        } elseif (filter_var($values['ferry_company_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $errors[] = 'フェリー会社の選択内容が無効です。';
        }
        if ($values['ferry_route_id'] === '') {
            $errors[] = '航路を選択してください。';
        } elseif (filter_var($values['ferry_route_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            $errors[] = '航路の選択内容が無効です。';
        }
        if (mb_strlen($values['ferry_company_name']) > 150) $errors[] = 'フェリー会社名が長すぎます。';
        return new self($values, $errors);
    }
}
