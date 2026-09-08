<?php

declare(strict_types=1);

namespace App\Services\FlightUrls;

use App\Models\FlightCity;

final class TravelistUrlBuilder implements FlightProviderUrlBuilder
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
        if ($from === null || $to === null) return 'https://travelist.jp/';

        $segments = [str_replace('-', '', $departure)];
        if ($return !== '') $segments[] = str_replace('-', '', $return);
        $segments[] = strtoupper($from);
        $segments[] = strtoupper($to);

        return 'https://travelist.jp/s/flights/' . implode('/', $segments) . '?' . FlightUrl::query([
            'mode' => 'flight',
            'adult_count' => $travelers,
            'child_count' => 0,
            'infant_count' => 0,
        ]);
    }

}
