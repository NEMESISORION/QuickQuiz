<?php
session_start();
require_once __DIR__.'/../src/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = $_SESSION['user_id'];

// Get current mode
$stmt = $pdo->prepare("SELECT dark_mode FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$currentMode = isset($user['dark_mode']) ? (int)$user['dark_mode'] : 0;
$newMode = $currentMode ? 0 : 1;

// Update
$stmt = $pdo->prepare("UPDATE users SET dark_mode = ? WHERE id = ?");
$stmt->execute([$newMode, $userId]);

echo json_encode(['success' => true, 'darkMode' => $newMode]);
