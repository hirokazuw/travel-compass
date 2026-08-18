<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Airline
{
    private const DISPLAY_OVERRIDES = [
        'JL' => '日本航空（JAL）',
        'NH' => '全日本空輸（ANA）',
        'BC' => 'スカイマーク',
        'MM' => 'Peach Aviation',
        'GK' => 'ジェットスター・ジャパン',
        '6J' => 'ソラシドエア',
        '7G' => 'スターフライヤー',
        'HD' => 'AIRDO',
        'FW' => 'IBEXエアラインズ',
        'OC' => 'オリエンタルエアブリッジ',
        'MZ' => '天草エアライン',
        'NU' => '日本トランスオーシャン航空',
        'JH' => 'フジドリームエアラインズ',
        'IJ' => 'SPRING JAPAN',
        'NQ' => 'AirJapan',
        'ZG' => 'ZIPAIR',
    ];

    private array $names = [];
    private ?array $columns = null;

    public function __construct(private PDO $db) {}

    public function displayName(string $iata): string
    {
        $iata = strtoupper(trim($iata));
        if ($iata === '') return '航空会社';
        if (array_key_exists($iata, $this->names)) return $this->names[$iata] ?? $iata;

        try {
            $columns = $this->columns();
            $nameColumns = array_values(array_intersect(
                ['name_ja', 'name_jp', 'japanese_name', 'display_name', 'name_en', 'name'],
                $columns
            ));
            $iataColumn = in_array('iata_code', $columns, true) ? 'iata_code' : 'iata';
            if (!in_array($iataColumn, $columns, true) || $nameColumns === []) {
                throw new \RuntimeException('Required airline master columns are missing.');
            }

            $select = implode(', ', array_map(static fn(string $column): string => '`' . $column . '`', $nameColumns));
            $activeCondition = in_array('active', $columns, true) ? ' AND active = 1' : '';
            $order = [];
            foreach (['name_ja', 'name_jp', 'japanese_name'] as $japaneseColumn) {
                if (in_array($japaneseColumn, $columns, true)) {
                    $order[] = "CASE WHEN `{$japaneseColumn}` IS NOT NULL AND TRIM(`{$japaneseColumn}`) <> '' AND `{$japaneseColumn}` <> '\\\\N' THEN 0 ELSE 1 END";
                }
            }
            if (in_array('name', $columns, true)) $order[] = "CASE WHEN name LIKE '%Cargo%' THEN 1 ELSE 0 END";
            if (in_array('id', $columns, true)) $order[] = 'id ASC';
            $orderBy = $order ? ' ORDER BY ' . implode(', ', $order) : '';

            $statement = $this->db->prepare(
                'SELECT ' . $select
                . ' FROM airlines WHERE UPPER(`' . $iataColumn . '`) = :iata'
                . $activeCondition . $orderBy . ' LIMIT 1'
            );
            $statement->execute([':iata' => $iata]);
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            $name = '';
            if (is_array($row)) {
                foreach (['name_ja', 'name_jp', 'japanese_name'] as $column) {
                    $candidate = trim((string)($row[$column] ?? ''));
                    if ($candidate !== '' && $candidate !== '\\N') {
                        $name = $candidate;
                        break;
                    }
                }
                if ($name === '' && isset(self::DISPLAY_OVERRIDES[$iata])) {
                    $name = self::DISPLAY_OVERRIDES[$iata];
                }
                if ($name === '') {
                    foreach (['display_name', 'name_en', 'name'] as $column) {
                        $candidate = trim((string)($row[$column] ?? ''));
                        if ($candidate !== '' && $candidate !== '\\N') {
                            $name = $candidate;
                            break;
                        }
                    }
                }
            }
            if ($name === '') $name = self::DISPLAY_OVERRIDES[$iata] ?? '';
            $this->names[$iata] = $name !== '' ? $name : null;
        } catch (\Throwable $e) {
            error_log('Airline master lookup: ' . $e->getMessage());
            $this->names[$iata] = self::DISPLAY_OVERRIDES[$iata] ?? null;
        }

        return $this->names[$iata] ?? $iata;
    }

    private function columns(): array
    {
        if ($this->columns !== null) return $this->columns;
        $columns = $this->db->query('SHOW COLUMNS FROM airlines')->fetchAll(PDO::FETCH_COLUMN);
        $this->columns = array_map(static fn(mixed $column): string => strtolower((string)$column), $columns);
        return $this->columns;
    }
}
