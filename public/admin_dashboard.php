<?php
session_start();
require_once __DIR__.'/../src/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }

$stmt = $pdo->query("SELECT * FROM quizzes ORDER BY created_at DESC");
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html><head>
  <meta charset="utf-8"><title>Admin Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head><body>
  <div class="admin-navbar">
    <div class="admin-navbar-brand">
      ⚡ QuickQuiz <span class="admin-navbar-badge">ADMIN</span>
    </div>
    <div class="admin-navbar-menu">
      <a href="admin_dashboard.php">Analytics</a>
      <a href="admin_dashboard.php">Settings</a>
      <a href="admin_dashboard.php">Dark</a>
      <a href="logout.php">Sign Out</a>
    </div>
  </div>

  <div style="container-type: inline-size; padding: 32px;">
    <div class="admin-header-card">
      <h2>📊 Admin Dashboard</h2>
      <p>Manage your quizzes, track student performance, and monitor overall progress from here.</p>
      <div class="admin-stats">
        <div class="admin-stat-item">
          <span class="number">1</span>
          <span class="label">Quizzes</span>
        </div>
        <div class="admin-stat-item">
          <span class="number">1</span>
          <span class="label">Students</span>
        </div>
        <div class="admin-stat-item">
          <span class="number">1</span>
          <span class="label">Attempts</span>
        </div>
      </div>
    </div>

    <div class="admin-action-buttons">
      <a href="admin_create_quiz.php" class="admin-action-btn">
        <i>➕</i>
        Create Quiz
      </a>
      <a href="admin_dashboard.php" class="admin-action-btn">
        <i>📋</i>
        View All Quizzes
      </a>
      <a href="admin_dashboard.php" class="admin-action-btn">
        <i>👥</i>
        Manage Users
      </a>
      <a href="admin_dashboard.php" class="admin-action-btn">
        <i>⚡</i>
        Recent Activity
      </a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px;">
      <div class="stat-card">
        <div class="icon">📦</div>
        <div class="value">1</div>
        <div class="label">Total Quizzes</div>
      </div>
      <div class="stat-card">
        <div class="icon">❓</div>
        <div class="value">1</div>
        <div class="label">Total Questions</div>
      </div>
      <div class="stat-card">
        <div class="icon">👥</div>
        <div class="value">1</div>
        <div class="label">Students</div>
      </div>
      <div class="stat-card">
        <div class="icon">📊</div>
        <div class="value">1</div>
        <div class="label">Avg. Score</div>
      </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;">
      <div class="quizzes-section">
        <div class="section-title">📚 Quiz Performance</div>
        <div style="background: linear-gradient(135deg, #8b5cf6 0%, #d946ef 100%); height: 240px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-size: 12px;">
          Chart
        </div>
      </div>
      <div class="quizzes-section">
        <div class="section-title">⚡ Recent Activity <span style="margin-left: auto; font-size: 12px; color: #6b7280;">Last 10</span></div>
        <div>
          <div style="display: flex; align-items: center; gap: 12px; padding: 12px; border-bottom: 1px solid #e5e7eb;">
            <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #8b5cf6 0%, #a78bfa 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700;">S</div>
            <div>
              <div style="font-weight: 600; color: #1f2937;">student completed <strong>valo</strong></div>
              <div style="font-size: 12px; color: #6b7280;">Dec 12, 3:30 PM</div>
            </div>
            <span style="background: #10b981; color: white; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; margin-left: auto;">1 pts</span>
          </div>
        </div>
      </div>
    </div>

    <div class="quizzes-section">
      <div class="section-title">🏆 Top Performers</div>
      <div style="text-align: center; padding: 40px;">
        <div style="width: 80px; height: 80px; background: linear-gradient(135deg, #fbbf24 0%, #fb923c 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; font-size: 32px;">👤</div>
        <div style="font-weight: 600; margin-bottom: 4px;">student</div>
        <div style="font-size: 12px; color: #6b7280;">1 pts</div>
      </div>
    </div>
  </div>
</body></html>
