<?php

declare(strict_types=1);

namespace App\Factories;

use App\Actions\FerrySearchAction;
use App\Models\{FerryCompany, FerryRoute, FerryMapMaster};
use App\Services\{FerrySearchService, FerryMapService};
use PDO;

final class FerrySearchFactory
{
    public static function create(PDO $db): FerrySearchAction
    {
        $routes = new FerryRoute($db);
        $search = new FerrySearchService($routes);
        return new FerrySearchAction(
            new FerryCompany($db), $routes, $search, new FerryMapService($routes, $search, FerryMapMaster::load())
        );
    }
}
