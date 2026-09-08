<?php

declare(strict_types=1);

namespace App\Services\FlightUrls;

use App\Models\FlightCity;

final class SkyTicketUrlBuilder implements FlightProviderUrlBuilder
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
        $query = FlightUrl::query($params);
        $query = preg_replace('/dep_date%5B\d+%5D=/', 'dep_date%5B%5D=', $query) ?? $query;
        return 'https://skyticket.jp/international-flights/ia_fare_result_mix.php?' . $query;
    }

}
