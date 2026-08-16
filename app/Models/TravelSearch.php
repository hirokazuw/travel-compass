<?php

namespace App\Models;

use PDO;

final class TravelSearch
{
    public function __construct(private PDO $db)
    {
    }

    public function create(array $values): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO travel_searches
                (
                    origin,
                    destination,
                    departure_date,
                    return_date,
                    travelers
                )
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

    public function recent(): array
    {
        return $this->db
            ->query(
                'SELECT *
                 FROM travel_searches
                 ORDER BY id DESC
                 LIMIT 6'
            )
            ->fetchAll();
    }
}