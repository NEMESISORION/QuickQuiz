<?php
session_start();
require_once __DIR__.'/../src/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }

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
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

$quiz_id = intval($_GET['id'] ?? 0);
if (!$quiz_id) { header("Location: admin_dashboard.php"); exit; }

// Get quiz info - only if created by this admin
$stmt = $pdo->prepare("SELECT q.*, (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count FROM quizzes q WHERE q.id = ? AND q.created_by = ?");
$stmt->execute([$quiz_id, $adminId]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$quiz) { 
    echo "<div style='text-align:center;padding:50px;font-family:sans-serif;'>";
    echo "<h2>⚠️ Access Denied</h2>";
    echo "<p>You can only view results for quizzes you created.</p>";
    echo "<a href='admin_dashboard.php' style='color:#7c3aed;'>← Back to Dashboard</a>";
    echo "</div>";
    exit; 
}

// Get all results
$stmt = $pdo->prepare("SELECT r.*, u.username FROM results r JOIN users u ON r.user_id = u.id WHERE r.quiz_id = ? ORDER BY r.score DESC, r.taken_at ASC");
$stmt->execute([$quiz_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalAttempts = count($results);
$avgScore = $totalAttempts > 0 ? round(array_sum(array_column($results, 'score')) / $totalAttempts, 1) : 0;
$highScore = $totalAttempts > 0 ? max(array_column($results, 'score')) : 0;
$passRate = 0;
$questionCount = $quiz['question_count'] ?? 1;

// Calculate pass rate (>= 50%)
$passCount = 0;
foreach ($results as $r) {
  $pct = ($r['score'] / $questionCount) * 100;
  if ($pct >= 50) $passCount++;
}
$passRate = $totalAttempts > 0 ? round(($passCount / $totalAttempts) * 100) : 0;

// Get unique students
$uniqueStudents = count(array_unique(array_column($results, 'user_id')));

// Score distribution
$distribution = ['excellent' => 0, 'good' => 0, 'average' => 0, 'needs_work' => 0];
foreach ($results as $r) {
  $pct = ($r['score'] / $questionCount) * 100;
  if ($pct >= 80) $distribution['excellent']++;
  elseif ($pct >= 60) $distribution['good']++;
  elseif ($pct >= 40) $distribution['average']++;
  else $distribution['needs_work']++;
}

// Avatar colors
$avatarColors = ['#7c3aed', '#0d9488', '#f59e0b', '#ef4444', '#3b82f6', '#ec4899', '#22c55e'];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Results — QuickQuiz Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/quickquiz.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
  <style>
    .quiz-banner {
      background: var(--admin-gradient);
      border-radius: var(--radius-xl);
      padding: 2rem;
      color: white;
      margin-bottom: 1.5rem;
      position: relative;
      overflow: hidden;
    }
    .quiz-banner::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -10%;
      width: 300px;
      height: 300px;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
      border-radius: 50%;
    }
    .quiz-banner h2 { font-size: 1.5rem; font-weight: 700; margin: 0 0 0.5rem; }
    .quiz-banner p { opacity: 0.9; margin: 0; font-size: 0.9375rem; }
    .quiz-banner-stats { display: flex; gap: 2rem; margin-top: 1.5rem; flex-wrap: wrap; }
    .quiz-banner-stat { text-align: center; }
    .quiz-banner-stat .value { font-size: 1.75rem; font-weight: 800; line-height: 1; }
    .quiz-banner-stat .label { font-size: 0.75rem; opacity: 0.8; margin-top: 0.25rem; }
    
    .distribution-chart {
      display: flex;
      gap: 0.75rem;
      height: 120px;
      align-items: flex-end;
      padding: 1rem 0;
    }
    .distribution-bar {
      flex: 1;
      border-radius: 6px 6px 0 0;
      position: relative;
      transition: all 0.3s;
      cursor: pointer;
      min-height: 8px;
    }
    .distribution-bar:hover { opacity: 0.85; transform: scaleY(1.02); transform-origin: bottom; }
    .distribution-bar .bar-label {
      position: absolute;
      bottom: -28px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 0.6875rem;
      color: var(--text-muted);
      white-space: nowrap;
    }
    .distribution-bar .bar-value {
      position: absolute;
      top: -24px;
      left: 50%;
      transform: translateX(-50%);
      font-size: 0.8125rem;
      font-weight: 700;
      color: var(--text-dark);
    }
    
    .results-table-container {
      background: var(--white);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow);
      overflow: hidden;
    }
    
    .results-table-header {
      padding: 1.25rem 1.5rem;
      background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
      border-bottom: 1px solid var(--border-light);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .results-table-header h5 {
      font-size: 1rem;
      font-weight: 700;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .score-pill {
      padding: 0.375rem 0.875rem;
      border-radius: 20px;
      font-size: 0.8125rem;
      font-weight: 600;
    }
    .score-pill.excellent { background: rgba(34, 197, 94, 0.1); color: #15803d; }
    .score-pill.good { background: rgba(59, 130, 246, 0.1); color: #1d4ed8; }
    .score-pill.average { background: rgba(245, 158, 11, 0.1); color: #b45309; }
    .score-pill.needs-work { background: rgba(239, 68, 68, 0.1); color: #b91c1c; }
    
    .user-cell {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    .user-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 700;
      font-size: 0.875rem;
      flex-shrink: 0;
    }
  </style>
</head>
<body class="admin-theme <?= $darkMode ? 'dark-mode' : '' ?>">
  <div class="top-bar">
    <div class="container d-flex justify-content-between align-items-center">
      <div class="brand">⚡ QuickQuiz <small>ADMIN</small></div>
      <div class="d-flex gap-2 align-items-center">
        <button type="button" id="darkModeToggle" class="btn btn-action" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); padding: 0.5rem 1rem; cursor: pointer;">
          <span id="darkModeIcon"><?= $darkMode ? '☀️' : '🌙' ?></span> <span id="darkModeText"><?= $darkMode ? 'Light' : 'Dark' ?></span>
        </button>
        <a class="btn btn-outline-light btn-action" href="admin_dashboard.php">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5m7-7-7 7 7 7"/></svg>
          Dashboard
        </a>
      </div>
    </div>
  </div>

  <div class="page-container">
    <!-- Quiz Banner -->
    <div class="quiz-banner">
      <h2>📊 <?= htmlspecialchars($quiz['title']) ?></h2>
      <p><?= $quiz['description'] ? htmlspecialchars($quiz['description']) : 'Quiz performance analytics and student results' ?></p>
      <div class="quiz-banner-stats">
        <div class="quiz-banner-stat">
          <div class="value"><?= $totalAttempts ?></div>
          <div class="label">Total Attempts</div>
        </div>
        <div class="quiz-banner-stat">
          <div class="value"><?= $uniqueStudents ?></div>
          <div class="label">Unique Students</div>
        </div>
        <div class="quiz-banner-stat">
          <div class="value"><?= $avgScore ?></div>
          <div class="label">Avg Score</div>
        </div>
        <div class="quiz-banner-stat">
          <div class="value"><?= $passRate ?>%</div>
          <div class="label">Pass Rate</div>
        </div>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid-4">
      <div class="stat-card-mini">
        <div class="stat-icon-sm" style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(74, 222, 128, 0.1) 100%);">🏆</div>
        <div class="stat-content">
          <div class="stat-value"><?= $highScore ?>/<?= $questionCount ?></div>
          <div class="stat-label">High Score</div>
        </div>
      </div>
      <div class="stat-card-mini">
        <div class="stat-icon-sm" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(99, 102, 241, 0.1) 100%);">📊</div>
        <div class="stat-content">
          <div class="stat-value"><?= $avgScore ?></div>
          <div class="stat-label">Average Score</div>
        </div>
      </div>
      <div class="stat-card-mini">
        <div class="stat-icon-sm" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(251, 191, 36, 0.1) 100%);">❓</div>
        <div class="stat-content">
          <div class="stat-value"><?= $questionCount ?></div>
          <div class="stat-label">Questions</div>
        </div>
      </div>
      <div class="stat-card-mini">
        <div class="stat-icon-sm" style="background: linear-gradient(135deg, rgba(124, 58, 237, 0.1) 0%, rgba(168, 85, 247, 0.1) 100%);">✅</div>
        <div class="stat-content">
          <div class="stat-value"><?= $passRate ?>%</div>
          <div class="stat-label">Pass Rate</div>
        </div>
      </div>
    </div>

    <!-- Score Distribution Chart -->
    <div class="chart-card" style="margin-bottom: 1.5rem;">
      <div class="chart-header">
        <h5>📈 Score Distribution</h5>
        <div class="chart-legend">
          <span><span class="dot" style="background: #22c55e;"></span> 80%+ Excellent</span>
          <span><span class="dot" style="background: #3b82f6;"></span> 60-79% Good</span>
          <span><span class="dot" style="background: #f59e0b;"></span> 40-59% Average</span>
          <span><span class="dot" style="background: #ef4444;"></span> &lt;40% Needs Work</span>
        </div>
      </div>
      <div class="distribution-chart" style="margin-bottom: 2rem;">
        <?php 
        $maxDist = max($distribution) ?: 1;
        $categories = [
          ['key' => 'excellent', 'label' => 'Excellent', 'color' => '#22c55e'],
          ['key' => 'good', 'label' => 'Good', 'color' => '#3b82f6'],
          ['key' => 'average', 'label' => 'Average', 'color' => '#f59e0b'],
          ['key' => 'needs_work', 'label' => 'Needs Work', 'color' => '#ef4444']
        ];
        foreach ($categories as $cat):
          $count = $distribution[$cat['key']];
          $height = $maxDist > 0 ? ($count / $maxDist) * 100 : 0;
        ?>
          <div class="distribution-bar" style="height: <?= max($height, 5) ?>%; background: <?= $cat['color'] ?>;">
            <span class="bar-value"><?= $count ?></span>
            <span class="bar-label"><?= $cat['label'] ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Top 3 Podium -->
    <?php if (count($results) >= 1): ?>
      <div class="leaderboard-card" style="margin-bottom: 1.5rem;">
        <div class="leaderboard-header">
          <h5>🏆 Top Performers</h5>
        </div>
        <div class="leaderboard-podium">
          <?php if (isset($results[1])): ?>
            <div class="podium-item second">
              <div class="avatar" style="background: linear-gradient(135deg, #94a3b8 0%, #64748b 100%);">
                <?= strtoupper(substr($results[1]['username'], 0, 1)) ?>
                <span class="medal">🥈</span>
              </div>
              <div class="name"><?= htmlspecialchars($results[1]['username']) ?></div>
              <div class="score"><?= $results[1]['score'] ?>/<?= $questionCount ?></div>
              <div class="podium-stand"></div>
            </div>
          <?php endif; ?>
          <?php if (isset($results[0])): ?>
            <div class="podium-item first">
              <div class="avatar" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);">
                <?= strtoupper(substr($results[0]['username'], 0, 1)) ?>
                <span class="medal">🥇</span>
              </div>
              <div class="name"><?= htmlspecialchars($results[0]['username']) ?></div>
              <div class="score"><?= $results[0]['score'] ?>/<?= $questionCount ?></div>
              <div class="podium-stand"></div>
            </div>
          <?php endif; ?>
          <?php if (isset($results[2])): ?>
            <div class="podium-item third">
              <div class="avatar" style="background: linear-gradient(135deg, #fb923c 0%, #f97316 100%);">
                <?= strtoupper(substr($results[2]['username'], 0, 1)) ?>
                <span class="medal">🥉</span>
              </div>
              <div class="name"><?= htmlspecialchars($results[2]['username']) ?></div>
              <div class="score"><?= $results[2]['score'] ?>/<?= $questionCount ?></div>
              <div class="podium-stand"></div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Results Table -->
    <div class="results-table-container">
      <div class="results-table-header">
        <h5>📋 All Results</h5>
        <span style="font-size: 0.875rem; color: var(--text-muted);"><?= $totalAttempts ?> entries</span>
      </div>
      
      <?php if (empty($results)): ?>
        <div class="empty-state">
          <div class="icon">📭</div>
          <p>No one has taken this quiz yet.<br>Share it with your students!</p>
        </div>
      <?php else: ?>
        <table class="table">
          <thead>
            <tr>
              <th style="width: 60px;">Rank</th>
              <th>Student</th>
              <th>Score</th>
              <th>Performance</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($results as $i => $r): 
              $pct = $questionCount > 0 ? round(($r['score'] / $questionCount) * 100) : 0;
              $perfClass = $pct >= 80 ? 'excellent' : ($pct >= 60 ? 'good' : ($pct >= 40 ? 'average' : 'needs-work'));
              $perfLabel = $pct >= 80 ? 'Excellent' : ($pct >= 60 ? 'Good' : ($pct >= 40 ? 'Average' : 'Needs Work'));
            ?>
              <tr>
                <td>
                  <?php if ($i === 0): ?>
                    <span style="font-size: 1.25rem;">🥇</span>
                  <?php elseif ($i === 1): ?>
                    <span style="font-size: 1.25rem;">🥈</span>
                  <?php elseif ($i === 2): ?>
                    <span style="font-size: 1.25rem;">🥉</span>
                  <?php else: ?>
                    <span class="text-muted">#<?= $i + 1 ?></span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="user-cell">
                    <div class="user-avatar" style="background: <?= $avatarColors[$i % count($avatarColors)] ?>;">
                      <?= strtoupper(substr($r['username'], 0, 1)) ?>
                    </div>
                    <span class="fw-semibold"><?=htmlspecialchars($r['username'])?></span>
                  </div>
                </td>
                <td>
                  <span class="fw-semibold" style="font-size: 1rem;"><?= $r['score'] ?></span>
                  <span class="text-muted" style="font-size: 0.8125rem;">/<?= $questionCount ?></span>
                </td>
                <td>
                  <span class="score-pill <?= $perfClass ?>"><?= $pct ?>% - <?= $perfLabel ?></span>
                </td>
                <td class="text-muted" style="font-size: 0.875rem;"><?= date('M j, Y g:i A', strtotime($r['taken_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>

    <!-- Action Buttons -->
    <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 2rem;">
      <a href="admin_edit_quiz.php?id=<?= $quiz_id ?>" class="btn btn-action" style="background: var(--admin-gradient); color: white; padding: 0.75rem 1.5rem;">
        ✏️ Edit Quiz
      </a>
      <a href="admin_dashboard.php" class="btn btn-action" style="background: #f1f5f9; color: var(--text-medium); padding: 0.75rem 1.5rem;">
        ← Back to Dashboard
      </a>
    </div>
  </div>

  <script>
    // Animate stats on load
    document.querySelectorAll('.stat-value, .quiz-banner-stat .value').forEach(el => {
      const text = el.textContent;
      const match = text.match(/^(\d+)/);
      if (match) {
        const target = parseInt(match[1]);
        const suffix = text.replace(match[1], '');
        let current = 0;
        const increment = target / 25;
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
    
    // Dark mode toggle
    document.getElementById('darkModeToggle').addEventListener('click', function() {
      fetch('toggle_dark_mode.php', { method: 'POST' })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            document.body.classList.toggle('dark-mode');
            document.getElementById('darkModeIcon').textContent = data.darkMode ? '☀️' : '🌙';
            document.getElementById('darkModeText').textContent = data.darkMode ? 'Light' : 'Dark';
          }
        });
    });
  </script>
</body>
</html>
