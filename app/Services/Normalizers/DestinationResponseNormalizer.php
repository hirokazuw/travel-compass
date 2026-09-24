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
            $suggestions[] = [
                'name' => $name,
                'category' => trim((string)($place['type'] ?? $place['category'] ?? '')),
                'address' => trim((string)($place['address'] ?? '')),
            ];
        }
        return array_slice($suggestions, 0, max(1, (int)($this->config['max_place_suggestions'] ?? 8)));
    }

}
