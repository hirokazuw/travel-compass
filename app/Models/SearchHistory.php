<?php

namespace App\Models;

use PDO;

final class SearchHistory
{
    public function __construct(private PDO $db)
    {
    }

    public function createFlight(array $values): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO flight_searches
                (origin, destination, departure_date, return_date, travelers)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $values['origin'],
            $values['destination'],
            $values['departure_date'],
            $values['return_date'] ?: null,
            $values['travelers'],
        ]);
    }

    public function createHotel(array $values): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO hotel_searches
                (destination, check_in, check_out, adults, children, guests)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $adults = (int)$values['hotel_adults'];
        $children = (int)$values['hotel_children'];
        $stmt->execute([
            $values['hotel_destination'],
            $values['check_in_date'],
            $values['check_out_date'],
            $adults,
            $children,
            $adults + $children,
        ]);
    }

    public function recent(int $limit = 6): array
    {
        $limit = max(1, $limit);
        $flightSearches = $this->db->query(
            'SELECT id, origin, destination, departure_date, return_date,
                    travelers, created_at, \'flight\' AS search_type
             FROM flight_searches
             ORDER BY created_at DESC, id DESC
             LIMIT ' . $limit
        )->fetchAll();
        $hotelSearches = $this->db->query(
            'SELECT id, destination, check_in, check_out, adults, children,
                    guests, created_at,
                    \'hotel\' AS search_type
             FROM hotel_searches
             ORDER BY created_at DESC, id DESC
             LIMIT ' . $limit
        )->fetchAll();

        $recent = array_merge($flightSearches, $hotelSearches);
        usort($recent, static function (array $left, array $right): int {
            $byDate = strcmp((string)$right['created_at'], (string)$left['created_at']);
            if ($byDate !== 0) return $byDate;
            return (int)$right['id'] <=> (int)$left['id'];
        });

        return array_slice($recent, 0, $limit);
    }
}
