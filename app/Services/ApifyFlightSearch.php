<?php
declare(strict_types=1);
namespace App\Services;
final class ApifyFlightSearch
{
    public function __construct(private ApifyClient $client, private ApiCache $cache, private ApifyResponseNormalizer $normalizer) {}
    public function isConfigured(): bool { return $this->client->isConfigured(); }
    public function search(string $from, string $to, string $outbound, string $return, int $adults): array
    {
        $input = ['departure_id'=>strtoupper($from),'arrival_id'=>strtoupper($to),'outbound_date'=>$outbound,'adults'=>max(1,$adults),'travel_class'=>1,'hl'=>'ja','gl'=>'jp','currency'=>'JPY','max_pages'=>$this->client->maxPages(),'fetch_booking_options'=>false];
        if ($return !== '') $input['return_date'] = $return;
        return $this->normalizer->normalizeFlights($this->cache->remember($input, fn(): array => $this->client->run('flights_url', $input)));
    }
}
