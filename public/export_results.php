<?php
session_start();
require_once __DIR__.'/../src/db.php';

// Only admins can export
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$quizId = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;

// Get results with user info
if ($quizId > 0) {
    // Export results for specific quiz
    $stmt = $pdo->prepare("SELECT q.title as quiz_title FROM quizzes q WHERE q.id = ?");
    $stmt->execute([$quizId]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$quiz) {
        die("Quiz not found");
    }
    
    $stmt = $pdo->prepare(
        "SELECT u.username, u.email, r.score, r.total_points, r.correct_count, r.wrong_count, 
                r.time_taken, r.passed, r.created_at
         FROM results r
         JOIN users u ON r.user_id = u.id
         WHERE r.quiz_id = ?
         ORDER BY r.created_at DESC"
    );
    $stmt->execute([$quizId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $filename = 'quiz_results_' . preg_replace('/[^a-z0-9]/i', '_', $quiz['quiz_title']) . '_' . date('Y-m-d') . '.csv';
    
} else {
    // Export all results
    $stmt = $pdo->query(
        "SELECT q.title as quiz_title, u.username, u.email, r.score, r.total_points, 
                r.correct_count, r.wrong_count, r.time_taken, r.passed, r.created_at
         FROM results r
         JOIN users u ON r.user_id = u.id
         JOIN quizzes q ON r.quiz_id = q.id
         ORDER BY r.created_at DESC"
    );
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $filename = 'all_quiz_results_' . date('Y-m-d') . '.csv';
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header row
if ($quizId > 0) {
    fputcsv($output, ['Username', 'Email', 'Score', 'Total Points', 'Correct', 'Wrong', 'Time (sec)', 'Passed', 'Date']);
} else {
    fputcsv($output, ['Quiz', 'Username', 'Email', 'Score', 'Total Points', 'Correct', 'Wrong', 'Time (sec)', 'Passed', 'Date']);
}

// Write data rows
foreach ($results as $row) {
    $passedText = $row['passed'] === null ? 'N/A' : ($row['passed'] ? 'Yes' : 'No');
    
    if ($quizId > 0) {
        fputcsv($output, [
            $row['username'],
            $row['email'],
            $row['score'] ?? 0,
            $row['total_points'] ?? 0,
            $row['correct_count'] ?? 0,
            $row['wrong_count'] ?? 0,
            $row['time_taken'] ?? 0,
            $passedText,
            $row['created_at']
        ]);
    } else {
        fputcsv($output, [
            $row['quiz_title'],
            $row['username'],
            $row['email'],
            $row['score'] ?? 0,
            $row['total_points'] ?? 0,
            $row['correct_count'] ?? 0,
            $row['wrong_count'] ?? 0,
            $row['time_taken'] ?? 0,
            $passedText,
            $row['created_at']
        ]);
    }
}

fclose($output);
exit;
