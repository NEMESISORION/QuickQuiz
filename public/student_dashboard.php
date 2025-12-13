<?php
session_start();
require_once __DIR__.'/../src/db.php';

// Run migrations
require_once __DIR__.'/../src/migrations.php';
runMigrations($pdo);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') { header("Location: login.php"); exit; }
$uid = $_SESSION['user_id'];
$uname = $_SESSION['username'];

// Get user's dark mode preference
$stmt = $pdo->prepare("SELECT dark_mode FROM users WHERE id = ?");
$stmt->execute([$uid]);
$userPref = $stmt->fetch(PDO::FETCH_ASSOC);
$darkMode = isset($userPref['dark_mode']) && $userPref['dark_mode'];

// Handle dark mode toggle
if (isset($_POST['toggle_dark_mode'])) {
    $newMode = $darkMode ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE users SET dark_mode = ? WHERE id = ?");
    $stmt->execute([$newMode, $uid]);
    header("Location: student_dashboard.php");
    exit;
}

// Get all PUBLISHED quizzes available for students
$stmt = $pdo->prepare("
    SELECT q.*, 
           (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count 
    FROM quizzes q 
    WHERE q.is_published = 1
    ORDER BY q.id DESC
");
$stmt->execute();
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// load user's previous results with more details
$stmt2 = $pdo->prepare("SELECT quiz_id, MAX(score) as best_score, COUNT(*) as attempts, MAX(taken_at) as last_attempt FROM results WHERE user_id = ? GROUP BY quiz_id");
$stmt2->execute([$uid]);
$have = [];
while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) { 
  $have[$row['quiz_id']] = [
    'score' => $row['best_score'], 
    'attempts' => $row['attempts'],
    'last_attempt' => $row['last_attempt']
  ]; 
}

// Get all user results for stats
$stmt = $pdo->prepare("SELECT r.*, q.title as quiz_title, (SELECT COUNT(*) FROM questions WHERE quiz_id = r.quiz_id) as total_questions FROM results r JOIN quizzes q ON r.quiz_id = q.id WHERE r.user_id = ? ORDER BY r.taken_at DESC");
$stmt->execute([$uid]);
$allResults = $stmt->fetchAll(PDO::FETCH_ASSOC);

$completedCount = count($have);
$totalQuizzes = count($quizzes);
$totalAttempts = count($allResults);
$totalScore = array_sum(array_column($allResults, 'score'));

// Calculate overall percentage
$totalPossibleScore = 0;
$totalEarnedScore = 0;
foreach ($allResults as $r) {
  $totalPossibleScore += $r['total_questions'];
  $totalEarnedScore += $r['score'];
}
$overallPercentage = $totalPossibleScore > 0 ? round(($totalEarnedScore / $totalPossibleScore) * 100) : 0;

// Calculate XP and Level
$xp = $totalScore * 10;
$level = floor($xp / 500) + 1;
$xpProgress = ($xp % 500) / 500 * 100;
$xpToNext = 500 - ($xp % 500);

// Calculate streak (simplified - based on unique days)
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT DATE(taken_at)) as streak_days FROM results WHERE user_id = ? AND taken_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->execute([$uid]);
$streakData = $stmt->fetch(PDO::FETCH_ASSOC);
$streak = $streakData['streak_days'] ?? 0;

// Achievements
$achievements = [
  ['id' => 'first_quiz', 'icon' => '🎯', 'title' => 'First Steps', 'desc' => 'Complete your first quiz', 'unlocked' => $totalAttempts >= 1],
  ['id' => 'five_quizzes', 'icon' => '🔥', 'title' => 'On Fire', 'desc' => 'Complete 5 quizzes', 'unlocked' => $totalAttempts >= 5],
  ['id' => 'perfect', 'icon' => '⭐', 'title' => 'Perfectionist', 'desc' => 'Get 100% on any quiz', 'unlocked' => false],
  ['id' => 'explorer', 'icon' => '🗺️', 'title' => 'Explorer', 'desc' => 'Try all available quizzes', 'unlocked' => $completedCount >= $totalQuizzes && $totalQuizzes > 0],
  ['id' => 'streak', 'icon' => '📅', 'title' => 'Dedicated', 'desc' => '3-day learning streak', 'unlocked' => $streak >= 3],
  ['id' => 'master', 'icon' => '🏆', 'title' => 'Quiz Master', 'desc' => 'Earn 1000 XP total', 'unlocked' => $xp >= 1000],
];

// Check for perfect score
foreach ($allResults as $r) {
  if ($r['score'] == $r['total_questions'] && $r['total_questions'] > 0) {
    $achievements[2]['unlocked'] = true;
    break;
  }
}

$unlockedCount = count(array_filter($achievements, fn($a) => $a['unlocked']));

// Recent activity
$recentResults = array_slice($allResults, 0, 5);

// Avatar colors
$avatarColors = ['#7c3aed', '#0d9488', '#f59e0b', '#ef4444', '#3b82f6', '#ec4899', '#22c55e'];
$userColor = $avatarColors[ord($uname[0]) % count($avatarColors)];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard — QuickQuiz</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/quickquiz.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#0d9488">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <style>
    .nav-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 18px;
      background: rgba(255, 255, 255, 0.15);
      border: 1px solid rgba(255, 255, 255, 0.3);
      border-radius: 10px;
      color: white;
      font-weight: 600;
      font-size: 0.9rem;
      text-decoration: none;
      transition: all 0.2s ease;
      backdrop-filter: blur(10px);
    }
    .nav-btn:hover {
      background: rgba(255, 255, 255, 0.25);
      border-color: rgba(255, 255, 255, 0.5);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }
    .nav-btn .icon {
      font-size: 1.2rem;
      line-height: 1;
    }
    .nav-btn-logout {
      background: rgba(239, 68, 68, 0.2);
      border-color: rgba(239, 68, 68, 0.4);
    }
    .nav-btn-logout:hover {
      background: rgba(239, 68, 68, 0.4);
      border-color: rgba(239, 68, 68, 0.6);
    }
  </style>
</head>
<body class="student-theme <?= $darkMode ? 'dark-mode' : '' ?>">
  <div class="top-bar" style="padding: 0.75rem 0;">
    <div class="container d-flex justify-content-between align-items-center">
      <div class="brand" style="font-size: 1.25rem;">⚡ QuickQuiz</div>
      <div class="d-flex align-items-center gap-2">
        <div class="d-flex align-items-center gap-2" style="color: white;">
          <div style="width: 38px; height: 38px; border-radius: 50%; background: rgba(255,255,255,0.25); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem; color: white; border: 2px solid rgba(255,255,255,0.4);">
            <?= strtoupper(substr($uname, 0, 1)) ?>
          </div>
          <div style="line-height: 1.2;">
            <div style="font-weight: 600; font-size: 0.9375rem; color: white;"><?=htmlspecialchars($uname)?></div>
            <div style="font-size: 0.75rem; opacity: 0.9; color: white;">Level <?= $level ?> • <?= $xp ?> XP</div>
          </div>
        </div>
        <a class="nav-btn" href="change_password.php" style="padding: 0.5rem 0.875rem; font-size: 0.8125rem;">
          <span class="icon">⚙️</span> Settings
        </a>
        <button type="button" id="darkModeToggle" class="nav-btn" style="cursor: pointer; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); padding: 0.5rem 0.875rem; font-size: 0.8125rem;">
          <span class="icon"><?= $darkMode ? '☀️' : '🌙' ?></span> <span id="darkModeText"><?= $darkMode ? 'Light' : 'Dark' ?></span>
        </button>
        <a class="nav-btn nav-btn-logout" href="logout.php" style="padding: 0.5rem 0.875rem; font-size: 0.8125rem;">Sign Out</a>
      </div>
    </div>
  </div>

  <div class="page-container">
    <!-- Welcome Banner -->
    <div class="welcome-banner animate-in">
      <h2>Welcome back, <?=htmlspecialchars($uname)?>! 👋</h2>
      <?php if ($totalQuizzes > 0): ?>
        <p>Ready to challenge yourself? Pick a quiz below and test your knowledge.</p>
      <?php else: ?>
        <p>Your teacher will share quiz links with you. Once you take a quiz, it will appear here!</p>
      <?php endif; ?>
      <div class="welcome-stats">
        <div class="welcome-stat">
          <div class="value"><?= $completedCount ?></div>
          <div class="label">Quizzes Taken</div>
        </div>
        <div class="welcome-stat">
          <div class="value"><?= $overallPercentage ?>%</div>
          <div class="label">Avg Score</div>
        </div>
        <div class="welcome-stat">
          <div class="value"><?= $unlockedCount ?>/<?= count($achievements) ?></div>
          <div class="label">Achievements</div>
        </div>
      </div>
    </div>

    <!-- Level & Streak Cards -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;" class="animate-in animate-delay-1">
      <div class="level-card">
        <div class="level-header">
          <div class="level-badge">
            <span class="icon">⭐</span>
            <span class="level">Level <?= $level ?></span>
          </div>
          <span class="level-xp"><?= $xp ?> / <?= $level * 500 ?> XP</span>
        </div>
        <div class="level-progress">
          <div class="level-progress-fill" style="width: <?= $xpProgress ?>%"></div>
        </div>
        <div style="font-size: 0.75rem; margin-top: 0.5rem; opacity: 0.8;"><?= $xpToNext ?> XP to next level</div>
      </div>
      <div class="streak-card">
        <span class="streak-icon">🔥</span>
        <div class="streak-content">
          <h4>Learning Streak</h4>
          <div class="streak-value"><?= $streak ?> Days</div>
        </div>
      </div>
    </div>

    <!-- Progress Overview -->
    <div class="progress-ring-container animate-in animate-delay-2">
      <svg style="position: absolute; width: 0; height: 0;">
        <defs>
          <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
            <stop offset="0%" stop-color="#0d9488" />
            <stop offset="100%" stop-color="#2dd4bf" />
          </linearGradient>
        </defs>
      </svg>
      <div class="progress-ring">
        <svg viewBox="0 0 100 100">
          <circle class="ring-bg" cx="50" cy="50" r="40"></circle>
          <circle class="ring-progress" cx="50" cy="50" r="40" style="stroke-dasharray: <?= ($overallPercentage / 100) * 251.2 ?> 251.2"></circle>
        </svg>
        <div class="ring-center">
          <div class="ring-value"><?= $overallPercentage ?>%</div>
          <div class="ring-label">Overall</div>
        </div>
      </div>
      <div class="progress-details">
        <h4>Your Progress</h4>
        <p>Keep going! You're making great progress.</p>
        <div class="progress-bars">
          <div class="progress-bar-item">
            <span class="label">Quizzes</span>
            <div class="bar-track">
              <div class="bar-fill" style="width: <?= $totalQuizzes > 0 ? ($completedCount / $totalQuizzes) * 100 : 0 ?>%; background: var(--student-gradient);"></div>
            </div>
            <span class="value"><?= $completedCount ?>/<?= $totalQuizzes ?></span>
          </div>
          <div class="progress-bar-item">
            <span class="label">Achievements</span>
            <div class="bar-track">
              <div class="bar-fill" style="width: <?= count($achievements) > 0 ? ($unlockedCount / count($achievements)) * 100 : 0 ?>%; background: linear-gradient(90deg, #f59e0b, #fbbf24);"></div>
            </div>
            <span class="value"><?= $unlockedCount ?>/<?= count($achievements) ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Achievements Section -->
    <div class="achievements-section animate-in animate-delay-3">
      <h4 class="section-title">
        <span class="icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">🏅</span>
        Achievements
      </h4>
      <div class="achievements-grid">
        <?php foreach ($achievements as $achievement): ?>
          <div class="achievement-card <?= $achievement['unlocked'] ? 'unlocked' : 'locked' ?>">
            <span class="achievement-icon"><?= $achievement['icon'] ?></span>
            <h6><?= $achievement['title'] ?></h6>
            <p><?= $achievement['desc'] ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid-4 animate-in animate-delay-3">
      <div class="stat-card-mini">
        <div class="stat-icon-sm" style="background: linear-gradient(135deg, rgba(13, 148, 136, 0.1) 0%, rgba(20, 184, 166, 0.1) 100%);">📝</div>
        <div class="stat-content">
          <div class="stat-value"><?= $totalAttempts ?></div>
          <div class="stat-label">Total Attempts</div>
        </div>
      </div>
      <div class="stat-card-mini">
        <div class="stat-icon-sm" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(251, 191, 36, 0.1) 100%);">🎯</div>
        <div class="stat-content">
          <div class="stat-value"><?= $totalScore ?></div>
          <div class="stat-label">Total Points</div>
        </div>
      </div>
      <div class="stat-card-mini">
        <div class="stat-icon-sm" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(167, 139, 250, 0.1) 100%);">⭐</div>
        <div class="stat-content">
          <div class="stat-value"><?= $xp ?></div>
          <div class="stat-label">Experience</div>
        </div>
      </div>
      <div class="stat-card-mini">
        <div class="stat-icon-sm" style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(74, 222, 128, 0.1) 100%);">✅</div>
        <div class="stat-content">
          <div class="stat-value"><?= $completedCount ?></div>
          <div class="stat-label">Quizzes Done</div>
        </div>
      </div>
    </div>

    <!-- Available Quizzes -->
    <h4 class="section-title animate-in animate-delay-4">
      <span class="icon" style="background: var(--student-gradient);">📚</span>
      Available Quizzes
    </h4>
    
    <?php if (empty($quizzes)): ?>
      <div class="table-container">
        <div class="empty-state">
          <div class="icon">📚</div>
          <p>No quizzes available yet.<br>Check back soon!</p>
        </div>
      </div>
    <?php else: ?>
      <div class="quiz-cards-grid animate-in animate-delay-4">
        <?php foreach ($quizzes as $q): 
          $completed = isset($have[$q['id']]);
          $bestScore = $completed ? $have[$q['id']]['score'] : 0;
          $attempts = $completed ? $have[$q['id']]['attempts'] : 0;
          $percentage = $q['question_count'] > 0 ? round(($bestScore / $q['question_count']) * 100) : 0;
          $scoreClass = $percentage >= 80 ? 'good' : ($percentage >= 50 ? 'medium' : '');
        ?>
          <div class="quiz-card-item">
            <div class="quiz-card-header">
              <span class="quiz-card-category">Quiz</span>
              <div class="quiz-card-difficulty">
                <span class="dot <?= $q['question_count'] >= 1 ? 'active' : '' ?>"></span>
                <span class="dot <?= $q['question_count'] >= 5 ? 'active' : '' ?>"></span>
                <span class="dot <?= $q['question_count'] >= 10 ? 'active' : '' ?>"></span>
              </div>
            </div>
            <div class="quiz-card-body">
              <h5><?=htmlspecialchars($q['title'])?></h5>
              <?php if (isset($q['description']) && $q['description']): ?>
                <p><?= htmlspecialchars(substr($q['description'], 0, 80)) ?><?= strlen($q['description']) > 80 ? '...' : '' ?></p>
              <?php else: ?>
                <p>Test your knowledge with this quiz!</p>
              <?php endif; ?>
              <div class="quiz-card-meta">
                <span>❓ <?= (int)$q['question_count'] ?> Questions</span>
                <span>⏱️ <?= $q['question_count'] ?> min</span>
                <?php if ($completed): ?>
                  <span>🔄 <?= $attempts ?> attempt<?= $attempts > 1 ? 's' : '' ?></span>
                <?php endif; ?>
              </div>
            </div>
            <div class="quiz-card-footer">
              <?php if ($completed): ?>
                <div class="quiz-card-score">
                  <div class="score-ring <?= $scoreClass ?>"><?= $percentage ?>%</div>
                  <span class="score-label">Best: <?= $bestScore ?>/<?= $q['question_count'] ?></span>
                </div>
              <?php else: ?>
                <span style="font-size: 0.8125rem; color: var(--text-muted);">Not attempted</span>
              <?php endif; ?>
              <a class="btn btn-sm btn-action" href="take_quiz.php?id=<?= $q['id'] ?>" style="background: var(--student-gradient); color: white; padding: 0.5rem 1.25rem; font-weight: 600;">
                <?= $completed ? '🔄 Retake' : '▶️ Start' ?>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Recent Activity -->
    <?php if (!empty($recentResults)): ?>
      <h4 class="section-title" style="margin-top: 2rem;">
        <span class="icon" style="background: var(--student-gradient);">📊</span>
        Recent Activity
      </h4>
      <div class="activity-card">
        <div class="activity-list">
          <?php foreach ($recentResults as $i => $result): 
            $pct = $result['total_questions'] > 0 ? round(($result['score'] / $result['total_questions']) * 100) : 0;
          ?>
            <div class="activity-item">
              <div class="activity-avatar" style="background: <?= $avatarColors[$i % count($avatarColors)] ?>;">
                <?= $pct >= 80 ? '🏆' : ($pct >= 50 ? '✓' : '📖') ?>
              </div>
              <div class="activity-content">
                <p>Completed <strong><?= htmlspecialchars($result['quiz_title']) ?></strong></p>
                <div class="activity-time"><?= date('M j, Y \a\t g:i A', strtotime($result['taken_at'])) ?></div>
              </div>
              <span class="activity-score" style="background: <?= $pct >= 80 ? 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)' : ($pct >= 50 ? 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)' : 'linear-gradient(135deg, #64748b 0%, #475569 100%)') ?>;">
                <?= $result['score'] ?>/<?= $result['total_questions'] ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <script>
    // Animate numbers
    document.querySelectorAll('.stat-value, .welcome-stat .value, .ring-value, .streak-value').forEach(el => {
      const text = el.textContent;
      const match = text.match(/^(\d+)/);
      if (match) {
        const target = parseInt(match[1]);
        const suffix = text.replace(match[1], '');
        let current = 0;
        const increment = target / 30;
        const timer = setInterval(() => {
          current += increment;
          if (current >= target) {
            el.textContent = target + suffix;
            clearInterval(timer);
          } else {
            el.textContent = Math.floor(current) + suffix;
          }
        }, 30);
      }
    });
    
    // Register Service Worker for PWA
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('sw.js')
        .then(reg => console.log('SW registered'))
        .catch(err => console.log('SW registration failed'));
    }
    
    // Dark mode toggle
    document.getElementById('darkModeToggle').addEventListener('click', function() {
      fetch('toggle_dark_mode.php', { method: 'POST' })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            document.body.classList.toggle('dark-mode');
            const icon = this.querySelector('.icon');
            const text = document.getElementById('darkModeText');
            if (data.darkMode) {
              icon.textContent = '☀️';
              text.textContent = 'Light';
            } else {
              icon.textContent = '🌙';
              text.textContent = 'Dark';
            }
          }
        });
    });
  </script>
</body>
</html>