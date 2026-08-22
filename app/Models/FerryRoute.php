<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class FerryRoute
{
    public function __construct(private PDO $db) {}

    /** @return array<int, array{id: int, label: string}> */
    public function findActiveOptionsByCompany(int $companyId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, departure_port, arrival_port
             FROM ferry_routes
             WHERE company_id = ? AND active = 1
             ORDER BY departure_port, arrival_port, id"
        );
        $stmt->execute([$companyId]);
        return array_map(static fn(array $route): array => [
            'id' => (int)$route['id'],
            'label' => (string)$route['departure_port'] . ' → ' . (string)$route['arrival_port'],
        ], $stmt->fetchAll());
    }

    public function findActiveByIdAndCompany(int $routeId, int $companyId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT r.id, r.route_name, r.departure_port, r.departure_prefecture,
                    r.arrival_port, r.arrival_prefecture, r.duration_minutes,
                    r.fare_from, r.fare_currency, r.fare_updated_at,
                    r.vehicle_available, r.overnight,
                    r.reservation_url AS route_reservation_url,
                    c.name AS company_name, c.name_ja AS company_name_ja,
                    c.logo_url AS company_logo_url,
                    c.reservation_url AS company_reservation_url,
                    c.official_url AS company_official_url
             FROM ferry_routes r
             INNER JOIN ferry_companies c ON c.id = r.company_id
             WHERE r.active = 1 AND c.active = 1
               AND r.id = ? AND r.company_id = ?"
        );
        $stmt->execute([$routeId, $companyId]);
        $route = $stmt->fetch();
        return $route ?: null;
    }

    /** @return array<int, array<string, mixed>> */
    public function findAllActiveForMap(): array
    {
        $stmt = $this->db->query(
            "SELECT r.id, r.company_id, r.route_name, r.departure_port, r.departure_prefecture,
                    r.arrival_port, r.arrival_prefecture, r.duration_minutes,
                    r.fare_from, r.fare_currency, r.fare_updated_at,
                    r.vehicle_available, r.overnight,
                    r.reservation_url AS route_reservation_url,
                    c.name AS company_name, c.name_ja AS company_name_ja,
                    c.reservation_url AS company_reservation_url,
                    c.official_url AS company_official_url
             FROM ferry_routes r
             INNER JOIN ferry_companies c ON c.id = r.company_id
             WHERE r.active = 1 AND c.active = 1
             ORDER BY r.departure_port, r.arrival_port, r.id"
        );
        return $stmt->fetchAll();
    }
}
