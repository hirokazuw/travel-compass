<?php

declare(strict_types=1);

namespace App\Actions;

use App\Http\JsonResponse;
use App\ViewModels\FerrySearchViewData;
use App\Models\FerryCompany;
use App\Models\FerryRoute;
use App\Requests\FerrySearchRequest;
use App\Services\FerrySearchService;
use App\Services\FerryMapService;

final class FerrySearchAction
{
    public function __construct(
        private FerryCompany $companies,
        private FerryRoute $routes,
        private FerrySearchService $search,
        private FerryMapService $map
    ) {}

    public function handle(array $input, string $sessionToken): FerrySearchViewData
    {
        $request = FerrySearchRequest::fromPost($input, $sessionToken);
        $values = $request->values;
        $errors = $request->errors;
        $options = [];
        $status = $request->errors === [] ? 'empty' : 'invalid';

        $companyId = filter_var($request->values['ferry_company_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $routeId = filter_var($request->values['ferry_route_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($companyId !== false) {
            $company = $this->companies->findActiveById((int)$companyId);
            if ($company === null) {
                $errors[] = '選択されたフェリー会社は利用できません。';
            } else {
                $values['ferry_company_name'] = (string)$company['display_name'];
                $options = $this->routes->findActiveOptionsByCompany((int)$companyId);
            }
        }
        if ($errors !== []) return new FerrySearchViewData(ferryValues: $values, ferryErrors: $errors, ferryRouteOptions: $options, ferryStatus: $status);

        try {
            $route = $this->search->findRoute((int)$companyId, (int)$routeId);
            if ($route === null) {
                return new FerrySearchViewData(ferryValues: $values, ferryRouteOptions: $options,
                    ferryErrors: ['選択された航路が指定のフェリー会社に属していないか、現在利用できません。'],
                    ferryStatus: 'invalid',
                );
            }
            return new FerrySearchViewData(ferryValues: $values, ferryRouteOptions: $options,
                ferryRoutes: [$route],
                ferryStatus: 'success',
            );
        } catch (\Throwable $e) {
            error_log('Ferry route search: ' . $e->getMessage());
            return new FerrySearchViewData(ferryValues: $values, ferryRouteOptions: $options, ferryStatus: 'error');
        }
    }

    public function companySuggestions(array $input, string $sessionToken): JsonResponse
    {
        $query = trim((string)($input['query'] ?? ''));
        if (!$this->validCsrf($input, $sessionToken) || $query === '' || mb_strlen($query) > 100) {
            return new JsonResponse(['suggestions' => []], 422);
        }
        try {
            return new JsonResponse(['suggestions' => $this->companies->suggestActive($query)]);
        } catch (\Throwable $e) {
            error_log('Ferry company suggestions: ' . $e->getMessage());
            return new JsonResponse(['suggestions' => []], 500);
        }
    }

    public function companyRoutes(array $input, string $sessionToken): JsonResponse
    {
        $companyId = filter_var($input['company_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$this->validCsrf($input, $sessionToken) || $companyId === false) {
            return new JsonResponse(['routes' => []], 422);
        }
        try {
            if ($this->companies->findActiveById((int)$companyId) === null) {
                return new JsonResponse(['routes' => []], 404);
            }
            return new JsonResponse(['routes' => $this->routes->findActiveOptionsByCompany((int)$companyId)]);
        } catch (\Throwable $e) {
            error_log('Ferry company routes: ' . $e->getMessage());
            return new JsonResponse(['routes' => []], 500);
        }
    }

    public function mapData(array $input, string $sessionToken): JsonResponse
    {
        if (!$this->validCsrf($input, $sessionToken)) {
            return new JsonResponse(['routes' => []], 422);
        }
        try {
            return new JsonResponse($this->map->data());
        } catch (\Throwable $e) {
            error_log('Ferry map data: ' . $e->getMessage());
            return new JsonResponse(['routes' => []], 500);
        }
    }

    private function validCsrf(array $input, string $sessionToken): bool
    {
        return hash_equals($sessionToken, (string)($input['csrf'] ?? ''));
    }

}
