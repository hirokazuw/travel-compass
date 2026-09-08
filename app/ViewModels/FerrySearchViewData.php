<?php

declare(strict_types=1);

namespace App\ViewModels;

final class FerrySearchViewData
{
    use RejectUnknownViewProperties;

    /**
     * @param array{ferry_company_name: string, ferry_company_id: string, ferry_route_id: string, ferry_search_mode: string} $ferryValues
     * @param list<string> $ferryErrors
     * @param list<array<string, mixed>> $ferryRoutes Presented service records.
     * @param list<array<string, mixed>> $ferryRouteOptions
     */
    public function __construct(
        public readonly array $ferryValues = ['ferry_company_name' => '', 'ferry_company_id' => '', 'ferry_route_id' => '', 'ferry_search_mode' => 'conditions'],
        public readonly array $ferryErrors = [],
        public readonly array $ferryRoutes = [],
        public readonly array $ferryRouteOptions = [],
        public readonly string $ferryStatus = 'idle'
    ) {}
}
