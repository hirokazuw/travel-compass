<?php

declare(strict_types=1);

namespace App\Services\FlightUrls;

use App\Models\FlightCity;

final class AgodaUrlBuilder implements FlightProviderUrlBuilder
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
        return 'https://www.agoda.com/ja-jp/flights/results?' . FlightUrl::query($params);
    }

}
