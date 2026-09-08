<?php

declare(strict_types=1);

namespace App\Factories;

use App\Actions\{HotelSearchAction, DestinationSuggestionsAction};
use App\Models\SearchHistory;
use App\Services\{HotelSearchService, HotelUrlBuilder, RakutenTravelService};

final class HotelSearchFactory
{
    public static function create(SearchHistory $history, ApifySearchFactory $apify, array $rakutenConfig, string $visitorId): HotelSearchAction
    {
        return new HotelSearchAction(
            $history,
            new HotelSearchService($apify->hotel(), new HotelUrlBuilder()),
            new RakutenTravelService($rakutenConfig),
            $visitorId
        );
    }

    public static function suggestions(ApifySearchFactory $apify): DestinationSuggestionsAction
    {
        return new DestinationSuggestionsAction($apify->destination());
    }
}
