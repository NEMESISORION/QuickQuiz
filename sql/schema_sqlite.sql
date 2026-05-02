-- SQLite schema for local development
-- Safe to re-run; this will reset data.

DROP TABLE IF EXISTS results;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS quizzes;
DROP TABLE IF EXISTS migrations;
DROP TABLE IF EXISTS users;

CREATE TABLE migrations (
    version INTEGER DEFAULT 0
);
INSERT INTO migrations (version) VALUES (1);

CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'student' CHECK (role IN ('admin', 'student')),
    dark_mode INTEGER DEFAULT 0,
    admin_code TEXT DEFAULT NULL,
    created_by_admin INTEGER DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_created_by ON users(created_by_admin);

CREATE TABLE quizzes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    time_limit INTEGER DEFAULT 0,
    randomize_questions INTEGER DEFAULT 0,
    show_answers INTEGER DEFAULT 1,
    pass_percentage INTEGER DEFAULT 0,
    negative_marking REAL DEFAULT 0,
    start_date TEXT DEFAULT NULL,
    end_date TEXT DEFAULT NULL,
    certificate_enabled INTEGER DEFAULT 0,
    is_published INTEGER DEFAULT 0,
    created_by INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX idx_quizzes_created_by ON quizzes(created_by);
CREATE INDEX idx_quizzes_published ON quizzes(is_published);
CREATE INDEX idx_quizzes_dates ON quizzes(start_date, end_date);

CREATE TABLE questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quiz_id INTEGER NOT NULL,
    question_text TEXT NOT NULL,
    option_a TEXT NOT NULL,
    option_b TEXT NOT NULL,
    option_c TEXT NOT NULL,
    option_d TEXT NOT NULL,
    correct_option TEXT NOT NULL CHECK (correct_option IN ('A', 'B', 'C', 'D')),
    time_limit INTEGER DEFAULT 60,
    points INTEGER DEFAULT 1,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);
CREATE INDEX idx_questions_quiz ON questions(quiz_id);

CREATE TABLE results (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    quiz_id INTEGER NOT NULL,
    score REAL NOT NULL,
    total_points INTEGER NOT NULL DEFAULT 0,
    correct_count INTEGER NOT NULL DEFAULT 0,
    wrong_count INTEGER NOT NULL DEFAULT 0,
    time_taken INTEGER DEFAULT 0,
    passed INTEGER DEFAULT NULL,
    answers_json TEXT,
    taken_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);
CREATE INDEX idx_results_user ON results(user_id);
CREATE INDEX idx_results_quiz ON results(quiz_id);
CREATE INDEX idx_results_taken_at ON results(taken_at);
CREATE INDEX idx_results_user_quiz ON results(user_id, quiz_id);

-- Demo users (password is "1234")
INSERT INTO users (username, password, role)
VALUES ('admin', '$2y$10$tz.tcHgDwdxsOaHkCPEQ2uFraBog2cr79m5HA6xe/ddrZlEN43O0q', 'admin');

INSERT INTO users (username, password, role)
VALUES ('student', '$2y$10$tz.tcHgDwdxsOaHkCPEQ2uFraBog2cr79m5HA6xe/ddrZlEN43O0q', 'student');

INSERT INTO quizzes (title, description, time_limit, created_by, is_published)
VALUES ('General Knowledge Quiz', 'Test your basic general knowledge.', 5, 1, 1);

INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option, time_limit, points)
VALUES
(1, 'What is the capital of France?', 'Berlin', 'Paris', 'Madrid', 'Rome', 'B', 60, 1),
(1, 'Which planet is known as the Red Planet?', 'Earth', 'Mars', 'Jupiter', 'Venus', 'B', 45, 1),
(1, 'How many continents are there?', '5', '6', '7', '8', 'C', 30, 1),
(1, 'Which gas do plants breathe in?', 'Oxygen', 'Hydrogen', 'Nitrogen', 'Carbon Dioxide', 'D', 60, 1);
