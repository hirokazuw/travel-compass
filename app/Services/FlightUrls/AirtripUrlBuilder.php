<?php

declare(strict_types=1);

namespace App\Services\FlightUrls;

use App\Models\FlightCity;

final class AirtripUrlBuilder implements FlightProviderUrlBuilder
{
    public function __construct(private FlightCity $cities) {}

    public function build(string $origin, string $destination, string $departure, string $return, int $travelers): string
    {
        return FlightUrl::validate($this->url($origin, $destination, $departure, $return, $travelers));
    }
    private function url(string $origin, string $destination, string $departure, string $return, int $travelers): string
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
        return 'https://www.airtrip.jp/ticket/search?' . FlightUrl::query($params);
    }

}
