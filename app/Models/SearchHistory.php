<?php

namespace App\Models;

use PDO;

final class SearchHistory
{
    public function __construct(private PDO $db)
    {
    }

    public function createFlight(array $values, string $visitorId): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO flight_searches
                (visitor_id, origin, destination, departure_date, return_date, travelers)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $visitorId,
            $values['origin'],
            $values['destination'],
            $values['departure_date'],
            $values['return_date'] ?: null,
            $values['travelers'],
        ]);
    }

    public function createHotel(array $values, string $visitorId): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO hotel_searches
                (visitor_id, destination, check_in, check_out, adults, children, guests)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $adults = (int)$values['hotel_adults'];
        $children = (int)$values['hotel_children'];
        $stmt->execute([
            $visitorId,
            $values['hotel_destination'],
            $values['check_in_date'],
            $values['check_out_date'],
            $adults,
            $children,
            $adults + $children,
        ]);
    }

    public function recent(string $visitorId, int $limit = 6): array
    {
        $limit = max(1, $limit);
        $flightStmt = $this->db->prepare(
            'SELECT id, origin, destination, departure_date, return_date,
                    travelers, created_at, \'flight\' AS search_type
             FROM flight_searches
             WHERE visitor_id = ?
             ORDER BY created_at DESC, id DESC
             LIMIT ' . $limit
        );
        $flightStmt->execute([$visitorId]);
        $flightSearches = $flightStmt->fetchAll();

        $hotelStmt = $this->db->prepare(
            'SELECT id, destination, check_in, check_out, adults, children,
                    guests, created_at,
                    \'hotel\' AS search_type
             FROM hotel_searches
             WHERE visitor_id = ?
             ORDER BY created_at DESC, id DESC
             LIMIT ' . $limit
        );
        $hotelStmt->execute([$visitorId]);
        $hotelSearches = $hotelStmt->fetchAll();

        $recent = array_merge($flightSearches, $hotelSearches);
        usort($recent, static function (array $left, array $right): int {
            $byDate = strcmp((string)$right['created_at'], (string)$left['created_at']);
            if ($byDate !== 0) return $byDate;
            return (int)$right['id'] <=> (int)$left['id'];
        });

        return array_slice($recent, 0, $limit);
    }
}
