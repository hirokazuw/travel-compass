<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class RakutenTravelService
{
    private const KEYWORD_URL = 'https://openapi.rakuten.co.jp/engine/api/Travel/KeywordHotelSearch/20260731';
    private const VACANT_URL = 'https://openapi.rakuten.co.jp/engine/api/Travel/VacantHotelSearch/20170426';

    public function __construct(private array $config) {}

    public function isConfigured(): bool
    {
        return trim((string)($this->config['application_id'] ?? '')) !== ''
            && trim((string)($this->config['access_key'] ?? '')) !== ''
            && trim((string)($this->config['referer'] ?? '')) !== '';
    }

    public function search(string $destination, string $checkIn, string $checkOut, int $adults, int $children): array
    {
        if (!$this->isConfigured()) return [];

        $keywordResponse = $this->request(self::KEYWORD_URL, [
            'keyword' => $destination,
            'hits' => 30,
            'page' => 1,
            'searchField' => 0,
            'hotelThumbnailSize' => 3,
            'responseType' => 'large',
            'sort' => 'standard',
        ]);
        $keywordHotels = $this->hotelRecords($keywordResponse);
        $hotelNumbers = array_values(array_slice(array_filter(array_map(
            static fn(array $hotel): int => (int)($hotel['hotelNo'] ?? 0),
            $keywordHotels
        )), 0, 15));

        $vacantByHotel = [];
        if ($hotelNumbers !== []) {
            try {
                $vacantResponse = $this->request(self::VACANT_URL, [
                    'hotelNo' => implode(',', $hotelNumbers),
                    'checkinDate' => $checkIn,
                    'checkoutDate' => $checkOut,
                    'adultNum' => $adults,
                    // 年齢入力がないため、子供人数は小学生低学年として検索する。
                    'lowClassNum' => $children,
                    'roomNum' => 1,
                    'hits' => 30,
                    'searchPattern' => 0,
                    'hotelThumbnailSize' => 3,
                    'responseType' => 'large',
                    'sort' => 'standard',
                ]);
                foreach ($this->hotelRecords($vacantResponse) as $hotel) {
                    $hotelNo = (int)($hotel['hotelNo'] ?? 0);
                    if ($hotelNo > 0) $vacantByHotel[$hotelNo] = $hotel;
                }
            } catch (\Throwable $e) {
                error_log('Rakuten vacant hotel search: ' . $e->getMessage());
            }
        }

        $hotels = [];
        foreach ($keywordHotels as $hotel) {
            $hotelNo = (int)($hotel['hotelNo'] ?? 0);
            $hotels[] = $this->normalize(
                array_replace($hotel, $vacantByHotel[$hotelNo] ?? []),
                $checkIn,
                $checkOut,
                $adults,
                $children
            );
        }
        return array_values(array_filter($hotels, static fn(array $hotel): bool => $hotel['name'] !== ''));
    }

    private function request(string $url, array $params): array
    {
        if (!function_exists('curl_init')) throw new RuntimeException('PHP cURL extension is required.');
        $referer = trim((string)$this->config['referer']);
        $refererParts = parse_url($referer);
        $origin = isset($refererParts['scheme'], $refererParts['host'])
            ? $refererParts['scheme'] . '://' . $refererParts['host'] . (isset($refererParts['port']) ? ':' . $refererParts['port'] : '')
            : rtrim($referer, '/');
        $params = array_merge([
            'applicationId' => $this->config['application_id'],
            'format' => 'json',
            'formatVersion' => 2,
        ], $params);
        $affiliateId = trim((string)($this->config['affiliate_id'] ?? ''));
        if ($affiliateId !== '') $params['affiliateId'] = $affiliateId;

        $curl = curl_init($url . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986));
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'accessKey: ' . $this->config['access_key'],
                'Referer: ' . $referer,
                'Origin: ' . $origin,
                'User-Agent: Mozilla/5.0 (compatible; TravelCompass/1.0; +https://hirokazu-watabe.jp/travel/)',
            ],
        ]);
        $raw = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if ($raw === false || $status < 200 || $status >= 300) {
            $errorResponse = is_string($raw) ? json_decode($raw, true) : null;
            $apiMessage = is_array($errorResponse)
                ? trim((string)($errorResponse['errors']['errorMessage'] ?? $errorResponse['error_description'] ?? $errorResponse['error'] ?? ''))
                : '';
            throw new RuntimeException('Rakuten Travel API failed: HTTP ' . $status . ($apiMessage !== '' ? ' ' . $apiMessage : ' ' . $error));
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) throw new RuntimeException('Rakuten Travel API returned invalid JSON.');
        if (isset($decoded['error'])) throw new RuntimeException('Rakuten Travel API: ' . (string)($decoded['error_description'] ?? $decoded['error']));
        return $decoded;
    }

    private function hotelRecords(array $response): array
    {
        $records = [];
        foreach ((array)($response['hotels'] ?? []) as $entry) {
            $record = [];
            $sections = $entry['hotel'] ?? $entry;
            if (!is_array($sections)) continue;
            if (!array_is_list($sections)) $sections = [$sections];
            foreach ($sections as $section) {
                if (!is_array($section)) continue;
                foreach ($section as $value) {
                    if (is_array($value)) $record = array_replace($record, $value);
                }
                $record = array_replace($record, array_filter($section, static fn(mixed $value): bool => !is_array($value)));
            }
            if ($record !== []) $records[] = $record;
        }
        return $records;
    }

    private function normalize(array $hotel, string $checkIn, string $checkOut, int $adults, int $children): array
    {
        $hotelNo = (int)($hotel['hotelNo'] ?? 0);
        $url = $this->httpsUrl((string)($hotel['planListUrl'] ?? '')) ?: $this->httpsUrl((string)($hotel['hotelInformationUrl'] ?? ''));
        if ($url !== '') {
            $url = $this->withStayConditions($url, $checkIn, $checkOut, $adults, $children);
        }
        $facilities = $this->stringList($hotel['hotelFacilities'] ?? []);
        return [
            'hotel_no' => $hotelNo,
            'booking_links' => $this->bookingLinks(
                trim((string)($hotel['hotelName'] ?? '')),
                $checkIn,
                $checkOut,
                $adults
            ),
            'name' => trim((string)($hotel['hotelName'] ?? '')),
            'image' => $this->httpsUrl((string)($hotel['hotelImageUrl'] ?? $hotel['hotelThumbnailUrl'] ?? '')),
            'rating' => isset($hotel['reviewAverage']) && is_numeric($hotel['reviewAverage']) ? number_format((float)$hotel['reviewAverage'], 1) : '',
            'reviews' => max(0, (int)($hotel['reviewCount'] ?? 0)),
            'address' => trim((string)($hotel['address1'] ?? '') . (string)($hotel['address2'] ?? '')),
            'access' => trim((string)($hotel['access'] ?? '')),
            'description' => trim((string)($hotel['hotelSpecial'] ?? '')),
            'facilities' => array_slice($facilities, 0, 5),
            'price' => max(0, (int)($hotel['hotelMinCharge'] ?? 0)),
            'url' => $url ?: 'https://travel.rakuten.co.jp/',
        ];
    }

    private function bookingLinks(string $hotelName, string $checkIn, string $checkOut, int $adults): array
    {
        if ($hotelName === '') return [];

        $jalanKeyword = rawurlencode(mb_convert_encoding($hotelName, 'SJIS-win', 'UTF-8'));
        $utf8Keyword = rawurlencode($hotelName);
        return [
            'jalan' => 'https://www.jalan.net/uw/uwp2011/uww2011init.do?keyword=' . $jalanKeyword,
            'yahoo' => 'https://travel.yahoo.co.jp/search?kwd=' . $utf8Keyword,
            'ikyu' => 'https://www.ikyu.com/search?kwd=' . $utf8Keyword,
            'expedia' => 'https://www.expedia.co.jp/Hotel-Search?' . http_build_query([
                'destination' => $hotelName,
                'd1' => $checkIn,
                'startDate' => $checkIn,
                'd2' => $checkOut,
                'endDate' => $checkOut,
                'adults' => max(1, $adults),
                'rooms' => 1,
                'sort' => 'RECOMMENDED',
            ], '', '&', PHP_QUERY_RFC3986),
        ];
    }

    private function stringList(mixed $value): array
    {
        $items = [];
        $values = (array)$value;
        array_walk_recursive($values, static function (mixed $item) use (&$items): void {
            if (is_scalar($item) && trim((string)$item) !== '') $items[] = trim((string)$item);
        });
        return array_values(array_unique($items));
    }

    private function httpsUrl(string $url): string
    {
        return filter_var($url, FILTER_VALIDATE_URL) && str_starts_with($url, 'https://') ? $url : '';
    }

    private function withStayConditions(string $url, string $checkIn, string $checkOut, int $adults, int $children): string
    {
        $arrival = new \DateTimeImmutable($checkIn);
        $departure = new \DateTimeImmutable($checkOut);
        $stayParams = [
            'f_nen1' => $arrival->format('Y'),
            'f_tuki1' => $arrival->format('n'),
            'f_hi1' => $arrival->format('j'),
            'f_nen2' => $departure->format('Y'),
            'f_tuki2' => $departure->format('n'),
            'f_hi2' => $departure->format('j'),
            'f_heya_su' => 1,
            'f_otona_su' => $adults,
            'f_s1' => $children,
        ];

        $parts = parse_url($url);
        if (!is_array($parts)) return $url;
        parse_str((string)($parts['query'] ?? ''), $outerQuery);

        // Affiliate URLs store the actual Rakuten Travel URL in the pc parameter.
        if (isset($outerQuery['pc']) && is_string($outerQuery['pc']) && filter_var($outerQuery['pc'], FILTER_VALIDATE_URL)) {
            $outerQuery['pc'] = $this->appendQuery($outerQuery['pc'], $stayParams);
            return $this->buildUrl($parts, $outerQuery);
        }

        return $this->appendQuery($url, $stayParams);
    }

    private function appendQuery(string $url, array $params): string
    {
        $parts = parse_url($url);
        if (!is_array($parts)) return $url;
        parse_str((string)($parts['query'] ?? ''), $query);
        return $this->buildUrl($parts, array_replace($query, $params));
    }

    private function buildUrl(array $parts, array $query): string
    {
        $authority = ($parts['scheme'] ?? 'https') . '://';
        if (isset($parts['user'])) {
            $authority .= $parts['user'] . (isset($parts['pass']) ? ':' . $parts['pass'] : '') . '@';
        }
        $authority .= $parts['host'] ?? '';
        if (isset($parts['port'])) $authority .= ':' . $parts['port'];
        $url = $authority . ($parts['path'] ?? '');
        if ($query !== []) $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        if (isset($parts['fragment'])) $url .= '#' . $parts['fragment'];
        return $url;
    }
}
