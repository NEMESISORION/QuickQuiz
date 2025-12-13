<?php
/**
 * Database Migrations
 * Run all column additions for new features
 * Optimized to check session/cache before running
 */

function runMigrations($pdo) {
    // Check if migrations have already run this session
    if (isset($_SESSION['migrations_run']) && $_SESSION['migrations_run'] === true) {
        return;
    }
    
    // Check if migrations table exists, if so check version
    try {
        $result = $pdo->query("SELECT version FROM migrations LIMIT 1");
        $currentVersion = $result->fetchColumn();
        if ($currentVersion >= 3) { // Current migration version
            $_SESSION['migrations_run'] = true;
            return;
        }
    } catch (PDOException $e) {
        // Migrations table doesn't exist, create it
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (version INT DEFAULT 0)");
            $pdo->exec("INSERT INTO migrations (version) VALUES (0)");
        } catch (PDOException $e2) {
            // Ignore if already exists
        }
    }
    
    $migrations = [
        // Users table - dark mode & tracking
        "ALTER TABLE users ADD COLUMN dark_mode TINYINT(1) DEFAULT 0",
        "ALTER TABLE users ADD COLUMN created_by_admin INT DEFAULT NULL",
        
        // Quizzes table - new features
        "ALTER TABLE quizzes ADD COLUMN randomize_questions TINYINT(1) DEFAULT 0",
        "ALTER TABLE quizzes ADD COLUMN show_answers TINYINT(1) DEFAULT 1",
        "ALTER TABLE quizzes ADD COLUMN pass_percentage INT DEFAULT 0",
        "ALTER TABLE quizzes ADD COLUMN negative_marking DECIMAL(3,2) DEFAULT 0",
        "ALTER TABLE quizzes ADD COLUMN start_date DATETIME DEFAULT NULL",
        "ALTER TABLE quizzes ADD COLUMN end_date DATETIME DEFAULT NULL",
        "ALTER TABLE quizzes ADD COLUMN certificate_enabled TINYINT(1) DEFAULT 0",
        "ALTER TABLE quizzes ADD COLUMN time_limit INT DEFAULT 0",
        "ALTER TABLE quizzes ADD COLUMN created_by INT DEFAULT NULL",
        "ALTER TABLE quizzes ADD COLUMN is_published TINYINT(1) DEFAULT 0 COMMENT '0=Draft, 1=Published'",
        
        // Questions table - points
        "ALTER TABLE questions ADD COLUMN points INT DEFAULT 1",
        "ALTER TABLE questions ADD COLUMN time_limit INT DEFAULT 60",
        
        // Results table - detailed tracking
        "ALTER TABLE results ADD COLUMN total_points INT NOT NULL DEFAULT 0",
        "ALTER TABLE results ADD COLUMN correct_count INT NOT NULL DEFAULT 0",
        "ALTER TABLE results ADD COLUMN wrong_count INT NOT NULL DEFAULT 0",
        "ALTER TABLE results ADD COLUMN time_taken INT DEFAULT 0",
        "ALTER TABLE results ADD COLUMN passed TINYINT(1) DEFAULT NULL",
        "ALTER TABLE results ADD COLUMN answers_json TEXT",
    ];
    
    foreach ($migrations as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // Column likely already exists, ignore
        }
    }
    
    // Update migration version
    try {
        $pdo->exec("UPDATE migrations SET version = 3");
    } catch (PDOException $e) {
        // Ignore
    }
    
    $_SESSION['migrations_run'] = true;
}
