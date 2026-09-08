<?php

declare(strict_types=1);

namespace App\Services\Normalizers;

final class HotelResponseNormalizer
{
    public function normalizeHotels(array $items): array
    {
        $properties = [];
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            if (is_array($item['properties'] ?? null)) {
                array_push($properties, ...$item['properties']);
            } elseif (isset($item['name'])) {
                $properties[] = $item;
            }
        }

        $hotels = [];
        foreach ($properties as $hotel) {
            if (!is_array($hotel)) continue;
            $name = trim((string)($hotel['name'] ?? ''));
            if ($name === '') continue;
            $images = [];
            foreach ((array)($hotel['images'] ?? []) as $image) {
                $url = is_array($image) ? (string)($image['original_image'] ?? '') : (string)$image;
                if (($url = NormalizedValue::httpsUrl($url)) !== '') $images[] = $url;
            }
            $hotels[] = [
                'name' => $name,
                'description' => trim((string)($hotel['description'] ?? '')),
                'official_url' => NormalizedValue::httpsUrl((string)($hotel['link'] ?? '')),
                'property_token' => trim((string)($hotel['property_token'] ?? '')),
                'google_place_id' => trim((string)($hotel['place_id'] ?? $hotel['placeId'] ?? '')),
                'address' => trim((string)($hotel['address'] ?? '')),
                'latitude' => $this->coordinate($hotel['gps_coordinates'] ?? [], 'latitude'),
                'longitude' => $this->coordinate($hotel['gps_coordinates'] ?? [], 'longitude'),
                'hotel_class' => $this->nullableText($hotel['hotel_class'] ?? $hotel['extracted_hotel_class'] ?? null),
                'rating' => isset($hotel['overall_rating']) && is_numeric($hotel['overall_rating']) ? (float)$hotel['overall_rating'] : null,
                'reviews' => isset($hotel['reviews']) && is_numeric($hotel['reviews']) ? max(0, (int)$hotel['reviews']) : null,
                'price_per_night' => ($price = $this->rateValue($hotel['rate_per_night'] ?? 0)) > 0 ? $price : null,
                'total_price' => ($total = $this->rateValue($hotel['total_rate'] ?? 0)) > 0 ? $total : null,
                'check_in_time' => $this->nullableText($hotel['check_in_time'] ?? null),
                'check_out_time' => $this->nullableText($hotel['check_out_time'] ?? null),
                'image_urls' => array_values(array_unique($images)),
                'amenities' => array_values(array_filter(array_map('strval', (array)($hotel['amenities'] ?? [])))),
                'deal' => $hotel['deal'] ?? null,
            ];
        }
        return $hotels;
    }

    private function nullableText(mixed $value): ?string
    {
        $value = trim((string)$value);
        return $value !== '' ? $value : null;
    }

    private function coordinate(mixed $coordinates, string $axis): ?float
    {
        if (!is_array($coordinates)) return null;
        $aliases = $axis === 'latitude' ? ['latitude', 'lat'] : ['longitude', 'lng', 'lon'];
        foreach ($aliases as $key) {
            if (isset($coordinates[$key]) && is_numeric($coordinates[$key])) return (float)$coordinates[$key];
        }
        return null;
    }

    private function rateValue(mixed $rate): int
    {
        if (!is_array($rate)) return NormalizedValue::priceValue($rate);
        foreach (['extracted_lowest', 'extracted_price', 'extracted', 'extracted_value', 'lowest', 'price', 'value', 'amount'] as $key) {
            if (!array_key_exists($key, $rate)) continue;
            $value = $this->rateValue($rate[$key]);
            if ($value > 0) return $value;
        }
        return 0;
    }

}
