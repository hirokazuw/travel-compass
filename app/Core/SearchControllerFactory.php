<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

final class SearchControllerFactory
{
    public static function create(PDO $db, array $config, string $visitorId): \App\Controllers\SearchController
    {
        // Shared only within this request; no static container or cross-request state.
        $history = new \App\Models\SearchHistory($db);
        $apify = new \App\Factories\ApifySearchFactory($config['apify'] ?? [], dirname(__DIR__, 2));
        $flightAction = \App\Factories\FlightSearchFactory::create($db, $history, $apify, $visitorId);
        $hotelAction = \App\Factories\HotelSearchFactory::create($history, $apify, $config['rakuten'] ?? [], $visitorId);
        $destinationAction = \App\Factories\HotelSearchFactory::suggestions($apify);
        $ferryAction = \App\Factories\FerrySearchFactory::create($db);
        $flightSuggestions = new \App\Actions\FlightCitySuggestionsAction(new \App\Models\FlightCity($db));
        return new \App\Controllers\SearchController(
            [
                'flight' => $flightAction->handle(...),
                'hotel' => $hotelAction->handle(...),
                'ferry' => $ferryAction->handle(...),
            ],
            [
                'flight_city_suggestions' => $flightSuggestions->handle(...),
                'hotel_destination_suggestions' => $destinationAction->handle(...),
                'ferry_company_suggestions' => $ferryAction->companySuggestions(...),
                'ferry_company_routes' => $ferryAction->companyRoutes(...),
                'ferry_map_data' => $ferryAction->mapData(...),
            ],
            new \App\ViewModels\SearchPageBuilder($history, $config, $visitorId)
        );
    }
}
