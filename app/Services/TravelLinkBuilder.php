<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FlightCity;

final class TravelLinkBuilder
{
    public function __construct(
        private FlightCity $cities,
        private array $config
    ) {
    }

    public function buildFlightLinks(
        string $origin,
        string $destination,
        string $departure,
        string $return,
        int $travelers,
        bool $domestic
    ): array {
        $links = [
            'maps' => 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($destination),
            'hotel' => $this->tripHotel($destination, $departure, $return, $travelers),
            'flight' => $this->tripFlight($origin, $destination, $departure, $return, $travelers),
            'bookingcom' => $this->booking($origin, $destination, $departure, $return, $travelers),
            'expedia' => $this->expedia($origin, $destination, $departure, $return, $travelers),
            'agoda' => $this->agoda($origin, $destination, $departure, $return, $travelers),
            'ena' => $this->ena($origin, $destination, $departure, $return, $travelers),
        ];

        if ($domestic) {
            $links['sakura'] = $this->sakura($origin, $destination, $departure, $return, $travelers);
            $links['airtrip'] = $this->airtrip($origin, $destination, $departure, $return, $travelers);
        }

        return $links;
    }

    private function tripHotel(string $destination, string $checkIn, string $checkOut, int $travelers): string
    {
        $cities = ['福岡' => ['city_id' => 248, 'country_id' => 78]];
        $city = $cities[mb_convert_kana(trim($destination), 'asKV')] ?? null;
        if ($city === null || $checkOut === '') {
            return $this->affiliateUrl('hotel_url');
        }

        return 'https://jp.trip.com/hotels/list?' . $this->query([
            'city' => $city['city_id'], 'provinceId' => 0, 'countryId' => $city['country_id'],
            'checkIn' => $checkIn, 'checkOut' => $checkOut, 'lat' => 0, 'lon' => 0,
            'districtId' => 0, 'barCurr' => 'JPY', 'searchType' => 'CT', 'searchValue' => '___',
            'crn' => 1, 'adult' => $travelers, 'children' => 0, 'searchBoxArg' => 't',
            'travelPurpose' => 0, 'domestic' => 'false',
            'Allianceid' => $this->config['trip']['alliance_id'] ?? '',
            'SID' => $this->config['trip']['sid'] ?? '',
        ]);
    }

    private function tripFlight(string $origin, string $destination, string $departure, string $return, int $travelers): string
    {
        $from = $this->cities->code($origin);
        $to = $this->cities->code($destination);
        if ($from === null || $to === null) return $this->affiliateUrl('flight_url');

        $params = [
            'dcity' => $from, 'acity' => $to, 'ddate' => $departure,
            'triptype' => $return !== '' ? 'rt' : 'ow', 'class' => 'y',
            'lowpricesource' => 'searchform', 'quantity' => $travelers,
            'searchboxarg' => 't', 'nonstoponly' => 'off', 'locale' => 'ja-JP', 'curr' => 'JPY',
            'Allianceid' => $this->config['trip']['alliance_id'] ?? '',
            'SID' => $this->config['trip']['sid'] ?? '',
        ];
        if ($return !== '') $params['rdate'] = $return;

        return ($this->config['trip']['flight_search_url'] ?? 'https://jp.trip.com/flights/showfarefirst')
            . '?' . $this->query($params);
    }

    private function airtrip(string $origin, string $destination, string $departure, string $return, int $travelers): string
    {
        $from = $this->cities->airtripCode($origin);
        $to = $this->cities->airtripCode($destination);
        if ($from === null || $to === null) return 'https://www.airtrip.jp/';
        [$year, $month, $day] = explode('-', $departure);
        $params = ['search_type' => 3, 'F1Departure' => $from, 'F1Destination' => $to,
            'F1Year' => $year, 'F1Month' => (string)(int)$month, 'F1Day' => (string)(int)$day,
            'valueAdultNum' => $travelers, 'valueChildNum' => 0, 'adult' => $travelers, 'child' => 0];
        if ($return !== '') {
            [$year, $month, $day] = explode('-', $return);
            $params += ['trip_way' => 'round_trip', 'F2Departure' => $to, 'F2Destination' => $from,
                'F2Year' => $year, 'F2Month' => (string)(int)$month, 'F2Day' => (string)(int)$day];
        }
        return 'https://www.airtrip.jp/ticket/search?' . $this->query($params);
    }

    private function sakura(string $origin, string $destination, string $departure, string $return, int $travelers): string
    {
        $from = $this->cities->code($origin); $to = $this->cities->code($destination);
        if ($from === null || $to === null) return 'https://www.sakuratravel.jp/';
        [$year, $month, $day] = explode('-', $departure);
        $params = ['search-ticket-type' => $return !== '' ? 'round-trip' : 'one-way',
            's_from' => strtoupper($from), 's_to' => strtoupper($to), 's_year' => $year,
            's_month' => (string)(int)$month, 's_day' => (string)(int)$day, 's_adult' => $travelers,
            's_child' => 0, 's_infant2' => 0, 's_infant' => 0, 'pc_screen_flg' => 4];
        if ($return !== '') {
            [$year, $month, $day] = explode('-', $return);
            $params += ['s_year2' => $year, 's_month2' => (string)(int)$month, 's_day2' => (string)(int)$day];
        }
        return 'https://www.sakuratravel.jp/search/search.php?' . $this->query($params);
    }

    private function expedia(string $origin, string $destination, string $departure, string $return, int $travelers): string
    {
        $from = $this->cities->code($origin); $to = $this->cities->code($destination);
        if ($from === null || $to === null) return 'https://www.expedia.co.jp/air';
        $params = ['load' => 1, 'FromAirport' => strtoupper($from), 'ToAirport' => strtoupper($to),
            'FromTime' => 362, 'NumAdult' => $travelers, 'NumChild' => 0];
        if ($return !== '') $params['ToTime'] = 362;
        return 'https://www.expedia.co.jp/go/flight/search/' . ($return !== '' ? 'Roundtrip' : 'oneway')
            . '/' . rawurlencode($departure) . '/' . rawurlencode($return !== '' ? $return : $departure)
            . '?' . $this->query($params);
    }

    private function agoda(string $origin, string $destination, string $departure, string $return, int $travelers): string
    {
        $from = $this->cities->code($origin); $to = $this->cities->code($destination);
        if ($from === null || $to === null) return 'https://www.agoda.com/ja-jp/flights';
        $params = ['departureFrom' => strtoupper($from), 'departureFromType' => 1, 'departDate' => $departure,
            'arrivalTo' => strtoupper($to), 'arrivalToType' => 1, 'adults' => $travelers,
            'children' => 0, 'infants' => 0, 'searchType' => $return !== '' ? 2 : 1, 'cabinType' => 4];
        if ($return !== '') $params['returnDate'] = $return;
        return 'https://www.agoda.com/ja-jp/flights/results?' . $this->query($params);
    }

    private function ena(string $origin, string $destination, string $departure, string $return, int $travelers): string
    {
        $from = $this->cities->code($origin); $to = $this->cities->code($destination);
        if ($from === null || $to === null) return 'https://www.ena.travel/';
        $from = strtoupper($from); $to = strtoupper($to);
        $routes = [$from . '-' . $to . '-' . str_replace('-', '', $departure) . '-nonselected'];
        if ($return !== '') $routes[] = $to . '-' . $from . '-' . str_replace('-', '', $return) . '-nonselected';
        return 'https://www.ena.travel/airsearch?' . $this->query([
            'route' => implode('|', $routes), 'seatClass' => 'economy', 'airline' => '-',
            'adt' => $travelers, 'chd' => 0, 'age' => '', 'seat' => '']);
    }

    private function booking(string $origin, string $destination, string $departure, string $return, int $travelers): string
    {
        $from = $this->cities->bookingCode($origin); $to = $this->cities->bookingCode($destination);
        if ($from === null || $to === null) return 'https://flights.booking.com/';
        $params = ['type' => $return !== '' ? 'ROUNDTRIP' : 'ONEWAY', 'adults' => $travelers,
            'cabinClass' => 'ECONOMY', 'children' => '', 'from' => $from, 'to' => $to,
            'fromLocationName' => $origin, 'toLocationName' => $destination, 'depart' => $departure,
            'sort' => 'BEST', 'travelPurpose' => 'leisure', 'aid' => '2311236'];
        if ($return !== '') $params['return'] = $return;
        return 'https://flights.booking.com/flights/' . $from . '-' . $to . '/?' . $this->query($params);
    }

    private function query(array $params): string
    {
        return http_build_query(array_filter($params, static fn($value) => $value !== ''), '', '&', PHP_QUERY_RFC3986);
    }

    private function affiliateUrl(string $key): string
    {
        $url = $this->config['affiliate'][$key] ?? '';
        return filter_var($url, FILTER_VALIDATE_URL) && str_starts_with($url, 'https://')
            ? $url : 'https://www.trip.com/';
    }
}
