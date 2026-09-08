<?php

declare(strict_types=1);

namespace App\Services\FlightUrls;

interface FlightProviderUrlBuilder
{
    public function build(string $origin, string $destination, string $departure, string $return, int $travelers): string;
}
