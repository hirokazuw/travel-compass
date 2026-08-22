<?php

namespace App\Controllers;

use App\Models\SearchHistory;
use App\Models\FlightCity;
use App\Requests\DestinationSuggestionRequest;
use App\Requests\FlightSearchRequest;
use App\Requests\HotelSearchRequest;
use App\Services\FlightSearchService;
use App\Services\ApifyDestinationSearch;
use App\Services\HotelSearchService;
use App\Services\RakutenTravelService;
use App\Services\FlightUrlBuilder;
use App\ViewModels\SearchViewData;
use App\ViewModels\SeoViewData;

final class SearchController
{
    public function __construct(
        private SearchHistory $searchHistory,
        private FlightCity $flightCity,
        private FlightSearchService $flightSearch,
        private RakutenTravelService $rakutenTravel,
        private HotelSearchService $hotelSearch,
        private ApifyDestinationSearch $apify,
        private FlightUrlBuilder $travelLinks,
        private array $config,
        private string $visitorId
    ) {}

    public function index(): void
    {
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && (string)($_POST['search_type'] ?? '') === 'hotel_destination_suggestions'
        ) {
            $this->destinationSuggestions();
            return;
        }

        $errors = [];
        $result = null;
        $flightOffers = [];
        $flightOffersStatus = 'idle';
        $isDomesticFlight = false;
        $activeFlightScope = 'domestic';
        $hotelErrors = [];
        $activeTab = 'flight';
        $activeHotelScope = 'domestic';
        $hotels = [];
        $rakutenHotelLinks = [];
        $hotelStatus = 'idle';
        $hotelValues = [
            'hotel_destination' => '',
            'check_in_date' => '',
            'check_out_date' => '',
            'hotel_adults' => '1',
            'hotel_children' => '0',
        ];

        $values = [
            'origin' => '',
            'destination' => '',
            'departure_date' => '',
            'return_date' => '',
            'travelers' => '1',
        ];

        $method = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $searchType = (string)($_POST['search_type'] ?? 'flight');
        if ($method === 'POST' && $searchType === 'flight') {
            extract($this->handleFlightSearch(), EXTR_OVERWRITE);
        }
        if ($method === 'POST' && $searchType === 'hotel') {
            $activeTab = 'hotel';
            extract($this->handleHotelSearch(), EXTR_OVERWRITE);
        }

        $_SESSION['csrf'] = bin2hex(random_bytes(32));

        $recent = $this->searchHistory->recent($this->visitorId);

        $appName =
            $this->config['app']['name']
            ?? 'Travel Compass';
        $appVersion = $this->config['app']['version'] ?? '1.7.1';
        $publicPath = dirname(__DIR__, 2) . '/public/assets/';
        $cssVersion = (string)(filemtime($publicPath . 'app.css') ?: $appVersion);
        $jsVersion = (string)(filemtime($publicPath . 'app.js') ?: $appVersion);
        $flightOffersMessage = SearchViewData::flightMessage($flightOffersStatus);
        $hotelMessage = SearchViewData::hotelMessage($hotelStatus);
        $isSearchResult = $_SERVER['REQUEST_METHOD'] === 'POST';
        $seo = SeoViewData::create($this->config, $isSearchResult);
        if ($isSearchResult) {
            header('X-Robots-Tag: noindex, follow');
        }

        require dirname(__DIR__)
            . '/Views/search/index.php';
    }

    private function handleFlightSearch(): array
    {
        $request = FlightSearchRequest::fromPost($_POST, (string)($_SESSION['csrf'] ?? ''));
        $state = ['values' => $request->values, 'errors' => $request->errors];
        if ($request->errors !== []) return $state;

        $values = $request->values;
        $this->searchHistory->createFlight($values, $this->visitorId);
        $isDomestic = $this->flightCity->isDomestic($values['origin'])
            && $this->flightCity->isDomestic($values['destination']);
        $flightResult = $this->flightSearch->search(
            $values['origin'], $values['destination'], $values['departure_date'],
            $values['return_date'], (int)$values['travelers']
        );
        return $state + [
            'isDomesticFlight' => $isDomestic,
            'activeFlightScope' => $isDomestic ? 'domestic' : 'overseas',
            'result' => $this->travelLinks->buildFlightLinks(
                $values['origin'], $values['destination'], $values['departure_date'],
                $values['return_date'], (int)$values['travelers'], $isDomestic
            ),
            'flightOffers' => $flightResult['offers'],
            'flightOffersStatus' => $flightResult['status'],
        ];
    }

    private function handleHotelSearch(): array
    {
        $request = HotelSearchRequest::fromPost($_POST, (string)($_SESSION['csrf'] ?? ''));
        $state = [
            'hotelValues' => $request->values,
            'hotelErrors' => $request->errors,
            'activeHotelScope' => $request->scope,
        ];
        if ($request->errors !== []) return $state;
        $this->searchHistory->createHotel($request->values, $this->visitorId);
        if (!$this->hotelSearch->isConfigured()) return $state + ['hotelStatus' => 'not_configured'];

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
            return $state + [
                'hotels' => $hotels,
                'rakutenHotelLinks' => $rakutenHotelLinks,
                'hotelStatus' => $hotels ? 'success' : 'empty',
            ];
        } catch (\Throwable $e) {
            error_log('Apify hotel search: ' . $e->getMessage());
            return $state + ['hotelStatus' => 'error'];
        }
    }

    private function destinationSuggestions(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        $request = DestinationSuggestionRequest::fromPost($_POST, (string)($_SESSION['csrf'] ?? ''));
        if ($request->error !== null) {
            http_response_code($request->status);
            echo json_encode(['suggestions' => [], 'message' => $request->error], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (!$this->apify->isConfigured()) {
            http_response_code(503);
            echo json_encode(['suggestions' => [], 'message' => '候補検索を現在利用できません。手入力で検索できます。'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $suggestions = $this->apify->search($request->query);
            echo json_encode([
                'suggestions' => $suggestions,
                'message' => $suggestions === [] ? '候補が見つかりませんでした。手入力で検索できます。' : '',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            error_log('Apify destination suggestion search: ' . $e->getMessage());
            http_response_code(502);
            echo json_encode(['suggestions' => [], 'message' => '候補を取得できませんでした。手入力で検索できます。'], JSON_UNESCAPED_UNICODE);
        }
    }


}
