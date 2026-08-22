<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\FerryCompany;
use App\Models\FerryRoute;
use App\Requests\FerrySearchRequest;
use App\Services\FerrySearchService;
use App\Services\FerryMapService;

final class FerryController
{
    public function __construct(
        private FerryCompany $companies,
        private FerryRoute $routes,
        private FerrySearchService $search,
        private FerryMapService $map
    ) {}

    public function handleSearch(array $input, string $sessionToken): array
    {
        $request = FerrySearchRequest::fromPost($input, $sessionToken);
        $state = [
            'ferryValues' => $request->values,
            'ferryErrors' => $request->errors,
            'ferryRoutes' => [],
            'ferryRouteOptions' => [],
            'ferryStatus' => $request->errors === [] ? 'empty' : 'invalid',
        ];

        $companyId = filter_var($request->values['ferry_company_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $routeId = filter_var($request->values['ferry_route_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($companyId !== false) {
            $company = $this->companies->findActiveById((int)$companyId);
            if ($company === null) {
                $state['ferryErrors'][] = '選択されたフェリー会社は利用できません。';
            } else {
                $state['ferryValues']['ferry_company_name'] = (string)$company['display_name'];
                $state['ferryRouteOptions'] = $this->routes->findActiveOptionsByCompany((int)$companyId);
            }
        }
        if ($state['ferryErrors'] !== []) return $state;

        try {
            $route = $this->search->findRoute((int)$companyId, (int)$routeId);
            if ($route === null) {
                return array_replace($state, [
                    'ferryErrors' => ['選択された航路が指定のフェリー会社に属していないか、現在利用できません。'],
                    'ferryStatus' => 'invalid',
                ]);
            }
            return array_replace($state, [
                'ferryRoutes' => [$route],
                'ferryStatus' => 'success',
            ]);
        } catch (\Throwable $e) {
            error_log('Ferry route search: ' . $e->getMessage());
            return array_replace($state, ['ferryStatus' => 'error']);
        }
    }

    public function companySuggestions(array $input, string $sessionToken): void
    {
        $query = trim((string)($input['query'] ?? ''));
        if (!$this->validCsrf($input, $sessionToken) || $query === '' || mb_strlen($query) > 100) {
            $this->json(['suggestions' => []], 422);
            return;
        }
        try {
            $this->json(['suggestions' => $this->companies->suggestActive($query)]);
        } catch (\Throwable $e) {
            error_log('Ferry company suggestions: ' . $e->getMessage());
            $this->json(['suggestions' => []], 500);
        }
    }

    public function companyRoutes(array $input, string $sessionToken): void
    {
        $companyId = filter_var($input['company_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$this->validCsrf($input, $sessionToken) || $companyId === false) {
            $this->json(['routes' => []], 422);
            return;
        }
        try {
            if ($this->companies->findActiveById((int)$companyId) === null) {
                $this->json(['routes' => []], 404);
                return;
            }
            $this->json(['routes' => $this->routes->findActiveOptionsByCompany((int)$companyId)]);
        } catch (\Throwable $e) {
            error_log('Ferry company routes: ' . $e->getMessage());
            $this->json(['routes' => []], 500);
        }
    }

    public function mapData(array $input, string $sessionToken): void
    {
        if (!$this->validCsrf($input, $sessionToken)) {
            $this->json(['routes' => []], 422);
            return;
        }
        try {
            $this->json($this->map->data());
        } catch (\Throwable $e) {
            error_log('Ferry map data: ' . $e->getMessage());
            $this->json(['routes' => []], 500);
        }
    }

    private function validCsrf(array $input, string $sessionToken): bool
    {
        return hash_equals($sessionToken, (string)($input['csrf'] ?? ''));
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
