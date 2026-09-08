<?php

declare(strict_types=1);

namespace App\Services\FlightUrls;

use App\Models\FlightCity;

final class SkyGateUrlBuilder implements FlightProviderUrlBuilder
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

        return 'https://www.skygate.co.jp/air/list?' . FlightUrl::query($params);
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

}
