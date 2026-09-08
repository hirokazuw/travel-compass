<?php

declare(strict_types=1);

namespace App\ViewModels;

final class SearchPageViewModel
{
    use RejectUnknownViewProperties;

    public readonly string $searchOutcome;

    public function __construct(
        public readonly FlightSearchViewData $flight,
        public readonly HotelSearchViewData $hotel,
        public readonly FerrySearchViewData $ferry,
        public readonly string $activeTab,
        public readonly bool $isSearchResult,
        public readonly string $csrfToken,
        public readonly array $recent,
        public readonly string $appName,
        public readonly string $appVersion,
        public readonly string $cssVersion,
        public readonly string $ferryMapCssVersion,
        public readonly string $jsVersion,
        public readonly array $seo
    ) {
        $hasError = $flight->errors || $hotel->hotelErrors || $ferry->ferryErrors
            || $flight->flightOffersStatus === 'error'
            || in_array($hotel->hotelStatus, ['error', 'not_configured'], true)
            || $ferry->ferryStatus === 'error';
        $this->searchOutcome = !$isSearchResult ? 'idle' : ($hasError ? 'error' : 'success');
    }
}
