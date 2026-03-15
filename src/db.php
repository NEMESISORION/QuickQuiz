<?php
// Supabase Session Pooler (IPv4 compatible)
$DB_HOST = 'aws-1-eu-west-1.pooler.supabase.com';
$DB_PORT = '5432';
$DB_NAME = 'postgres';
$DB_USER = 'postgres.pnuijwzntgizsfmpkauz';
$DB_PASS = 'ThisIsNemesisDatabase343999';

try {
    $pdo = new PDO(
        "pgsql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
