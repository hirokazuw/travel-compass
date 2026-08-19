<?php
declare(strict_types=1);
namespace App\Services;
final class ApifyDestinationSearch
{
    public function __construct(private ApifyClient $client, private ApiCache $cache, private ApifyResponseNormalizer $normalizer) {}
    public function isConfigured(): bool { return $this->client->isConfigured(); }
    public function search(string $query): array
    {
        $input=['keyword'=>$query,'language'=>'ja'];
        return array_slice($this->normalizer->normalizeDestinationSuggestions($this->cache->remember(['source'=>'destination-suggestions',...$input], fn(): array => $this->client->run('places_url',$input))),0,$this->client->maxPlaceSuggestions());
    }
}
