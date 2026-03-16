<?php
session_start();
require_once __DIR__.'/../src/db.php';

// Run migrations
require_once __DIR__.'/../src/migrations.php';
runMigrations($pdo);

// Only allow admin (matches login.php session keys)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: login.php");
        exit;
}

$adminName = $_SESSION['username'] ?? 'Admin';
$adminId = $_SESSION['user_id'];

// Get user's dark mode preference
$stmt = $pdo->prepare("SELECT dark_mode FROM users WHERE id = ?");
$stmt->execute([$adminId]);
$userPref = $stmt->fetch(PDO::FETCH_ASSOC);
$darkMode = isset($userPref['dark_mode']) && $userPref['dark_mode'];

// Handle dark mode toggle
if (isset($_POST['toggle_dark_mode'])) {
    $newMode = $darkMode ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE users SET dark_mode = ? WHERE id = ?");
    $stmt->execute([$newMode, $adminId]);
    header("Location: admin_dashboard.php");
    exit;
}

// Add created_by column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE quizzes ADD COLUMN created_by INT DEFAULT NULL");
} catch (PDOException $e) {
    // Column already exists, ignore
}

// Add time_limit column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE quizzes ADD COLUMN time_limit INT DEFAULT 0 COMMENT 'Time limit in minutes (0 = no limit)'");
} catch (PDOException $e) {
    // Column already exists, ignore
}

// IMPORTANT: Set ALL old quizzes to created_by = -1 so no admin sees them
// This runs once and ensures new admins get a clean dashboard
$pdo->exec("UPDATE quizzes SET created_by = -1 WHERE created_by IS NULL OR created_by = 0");

// Handle delete quiz
if (isset($_GET['delete_quiz'])) {
    $deleteId = intval($_GET['delete_quiz']);
    // Verify this quiz belongs to the current admin
    $stmt = $pdo->prepare("SELECT id FROM quizzes WHERE id = ? AND created_by = ?");
    $stmt->execute([$deleteId, $adminId]);
    if ($stmt->fetch()) {
        // Delete results first (foreign key constraint)
        $stmt = $pdo->prepare("DELETE FROM results WHERE quiz_id = ?");
        $stmt->execute([$deleteId]);
        // Delete questions (should cascade, but just in case)
        $stmt = $pdo->prepare("DELETE FROM questions WHERE quiz_id = ?");
        $stmt->execute([$deleteId]);
        // Delete the quiz
        $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ? AND created_by = ?");
        $stmt->execute([$deleteId, $adminId]);
    }
    header("Location: admin_dashboard.php?deleted=1");
    exit;
}

$successMsg = null;
if (isset($_GET['deleted'])) $successMsg = "Quiz deleted successfully!";

// Fetch quizzes created by this admin only
$stmt = $pdo->prepare(
    "SELECT q.id, q.title, q.description, q.time_limit,
            (SELECT COUNT(*) FROM questions qq WHERE qq.quiz_id = q.id) AS question_count
     FROM quizzes q
     WHERE q.created_by = ?
     ORDER BY q.id DESC"
);
$stmt->execute([$adminId]);
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalQuizzes = count($quizzes);
$totalQuestions = array_sum(array_column($quizzes, 'question_count'));

// Get students who have taken THIS admin's quizzes only
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT r.user_id) 
    FROM results r 
    JOIN quizzes q ON r.quiz_id = q.id 
    WHERE q.created_by = ?
");
$stmt->execute([$adminId]);
$totalStudents = $stmt->fetchColumn();

// Get total attempts and average score - ONLY for this admin's quizzes
$stmt = $pdo->prepare("
    SELECT COUNT(*) as attempts, AVG(r.score) as avg_score 
    FROM results r 
    JOIN quizzes q ON r.quiz_id = q.id 
    WHERE q.created_by = ?
");
$stmt->execute([$adminId]);
$resultsStats = $stmt->fetch(PDO::FETCH_ASSOC);
$totalAttempts = $resultsStats['attempts'] ?? 0;
$avgScore = round($resultsStats['avg_score'] ?? 0, 1);

// Get recent activity (last 10 quiz attempts) - ONLY for this admin's quizzes
$stmt = $pdo->prepare(
    "SELECT r.*, u.username, q.title as quiz_title 
     FROM results r 
     JOIN users u ON r.user_id = u.id 
     JOIN quizzes q ON r.quiz_id = q.id 
     WHERE q.created_by = ?
     ORDER BY r.taken_at DESC 
     LIMIT 10"
);
$stmt->execute([$adminId]);
$recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get quiz performance data (attempts per quiz for chart) - ONLY for this admin's quizzes
$stmt = $pdo->prepare(
    "SELECT q.title, COUNT(r.id) as attempts, AVG(r.score) as avg_score
     FROM quizzes q
     LEFT JOIN results r ON q.id = r.quiz_id
     WHERE q.created_by = ?
     GROUP BY q.id, q.title
     ORDER BY attempts DESC
     LIMIT 7"
);
$stmt->execute([$adminId]);
$quizPerformance = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get top performers - ONLY for this admin's quizzes
$stmt = $pdo->prepare(
    "SELECT u.username, SUM(r.score) as total_score, COUNT(r.id) as quizzes_taken
     FROM results r
     JOIN users u ON r.user_id = u.id
     JOIN quizzes q ON r.quiz_id = q.id
     WHERE q.created_by = ?
     GROUP BY r.user_id, u.username
     ORDER BY total_score DESC
     LIMIT 5"
);
$stmt->execute([$adminId]);
$topPerformers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Color palette for avatars
$avatarColors = ['#7c3aed', '#0d9488', '#f59e0b', '#ef4444', '#3b82f6', '#ec4899', '#22c55e'];
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard — QuickQuiz Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/quickquiz.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#7c3aed">
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
<body class="admin-theme <?= $darkMode ? 'dark-mode' : '' ?>">
  <?php if ($successMsg): ?>
    <div class="success-toast" style="position: fixed; top: 100px; right: 20px; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; padding: 1rem 1.5rem; border-radius: 12px; font-weight: 600; box-shadow: 0 10px 40px rgba(34, 197, 94, 0.3); z-index: 3000; animation: toastEnter 0.3s ease, toastExit 0.3s ease 2.7s forwards;">
      ✓ <?= htmlspecialchars($successMsg) ?>
    </div>
    <style>
      @keyframes toastEnter { from { opacity: 0; transform: translateX(100px); } to { opacity: 1; transform: translateX(0); } }
      @keyframes toastExit { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(100px); } }
    </style>
  <?php endif; ?>

  <div class="top-bar" style="padding: 0.75rem 0;">
    <div class="container d-flex justify-content-between align-items-center" style="flex-wrap: nowrap;">
      <div class="brand" style="font-size: 1.25rem;">⚡ QuickQuiz <small>ADMIN</small></div>
      <div class="d-flex align-items-center gap-2" style="flex-wrap: nowrap;">
        <div class="d-flex align-items-center gap-2" style="color: white;">
          <div style="width: 38px; height: 38px; border-radius: 50%; background: rgba(255,255,255,0.25); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1rem; color: white; border: 2px solid rgba(255,255,255,0.4);">
            <?= strtoupper(substr($adminName, 0, 1)) ?>
          </div>
          <div style="line-height: 1.2;">
            <div style="font-weight: 600; font-size: 0.9375rem; color: white;"><?= htmlspecialchars($adminName) ?></div>
          </div>
        </div>
        <a class="nav-btn" href="admin_analytics.php" style="white-space: nowrap; padding: 0.5rem 0.875rem; font-size: 0.8125rem;">
          <span class="icon">📊</span> Analytics
        </a>
        <a class="nav-btn" href="change_password.php" style="white-space: nowrap; padding: 0.5rem 0.875rem; font-size: 0.8125rem;">
          <span class="icon">⚙️</span> Settings
        </a>
        <button type="button" id="darkModeToggle" class="nav-btn" style="cursor: pointer; background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.3); white-space: nowrap; padding: 0.5rem 0.875rem; font-size: 0.8125rem;">
          <span class="icon"><?= $darkMode ? '☀️' : '🌙' ?></span> <span id="darkModeText"><?= $darkMode ? 'Light' : 'Dark' ?></span>
        </button>
        <a class="nav-btn nav-btn-logout" href="logout.php" style="white-space: nowrap; padding: 0.5rem 0.875rem; font-size: 0.8125rem;">Sign Out</a>
      </div>
    </div>
  </div>

  <div class="page-container">
    <!-- Welcome Banner -->
    <div class="welcome-banner animate-in">
      <h2>📊 Admin Dashboard</h2>
      <p>Manage your quizzes, track student performance, and monitor overall progress from here.</p>
      <div class="welcome-stats">
        <div class="welcome-stat">
          <div class="value"><?= $totalQuizzes ?></div>
          <div class="label">Quizzes</div>
        </div>
        <div class="welcome-stat">
          <div class="value"><?= $totalStudents ?></div>
          <div class="label">Students</div>
        </div>
        <div class="welcome-stat">
          <div class="value"><?= $totalAttempts ?></div>
          <div class="label">Attempts</div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions animate-in animate-delay-1">
      <a href="admin_create_quiz.php" class="quick-action-btn">
        <span class="icon">➕</span>
        Create Quiz
      </a>
      <a href="#quizzes" class="quick-action-btn">
        <span class="icon">📋</span>
        View All Quizzes
      </a>
      <a href="admin_users.php" class="quick-action-btn">
        <span class="icon">👥</span>
        Manage Users
      </a>
      <a href="#activity" class="quick-action-btn">
        <span class="icon">📈</span>
        Recent Activity
      </a>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid-4 animate-in animate-delay-2">
      <div class="stat-card-mini">
        <div class="stat-icon-sm" style="background: linear-gradient(135deg, rgba(124, 58, 237, 0.1) 0%, rgba(168, 85, 247, 0.1) 100%);">📚</div>
        <div class="stat-content">
          <div class="stat-value"><?= $totalQuizzes ?></div>
          <div class="stat-label">Total Quizzes</div>
        </div>
      </div>
      <div class="stat-card-mini">
        <div class="stat-icon-sm" style="background: linear-gradient(135deg, rgba(13, 148, 136, 0.1) 0%, rgba(20, 184, 166, 0.1) 100%);">❓</div>
        <div class="stat-content">
          <div class="stat-value"><?= $totalQuestions ?></div>
          <div class="stat-label">Total Questions</div>
        </div>
      </div>
      <div class="stat-card-mini">
        <div class="stat-icon-sm" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(99, 102, 241, 0.1) 100%);">👥</div>
        <div class="stat-content">
          <div class="stat-value"><?= $totalStudents ?></div>
          <div class="stat-label">Students</div>
        </div>
      </div>
      <div class="stat-card-mini">
        <div class="stat-icon-sm" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(251, 191, 36, 0.1) 100%);">🎯</div>
        <div class="stat-content">
          <div class="stat-value"><?= $avgScore ?></div>
          <div class="stat-label">Avg. Score</div>
        </div>
      </div>
    </div>

    <!-- Dashboard Grid: Charts + Activity -->
    <div class="dashboard-grid animate-in animate-delay-3">
      <!-- Left: Charts -->
      <div>
        <!-- Quiz Performance Chart -->
        <div class="chart-card" style="margin-bottom: 1.5rem;">
          <div class="chart-header">
            <h5>📊 Quiz Performance</h5>
            <div class="chart-legend">
              <span><span class="dot" style="background: #7c3aed;"></span> Attempts</span>
            </div>
          </div>
          <div class="bar-chart" style="margin-bottom: 2rem;">
            <?php 
            $maxAttempts = max(array_column($quizPerformance, 'attempts') ?: [1]);
            foreach ($quizPerformance as $qp): 
              $height = $maxAttempts > 0 ? ($qp['attempts'] / $maxAttempts) * 100 : 0;
              $shortTitle = strlen($qp['title']) > 8 ? substr($qp['title'], 0, 8) . '..' : $qp['title'];
            ?>
              <div class="bar" style="height: <?= max($height, 5) ?>%; background: linear-gradient(180deg, #a855f7 0%, #7c3aed 100%);">
                <span class="bar-value"><?= $qp['attempts'] ?></span>
                <span class="bar-label"><?= htmlspecialchars($shortTitle) ?></span>
              </div>
            <?php endforeach; ?>
            <?php if (empty($quizPerformance)): ?>
              <div style="width: 100%; text-align: center; color: var(--text-muted); font-size: 0.875rem;">No data yet</div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Top Performers -->
        <div class="leaderboard-card">
          <div class="leaderboard-header">
            <h5>🏆 Top Performers</h5>
          </div>
          <?php if (!empty($topPerformers)): ?>
            <div class="leaderboard-podium">
              <?php if (isset($topPerformers[1])): ?>
                <div class="podium-item second">
                  <div class="avatar" style="background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);">
                    <?= strtoupper(substr($topPerformers[1]['username'], 0, 1)) ?>
                    <span class="medal">🥈</span>
                  </div>
                  <div class="name"><?= htmlspecialchars($topPerformers[1]['username']) ?></div>
                  <div class="score"><?= $topPerformers[1]['total_score'] ?> pts</div>
                  <div class="podium-stand"></div>
                </div>
              <?php endif; ?>
              <?php if (isset($topPerformers[0])): ?>
                <div class="podium-item first">
                  <div class="avatar" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);">
                    <?= strtoupper(substr($topPerformers[0]['username'], 0, 1)) ?>
                    <span class="medal">🥇</span>
                  </div>
                  <div class="name"><?= htmlspecialchars($topPerformers[0]['username']) ?></div>
                  <div class="score"><?= $topPerformers[0]['total_score'] ?> pts</div>
                  <div class="podium-stand"></div>
                </div>
              <?php endif; ?>
              <?php if (isset($topPerformers[2])): ?>
                <div class="podium-item third">
                  <div class="avatar" style="background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);">
                    <?= strtoupper(substr($topPerformers[2]['username'], 0, 1)) ?>
                    <span class="medal">🥉</span>
                  </div>
                  <div class="name"><?= htmlspecialchars($topPerformers[2]['username']) ?></div>
                  <div class="score"><?= $topPerformers[2]['total_score'] ?> pts</div>
                  <div class="podium-stand"></div>
                </div>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="empty-state" style="padding: 2rem;">
              <p style="margin: 0; font-size: 0.875rem;">No quiz results yet</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Right: Activity Feed -->
      <div class="activity-card" id="activity">
        <div class="activity-header">
          <h5>⚡ Recent Activity</h5>
          <span style="font-size: 0.75rem; color: var(--text-muted);">Last 10</span>
        </div>
        <div class="activity-list">
          <?php if (empty($recentActivity)): ?>
            <div class="empty-state" style="padding: 2rem;">
              <div class="icon" style="font-size: 2rem;">📭</div>
              <p style="margin: 0; font-size: 0.875rem;">No activity yet</p>
            </div>
          <?php else: ?>
            <?php foreach ($recentActivity as $i => $activity): ?>
              <div class="activity-item">
                <div class="activity-avatar" style="background: <?= $avatarColors[$i % count($avatarColors)] ?>;">
                  <?= strtoupper(substr($activity['username'], 0, 1)) ?>
                </div>
                <div class="activity-content">
                  <p><strong><?= htmlspecialchars($activity['username']) ?></strong> completed <strong><?= htmlspecialchars($activity['quiz_title']) ?></strong></p>
                  <div class="activity-time"><?= date('M j, g:i A', strtotime($activity['taken_at'])) ?></div>
                </div>
                <span class="activity-score"><?= $activity['score'] ?> pts</span>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Quizzes Section -->
    <div id="quizzes" class="animate-in animate-delay-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="section-title mb-0">
          <span class="icon">📋</span>
          Your Quizzes
        </h4>
        <a class="btn btn-action" href="admin_create_quiz.php" style="background: var(--admin-gradient); color: white;">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 5v14m-7-7h14"/></svg>
          New Quiz
        </a>
      </div>
      
      <div class="table-container">
        <?php if (empty($quizzes)): ?>
          <div class="empty-state">
            <div class="icon">📝</div>
            <p>No quizzes yet.<br>Create your first quiz to get started!</p>
            <a class="btn btn-primary btn-action mt-3" href="admin_create_quiz.php">Create Quiz</a>
          </div>
        <?php else: ?>
          <table class="table">
            <thead>
              <tr>
                <th style="width: 30%;">Quiz Title</th>
                <th>Questions</th>
                <th>Time Limit</th>
                <th>Status</th>
                <th>Attempts</th>
                <th style="width: 220px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($quizzes as $q): 
                // Get attempt count for this quiz
                $stmtAttempts = $pdo->prepare("SELECT COUNT(*) FROM results WHERE quiz_id = ?");
                $stmtAttempts->execute([$q['id']]);
                $attemptCount = $stmtAttempts->fetchColumn();
                $isPublished = $q['is_published'] ?? 0;
              ?>
                <tr>
                  <td>
                    <div class="fw-semibold text-dark"><?=htmlspecialchars($q['title'])?></div>
                    <?php if ($q['description']): ?>
                      <small class="text-muted"><?= htmlspecialchars(substr($q['description'], 0, 60)) ?><?= strlen($q['description']) > 60 ? '...' : '' ?></small>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge bg-light text-dark" style="font-size: 0.8125rem; padding: 0.5rem 0.875rem;">
                      <?= (int)$q['question_count'] ?> <?= $q['question_count'] == 1 ? 'question' : 'questions' ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge" style="font-size: 0.8125rem; padding: 0.5rem 0.875rem; background: <?= intval($q['time_limit'] ?? 0) > 0 ? 'linear-gradient(135deg, #0d9488 0%, #14b8a6 100%)' : '#f1f5f9' ?>; color: <?= intval($q['time_limit'] ?? 0) > 0 ? 'white' : '#64748b' ?>;">
                      ⏱️ <?= intval($q['time_limit'] ?? 0) > 0 ? intval($q['time_limit']) . ' min' : 'Auto' ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($isPublished): ?>
                      <span class="badge" style="font-size: 0.75rem; padding: 0.4rem 0.75rem; background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white;">
                        ✓ Published
                      </span>
                    <?php else: ?>
                      <span class="badge" style="font-size: 0.75rem; padding: 0.4rem 0.75rem; background: #fef3c7; color: #92400e;">
                        📝 Draft
                      </span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span style="font-size: 0.875rem; color: var(--text-medium);">
                      <?= $attemptCount ?> <?= $attemptCount == 1 ? 'attempt' : 'attempts' ?>
                    </span>
                  </td>
                  <td>
                    <div class="d-flex gap-2 flex-wrap">
                      <a class="btn btn-sm btn-action" href="admin_edit_quiz.php?id=<?= $q['id'] ?>" style="background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); color: white; padding: 0.4rem 0.75rem;">
                        ✏️ Edit
                      </a>
                      <a class="btn btn-sm btn-action" href="admin_result.php?id=<?= $q['id'] ?>" style="background: #f1f5f9; color: #334155; padding: 0.4rem 0.6rem;">
                        📊
                      </a>
                      <a class="btn btn-sm btn-action" href="admin_dashboard.php?delete_quiz=<?= $q['id'] ?>" 
                         onclick="return confirm('Are you sure you want to delete this quiz? This will also delete all questions and student results. This action cannot be undone!');"
                         style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 0.4rem 0.6rem;">
                        🗑️
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    // Animate numbers on load
    document.querySelectorAll('.stat-value, .welcome-stat .value').forEach(el => {
      const target = parseInt(el.textContent);
      if (!isNaN(target) && target > 0) {
        let current = 0;
        const increment = target / 30;
        const timer = setInterval(() => {
          current += increment;
          if (current >= target) {
            el.textContent = target;
            clearInterval(timer);
          } else {
            el.textContent = Math.floor(current);
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
