<?php

namespace App\Actions;

use App\ViewModels\FlightSearchViewData;
use App\Models\SearchHistory;
use App\Models\FlightCity;
use App\Requests\FlightSearchRequest;
use App\Services\FlightSearchService;
use App\Services\FlightUrlBuilder;

final class FlightSearchAction
{
    public function __construct(
        private SearchHistory $searchHistory,
        private FlightCity $flightCity,
        private FlightSearchService $flightSearch,
        private FlightUrlBuilder $travelLinks,
        private string $visitorId
    ) {}

    public function handle(array $input, string $sessionToken): FlightSearchViewData
    {
        $request = FlightSearchRequest::fromPost($input, $sessionToken);
        if ($request->errors !== []) return new FlightSearchViewData(values: $request->values, errors: $request->errors);

        $values = $request->values;
        $this->saveFlightHistory($values);
        $isDomestic = $this->flightCity->isDomestic($values['origin'])
            && $this->flightCity->isDomestic($values['destination']);
        $flightResult = $this->flightSearch->search(
            $values['origin'], $values['destination'], $values['departure_date'],
            $values['return_date'], (int)$values['travelers']
        );
        return new FlightSearchViewData(values: $values,
            isDomesticFlight: $isDomestic,
            activeFlightScope: $isDomestic ? 'domestic' : 'overseas',
            result: $this->travelLinks->buildFlightLinks(
                $values['origin'], $values['destination'], $values['departure_date'],
                $values['return_date'], (int)$values['travelers'], $isDomestic
            ),
            flightOffers: $flightResult['offers'],
            flightOffersStatus: $flightResult['status'],
        );
    }

    private function saveFlightHistory(array $values): void
    {
        try {
            $this->searchHistory->createFlight($values, $this->visitorId);
        } catch (\Throwable $e) {
            error_log('Flight search history write: ' . $e->getMessage());
        }
    }

}
