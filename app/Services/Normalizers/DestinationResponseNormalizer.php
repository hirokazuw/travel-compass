<?php

declare(strict_types=1);

namespace App\Services\Normalizers;

final class DestinationResponseNormalizer
{
    public function __construct(private array $config) {}

    public function normalizeDestinationSuggestions(array $items): array
    {
        $suggestions = [];
        foreach ($items as $place) {
            if (!is_array($place)) continue;
            $name = trim((string)($place['name'] ?? $place['keyword'] ?? ''));
            if ($name === '') continue;
            $coordinate = is_array($place['coordinate'] ?? null) ? array_values($place['coordinate']) : [];
            $suggestions[] = [
                'name' => $name,
                'category' => trim((string)($place['type'] ?? $place['category'] ?? '')),
                'address' => trim((string)($place['address'] ?? '')),
                'place_id' => trim((string)($place['item_id'] ?? $place['place_id'] ?? '')),
                'country_code' => strtoupper(trim((string)($place['country_code'] ?? ''))),
                'latitude' => isset($coordinate[0]) && is_numeric($coordinate[0]) ? (float)$coordinate[0] : null,
                'longitude' => isset($coordinate[1]) && is_numeric($coordinate[1]) ? (float)$coordinate[1] : null,
            ];
        }
        return array_slice($suggestions, 0, max(1, (int)($this->config['max_place_suggestions'] ?? 8)));
    }

}
