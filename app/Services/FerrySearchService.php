<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FerryRoute;
use DateTimeImmutable;

final class FerrySearchService
{
    public function __construct(private FerryRoute $routes) {}

    /** @return array<int, array<string, mixed>> */
    public function findRoute(int $companyId, int $routeId): ?array
    {
        $route = $this->routes->findActiveByIdAndCompany($routeId, $companyId);
        return $route === null ? null : $this->presentRoute($route);
    }

    public function presentRoute(array $route): array
    {
        $fare = isset($route['fare_from']) ? (int)$route['fare_from'] : 0;
        return [
            'company_name' => trim((string)($route['company_name_ja'] ?: $route['company_name'])),
            'route_name' => trim((string)($route['route_name'] ?? '')),
            'departure_port' => (string)$route['departure_port'],
            'arrival_port' => (string)$route['arrival_port'],
            'duration' => $this->duration((int)($route['duration_minutes'] ?? 0)),
            'fare_from' => $fare > 0 ? number_format($fare) : '',
            'fare_currency' => strtoupper((string)($route['fare_currency'] ?? 'JPY')),
            'fare_updated' => $this->fareUpdated((string)($route['fare_updated_at'] ?? '')),
            'vehicle_available' => (bool)$route['vehicle_available'],
            'overnight' => (bool)$route['overnight'],
            'destination_url' => $this->destinationUrl($route),
        ];
    }

    private function duration(int $minutes): string
    {
        if ($minutes <= 0) return '';
        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;
        return '約' . ($hours > 0 ? $hours . '時間' : '') . ($remaining > 0 ? $remaining . '分' : '');
    }

    private function fareUpdated(string $date): string
    {
        if ($date === '') return '';
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date
            ? $parsed->format('Y年n月') . '確認' : '';
    }

    private function firstUrl(array $candidates): string
    {
        foreach ($candidates as $candidate) {
            $url = trim((string)$candidate);
            $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
            if (filter_var($url, FILTER_VALIDATE_URL) && in_array($scheme, ['http', 'https'], true)) return $url;
        }
        return '';
    }

    private function destinationUrl(array $route): string
    {
        if ($this->isOaraiTomakomaiRoute($route)) {
            return 'https://www.sunflower.co.jp/';
        }

        // Routes operated under the same company may use different official
        // booking sites. Always resolve the selected route URL first.
        return $this->firstUrl([
            $route['route_reservation_url'] ?? '',
            $route['company_reservation_url'] ?? '',
            $route['company_official_url'] ?? '',
        ]);
    }

    private function isOaraiTomakomaiRoute(array $route): bool
    {
        $departure = trim((string)($route['departure_port'] ?? ''));
        $arrival = trim((string)($route['arrival_port'] ?? ''));
        return (str_contains($departure, '大洗') && str_contains($arrival, '苫小牧'))
            || (str_contains($departure, '苫小牧') && str_contains($arrival, '大洗'));
    }
}
