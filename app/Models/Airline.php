<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Airline
{
    public function __construct(private PDO $db) {}

    /** @return array<string, array<string, mixed>> Airlines keyed by uppercase IATA code. */
    public function findActiveByIataCodes(array $codes): array
    {
        $codes = array_values(array_unique(array_filter(array_map(
            static fn(mixed $code): string => strtoupper(trim((string)$code)),
            $codes
        ), static fn(string $code): bool => preg_match('/^[A-Z0-9]{2,3}$/', $code) === 1)));
        if ($codes === []) return [];

        $placeholders = implode(', ', array_fill(0, count($codes), '?'));
        $stmt = $this->db->prepare(
            'SELECT iata_code, icao_code, name, callsign, alliance, ffp_name,
                    ffp_currency, credits_json, official_url
             FROM airlines
             WHERE active = 1 AND UPPER(iata_code) IN (' . $placeholders . ')'
        );
        $stmt->execute($codes);

        $airlines = [];
        foreach ($stmt->fetchAll() as $airline) {
            $airlines[strtoupper((string)$airline['iata_code'])] = $airline;
        }
        return $airlines;
    }
}
