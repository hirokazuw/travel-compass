<?php

declare(strict_types=1);

namespace App\Factories;

use App\Services\{ApifyClient, ApiCache, ApifyFlightSearch, ApifyHotelSearch, ApifyDestinationSearch};
use App\Services\Normalizers\{FlightResponseNormalizer, HotelResponseNormalizer, DestinationResponseNormalizer};

final class ApifySearchFactory
{
    private ApifyClient $client;

    public function __construct(private array $config, private string $root)
    {
        $this->client = new ApifyClient($config);
    }

    public function flight(): ApifyFlightSearch
    {
        return new ApifyFlightSearch($this->client,
            $this->cache('flight_cache_dir', 'flights', 'cache_ttl', 3600), new FlightResponseNormalizer());
    }

    public function hotel(): ApifyHotelSearch
    {
        return new ApifyHotelSearch($this->client,
            $this->cache('hotel_cache_dir', 'hotels', 'cache_ttl', 3600), new HotelResponseNormalizer());
    }

    public function destination(): ApifyDestinationSearch
    {
        return new ApifyDestinationSearch($this->client,
            $this->cache('places_cache_dir', 'place-suggestions', 'places_cache_ttl', 900), new DestinationResponseNormalizer($this->config));
    }

    private function cache(string $pathKey, string $directory, string $ttlKey, int $defaultTtl): ApiCache
    {
        return new ApiCache(
            (string)($this->config[$pathKey] ?? $this->root . '/storage/cache/apify/' . $directory),
            max(0, (int)($this->config[$ttlKey] ?? $defaultTtl))
        );
    }
}
