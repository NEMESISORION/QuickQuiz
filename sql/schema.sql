-- ==============================
-- DATABASE CREATION
-- ==============================
CREATE DATABASE IF NOT EXISTS quizquick CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE quizquick;

-- ==============================
-- MIGRATIONS TABLE (for tracking)
-- ==============================
CREATE TABLE IF NOT EXISTS migrations (
    version INT DEFAULT 0
);
INSERT INTO migrations (version) VALUES (3);

-- ==============================
-- USERS TABLE
-- ==============================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student') NOT NULL DEFAULT 'student',
    dark_mode TINYINT(1) DEFAULT 0 COMMENT 'User preference for dark mode',
    created_by_admin INT DEFAULT NULL COMMENT 'Admin who created this user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_created_by (created_by_admin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================
-- QUIZZES TABLE
-- ==============================
CREATE TABLE quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    time_limit INT DEFAULT 0 COMMENT 'Time limit in minutes (0 = no limit)',
    randomize_questions TINYINT(1) DEFAULT 0 COMMENT 'Shuffle question order',
    show_answers TINYINT(1) DEFAULT 1 COMMENT 'Show correct answers after submission',
    pass_percentage INT DEFAULT 0 COMMENT 'Pass threshold percentage (0 = no threshold)',
    negative_marking DECIMAL(3,2) DEFAULT 0 COMMENT 'Points to deduct for wrong answer',
    start_date DATETIME DEFAULT NULL COMMENT 'Quiz available from this date',
    end_date DATETIME DEFAULT NULL COMMENT 'Quiz available until this date',
    certificate_enabled TINYINT(1) DEFAULT 0 COMMENT 'Generate certificate on passing',
    is_published TINYINT(1) DEFAULT 0 COMMENT '0=Draft, 1=Published - Students can only see published quizzes',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_by (created_by),
    INDEX idx_published (is_published),
    INDEX idx_dates (start_date, end_date),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================
-- QUESTIONS TABLE
-- ==============================
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(500) NOT NULL,
    option_b VARCHAR(500) NOT NULL,
    option_c VARCHAR(500) NOT NULL,
    option_d VARCHAR(500) NOT NULL,
    correct_option ENUM('A','B','C','D') NOT NULL,
    time_limit INT DEFAULT 60 COMMENT 'Time limit in seconds for this question',
    points INT DEFAULT 1 COMMENT 'Points awarded for correct answer',
    INDEX idx_quiz (quiz_id),
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================
-- RESULTS TABLE
-- ==============================
CREATE TABLE results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    score DECIMAL(10,2) NOT NULL COMMENT 'Total score with decimals for negative marking',
    total_points INT NOT NULL DEFAULT 0 COMMENT 'Maximum possible points',
    correct_count INT NOT NULL DEFAULT 0,
    wrong_count INT NOT NULL DEFAULT 0,
    time_taken INT DEFAULT 0 COMMENT 'Time taken in seconds',
    passed TINYINT(1) DEFAULT NULL COMMENT 'Whether user passed (NULL if no threshold)',
    answers_json TEXT COMMENT 'JSON of user answers for review',
    taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_quiz (quiz_id),
    INDEX idx_taken_at (taken_at),
    INDEX idx_user_quiz (user_id, quiz_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================
-- SAMPLE USERS
-- ==============================

-- Admin login: admin / 1234
INSERT INTO users (username, password, role)
VALUES ('admin', MD5('1234'), 'admin');

-- Student login: student / 1234
INSERT INTO users (username, password, role)
VALUES ('student', MD5('1234'), 'student');

-- ==============================
-- SAMPLE QUIZ (Published by default for demo)
-- ==============================
INSERT INTO quizzes (title, description, time_limit, created_by, is_published)
VALUES ('General Knowledge Quiz', 'Test your basic general knowledge.', 5, 1, 1);

-- ==============================
-- SAMPLE QUESTIONS
-- ==============================

INSERT INTO questions 
(quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option, time_limit, points)
VALUES
(1, 'What is the capital of France?', 'Berlin', 'Paris', 'Madrid', 'Rome', 'B', 60, 1),
(1, 'Which planet is known as the Red Planet?', 'Earth', 'Mars', 'Jupiter', 'Venus', 'B', 45, 1),
(1, 'How many continents are there?', '5', '6', '7', '8', 'C', 30, 1),
(1, 'Which gas do plants breathe in?', 'Oxygen', 'Hydrogen', 'Nitrogen', 'Carbon Dioxide', 'D', 60, 1);
