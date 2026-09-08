<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FlightCity;
use App\Services\FlightUrls\{FlightProviderUrlBuilder, FlightUrl, ExpediaUrlBuilder, AgodaUrlBuilder,
    AirtripUrlBuilder, TravelistUrlBuilder, RealTicketUrlBuilder, JtbUrlBuilder, SkyTicketUrlBuilder, SkyGateUrlBuilder};

final class FlightUrlBuilder
{
    /** @var array<string, FlightProviderUrlBuilder> */
    private array $providers;

    public function __construct(private FlightCity $cities)
    {
        $this->providers = [
            'expedia' => new ExpediaUrlBuilder($cities),
            'agoda' => new AgodaUrlBuilder($cities),
            'airtrip' => new AirtripUrlBuilder($cities),
            'travelist' => new TravelistUrlBuilder($cities),
            'realticket' => new RealTicketUrlBuilder($cities),
            'jtb' => new JtbUrlBuilder($cities),
            'skyticket' => new SkyTicketUrlBuilder($cities),
            'skygate' => new SkyGateUrlBuilder($cities),
        ];
    }

    public function buildFlightLinks(
        string $origin,
        string $destination,
        string $departure,
        string $return,
        int $travelers,
        bool $domestic
    ): array {
        $links = ['maps' => FlightUrl::validate('https://www.google.com/maps/search/?api=1&query=' . rawurlencode($destination))];
        $providers = $domestic
            ? ['expedia', 'agoda', 'airtrip', 'travelist', 'realticket']
            : ['expedia', 'agoda', 'jtb', 'skyticket', 'skygate'];
        foreach ($providers as $provider) {
            $links[$provider] = $this->providers[$provider]->build($origin, $destination, $departure, $return, $travelers);
        }
        return $links;
    }
}
