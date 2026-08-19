<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FlightCity;

final class FlightUrlBuilder
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
            'flight' => $this->tripFlight($origin, $destination, $departure, $return, $travelers),
            'bookingcom' => $this->booking($origin, $destination, $departure, $return, $travelers),
            'expedia' => $this->expedia($origin, $destination, $departure, $return, $travelers),
            'agoda' => $this->agoda($origin, $destination, $departure, $return, $travelers),
            'ena' => $this->ena($origin, $destination, $departure, $return, $travelers),
        ];

        if ($domestic) {
            $links['airtrip'] = $this->airtrip($origin, $destination, $departure, $return, $travelers);
            $links['travelist'] = $this->travelist($origin, $destination, $departure, $return, $travelers);
            $links['realticket'] = $this->realTicket($origin, $destination, $departure, $return, $travelers);
        } else {
            $links['jtb'] = $this->jtb($origin, $destination, $departure, $return, $travelers);
            $links['skyticket'] = $this->skyTicketInternational($origin, $destination, $departure, $return, $travelers);
            $links['skygate'] = $this->skyGate($origin, $destination, $departure, $return, $travelers);
        }

        return $links;
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

    private function travelist(string $origin, string $destination, string $departure, string $return, int $travelers): string
    {
        $from = $this->cities->code($origin);
        $to = $this->cities->code($destination);
        if ($from === null || $to === null) return 'https://travelist.jp/';

        $segments = [str_replace('-', '', $departure)];
        if ($return !== '') $segments[] = str_replace('-', '', $return);
        $segments[] = strtoupper($from);
        $segments[] = strtoupper($to);

        return 'https://travelist.jp/s/flights/' . implode('/', $segments) . '?' . $this->query([
            'mode' => 'flight',
            'adult_count' => $travelers,
            'child_count' => 0,
            'infant_count' => 0,
        ]);
    }

    private function realTicket(string $origin, string $destination, string $departure, string $return, int $travelers): string
    {
        $from = $this->cities->code($origin);
        $to = $this->cities->code($destination);
        if ($from === null || $to === null) return 'https://rt.travelwest.jp/';

        [, $month, $day] = explode('-', $departure);
        $params = [
            's_month' => $month,
            's_day' => $day,
            's_adult' => $travelers,
            's_child' => 0,
            's_infant' => 0,
            's_from' => strtoupper($from),
            's_to' => strtoupper($to),
        ];

        if ($return !== '') {
            [, $returnMonth, $returnDay] = explode('-', $return);
            $params += [
                'way' => 2,
                's_month2' => $returnMonth,
                's_day2' => $returnDay,
                'hdn_mode_select' => 'round-trip',
            ];
        } else {
            $params += [
                'tenplate_no' => 2,
                's_infant2' => 0,
            ];
        }

        return 'https://rt.travelwest.jp/search.php?' . $this->query($params);
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
        $fromLocation = $this->cities->find($origin);
        $toLocation = $this->cities->find($destination);
        if ($fromLocation === null || $toLocation === null) return 'https://www.expedia.co.jp/Flights';

        $from = strtoupper((string)$fromLocation['iata']);
        $to = strtoupper((string)$toLocation['iata']);
        $fromType = ($fromLocation['code_type'] ?? '') === 'metropolitan' ? 'METROCODE' : 'AIRPORT';
        $toType = ($toLocation['code_type'] ?? '') === 'metropolitan' ? 'METROCODE' : 'AIRPORT';
        if ($from === 'SEL') {
            $from = 'ICN';
            $fromType = 'AIRPORT';
        }
        if ($to === 'SEL') {
            $to = 'ICN';
            $toType = 'AIRPORT';
        }
        $fromLabel = $this->expediaLocationLabel($origin, $from, $fromType);
        $toLabel = $this->expediaLocationLabel($destination, $to, $toType);
        $departureForLeg = $this->expediaDate($departure);
        $params = [
            'flight-type' => 'on',
            'mode' => 'search',
            'trip' => $return !== '' ? 'roundtrip' : 'oneway',
            'leg1' => "from:{$fromLabel},to:{$toLabel},departure:{$departureForLeg}TANYT,fromType:{$fromType},toType:{$toType}",
            'options' => 'cabinclass:economy',
            'fromDate' => $departureForLeg,
            'd1' => $departure,
            'passengers' => 'adults:' . max(1, $travelers) . ',infantinlap:N',
        ];
        if ($return !== '') {
            $returnForLeg = $this->expediaDate($return);
            $params += [
                'leg2' => "from:{$toLabel},to:{$fromLabel},departure:{$returnForLeg}TANYT,fromType:{$toType},toType:{$fromType}",
                'toDate' => $returnForLeg,
                'd2' => $return,
            ];
        }

        return 'https://www.expedia.co.jp/Flights-Search?' . $this->query($params);
    }

    private function expediaDate(string $date): string
    {
        [$year, $month, $day] = explode('-', $date);
        return $year . '/' . (int)$month . '/' . (int)$day;
    }

    private function expediaLocationLabel(string $city, string $code, string $type): string
    {
        if ($code === 'ICN') return $city . ', 韓国 (ICN-仁川国際空港)';
        $country = $this->cities->isDomestic($city) ? ', 日本' : '';
        return $city . $country . ' (' . $code . ($type === 'METROCODE' ? '-すべての空港' : '') . ')';
    }

    private function agoda(string $origin, string $destination, string $departure, string $return, int $travelers): string
    {
        $fromLocation = $this->cities->find($origin);
        $toLocation = $this->cities->find($destination);
        if ($fromLocation === null || $toLocation === null) return 'https://www.agoda.com/ja-jp/flights';
        $params = [
            'departureFrom' => strtoupper((string)$fromLocation['iata']),
            'departureFromType' => ($fromLocation['code_type'] ?? '') === 'metropolitan' ? 0 : 1,
            'arrivalTo' => strtoupper((string)$toLocation['iata']),
            'arrivalToType' => ($toLocation['code_type'] ?? '') === 'metropolitan' ? 0 : 1,
            'departDate' => $departure,
            'adults' => max(1, $travelers),
            'searchType' => $return !== '' ? 2 : 1,
            'cabinType' => 'Economy',
            'sort' => 8,
        ];
        if ($return !== '') $params['returnDate'] = $return;
        return 'https://www.agoda.com/ja-jp/flights/results?' . $this->query($params);
    }

    private function jtb(string $origin, string $destination, string $departure, string $return, int $travelers): string
    {
        $from = $this->cities->code($origin);
        $to = $this->cities->code($destination);
        if ($from === null || $to === null) return 'https://www.jtb.co.jp/ovs_air/';
        $params = [
            'trvlType' => $return !== '' ? 1 : 0,
            'deptDt1' => str_replace('-', '', $departure),
            'deptCd1' => strtoupper($from),
            'deptCtyCd1' => '',
            'arvlCd1' => strtoupper($to),
            'arvlCtyCd1' => '',
            'totalNumAdlt' => max(1, $travelers),
            'totalNumChld' => 0,
            'totalNumIns' => 0,
            'totalNumInf' => 0,
            'nonstopFltSpecifiedFlg' => 0,
            'cabinCls' => 0,
            'alnc' => 0,
        ];
        if ($return !== '') {
            $params += [
                'deptDt2' => str_replace('-', '', $return),
                'deptCd2' => strtoupper($to),
                'deptCtyCd2' => '',
                'arvlCd2' => strtoupper($from),
                'arvlCtyCd2' => '',
            ];
        }
        for ($leg = $return !== '' ? 3 : 2; $leg <= 6; $leg++) {
            $params += [
                'deptDt' . $leg => '',
                'deptCd' . $leg => '',
                'deptCtyCd' . $leg => '',
                'arvlCd' . $leg => '',
                'arvlCtyCd' . $leg => '',
            ];
        }
        $params['caCd'] = '';
        return 'https://www.jtb.co.jp/ovs_air/search/search_result/?'
            . $this->query($params);
    }

    private function skyTicketInternational(string $origin, string $destination, string $departure, string $return, int $travelers): string
    {
        $from = $this->cities->code($origin);
        $to = $this->cities->code($destination);
        if ($from === null || $to === null) return 'https://skyticket.jp/international-flights/';
        $from = strtoupper($from);
        $to = strtoupper($to);
        $params = [
            'trip_type' => $return !== '' ? 2 : 1,
            'dep_port_name0' => $origin . '(' . $from . ')',
            'dep_port0' => $from,
            'arr_port_name0' => $destination . '(' . $to . ')',
            'arr_port0' => $to,
            'dep_date' => [$departure],
            'cabin_class' => 'Y',
            'adt_pax' => max(1, $travelers),
            'chd_pax' => 0,
            'inf_pax' => 0,
        ];
        if ($return !== '') {
            $params += [
                'dep_port_name1' => $destination . '(' . $to . ')',
                'dep_port1' => $to,
                'arr_port_name1' => $origin . '(' . $from . ')',
                'arr_port1' => $from,
            ];
            $params['dep_date'][] = $return;
        }
        $query = $this->query($params);
        $query = preg_replace('/dep_date%5B\d+%5D=/', 'dep_date%5B%5D=', $query) ?? $query;
        return 'https://skyticket.jp/international-flights/ia_fare_result_mix.php?' . $query;
    }

    private function skyGate(string $origin, string $destination, string $departure, string $return, int $travelers): string
    {
        $from = $this->cities->code($origin);
        $to = $this->cities->code($destination);
        if ($from === null || $to === null) return 'https://www.skygate.co.jp/';

        $from = strtoupper($from);
        $to = strtoupper($to);
        $params = [
            'searchKind' => $return !== '' ? 0 : 1,
            'fromDate' => str_replace('-', '/', $departure),
            'departure' => $from,
            'destinations' => $to,
            'adultNum' => max(1, $travelers),
            'AgentCode' => 'SGTOP',
            'business' => 0,
            'seatClass' => 'Y',
            'order' => 2,
            'disableMix' => 0,
            'searchWait' => 1,
            'serviceWorkerKey' => $this->uuidV4(),
            'isResearch' => 1,
        ];
        if ($return !== '') {
            $params += [
                'arrival' => $from,
                'toDates' => str_replace('-', '/', $return),
            ];
        }

        return 'https://www.skygate.co.jp/air/list?' . $this->query($params);
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return substr($hex, 0, 8) . '-'
            . substr($hex, 8, 4) . '-'
            . substr($hex, 12, 4) . '-'
            . substr($hex, 16, 4) . '-'
            . substr($hex, 20);
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
