<?php
/**
 * QuickQuiz - Shared Functions
 * Centralized helper functions for the application
 */

// Prevent direct access
if (!defined('QUICKQUIZ_LOADED')) {
    define('QUICKQUIZ_LOADED', true);
}

/**
 * Get user's dark mode preference
 */
function getUserDarkMode($pdo, $userId) {
    static $cache = [];
    if (isset($cache[$userId])) {
        return $cache[$userId];
    }
    
    $stmt = $pdo->prepare("SELECT dark_mode FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $cache[$userId] = isset($result['dark_mode']) && $result['dark_mode'];
    return $cache[$userId];
}

/**
 * Toggle user's dark mode and return new state
 */
function toggleDarkMode($pdo, $userId) {
    $currentMode = getUserDarkMode($pdo, $userId);
    $newMode = $currentMode ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE users SET dark_mode = ? WHERE id = ?");
    $stmt->execute([$newMode, $userId]);
    return $newMode;
}

/**
 * Sanitize output for HTML
 */
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format date safely
 */
function formatDate($date, $format = 'M j, Y') {
    if (empty($date)) return 'N/A';
    try {
        return date($format, strtotime($date));
    } catch (Exception $e) {
        return 'N/A';
    }
}

/**
 * Format date with time
 */
function formatDateTime($date, $format = 'M j, Y g:i A') {
    return formatDate($date, $format);
}

/**
 * Check if user is logged in with specific role
 */
function requireAuth($role = null) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
    if ($role !== null && $_SESSION['role'] !== $role) {
        header("Location: login.php");
        exit;
    }
}

/**
 * Get quiz count for admin
 */
function getAdminQuizCount($pdo, $adminId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM quizzes WHERE created_by = ?");
    $stmt->execute([$adminId]);
    return $stmt->fetchColumn();
}

/**
 * Get student count for admin's quizzes
 */
function getAdminStudentCount($pdo, $adminId) {
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT r.user_id) 
        FROM results r 
        JOIN quizzes q ON r.quiz_id = q.id 
        WHERE q.created_by = ?
    ");
    $stmt->execute([$adminId]);
    return $stmt->fetchColumn();
}

/**
 * Get attempt count for admin's quizzes
 */
function getAdminAttemptCount($pdo, $adminId) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM results r 
        JOIN quizzes q ON r.quiz_id = q.id 
        WHERE q.created_by = ?
    ");
    $stmt->execute([$adminId]);
    return $stmt->fetchColumn();
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Safe redirect
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * JSON response helper
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get base URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . dirname($_SERVER['PHP_SELF']);
}
