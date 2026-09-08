<?php

namespace App\Actions;

use App\Models\SearchHistory;
use App\Requests\HotelSearchRequest;
use App\Services\HotelSearchService;
use App\Services\RakutenTravelService;
use App\ViewModels\HotelOtaViewData;
use App\ViewModels\HotelSearchViewData;

final class HotelSearchAction
{
    public function __construct(
        private SearchHistory $searchHistory,
        private HotelSearchService $hotelSearch,
        private RakutenTravelService $rakutenTravel,
        private string $visitorId
    ) {}

    public function handle(array $input, string $sessionToken): HotelSearchViewData
    {
        $request = HotelSearchRequest::fromPost($input, $sessionToken);
        if ($request->errors !== []) return new HotelSearchViewData(
            hotelValues: $request->values, hotelErrors: $request->errors, activeHotelScope: $request->scope
        );
        $this->saveHotelHistory($request->values);
        if (!$this->hotelSearch->isConfigured()) return new HotelSearchViewData(hotelValues: $request->values, activeHotelScope: $request->scope, hotelStatus: 'not_configured');

        $destination = $request->values['hotel_destination'];
        try {
            $hotels = $this->hotelSearch->search(
                $destination, $request->values['check_in_date'], $request->values['check_out_date'],
                $request->adults, $request->children
            );
            $rakutenHotelLinks = [];
            $rakutenHotelMatches = [];
            if ($request->scope === 'domestic' && $this->rakutenTravel->isAffiliateConfigured()) {
                try {
                    $links = $this->rakutenTravel->searchAffiliateLinks(
                        $destination, $request->values['check_in_date'], $request->values['check_out_date'],
                        $request->adults, $request->children
                    );
                    $rakutenHotelMatches = $this->hotelSearch->matchRakutenHotels($hotels, $links);
                    $rakutenHotelLinks = array_map(
                        static fn(array $match): string => $match['url'],
                        $rakutenHotelMatches
                    );
                } catch (\Throwable $e) {
                    error_log('Rakuten hotel link search: ' . $e->getMessage());
                }
            }
            try {
                $hotels = $this->hotelSearch->addHotelCardLinks(
                    $hotels, $destination,
                    $request->values['check_in_date'], $request->values['check_out_date'],
                    $request->adults, $request->children, $request->scope === 'domestic',
                    $request->scope === 'domestic' ? $rakutenHotelMatches : null
                );
            } catch (\Throwable $e) {
                error_log('Hotel card link generation: ' . $e->getMessage());
            }
            return new HotelSearchViewData(hotelValues: $request->values, activeHotelScope: $request->scope,
                hotels: $hotels,
                rakutenHotelLinks: $rakutenHotelLinks,
                hotelStatus: $hotels ? 'success' : 'empty',
                hotelOtaGuide: HotelOtaViewData::create(
                    $request->scope,
                    $request->values
                ),
            );
        } catch (\Throwable $e) {
            error_log('Apify hotel search: ' . $e->getMessage());
            return new HotelSearchViewData(hotelValues: $request->values, activeHotelScope: $request->scope, hotelStatus: 'error');
        }
    }

    private function saveHotelHistory(array $values): void
    {
        try {
            $this->searchHistory->createHotel($values, $this->visitorId);
        } catch (\Throwable $e) {
            error_log('Hotel search history write: ' . $e->getMessage());
        }
    }

}
