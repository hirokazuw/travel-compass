<?php

declare(strict_types=1);

namespace App\Services\FlightUrls;

use App\Models\FlightCity;

final class ExpediaUrlBuilder implements FlightProviderUrlBuilder
{
    public function __construct(private FlightCity $cities) {}

    public function build(string $origin, string $destination, string $departure, string $return, int $travelers): string
    {
        return FlightUrl::validate($this->url($origin, $destination, $departure, $return, $travelers));
    }
    private function url(string $origin, string $destination, string $departure, string $return, int $travelers): string
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

        return 'https://www.expedia.co.jp/Flights-Search?' . FlightUrl::query($params);
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

}
