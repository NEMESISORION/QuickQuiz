<?php
session_start();
require_once __DIR__.'/../src/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') { header("Location: login.php"); exit; }
$uid = $_SESSION['user_id'];
$uname = $_SESSION['username'];

// list currently available quizzes for students
$stmt = $pdo->query("
    SELECT * FROM quizzes
    WHERE is_published = 1
      AND (start_date IS NULL OR start_date <= CURRENT_TIMESTAMP)
      AND (end_date IS NULL OR end_date >= CURRENT_TIMESTAMP)
    ORDER BY created_at DESC
");
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// optionally load user's previous results
$stmt2 = $pdo->prepare("SELECT quiz_id, score FROM results WHERE user_id = ?");
$stmt2->execute([$uid]);
$have = [];
while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) { $have[$row['quiz_id']] = $row['score']; }
?>
<!doctype html><html><head>
  <meta charset="utf-8"><title>Student Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head><body>
  <div class="student-navbar">
    <div class="student-navbar-brand">
      ⚡ QuickQuiz
    </div>
    <div class="student-profile">
      <div class="student-avatar">S</div>
      <div class="student-info">
        <div class="name"><?=htmlspecialchars($uname)?></div>
        <div class="level">Level 1 • 270 XP</div>
      </div>
    </div>
    <div class="student-navbar-menu">
      <a href="student_dashboard.php">Settings</a>
      <a href="student_dashboard.php">Dark</a>
      <a href="logout.php">Sign Out</a>
    </div>
  </div>

  <div style="container-type: inline-size; padding: 32px;">
    <div class="welcome-card">
      <h2>Welcome back, <?=htmlspecialchars($uname)?>! 👋</h2>
      <p>Ready to challenge yourself? Pick a quiz below and test your knowledge.</p>
      <div class="welcome-stats">
        <div class="welcome-stat-item">
          <span class="number">4</span>
          <span class="label">Quizzes Taken</span>
        </div>
        <div class="welcome-stat-item">
          <span class="number">60%</span>
          <span class="label">Avg Score</span>
        </div>
        <div class="welcome-stat-item">
          <span class="number">5/6</span>
          <span class="label">Achievements</span>
        </div>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
      <div class="level-card">
        <div class="level-title">⭐ Level 1</div>
        <div class="xp-progress">
          <div class="xp-progress-bar"></div>
        </div>
        <div class="xp-text">210 XP to next level</div>
      </div>
      <div class="streak-card">
        <div class="streak-number">3 Days</div>
        <div class="streak-label">Learning Streak</div>
      </div>
    </div>

    <div class="progress-card">
      <h3>Your Progress</h3>
      <div class="progress-item">
        <div class="progress-item-label">
          <span>Quizzes</span>
          <span>4/1</span>
        </div>
        <div class="progress-bar">
          <div class="progress-bar-teal"></div>
        </div>
      </div>
      <div class="progress-item">
        <div class="progress-item-label">
          <span>Achievements</span>
          <span>5/6</span>
        </div>
        <div class="progress-bar">
          <div class="progress-bar-orange"></div>
        </div>
      </div>
    </div>

    <div class="achievements-section">
      <div class="section-title">🏆 Achievements</div>
      <div class="achievements-grid">
        <div class="achievement-badge">
          <div class="achievement-icon">🎯</div>
          <div class="name">First Steps</div>
          <div class="description">Complete your first quiz</div>
        </div>
        <div class="achievement-badge">
          <div class="achievement-icon">🔥</div>
          <div class="name">On Fire</div>
          <div class="description">Complete 5 quizzes</div>
        </div>
        <div class="achievement-badge">
          <div class="achievement-icon">⭐</div>
          <div class="name">Perfectionist</div>
          <div class="description">Get 100% on any quiz</div>
        </div>
        <div class="achievement-badge">
          <div class="achievement-icon">🗺️</div>
          <div class="name">Explorer</div>
          <div class="description">Try all available quizzes</div>
        </div>
        <div class="achievement-badge">
          <div class="achievement-icon">📅</div>
          <div class="name">Dedicated</div>
          <div class="description">3-day learning streak</div>
        </div>
        <div class="achievement-badge locked">
          <div class="achievement-icon">🧠</div>
          <div class="name">Quiz Master</div>
          <div class="description">Locked - 10 quiz mastery</div>
        </div>
      </div>
    </div>

    <div class="quizzes-section">
      <div class="section-title">📚 Available Quizzes</div>
      <table class="table">
        <thead><tr><th>Title</th><th>Actions</th><th>Your Score</th></tr></thead>
        <tbody>
          <?php foreach ($quizzes as $q): ?>
            <tr>
              <td><?=htmlspecialchars($q['title'])?></td>
              <td><a class="btn btn-primary btn-sm" href="take_quiz.php?id=<?= $q['id'] ?>">Take Quiz</a></td>
              <td><?= isset($have[$q['id']]) ? htmlspecialchars($have[$q['id']]) : '-' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</body></html>
