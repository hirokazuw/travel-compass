<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FerryRoute;

final class FerryMapService
{
    private const REGION_PREFECTURES = [
        'hokkaido' => ['北海道'],
        'tohoku' => ['青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県'],
        'kanto' => ['茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県'],
        'hokuriku' => ['新潟県', '富山県', '石川県', '福井県'],
        'tokai' => ['山梨県', '長野県', '岐阜県', '静岡県', '愛知県', '三重県'],
        'kinki' => ['滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県'],
        'chugoku' => ['鳥取県', '島根県', '岡山県', '広島県', '山口県'],
        'shikoku' => ['徳島県', '香川県', '愛媛県', '高知県'],
        'kyushu' => ['福岡県', '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県'],
        'okinawa' => ['沖縄県'],
    ];

    private const REGION_CENTERS = [
        'hokkaido' => [70, 21], 'tohoku' => [65, 42], 'kanto' => [60, 65],
        'hokuriku' => [51, 56], 'tokai' => [51, 68], 'kinki' => [41, 71],
        'chugoku' => [29, 69], 'shikoku' => [33, 76], 'kyushu' => [20, 82],
        'okinawa' => [78, 85], 'overseas' => [8, 23],
    ];

    private const PORT_COORDINATES = [
        '苫小牧東' => [75, 25], '苫小牧' => [72, 26], '小樽' => [68, 22], '函館' => [67, 30],
        '稚内' => [70, 15], '鴛泊' => [65, 16], '香深' => [63, 14], '室蘭' => [70, 28],
        '青森' => [64, 36], '大間' => [66, 33], '仙台' => [67, 46],
        '大洗' => [64, 57], '東京' => [60, 62], '横須賀' => [58, 65],
        '新潟' => [53, 49], '敦賀' => [46, 61], '舞鶴' => [42, 60],
        '名古屋' => [52, 69], '大阪港国際' => [42, 71], '大阪南港' => [41, 72], '大阪港' => [41, 71],
        '神戸' => [39, 71], '泉大津' => [41, 74], '新門司' => [26, 71], '下関' => [27, 68],
        '徳島' => [39, 76], '高松' => [36, 73], '坂手' => [38, 72], '東予' => [32, 76],
        '別府' => [25, 77], '大分' => [27, 78], '宮崎' => [23, 84], '志布志' => [22, 88],
        '鹿児島' => [18, 88], '博多' => [22, 71], '那覇' => [78, 84], '石垣' => [74, 88],
    ];

    public function __construct(
        private FerryRoute $routes,
        private FerrySearchService $presenter
    ) {}

    public function data(): array
    {
        $routes = [];
        foreach ($this->routes->findAllActiveForMap() as $route) {
            $departure = $this->port((string)$route['departure_port'], (string)($route['departure_prefecture'] ?? ''));
            $arrival = $this->port((string)$route['arrival_port'], (string)($route['arrival_prefecture'] ?? ''));
            $routes[] = array_merge($this->presenter->presentRoute($route), [
                'id' => (int)$route['id'],
                'company_id' => (int)$route['company_id'],
                'departure' => $departure,
                'arrival' => $arrival,
                'label' => $departure['name'] . ' → ' . $arrival['name'],
            ]);
        }
        return ['routes' => $routes];
    }

    private function port(string $name, string $prefecture): array
    {
        $region = $this->region($prefecture);
        $coordinates = self::REGION_CENTERS[$region];
        if ($region === 'overseas') {
            return ['name' => $name, 'region' => $region, 'x' => $coordinates[0], 'y' => $coordinates[1]];
        }
        foreach (self::PORT_COORDINATES as $needle => $position) {
            if (str_contains($name, $needle)) {
                $coordinates = $position;
                break;
            }
        }
        return ['name' => $name, 'region' => $region, 'x' => $coordinates[0], 'y' => $coordinates[1]];
    }

    private function region(string $prefecture): string
    {
        foreach (self::REGION_PREFECTURES as $region => $prefectures) {
            if (in_array($prefecture, $prefectures, true)) return $region;
        }
        return 'overseas';
    }
}
