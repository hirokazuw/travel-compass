<?php

namespace App\Controllers;

use App\Models\TravelSearch;
use App\Models\FlightCity;
use App\Services\FlightSearchService;
use App\Services\RakutenTravelService;
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
        private TravelLinkBuilder $travelLinks,
        private array $config
    ) {}

    public function index(): void
    {
        $errors = [];
        $result = null;
        $flightOffers = [];
        $flightOffersStatus = 'idle';
        $isDomesticFlight = false;
        $activeFlightScope = 'domestic';
        $hotelErrors = [];
        $activeTab = 'flight';
        $activeHotelProvider = 'rakuten';
        $activeHotelScope = 'domestic';
        $rakutenErrors = [];
        $rakutenHotels = [];
        $rakutenStatus = 'idle';
        $rakutenValues = [
            'rakuten_destination' => '',
            'rakuten_check_in' => '',
            'rakuten_check_out' => '',
            'rakuten_adults' => '2',
            'rakuten_children' => '0',
        ];

        $hotelValues = [
            'hotel_destination' => '',
            'check_in_date' => '',
            'check_out_date' => '',
            'hotel_adults' => '2',
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
            $activeFlightScope = (string)($_POST['flight_scope'] ?? 'domestic') === 'overseas'
                ? 'overseas'
                : 'domestic';

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
            }
        }

        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            (string)($_POST['search_type'] ?? '') === 'hotel'
        ) {
            $activeTab = 'hotel';
            $activeHotelScope = 'domestic';

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
                if ($this->rakutenTravel->isConfigured()) {
                    try {
                        $rakutenHotels = $this->rakutenTravel->search(
                            $destination,
                            $hotelValues['check_in_date'],
                            $hotelValues['check_out_date'],
                            (int)$adults,
                            (int)$children
                        );
                        $activeHotelProvider = 'rakuten';
                        $rakutenStatus = $rakutenHotels ? 'success' : 'empty';
                        $rakutenValues = [
                            'rakuten_destination' => $destination,
                            'rakuten_check_in' => $hotelValues['check_in_date'],
                            'rakuten_check_out' => $hotelValues['check_out_date'],
                            'rakuten_adults' => (string)$adults,
                            'rakuten_children' => (string)$children,
                        ];
                    } catch (\Throwable $e) {
                        error_log('Rakuten hotel search: ' . $e->getMessage());
                        $rakutenStatus = 'error';
                    }
                } else {
                    $rakutenStatus = 'not_configured';
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
                'hotel_adults' => trim((string)($_POST['hotel_adults'] ?? '2')),
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
        $appVersion = $this->config['app']['version'] ?? '1.4.0';
        $publicPath = dirname(__DIR__, 2) . '/public/assets/';
        $cssVersion = (string)(filemtime($publicPath . 'app.css') ?: $appVersion);
        $jsVersion = (string)(filemtime($publicPath . 'app.js') ?: $appVersion);
        $flightOffersMessage = SearchViewData::flightMessage($flightOffersStatus);
        $rakutenMessage = SearchViewData::rakutenMessage($rakutenStatus);
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


}
