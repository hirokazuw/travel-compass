<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FerryRoute;
use App\Models\FerryMapMaster;

final class FerryMapService
{
    private FerryMapMaster $master;

    public function __construct(
        private FerryRoute $routes,
        private FerrySearchService $presenter,
        ?FerryMapMaster $master = null
    ) {
        $this->master = $master ?? FerryMapMaster::load();
    }

    public function data(): array
    {
        $routes = [];
        foreach ($this->routes->findAllActiveForMap() as $route) {
            $departure = $this->master->project((string)$route['departure_port'], (string)($route['departure_prefecture'] ?? ''));
            $arrival = $this->master->project((string)$route['arrival_port'], (string)($route['arrival_prefecture'] ?? ''));
            $routes[] = array_merge($this->presenter->presentRoute($route), [
                'id' => (int)$route['id'],
                'company_id' => (int)$route['company_id'],
                'departure' => $departure,
                'arrival' => $arrival,
                'label' => $departure['name'] . ' → ' . $arrival['name'],
            ]);
        }
        return ['routes' => $routes];
    }

}
