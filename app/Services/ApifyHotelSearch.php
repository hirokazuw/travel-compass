<?php
declare(strict_types=1);
namespace App\Services;
final class ApifyHotelSearch
{
    public function __construct(private ApifyClient $client, private ApiCache $cache, private ApifyResponseNormalizer $normalizer) {}
    public function isConfigured(): bool { return $this->client->isConfigured(); }
    public function search(string $destination, string $checkIn, string $checkOut, int $adults, int $children): array
    {
        $input=['search_type'=>'search','q'=>$destination,'check_in_date'=>$checkIn,'check_out_date'=>$checkOut,'adults'=>max(1,$adults),'children'=>max(0,$children),'hl'=>'ja','gl'=>'jp','currency'=>'JPY','max_pages'=>$this->client->maxPages()];
        if ($children > 0) $input['children_ages']=implode(',',array_fill(0,$children,'8'));
        return $this->normalizer->normalizeHotels($this->cache->remember($input, fn(): array => $this->client->run('hotels_url',$input)));
    }
}
