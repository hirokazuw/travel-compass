<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FlightCity;

final class FlightSearchService
{
    public function __construct(
        private FlightCity $cities,
        private SerpApiFlightSearch $provider
    ) {
    }

    /** @return array{offers: array, status: string} */
    public function search(
        string $origin,
        string $destination,
        string $departure,
        string $return,
        int $travelers
    ): array {
        if (!$this->provider->isConfigured()) {
            return ['offers' => [], 'status' => 'not_configured'];
        }

        $originCode = $this->cities->flightSearchCode($origin);
        $destinationCode = $this->cities->flightSearchCode($destination);
        if ($originCode === null || $destinationCode === null) {
            return ['offers' => [], 'status' => 'unsupported_route'];
        }

        try {
            $offers = $this->provider->search(
                $originCode,
                $destinationCode,
                $departure,
                $return,
                $travelers
            );

            return [
                'offers' => $offers,
                'status' => $offers ? 'success' : 'empty',
            ];
        } catch (\Throwable $e) {
            error_log('SerpApi flight search: ' . $e->getMessage());
            return ['offers' => [], 'status' => 'error'];
        }
    }
}
