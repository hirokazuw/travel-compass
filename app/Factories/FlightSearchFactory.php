<?php

declare(strict_types=1);

namespace App\Factories;

use App\Actions\FlightSearchAction;
use App\Models\{Airline, FlightCity, SearchHistory};
use App\Services\{FlightOfferAggregator, FlightSearchService, FlightUrlBuilder};
use PDO;

final class FlightSearchFactory
{
    public static function create(PDO $db, SearchHistory $history, ApifySearchFactory $apify, string $visitorId): FlightSearchAction
    {
        $cities = new FlightCity($db);
        return new FlightSearchAction(
            $history,
            $cities,
            new FlightSearchService($cities, $apify->flight(), new FlightOfferAggregator(new Airline($db))),
            new FlightUrlBuilder($cities),
            $visitorId
        );
    }
}
