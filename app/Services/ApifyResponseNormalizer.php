<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Normalizers\{HotelResponseNormalizer, FlightResponseNormalizer, DestinationResponseNormalizer};

/** Compatibility facade. Production search services use domain normalizers directly. */
final class ApifyResponseNormalizer
{
    public function __construct(private array $config) {}

    public function normalizeHotels(array $items): array
    {
        return (new HotelResponseNormalizer())->normalizeHotels($items);
    }

    public function normalizeFlights(array $items): array
    {
        return (new FlightResponseNormalizer())->normalizeFlights($items);
    }

    public function normalizeDestinationSuggestions(array $items): array
    {
        return (new DestinationResponseNormalizer($this->config))->normalizeDestinationSuggestions($items);
    }
}
