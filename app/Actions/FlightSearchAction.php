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
        $labels = [];
        foreach (['origin', 'destination'] as $field) {
            if (preg_match('/^[A-Z]{3}$/D', $request->values[$field]) && ($input[$field . '_iata'] ?? '') !== '') {
                $labels[$field] = mb_substr(trim((string)($input[$field] ?? '')), 0, 150);
            }
        }
        if ($request->errors !== []) return new FlightSearchViewData(values: $request->values, errors: $request->errors, cityLabels: $labels);

        $values = $request->values;
        $historyValues = $values;
        foreach (['origin', 'destination'] as $field) {
            if (preg_match('/^[A-Z]{3}$/D', $values[$field])) {
                $label = $values[$field];
                try {
                    foreach ($this->flightCity->suggest($values[$field]) as $city) {
                        if ($city['iata'] === $values[$field]) { $label = $city['label']; break; }
                    }
                } catch (\Throwable $e) {
                    \App\Core\RequestLog::failure('flight.history_label', $e);
                }
                $labels[$field] = $label;
                $historyValues[$field] = $label;
            }
        }
        $this->saveFlightHistory($historyValues);
        $isDomestic = $this->flightCity->isDomestic($values['origin'])
            && $this->flightCity->isDomestic($values['destination']);
        $flightResult = $this->flightSearch->search(
            $values['origin'], $values['destination'], $values['departure_date'],
            $values['return_date'], (int)$values['travelers']
        );
        return new FlightSearchViewData(values: $values,
            cityLabels: $labels,
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
            \App\Core\RequestLog::failure('flight.history_write', $e);
        }
    }

}
