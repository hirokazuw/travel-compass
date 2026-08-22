<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Airline;

final class FlightOfferAggregator
{
    public function __construct(private Airline $airlines) {}

    /** @return array<int, array<string, mixed>> */
    public function byAirline(array $offers): array
    {
        $master = $this->airlines->findActiveByIataCodes(array_column($offers, 'carrier_code'));
        $groups = [];

        foreach ($offers as $offer) {
            $code = strtoupper(trim((string)($offer['carrier_code'] ?? '')));
            // Keep an identifiable fallback group if an Actor result has no flight number/code.
            $key = $code !== '' ? $code : 'name:' . strtolower(trim((string)($offer['carrier_name'] ?? '')));
            $price = $this->priceValue($offer['price'] ?? 0);
            if ($price <= 0) continue;

            if (!isset($groups[$key])) {
                $airline = $master[$code] ?? [];
                $groups[$key] = [
                    'carrier_code' => $code,
                    'carrier_name' => trim((string)($airline['name'] ?? $offer['carrier_name'] ?? '航空会社')),
                    'airline_logo' => (string)($offer['airline_logo'] ?? ''),
                    'price' => number_format($price),
                    'currency' => (string)($offer['currency'] ?? 'JPY'),
                    '_price' => $price,
                    'flight_count' => 0,
                    'direct_flight_count' => 0,
                    'alliance' => $this->allianceName((string)($airline['alliance'] ?? '')),
                    'ffp_name' => trim((string)($airline['ffp_name'] ?? '')),
                    'official_url' => $this->webUrl((string)($airline['official_url'] ?? '')),
                ];
            }

            $groups[$key]['flight_count']++;
            if ((int)($offer['stops'] ?? 0) === 0) $groups[$key]['direct_flight_count']++;
            if ($price < $groups[$key]['_price']) {
                $groups[$key]['_price'] = $price;
                $groups[$key]['price'] = number_format($price);
                $groups[$key]['currency'] = (string)($offer['currency'] ?? 'JPY');
            }
        }

        $groups = array_values($groups);
        usort($groups, static fn(array $left, array $right): int => $left['_price'] <=> $right['_price']);
        return array_map(static function (array $group): array {
            unset($group['_price']);
            return $group;
        }, $groups);
    }

    private function priceValue(mixed $price): int
    {
        if (is_numeric($price)) return max(0, (int)round((float)$price));
        $digits = preg_replace('/[^0-9.]/', '', (string)$price);
        return $digits !== '' ? max(0, (int)round((float)$digits)) : 0;
    }

    private function allianceName(string $alliance): string
    {
        $alliance = strtolower(trim($alliance));
        return match ($alliance) {
            'ow', 'oneworld' => 'oneworld',
            'sa', 'star alliance' => 'Star Alliance',
            'st', 'skyteam' => 'SkyTeam',
            default => trim($alliance),
        };
    }

    private function webUrl(string $url): string
    {
        $url = trim($url);
        return filter_var($url, FILTER_VALIDATE_URL)
            && in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
            ? $url : '';
    }
}
