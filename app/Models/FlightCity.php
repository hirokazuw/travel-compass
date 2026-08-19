<?php

namespace App\Models;

use PDO;

final class FlightCity
{
    private const BUILTIN_CITIES = [
        'ホノルル' => ['iata' => 'HNL', 'code_type' => 'airport'],
        'グアム' => ['iata' => 'GUM', 'code_type' => 'airport'],
        'ソウル' => ['iata' => 'SEL', 'code_type' => 'metropolitan', 'airports' => ['ICN', 'GMP']],
        '台北' => ['iata' => 'TPE', 'code_type' => 'airport'],
        '香港' => ['iata' => 'HKG', 'code_type' => 'airport'],
        'バンコク' => ['iata' => 'BKK', 'code_type' => 'metropolitan', 'airports' => ['BKK', 'DMK']],
        'シンガポール' => ['iata' => 'SIN', 'code_type' => 'airport'],
        'マニラ' => ['iata' => 'MNL', 'code_type' => 'airport'],
        'セブ' => ['iata' => 'CEB', 'code_type' => 'airport'],
        'ロサンゼルス' => ['iata' => 'LAX', 'code_type' => 'airport'],
        'ニューヨーク' => ['iata' => 'NYC', 'code_type' => 'metropolitan', 'airports' => ['JFK', 'LGA', 'EWR']],
        'ロンドン' => ['iata' => 'LON', 'code_type' => 'metropolitan', 'airports' => ['LHR', 'LGW', 'LCY', 'LTN', 'STN']],
        'パリ' => ['iata' => 'PAR', 'code_type' => 'metropolitan', 'airports' => ['CDG', 'ORY']],
        'シドニー' => ['iata' => 'SYD', 'code_type' => 'airport'],
    ];
    private string|false|null $countryColumn = null;

    public function __construct(private PDO $db)
    {
    }

    /**
     * 都市・空港情報を取得
     *
     * @return array|null
     */
    public function find(string $city): ?array
    {
      $city = mb_convert_kana(
        trim($city),
        'asKV'
      );

      if ($city === '') {
        return null;
      }

      $countryColumn = $this->countryColumn();
      $countrySelect = $countryColumn === null
        ? ', NULL AS country'
        : ', `' . $countryColumn . '` AS country';

      $sql = "
                SELECT
                    city,
                    iata,
                    code_type,
                    airports
                    {$countrySelect}
                FROM iata_cities
                WHERE city = :city
                   OR UPPER(iata) = UPPER(:iata)
                   OR JSON_SEARCH(aliases, 'one', :alias) IS NOT NULL
                ORDER BY
                    CASE
                        WHEN UPPER(iata) = UPPER(:exact_iata) THEN 0
                        WHEN city = :exact_city THEN 1
                        ELSE 2
                    END,
                    id ASC
                LIMIT 1
            ";

      $stmt = $this->db->prepare($sql);

      $stmt->execute([
        ':city' => $city,
        ':iata' => $city,
        ':alias' => $city,
        ':exact_iata' => $city,
        ':exact_city' => $city,
      ]);

      $result = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($result === false) {
        if (isset(self::BUILTIN_CITIES[$city])) {
          return [
            'city' => $city,
            ...self::BUILTIN_CITIES[$city],
          ];
        }

        $iata = strtoupper($city);
        if (preg_match('/^[A-Z]{3}$/', $iata)) {
          return [
            'city' => $iata,
            'iata' => $iata,
            'code_type' => 'airport',
          ];
        }

        return null;
      }

      $result['iata'] = strtoupper($result['iata']);

      return $result;
    }
    /**
     * IATAコードだけ取得
     */
    public function code(string $city): ?string
    {
        $result = $this->find($city);

        if ($result === null) {
            return null;
        }

        return strtolower($result['iata']);
    }

    /**
     * Google Flights Actor用の空港コードを取得
     *
     * metropolitanは実空港コードをカンマ区切りで返し、
     * 通常のOTAリンクで使うcode()には影響させない。
     */
    public function flightSearchCode(string $city): ?string
    {
        $result = $this->find($city);

        if ($result === null) {
            return null;
        }

        $iata = strtoupper((string)$result['iata']);
        if (($result['code_type'] ?? '') !== 'metropolitan') {
            return $iata;
        }

        $airports = $this->airportCodes($result['airports'] ?? null);
        return $airports === [] ? $iata : implode(',', $airports);
    }

    /** @return list<array{iata: string, name: string}> */
    public function airportCandidates(string $city): array
    {
        $result = $this->find($city);
        if ($result === null) return [];

        $iata = strtoupper((string)$result['iata']);
        $codes = ($result['code_type'] ?? '') === 'metropolitan'
            ? $this->airportCodes($result['airports'] ?? null)
            : [$iata];
        if ($codes === []) $codes = [$iata];

        $name = trim((string)($result['city'] ?? $city));
        return array_map(
            static fn(string $code): array => ['iata' => $code, 'name' => $name !== '' ? $name : $code],
            $codes
        );
    }

    /** @return list<string> */
    private function airportCodes(mixed $value): array
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return [];
            }

            $decoded = json_decode($value, true);
            $value = is_array($decoded)
                ? $decoded
                : preg_split('/[\s,|]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        }

        if (!is_array($value)) {
            return [];
        }

        $codes = [];
        array_walk_recursive(
            $value,
            static function (mixed $airport, mixed $key) use (&$codes): void {
                foreach ([$key, $airport] as $candidate) {
                    $candidate = strtoupper(trim((string)$candidate));
                    if (preg_match('/^[A-Z]{3}$/', $candidate)) {
                        $codes[] = $candidate;
                    }
                }
            }
        );

        return array_values(array_unique($codes));
    }

    public function isDomestic(string $city): bool
    {
        $result = $this->find($city);
        if ($result === null || ($result['country'] ?? null) === null) {
            return false;
        }

        $country = mb_strtoupper(trim((string)$result['country']));
        return in_array($country, ['JP', 'JPN', 'JAPAN', '日本', '78'], true);
    }

    private function countryColumn(): ?string
    {
        if ($this->countryColumn !== null) {
            return $this->countryColumn === false ? null : $this->countryColumn;
        }

        try {
            $columns = $this->db->query('SHOW COLUMNS FROM iata_cities')->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Throwable) {
            $this->countryColumn = false;
            return null;
        }

        $lookup = array_change_key_case(array_combine($columns, $columns), CASE_LOWER);
        foreach (['country_code', 'iso_country', 'country_iso2', 'country', 'country_id'] as $candidate) {
            if (isset($lookup[$candidate])) {
                $this->countryColumn = $lookup[$candidate];
                return $this->countryColumn;
            }
        }

        $this->countryColumn = false;
        return null;
    }
    /**
     * Booking.com用の場所コード
     *
     * 例:
     * 東京 → TYO.CITY
     * 大阪 → OSA.CITY
     * 羽田 → HND.AIRPORT
     * 関西 → KIX.AIRPORT
     */
    public function bookingCode(string $city): ?string
    {
        $result = $this->find($city);

        if ($result === null) {
            return null;
        }

        $suffix = $result['code_type'] === 'metropolitan'
            ? '.CITY'
            : '.AIRPORT';

        return $result['iata'] . $suffix;
    }

    /**
     * AirTrip uses its own metropolitan codes for some Japanese cities.
     */
    public function airtripCode(string $city): ?string
    {
        $result = $this->find($city);
        if ($result === null) {
            return null;
        }

        $code = strtoupper((string)$result['iata']);
        if (($result['code_type'] ?? '') !== 'metropolitan') {
            return $code;
        }

        return [
            'TYO' => 'TKY',
            'OSA' => 'OSK',
            'NGO' => 'NGY',
            'FUK' => 'FKK',
        ][$code] ?? $code;
    }
}
