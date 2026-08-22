<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class FerryCompany
{
    public function __construct(private PDO $db) {}

    /** @return array<int, array{id: int, name: string}> */
    public function suggestActive(string $query, int $limit = 8): array
    {
        $limit = max(1, min(20, $limit));
        $stmt = $this->db->prepare(
            "SELECT id, COALESCE(NULLIF(name_ja, ''), name) AS display_name
             FROM ferry_companies
             WHERE active = 1
               AND (name_ja LIKE ? ESCAPE '!' OR name LIKE ? ESCAPE '!')
             ORDER BY CASE WHEN name_ja LIKE ? ESCAPE '!' THEN 0 ELSE 1 END,
                      display_name, id
             LIMIT " . $limit
        );
        $contains = '%' . $this->escapeLike($query) . '%';
        $prefix = $this->escapeLike($query) . '%';
        $stmt->execute([$contains, $contains, $prefix]);
        return array_map(static fn(array $company): array => [
            'id' => (int)$company['id'],
            'name' => (string)$company['display_name'],
        ], $stmt->fetchAll());
    }

    public function findActiveById(int $companyId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, COALESCE(NULLIF(name_ja, ''), name) AS display_name
             FROM ferry_companies WHERE id = ? AND active = 1"
        );
        $stmt->execute([$companyId]);
        $company = $stmt->fetch();
        return $company ?: null;
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
