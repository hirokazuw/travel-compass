<?php

declare(strict_types=1);

namespace App\ViewModels;

final class FlightSearchViewData
{
    use RejectUnknownViewProperties;

    /**
     * @param array{origin: string, destination: string, departure_date: string, return_date: string, travelers: string} $values
     * @param list<string> $errors
     * @param array<string, string>|null $result
     * @param list<array<string, mixed>> $flightOffers Normalized service records.
     */
    public function __construct(
        public readonly array $values = ['origin' => '', 'destination' => '', 'departure_date' => '', 'return_date' => '', 'travelers' => '1'],
        public readonly array $errors = [],
        public readonly ?array $result = null,
        public readonly array $flightOffers = [],
        public readonly string $flightOffersStatus = 'idle',
        public readonly bool $isDomesticFlight = false,
        public readonly string $activeFlightScope = 'domestic'
    ) {}

    public function message(): string
    {
        return SearchViewData::flightMessage($this->flightOffersStatus);
    }
}
