<?php

declare(strict_types=1);

$dsn = getenv('TEST_DB_DSN') ?: '';
if ($dsn === '') {
    echo "SKIP MySQL baseline test: TEST_DB_DSN is not set.\n";
    exit(0);
}

$db = new PDO(
    $dsn,
    getenv('TEST_DB_USER') ?: '',
    getenv('TEST_DB_PASSWORD') ?: '',
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$expectedRows = [
    'flight_searches' => 0,
    'hotel_searches' => 0,
    'iata_cities' => 1147,
    'airlines' => 160,
    'ferry_companies' => 25,
    'ferry_routes' => 55,
];

foreach ($expectedRows as $table => $expected) {
    $quoted = '`' . str_replace('`', '``', $table) . '`';
    $actual = (int)$db->query("SELECT COUNT(*) FROM {$quoted}")->fetchColumn();
    if ($actual !== $expected) {
        fwrite(STDERR, "FAIL {$table}: expected {$expected} rows, got {$actual}\n");
        exit(1);
    }
    echo "PASS {$table}: {$actual} rows\n";
}

$foreignKey = $db->query(
    "SELECT COUNT(*)
       FROM information_schema.REFERENTIAL_CONSTRAINTS
      WHERE CONSTRAINT_SCHEMA = DATABASE()
        AND TABLE_NAME = 'ferry_routes'
        AND REFERENCED_TABLE_NAME = 'ferry_companies'"
)->fetchColumn();
if ((int)$foreignKey !== 1) {
    fwrite(STDERR, "FAIL ferry route foreign key is missing\n");
    exit(1);
}

echo "MySQL baseline contract OK\n";
