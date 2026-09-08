<?php

declare(strict_types=1);

namespace App\ViewModels;

final class HotelSearchViewData
{
    use RejectUnknownViewProperties;

    /**
     * @param array{hotel_destination: string, check_in_date: string, check_out_date: string, hotel_adults: string, hotel_children: string} $hotelValues
     * @param list<string> $hotelErrors
     * @param list<array<string, mixed>> $hotels Normalized service records.
     * @param array<int, string> $rakutenHotelLinks
     */
    public function __construct(
        public readonly array $hotelValues = ['hotel_destination' => '', 'check_in_date' => '', 'check_out_date' => '', 'hotel_adults' => '1', 'hotel_children' => '0'],
        public readonly array $hotelErrors = [],
        public readonly string $activeHotelScope = 'domestic',
        public readonly array $hotels = [],
        public readonly array $rakutenHotelLinks = [],
        public readonly string $hotelStatus = 'idle',
        public readonly ?array $hotelOtaGuide = null
    ) {}

    public function message(): string
    {
        return SearchViewData::hotelMessage($this->hotelStatus);
    }
}
