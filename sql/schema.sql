-- ==============================
-- DROP EXISTING TABLES (safe re-run)
-- ==============================
DROP TABLE IF EXISTS results;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS quizzes;
DROP TABLE IF EXISTS users;

-- ==============================
-- USERS TABLE
-- ==============================
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(10) NOT NULL DEFAULT 'student' CHECK (role IN ('admin', 'student')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==============================
-- QUIZZES TABLE
-- ==============================
CREATE TABLE quizzes (
    id SERIAL PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- ==============================
-- QUESTIONS TABLE
-- ==============================
CREATE TABLE questions (
    id SERIAL PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(255) NOT NULL,
    option_b VARCHAR(255) NOT NULL,
    option_c VARCHAR(255) NOT NULL,
    option_d VARCHAR(255) NOT NULL,
    correct_option VARCHAR(1) NOT NULL CHECK (correct_option IN ('A','B','C','D')),
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- ==============================
-- RESULTS TABLE
-- ==============================
CREATE TABLE results (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    score INT NOT NULL,
    taken_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
);

-- ==============================
-- SAMPLE USERS
-- ==============================

-- Admin login: admin / 1234
INSERT INTO users (username, password, role)
VALUES ('admin', md5('1234'), 'admin');

-- Student login: student / 1234
INSERT INTO users (username, password, role)
VALUES ('student', md5('1234'), 'student');

-- ==============================
-- SAMPLE QUIZ
-- ==============================
INSERT INTO quizzes (title, description, created_by)
VALUES ('General Knowledge Quiz', 'Test your basic general knowledge.', 1);

-- ==============================
-- SAMPLE QUESTIONS
-- ==============================
INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option)
VALUES
(1, 'What is the capital of France?', 'Berlin', 'Paris', 'Madrid', 'Rome', 'B'),
(1, 'Which planet is known as the Red Planet?', 'Earth', 'Mars', 'Jupiter', 'Venus', 'B'),
(1, 'How many continents are there?', '5', '6', '7', '8', 'C'),
(1, 'Which gas do plants breathe in?', 'Oxygen', 'Hydrogen', 'Nitrogen', 'Carbon Dioxide', 'D');
