<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FlightCity;

final class FlightSearchService
{
    public function __construct(
        private FlightCity $cities,
        private ApifyService $apify,
        private ScrapeDoService $scrapeDo,
        private AviasalesPriceSearch $aviasales,
        private SerpApiFlightSearch $serpApi,
        private string $preferredProvider = 'apify'
    ) {}

    public function search(
        string $origin,
        string $destination,
        string $departure,
        string $return,
        int $travelers
    ): array {
        $originCode = $this->cities->code($origin);
        $destinationCode = $this->cities->code($destination);
        if ($originCode === null || $destinationCode === null) {
            return ['routes' => [], 'offers' => [], 'status' => 'unsupported_route', 'source' => 'none'];
        }

        $scrapeOrigin = $this->cities->flightSearchCode($origin);
        $scrapeDestination = $this->cities->flightSearchCode($destination);
        if (
            $this->preferredProvider === 'apify' &&
            $this->apify->isConfigured() &&
            $scrapeOrigin !== null &&
            $scrapeDestination !== null
        ) {
            try {
                $offers = $this->apify->searchFlights($scrapeOrigin, $scrapeDestination, $departure, $return, $travelers);
                if ($offers) {
                    return ['routes' => [], 'offers' => $offers, 'status' => 'success', 'source' => 'apify'];
                }
            } catch (\Throwable $e) {
                error_log('Apify flight search: ' . $e->getMessage());
            }
        }

        if ($this->scrapeDo->isConfigured() && $scrapeOrigin !== null && $scrapeDestination !== null) {
            try {
                $offers = $this->scrapeDo->searchFlights($scrapeOrigin, $scrapeDestination, $departure, $return, $travelers);
                if ($offers) {
                    return ['routes' => [], 'offers' => $offers, 'status' => 'success', 'source' => 'scrapedo'];
                }
            } catch (\Throwable $e) {
                error_log('Scrape.do flight search: ' . $e->getMessage());
            }
        }

        if (!$this->apify->isConfigured() && !$this->scrapeDo->isConfigured() && !$this->aviasales->isConfigured() && !$this->serpApi->isConfigured()) {
            return ['routes' => [], 'offers' => [], 'status' => 'not_configured', 'source' => 'none'];
        }

        if ($this->aviasales->isConfigured()) {
            try {
                $offers = $this->aviasales->search($originCode, $destinationCode, $departure, $return);
                if ($offers) {
                    return ['routes' => [], 'offers' => $offers, 'status' => 'success', 'source' => 'aviasales'];
                }
            } catch (\Throwable $e) {
                error_log('Aviasales price search: ' . $e->getMessage());
            }
        }

        if (!$this->serpApi->isConfigured()) {
            return ['routes' => [], 'offers' => [], 'status' => 'empty', 'source' => 'none'];
        }

        $serpOrigin = $this->cities->flightSearchCode($origin);
        $serpDestination = $this->cities->flightSearchCode($destination);
        if ($serpOrigin === null || $serpDestination === null) {
            return ['routes' => [], 'offers' => [], 'status' => 'unsupported_route', 'source' => 'none'];
        }

        try {
            $offers = $this->serpApi->search($serpOrigin, $serpDestination, $departure, $return, $travelers);
            return [
                'routes' => [],
                'offers' => $offers,
                'status' => $offers ? 'success' : 'empty',
                'source' => $offers ? 'serpapi' : 'none',
            ];
        } catch (\Throwable $e) {
            error_log('SerpApi flight search: ' . $e->getMessage());
            return ['routes' => [], 'offers' => [], 'status' => 'error', 'source' => 'none'];
        }
    }
}
