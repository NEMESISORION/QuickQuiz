<?php
// -------------------------------------------------------
// Supabase PostgreSQL connection
// Fill in your credentials from:
// Supabase Dashboard → Project Settings → Database
// -------------------------------------------------------

$DB_HOST = 'db.pnuijwzntgizsfmpkauz.supabase.co';
$DB_PORT = '5432';
$DB_NAME = 'postgres';
$DB_USER = 'postgres';
$DB_PASS = 'ThisIsNemesisDatabase343999'; // still need this          // the password you set when creating the project

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
