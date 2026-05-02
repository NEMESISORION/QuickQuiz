<?php
$dbDir = __DIR__ . '/../data';
$dbPath = $dbDir . '/quickquiz.sqlite';
$schemaPath = __DIR__ . '/../sql/schema_sqlite.sql';

if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

$isNewDb = !file_exists($dbPath);

try {
    $pdo = new PDO(
        'sqlite:' . $dbPath,
        null,
        null,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec('PRAGMA foreign_keys = ON');

    $hasUsersTable = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")
        ->fetchColumn();

    if ($isNewDb || !$hasUsersTable) {
        if (!file_exists($schemaPath)) {
            throw new RuntimeException('SQLite schema missing: ' . $schemaPath);
        }
        $schemaSql = file_get_contents($schemaPath);
        if ($schemaSql === false) {
            throw new RuntimeException('Failed to read SQLite schema: ' . $schemaPath);
        }
        $pdo->exec($schemaSql);
    }
} catch (Exception $e) {
    die('Database connection failed: ' . $e->getMessage());
}
?>
