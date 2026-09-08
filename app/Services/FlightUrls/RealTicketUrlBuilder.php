<?php

declare(strict_types=1);

namespace App\Services\FlightUrls;

use App\Models\FlightCity;

final class RealTicketUrlBuilder implements FlightProviderUrlBuilder
{
    public function __construct(private FlightCity $cities) {}

    public function build(string $origin, string $destination, string $departure, string $return, int $travelers): string
    {
        return FlightUrl::validate($this->url($origin, $destination, $departure, $return, $travelers));
    }
    private function url(string $origin, string $destination, string $departure, string $return, int $travelers): string
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

        return 'https://rt.travelwest.jp/search.php?' . FlightUrl::query($params);
    }

}
