<?php

namespace App\Controllers;

use App\Models\TravelSearch;
use App\Models\FlightCity;
use App\Services\FlightSearchService;
use App\Services\ApifyService;
use App\Services\HotelSearchService;
use App\Services\RakutenTravelService;
use App\Services\ScrapeDoService;
use App\Services\TravelLinkBuilder;
use App\ViewModels\SearchViewData;
use App\ViewModels\SeoViewData;
use DateTimeImmutable;

final class SearchController
{
    public function __construct(
        private TravelSearch $model,
        private FlightCity $flightCity,
        private FlightSearchService $flightSearch,
        private RakutenTravelService $rakutenTravel,
        private HotelSearchService $hotelSearch,
        private ApifyService $apify,
        private ScrapeDoService $scrapeDo,
        private TravelLinkBuilder $travelLinks,
        private array $config
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
        $flightOffersSource = 'none';
        $isDomesticFlight = false;
        $activeFlightScope = 'domestic';
        $hotelErrors = [];
        $activeTab = 'flight';
        $activeHotelProvider = 'rakuten';
        $activeHotelScope = 'domestic';
        $rakutenErrors = [];
        $rakutenHotels = [];
        $rakutenStatus = 'idle';
        $overseasHotels = [];
        $overseasBookingLinks = [];
        $overseasHotelStatus = 'idle';
        $hotels = [];
        $hotelBookingLinks = [];
        $rakutenHotelLinks = [];
        $hotelStatus = 'idle';
        $rakutenValues = [
            'rakuten_destination' => '',
            'rakuten_check_in' => '',
            'rakuten_check_out' => '',
            'rakuten_adults' => '1',
            'rakuten_children' => '0',
        ];

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

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            (string)($_POST['search_type'] ?? 'flight') === 'flight'
        ) {
            if (
                !hash_equals(
                    $_SESSION['csrf'] ?? '',
                    (string)($_POST['csrf'] ?? '')
                )
            ) {
                $errors[] = '送信内容を確認できませんでした。';
            }

            foreach ($values as $key => $default) {
                $values[$key] = trim(
                    (string)($_POST[$key] ?? $default)
                );
            }

            if (
                $values['origin'] === '' ||
                mb_strlen($values['origin']) > 100
            ) {
                $errors[] = '出発地を入力してください。';
            }

            if (
                $values['destination'] === '' ||
                mb_strlen($values['destination']) > 100
            ) {
                $errors[] = '目的地を入力してください。';
            }

            $dep = $this->date($values['departure_date']);

            $ret = $values['return_date'] === ''
                ? null
                : $this->date($values['return_date']);

            if (!$dep) {
                $errors[] = '正しい出発日を入力してください。';
            }

            if (
                $values['return_date'] !== '' &&
                !$ret
            ) {
                $errors[] = '正しい帰着日を入力してください。';
            }

            if (
                $dep &&
                $ret &&
                $ret < $dep
            ) {
                $errors[] = '帰着日は出発日以降にしてください。';
            }

            $values['travelers'] = (string)(
                filter_var(
                    $values['travelers'],
                    FILTER_VALIDATE_INT,
                    [
                        'options' => [
                            'min_range' => 1,
                            'max_range' => 9,
                        ],
                    ]
                ) ?: 0
            );

            if ($values['travelers'] === '0') {
                $errors[] = '人数は1〜9名です。';
            }

            if (!$errors) {

                $this->model->create($values);

                $isDomesticFlight =
                    $this->flightCity->isDomestic($values['origin']) &&
                    $this->flightCity->isDomestic($values['destination']);
                $activeFlightScope = $isDomesticFlight ? 'domestic' : 'overseas';
                $result = $this->travelLinks->buildFlightLinks(
                    $values['origin'], $values['destination'], $values['departure_date'],
                    $values['return_date'], (int)$values['travelers'], $isDomesticFlight
                );

                $flightResult = $this->flightSearch->search(
                    $values['origin'], $values['destination'], $values['departure_date'],
                    $values['return_date'], (int)$values['travelers']
                );
                $flightOffers = $flightResult['offers'];
                $flightOffersStatus = $flightResult['status'];
                $flightOffersSource = (string)($flightResult['source'] ?? 'none');
            }
        }

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            (string)($_POST['search_type'] ?? '') === 'hotel'
        ) {
            $activeTab = 'hotel';
            $activeHotelScope = (string)($_POST['hotel_scope'] ?? 'domestic') === 'overseas' ? 'overseas' : 'domestic';
            if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
                $hotelErrors[] = '送信内容を確認できませんでした。';
            }

            foreach ($hotelValues as $key => $default) {
                $hotelValues[$key] = trim((string)($_POST[$key] ?? $default));
            }

            if ($hotelValues['hotel_destination'] === '' || mb_strlen($hotelValues['hotel_destination']) > 100) {
                $hotelErrors[] = '目的地を入力してください。';
            }

            $checkIn = $this->date($hotelValues['check_in_date']);
            $checkOut = $this->date($hotelValues['check_out_date']);
            if (!$checkIn) $hotelErrors[] = '正しいチェックイン日を入力してください。';
            if (!$checkOut) $hotelErrors[] = '正しいチェックアウト日を入力してください。';
            if ($checkIn && $checkOut && $checkOut <= $checkIn) {
                $hotelErrors[] = 'チェックアウト日はチェックイン日より後にしてください。';
            }

            $adults = filter_var($hotelValues['hotel_adults'], FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 9],
            ]);
            $children = filter_var($hotelValues['hotel_children'], FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 0, 'max_range' => 9],
            ]);
            if ($adults === false) $hotelErrors[] = '大人人数は1〜9名です。';
            if ($children === false) $hotelErrors[] = '子供人数は0〜9名です。';

            if (!$hotelErrors) {
                $destination = $hotelValues['hotel_destination'];
                $activeHotelProvider = 'apify';
                if (!$this->hotelSearch->isConfigured()) {
                    $hotelStatus = 'not_configured';
                } else {
                    try {
                        $hotels = $this->hotelSearch->search(
                            $destination,
                            $hotelValues['check_in_date'],
                            $hotelValues['check_out_date'],
                            (int)$adults,
                            (int)$children
                        );
                        $hotelBookingLinks = $this->hotelSearch->bookingLinks(
                            $destination, $hotelValues['check_in_date'], $hotelValues['check_out_date'],
                            (int)$adults, (int)$children, $activeHotelScope === 'domestic'
                        );
                        if ($activeHotelScope === 'domestic' && $this->rakutenTravel->isAffiliateConfigured()) {
                            try {
                                $rakutenLinks = $this->rakutenTravel->searchAffiliateLinks(
                                    $destination, $hotelValues['check_in_date'], $hotelValues['check_out_date'],
                                    (int)$adults, (int)$children
                                );
                                $rakutenHotelLinks = $this->hotelSearch->matchRakutenLinks($hotels, $rakutenLinks);
                            } catch (\Throwable $e) {
                                error_log('Rakuten hotel link search: ' . $e->getMessage());
                            }
                        }
                        $hotelStatus = $hotels ? 'success' : 'empty';
                    } catch (\Throwable $e) {
                        error_log('Apify hotel search: ' . $e->getMessage());
                        $hotelStatus = 'error';
                    }
                }
            }
        }

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            (string)($_POST['search_type'] ?? '') === 'rakuten_hotel'
        ) {
            $activeTab = 'hotel';
            $activeHotelProvider = 'rakuten';
            $activeHotelScope = 'domestic';
            if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
                $rakutenErrors[] = '送信内容を確認できませんでした。';
            }
            $hotelValues = [
                'hotel_destination' => trim((string)($_POST['hotel_destination'] ?? '')),
                'check_in_date' => trim((string)($_POST['check_in_date'] ?? '')),
                'check_out_date' => trim((string)($_POST['check_out_date'] ?? '')),
                'hotel_adults' => trim((string)($_POST['hotel_adults'] ?? '1')),
                'hotel_children' => trim((string)($_POST['hotel_children'] ?? '0')),
            ];
            $rakutenValues = [
                'rakuten_destination' => $hotelValues['hotel_destination'],
                'rakuten_check_in' => $hotelValues['check_in_date'],
                'rakuten_check_out' => $hotelValues['check_out_date'],
                'rakuten_adults' => $hotelValues['hotel_adults'],
                'rakuten_children' => $hotelValues['hotel_children'],
            ];
            if ($rakutenValues['rakuten_destination'] === '' || mb_strlen($rakutenValues['rakuten_destination']) > 100) {
                $rakutenErrors[] = '目的地を入力してください。';
            }
            $rakutenCheckIn = $this->date($rakutenValues['rakuten_check_in']);
            $rakutenCheckOut = $this->date($rakutenValues['rakuten_check_out']);
            if (!$rakutenCheckIn) $rakutenErrors[] = '正しいチェックイン日を入力してください。';
            if (!$rakutenCheckOut) $rakutenErrors[] = '正しいチェックアウト日を入力してください。';
            if ($rakutenCheckIn && $rakutenCheckOut && $rakutenCheckOut <= $rakutenCheckIn) {
                $rakutenErrors[] = 'チェックアウト日はチェックイン日より後にしてください。';
            }
            $rakutenAdults = filter_var($rakutenValues['rakuten_adults'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 9]]);
            $rakutenChildren = filter_var($rakutenValues['rakuten_children'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 9]]);
            if ($rakutenAdults === false) $rakutenErrors[] = '大人人数は1〜9名です。';
            if ($rakutenChildren === false) $rakutenErrors[] = '子供人数は0〜9名です。';

            if (!$rakutenErrors) {
                if (!$this->rakutenTravel->isConfigured()) {
                    $rakutenStatus = 'not_configured';
                } else {
                    try {
                        $rakutenHotels = $this->rakutenTravel->search(
                            $rakutenValues['rakuten_destination'],
                            $rakutenValues['rakuten_check_in'],
                            $rakutenValues['rakuten_check_out'],
                            (int)$rakutenAdults,
                            (int)$rakutenChildren
                        );
                        $rakutenStatus = $rakutenHotels ? 'success' : 'empty';
                    } catch (\Throwable $e) {
                        error_log('Rakuten Travel search: ' . $e->getMessage());
                        $rakutenStatus = 'error';
                    }
                }
            }
        }

        $_SESSION['csrf'] = bin2hex(random_bytes(32));

        $recent = $this->model->recent();

        $appName =
            $this->config['app']['name']
            ?? 'Travel Compass';
        $appVersion = $this->config['app']['version'] ?? '1.6.1';
        $publicPath = dirname(__DIR__, 2) . '/public/assets/';
        $cssVersion = (string)(filemtime($publicPath . 'app.css') ?: $appVersion);
        $jsVersion = (string)(filemtime($publicPath . 'app.js') ?: $appVersion);
        $flightOffersMessage = SearchViewData::flightMessage($flightOffersStatus);
        $rakutenMessage = SearchViewData::rakutenMessage($rakutenStatus);
        $overseasHotelMessage = SearchViewData::overseasHotelMessage($overseasHotelStatus);
        $hotelMessage = SearchViewData::hotelMessage($hotelStatus);
        $isSearchResult = $_SERVER['REQUEST_METHOD'] === 'POST';
        $seo = SeoViewData::create($this->config, $isSearchResult);
        if ($isSearchResult) {
            header('X-Robots-Tag: noindex, follow');
        }

        require dirname(__DIR__)
            . '/Views/search/index.php';
    }

    private function date(string $value): ?DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $value
        );

        return $date &&
            $date->format('Y-m-d') === $value
            ? $date
            : null;
    }

    private function destinationSuggestions(): void
    {
        header('Content-Type: application/json; charset=UTF-8');
        if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
            http_response_code(403);
            echo json_encode(['suggestions' => [], 'message' => '送信内容を確認できませんでした。'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $query = trim((string)($_POST['query'] ?? ''));
        if (mb_strlen($query) < 2 || mb_strlen($query) > 100) {
            http_response_code(422);
            echo json_encode(['suggestions' => [], 'message' => '目的地を2〜100文字で入力してください。'], JSON_UNESCAPED_UNICODE);
            return;
        }
        if (!$this->apify->isConfigured()) {
            http_response_code(503);
            echo json_encode(['suggestions' => [], 'message' => '候補検索を現在利用できません。手入力で検索できます。'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            $suggestions = $this->apify->searchDestinationSuggestions($query);
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
