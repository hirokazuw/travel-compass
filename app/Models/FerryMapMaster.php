<?php

declare(strict_types=1);

namespace App\Models;

/** Image positions are percentages, not geographic latitude/longitude. */
final class FerryMapMaster
{
    private array $regions;
    private array $ports;
    private array $prefectureRegions = [];

    public static function load(): self
    {
        return new self(json_decode(
            file_get_contents(dirname(__DIR__, 2) . '/database/ferry-map.json'),
            true, 512, JSON_THROW_ON_ERROR
        ));
    }

    public function __construct(array $data)
    {
        if (!is_array($data['regions'] ?? null) || !is_array($data['ports'] ?? null)
            || !isset($data['regions']['overseas'])) {
            throw new \InvalidArgumentException('Ferry map requires regions, ports and overseas fallback');
        }
        foreach ($data['regions'] as $id => $region) {
            if (!is_string($id) || !preg_match('/^[a-z]+$/D', $id) || !is_array($region)
                || !is_array($region['prefectures'] ?? null)) {
                throw new \InvalidArgumentException('Invalid ferry region');
            }
            self::position($region['center'] ?? null);
            foreach ($region['prefectures'] as $prefecture) {
                if (!is_string($prefecture) || trim($prefecture) === '' || isset($this->prefectureRegions[$prefecture])) {
                    throw new \InvalidArgumentException('Invalid or duplicate ferry prefecture');
                }
                $this->prefectureRegions[$prefecture] = $id;
            }
        }
        if ($data['regions']['overseas']['prefectures'] !== []) {
            throw new \InvalidArgumentException('Overseas must remain the unknown-prefecture fallback');
        }
        $names = $aliases = $priorities = [];
        foreach ($data['ports'] as $port) {
            if (!is_array($port) || !is_string($port['name'] ?? null) || trim($port['name']) === ''
                || isset($names[$port['name']]) || !is_string($port['region'] ?? null)
                || !isset($data['regions'][$port['region']]) || !is_int($port['priority'] ?? null)
                || $port['priority'] < 0 || isset($priorities[$port['priority']])
                || !is_array($port['aliases'] ?? null) || $port['aliases'] === []) {
                throw new \InvalidArgumentException('Invalid ferry port name, region, priority or aliases');
            }
            self::position($port['position'] ?? null);
            $names[$port['name']] = true;
            $priorities[$port['priority']] = true;
            foreach ($port['aliases'] as $alias) {
                if (!is_string($alias) || trim($alias) === '' || isset($aliases[$alias])) {
                    throw new \InvalidArgumentException('Invalid or duplicate ferry port alias');
                }
                $aliases[$alias] = true;
            }
        }
        $this->regions = $data['regions'];
        $this->ports = array_values($data['ports']);
        usort($this->ports, static fn(array $a, array $b): int => $a['priority'] <=> $b['priority']);
    }

    /** Canonical identity and declared region; aliases use explicit legacy substring priority. */
    public function match(string $name): ?array
    {
        foreach ($this->ports as $port) {
            foreach ($port['aliases'] as $alias) {
                if (str_contains($name, $alias)) return $port;
            }
        }
        return null;
    }

    public function project(string $name, string $prefecture): array
    {
        // Exact prefecture matching is intentional, including blanks and trailing whitespace.
        $region = $this->prefectureRegions[$prefecture] ?? 'overseas';
        $position = $this->regions[$region]['center'];
        if ($region !== 'overseas') {
            $port = $this->match($name);
            if ($port !== null) $position = $port['position'];
        }
        return ['name' => $name, 'region' => $region, 'x' => $position[0], 'y' => $position[1]];
    }

    private static function position(mixed $position): void
    {
        if (!is_array($position) || array_keys($position) !== [0, 1]) {
            throw new \InvalidArgumentException('Ferry position requires [x, y]');
        }
        foreach ($position as $value) {
            if ((!is_int($value) && !is_float($value)) || !is_finite((float)$value) || $value < 0 || $value > 100) {
                throw new \InvalidArgumentException('Ferry position must be within 0..100');
            }
        }
    }
}
