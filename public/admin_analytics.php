<?php
session_start();
require_once __DIR__.'/../src/db.php';

// Run migrations
require_once __DIR__.'/../src/migrations.php';
runMigrations($pdo);

// Only admins can view analytics
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$quizId = isset($_GET['quiz_id']) ? intval($_GET['quiz_id']) : 0;

// Get user's dark mode preference
$adminId = $_SESSION['user_id'];
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

// Get quiz info if specific quiz selected (only if it belongs to this admin)
$quiz = null;
if ($quizId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ? AND created_by = ?");
    $stmt->execute([$quizId, $adminId]);
    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get all quizzes for dropdown - ONLY this admin's quizzes
$stmt = $pdo->prepare("SELECT id, title FROM quizzes WHERE created_by = ? ORDER BY title");
$stmt->execute([$adminId]);
$allQuizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate analytics
if ($quizId > 0 && $quiz) {
    // Quiz-specific analytics
    $stmt = $pdo->prepare(
        "SELECT r.*, u.username 
         FROM results r 
         JOIN users u ON r.user_id = u.id 
         WHERE r.quiz_id = ? 
         ORDER BY r.taken_at DESC"
    );
    $stmt->execute([$quizId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get question count
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM questions WHERE quiz_id = ?");
    $stmt->execute([$quizId]);
    $questionCount = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    // Calculate stats
    $totalAttempts = count($results);
    $uniqueStudents = count(array_unique(array_column($results, 'user_id')));
    $scores = array_column($results, 'score');
    $avgScore = $totalAttempts > 0 ? round(array_sum($scores) / $totalAttempts, 1) : 0;
    $maxScore = $totalAttempts > 0 ? max($scores) : 0;
    $minScore = $totalAttempts > 0 ? min($scores) : 0;
    
    // Pass rate
    $passedCount = count(array_filter($results, fn($r) => $r['passed'] === 1 || $r['passed'] === '1'));
    $passRate = $totalAttempts > 0 ? round(($passedCount / $totalAttempts) * 100, 1) : 0;
    
    // Average time
    $times = array_filter(array_column($results, 'time_taken'), fn($t) => $t > 0);
    $avgTime = count($times) > 0 ? round(array_sum($times) / count($times)) : 0;
    
    // Score distribution for chart
    $distribution = [
        '0-20' => 0,
        '21-40' => 0,
        '41-60' => 0,
        '61-80' => 0,
        '81-100' => 0
    ];
    
    foreach ($results as $r) {
        $totalPoints = $r['total_points'] ?? 1;
        $pct = $totalPoints > 0 ? ($r['score'] / $totalPoints) * 100 : 0;
        if ($pct <= 20) $distribution['0-20']++;
        elseif ($pct <= 40) $distribution['21-40']++;
        elseif ($pct <= 60) $distribution['41-60']++;
        elseif ($pct <= 80) $distribution['61-80']++;
        else $distribution['81-100']++;
    }
    
    // Recent results
    $recentResults = array_slice($results, 0, 10);
    
} else {
    // Overall analytics - ONLY for this admin's quizzes
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM quizzes WHERE created_by = ?");
    $stmt->execute([$adminId]);
    $totalQuizzes = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM results r JOIN quizzes q ON r.quiz_id = q.id WHERE q.created_by = ?");
    $stmt->execute([$adminId]);
    $totalAttempts = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT r.user_id) as cnt FROM results r JOIN quizzes q ON r.quiz_id = q.id WHERE q.created_by = ?");
    $stmt->execute([$adminId]);
    $activeStudents = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    
    $stmt = $pdo->prepare("SELECT AVG(r.score) as avg FROM results r JOIN quizzes q ON r.quiz_id = q.id WHERE q.created_by = ?");
    $stmt->execute([$adminId]);
    $avgScoreOverall = round($stmt->fetch(PDO::FETCH_ASSOC)['avg'] ?? 0, 1);
    
    // Top performing quizzes - ONLY for this admin
    $stmt = $pdo->prepare(
        "SELECT q.id, q.title, COUNT(r.id) as attempts, AVG(r.score) as avg_score
         FROM quizzes q
         LEFT JOIN results r ON q.id = r.quiz_id
         WHERE q.created_by = ?
         GROUP BY q.id, q.title
         ORDER BY attempts DESC
         LIMIT 5"
    );
    $stmt->execute([$adminId]);
    $topQuizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent activity - ONLY for this admin's quizzes
    $stmt = $pdo->prepare(
        "SELECT r.*, q.title as quiz_title, u.username
         FROM results r
         JOIN quizzes q ON r.quiz_id = q.id
         JOIN users u ON r.user_id = u.id
         WHERE q.created_by = ?
         ORDER BY r.id DESC
         LIMIT 10"
    );
    $stmt->execute([$adminId]);
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Analytics — QuickQuiz</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/quickquiz.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .chart-container { 
      position: relative; 
      height: 250px; 
      width: 100%; 
      background: <?= $darkMode ? 'rgba(0,0,0,0.2)' : '#f8fafc' ?>; 
      border-radius: 12px; 
      padding: 1rem; 
    }
    .stat-card {
      background: var(--white);
      border-radius: var(--radius-lg);
      padding: 1.5rem;
      box-shadow: var(--shadow);
      text-align: center;
    }
    .stat-card .icon { font-size: 2rem; margin-bottom: 0.5rem; }
    .stat-card .value { font-size: 2rem; font-weight: 800; color: var(--text-dark); }
    .stat-card .label { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .activity-item {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0.75rem;
      border-radius: 8px;
      transition: var(--transition);
    }
    .activity-item:hover { background: <?= $darkMode ? 'rgba(255,255,255,0.05)' : '#f8fafc' ?>; }
    .activity-avatar {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: var(--admin-gradient);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 700;
    }
  </style>
</head>
<body class="admin-theme <?= $darkMode ? 'dark-mode' : '' ?>">
  <div class="top-bar">
    <div class="container d-flex justify-content-between align-items-center">
      <a href="admin_dashboard.php" class="brand" style="text-decoration: none;">
        ⚡ QuickQuiz <small>Admin</small>
      </a>
      <div class="d-flex gap-2 align-items-center">
        <button type="button" id="darkModeToggle" class="btn btn-action" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); padding: 0.5rem 1rem; cursor: pointer;">
          <span id="darkModeIcon"><?= $darkMode ? '☀️' : '🌙' ?></span> <span id="darkModeText"><?= $darkMode ? 'Light' : 'Dark' ?></span>
        </button>
        <a class="btn btn-outline-light btn-action" href="admin_dashboard.php">Dashboard</a>
        <a class="btn btn-outline-light btn-action" href="change_password.php">⚙️</a>
      </div>
    </div>
  </div>

  <div class="page-container" style="max-width: 1200px;">
    <div class="page-header">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
          <h2>📊 Analytics</h2>
          <p><?= $quiz ? htmlspecialchars($quiz['title']) : 'Overview of all quizzes' ?></p>
        </div>
        <div class="d-flex gap-2">
          <select class="form-select" style="min-width: 200px;" onchange="if(this.value) window.location.href='admin_analytics.php?quiz_id='+this.value; else window.location.href='admin_analytics.php';">
            <option value="">All Quizzes</option>
            <?php foreach ($allQuizzes as $q): ?>
              <option value="<?= $q['id'] ?>" <?= $quizId == $q['id'] ? 'selected' : '' ?>><?= htmlspecialchars($q['title']) ?></option>
            <?php endforeach; ?>
          </select>
          <a href="export_results.php<?= $quizId ? '?quiz_id='.$quizId : '' ?>" class="btn" style="background: var(--admin-gradient); color: white; padding: 0.5rem 1rem; font-weight: 600;">
            📥 Export CSV
          </a>
        </div>
      </div>
    </div>

    <?php if ($quizId > 0 && $quiz): ?>
      <!-- Quiz-specific Analytics -->
      <div class="row g-4 mb-4">
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="icon">📝</div>
            <div class="value"><?= $totalAttempts ?></div>
            <div class="label">Total Attempts</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="icon">👥</div>
            <div class="value"><?= $uniqueStudents ?></div>
            <div class="label">Unique Students</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="icon">🎯</div>
            <div class="value"><?= $avgScore ?></div>
            <div class="label">Average Score</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="icon">✅</div>
            <div class="value"><?= $passRate ?>%</div>
            <div class="label">Pass Rate</div>
          </div>
        </div>
      </div>

      <div class="row g-4 mb-4">
        <div class="col-12 col-md-6">
          <div class="card-modern" style="padding: 1.5rem;">
            <h5 style="font-weight: 700; margin: 0 0 1rem; display: flex; align-items: center; gap: 0.5rem;">
              📊 Score Distribution
            </h5>
            <div class="chart-container">
              <canvas id="scoreChart"></canvas>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6">
          <div class="card-modern" style="padding: 1.5rem;">
            <h5 style="font-weight: 700; margin: 0 0 1rem; display: flex; align-items: center; gap: 0.5rem;">
              📈 Quick Stats
            </h5>
            <div style="display: grid; gap: 0.75rem;">
              <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: <?= $darkMode ? 'rgba(0,0,0,0.2)' : '#f8fafc' ?>; border-radius: 8px;">
                <span style="color: var(--text-muted);">Questions</span>
                <strong><?= $questionCount ?></strong>
              </div>
              <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: <?= $darkMode ? 'rgba(0,0,0,0.2)' : '#f8fafc' ?>; border-radius: 8px;">
                <span style="color: var(--text-muted);">Highest Score</span>
                <strong style="color: #22c55e;"><?= $maxScore ?></strong>
              </div>
              <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: <?= $darkMode ? 'rgba(0,0,0,0.2)' : '#f8fafc' ?>; border-radius: 8px;">
                <span style="color: var(--text-muted);">Lowest Score</span>
                <strong style="color: #ef4444;"><?= $minScore ?></strong>
              </div>
              <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: <?= $darkMode ? 'rgba(0,0,0,0.2)' : '#f8fafc' ?>; border-radius: 8px;">
                <span style="color: var(--text-muted);">Avg. Time</span>
                <strong><?= $avgTime > 0 ? gmdate("i:s", $avgTime) : 'N/A' ?></strong>
              </div>
              <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: <?= $darkMode ? 'rgba(0,0,0,0.2)' : '#f8fafc' ?>; border-radius: 8px;">
                <span style="color: var(--text-muted);">Passed</span>
                <strong style="color: #22c55e;"><?= $passedCount ?></strong>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Results -->
      <div class="card-modern" style="padding: 1.5rem;">
        <h5 style="font-weight: 700; margin: 0 0 1rem; display: flex; align-items: center; gap: 0.5rem;">
          🕐 Recent Attempts
        </h5>
        <?php if (empty($recentResults)): ?>
          <p style="color: var(--text-muted); text-align: center; padding: 2rem;">No attempts yet</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Student</th>
                  <th>Score</th>
                  <th>Time</th>
                  <th>Status</th>
                  <th>Date</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentResults as $r): ?>
                  <tr>
                    <td><strong><?= htmlspecialchars($r['username']) ?></strong></td>
                    <td><?= number_format($r['score'], 1) ?>/<?= $r['total_points'] ?? '?' ?></td>
                    <td><?= $r['time_taken'] > 0 ? gmdate("i:s", $r['time_taken']) : '--' ?></td>
                    <td>
                      <?php if ($r['passed'] === '1' || $r['passed'] === 1): ?>
                        <span class="badge" style="background: rgba(34,197,94,0.1); color: #22c55e;">✅ Passed</span>
                      <?php elseif ($r['passed'] === '0' || $r['passed'] === 0): ?>
                        <span class="badge" style="background: rgba(239,68,68,0.1); color: #ef4444;">❌ Failed</span>
                      <?php else: ?>
                        <span class="badge" style="background: rgba(100,116,139,0.1); color: #64748b;">--</span>
                      <?php endif; ?>
                    </td>
                    <td style="color: var(--text-muted);"><?= date('M j, g:i A', strtotime($r['created_at'])) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <script>
        new Chart(document.getElementById('scoreChart'), {
          type: 'bar',
          data: {
            labels: ['0-20%', '21-40%', '41-60%', '61-80%', '81-100%'],
            datasets: [{
              label: 'Students',
              data: [<?= implode(',', array_values($distribution)) ?>],
              backgroundColor: [
                'rgba(239, 68, 68, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(59, 130, 246, 0.8)',
                'rgba(34, 197, 94, 0.8)',
                'rgba(124, 58, 237, 0.8)'
              ],
              borderRadius: 8,
            }]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
              y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
          }
        });
      </script>

    <?php else: ?>
      <!-- Overall Analytics -->
      <div class="row g-4 mb-4">
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="icon">📚</div>
            <div class="value"><?= $totalQuizzes ?></div>
            <div class="label">Total Quizzes</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="icon">📝</div>
            <div class="value"><?= $totalAttempts ?></div>
            <div class="label">Total Attempts</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="icon">👥</div>
            <div class="value"><?= $activeStudents ?></div>
            <div class="label">Active Students</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card">
            <div class="icon">🎯</div>
            <div class="value"><?= $avgScoreOverall ?></div>
            <div class="label">Avg. Score</div>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-12 col-md-6">
          <div class="card-modern" style="padding: 1.5rem;">
            <h5 style="font-weight: 700; margin: 0 0 1rem; display: flex; align-items: center; gap: 0.5rem;">
              🏆 Top Quizzes
            </h5>
            <?php if (empty($topQuizzes)): ?>
              <p style="color: var(--text-muted); text-align: center; padding: 2rem;">No quizzes yet</p>
            <?php else: ?>
              <?php foreach ($topQuizzes as $i => $q): ?>
                <div class="activity-item">
                  <div class="activity-avatar"><?= $i + 1 ?></div>
                  <div style="flex: 1;">
                    <div style="font-weight: 600;"><?= htmlspecialchars($q['title']) ?></div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                      <?= $q['attempts'] ?> attempts • Avg: <?= round($q['avg_score'] ?? 0, 1) ?>
                    </div>
                  </div>
                  <a href="admin_analytics.php?quiz_id=<?= $q['id'] ?>" style="color: var(--admin-primary); font-size: 0.8rem; font-weight: 600; text-decoration: none;">
                    View →
                  </a>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="col-12 col-md-6">
          <div class="card-modern" style="padding: 1.5rem;">
            <h5 style="font-weight: 700; margin: 0 0 1rem; display: flex; align-items: center; gap: 0.5rem;">
              🕐 Recent Activity
            </h5>
            <?php if (empty($recentActivity)): ?>
              <p style="color: var(--text-muted); text-align: center; padding: 2rem;">No activity yet</p>
            <?php else: ?>
              <?php foreach ($recentActivity as $r): ?>
                <div class="activity-item">
                  <div class="activity-avatar"><?= strtoupper(substr($r['username'], 0, 1)) ?></div>
                  <div style="flex: 1;">
                    <div style="font-weight: 600;"><?= htmlspecialchars($r['username']) ?></div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                      <?= htmlspecialchars($r['quiz_title']) ?> • Score: <?= number_format($r['score'], 1) ?>
                    </div>
                  </div>
                  <div style="font-size: 0.75rem; color: var(--text-muted);">
                    <?= isset($r['taken_at']) ? date('M j', strtotime($r['taken_at'])) : 'N/A' ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
  <script>
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
