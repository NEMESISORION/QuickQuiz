-- ==============================
-- DROP EXISTING TABLES (safe re-run)
-- ==============================
DROP TABLE IF EXISTS results;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS quizzes;
DROP TABLE IF EXISTS migrations;
DROP TABLE IF EXISTS users;

-- ==============================
-- MIGRATIONS TABLE
-- ==============================
CREATE TABLE migrations (
    version INT DEFAULT 0
);
INSERT INTO migrations (version) VALUES (3);

-- ==============================
-- USERS TABLE
-- ==============================
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(10) NOT NULL DEFAULT 'student' CHECK (role IN ('admin', 'student')),
    dark_mode SMALLINT DEFAULT 0,
    admin_code VARCHAR(255) DEFAULT NULL,
    created_by_admin INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_created_by ON users(created_by_admin);

-- ==============================
-- QUIZZES TABLE
-- ==============================
CREATE TABLE quizzes (
    id SERIAL PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    time_limit INT DEFAULT 0,
    randomize_questions SMALLINT DEFAULT 0,
    show_answers SMALLINT DEFAULT 1,
    pass_percentage INT DEFAULT 0,
    negative_marking DECIMAL(3,2) DEFAULT 0,
    start_date TIMESTAMP DEFAULT NULL,
    end_date TIMESTAMP DEFAULT NULL,
    certificate_enabled SMALLINT DEFAULT 0,
    is_published SMALLINT DEFAULT 0,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX idx_quizzes_created_by ON quizzes(created_by);
CREATE INDEX idx_quizzes_published ON quizzes(is_published);
CREATE INDEX idx_quizzes_dates ON quizzes(start_date, end_date);

-- ==============================
-- QUESTIONS TABLE
-- ==============================
CREATE TABLE questions (
    id SERIAL PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(500) NOT NULL,
    option_b VARCHAR(500) NOT NULL,
    option_c VARCHAR(500) NOT NULL,
    option_d VARCHAR(500) NOT NULL,
    correct_option VARCHAR(1) NOT NULL CHECK (correct_option IN ('A','B','C','D')),
    time_limit INT DEFAULT 60,
    points INT DEFAULT 1,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);
CREATE INDEX idx_questions_quiz ON questions(quiz_id);

-- ==============================
-- RESULTS TABLE
-- ==============================
CREATE TABLE results (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    score DECIMAL(10,2) NOT NULL,
    total_points INT NOT NULL DEFAULT 0,
    correct_count INT NOT NULL DEFAULT 0,
    wrong_count INT NOT NULL DEFAULT 0,
    time_taken INT DEFAULT 0,
    passed SMALLINT DEFAULT NULL,
    answers_json TEXT,
    taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);
CREATE INDEX idx_results_user ON results(user_id);
CREATE INDEX idx_results_quiz ON results(quiz_id);
CREATE INDEX idx_results_taken_at ON results(taken_at);
CREATE INDEX idx_results_user_quiz ON results(user_id, quiz_id);

-- ==============================
-- SAMPLE USERS
-- ==============================
INSERT INTO users (username, password, role)
VALUES ('admin', md5('1234'), 'admin');

INSERT INTO users (username, password, role)
VALUES ('student', md5('1234'), 'student');

-- ==============================
-- SAMPLE QUIZ
-- ==============================
INSERT INTO quizzes (title, description, time_limit, created_by, is_published)
VALUES ('General Knowledge Quiz', 'Test your basic general knowledge.', 5, 1, 1);

-- ==============================
-- SAMPLE QUESTIONS
-- ==============================
INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option, time_limit, points)
VALUES
(1, 'What is the capital of France?', 'Berlin', 'Paris', 'Madrid', 'Rome', 'B', 60, 1),
(1, 'Which planet is known as the Red Planet?', 'Earth', 'Mars', 'Jupiter', 'Venus', 'B', 45, 1),
(1, 'How many continents are there?', '5', '6', '7', '8', 'C', 30, 1),
(1, 'Which gas do plants breathe in?', 'Oxygen', 'Hydrogen', 'Nitrogen', 'Carbon Dioxide', 'D', 60, 1);
