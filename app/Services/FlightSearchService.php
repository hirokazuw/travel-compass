<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FlightCity;

final class FlightSearchService
{
    public function __construct(
        private FlightCity $cities,
        private AeroDataBoxRouteSearch $routes,
        private AviasalesPriceSearch $aviasales,
        private SerpApiFlightSearch $serpApi
    ) {}

    public function search(
        string $origin,
        string $destination,
        string $departure,
        string $return,
        int $travelers
    ): array {
        $originCode = $this->cities->code($origin);
        $destinationCode = $this->cities->code($destination);
        if ($originCode === null || $destinationCode === null) {
            return ['routes' => [], 'offers' => [], 'status' => 'unsupported_route', 'source' => 'none'];
        }

        $routeOperators = [];
        if ($this->routes->isConfigured()) {
            try {
                $routeOperators = $this->routes->search(
                    $this->cities->airportCandidates($origin),
                    $this->cities->airportCandidates($destination)
                );
            } catch (\Throwable $e) {
                error_log('AeroDataBox route search: ' . $e->getMessage());
            }
        }

        if (!$this->aviasales->isConfigured() && !$this->serpApi->isConfigured()) {
            return ['routes' => $routeOperators, 'offers' => [], 'status' => 'not_configured', 'source' => 'none'];
        }

        if ($this->aviasales->isConfigured()) {
            try {
                $offers = $this->aviasales->search($originCode, $destinationCode, $departure, $return);
                if ($offers) {
                    return ['routes' => $routeOperators, 'offers' => $offers, 'status' => 'success', 'source' => 'aviasales'];
                }
            } catch (\Throwable $e) {
                error_log('Aviasales price search: ' . $e->getMessage());
            }
        }

        if (!$this->serpApi->isConfigured()) {
            return ['routes' => $routeOperators, 'offers' => [], 'status' => 'empty', 'source' => 'none'];
        }

        $serpOrigin = $this->cities->flightSearchCode($origin);
        $serpDestination = $this->cities->flightSearchCode($destination);
        if ($serpOrigin === null || $serpDestination === null) {
            return ['routes' => $routeOperators, 'offers' => [], 'status' => 'unsupported_route', 'source' => 'none'];
        }

        try {
            $offers = $this->serpApi->search($serpOrigin, $serpDestination, $departure, $return, $travelers);
            return [
                'routes' => $routeOperators,
                'offers' => $offers,
                'status' => $offers ? 'success' : 'empty',
                'source' => $offers ? 'serpapi' : 'none',
            ];
        } catch (\Throwable $e) {
            error_log('SerpApi flight search: ' . $e->getMessage());
            return ['routes' => $routeOperators, 'offers' => [], 'status' => 'error', 'source' => 'none'];
        }
    }
}
