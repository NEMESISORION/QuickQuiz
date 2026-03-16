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
    header("Location: admin_create_quiz.php");
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

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $timeLimit = intval($_POST['time_limit'] ?? 0);
    if ($title === '') $error = "Title required";
    if (!$error) {
        $stmt = $pdo->prepare("INSERT INTO quizzes (title, description, time_limit, created_by) VALUES (?, ?, ?, ?) RETURNING id");
        $stmt->execute([$title, $desc, $timeLimit, $adminId]);
        $id = $stmt->fetchColumn();
        header("Location: admin_edit_quiz.php?id=$id");
        exit;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create Quiz — QuickQuiz Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/quickquiz.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
  <style>
    /* Dark mode overrides for create quiz page */
    body.dark-mode .form-card {
      background: #1e293b !important;
      border-color: #334155 !important;
    }
    body.dark-mode .form-label {
      color: #e2e8f0 !important;
    }
    body.dark-mode .form-control {
      background: #0f172a !important;
      border-color: #475569 !important;
      color: #f1f5f9 !important;
    }
    body.dark-mode .form-control::placeholder {
      color: #64748b !important;
    }
    body.dark-mode .text-muted {
      color: #94a3b8 !important;
    }
    body.dark-mode .time-limit-box {
      background: linear-gradient(135deg, #1e3a2f 0%, #14332a 100%) !important;
      border-color: #22c55e !important;
    }
    body.dark-mode .time-limit-box .form-label {
      color: #4ade80 !important;
    }
    body.dark-mode .time-limit-box .text-muted {
      color: #86efac !important;
    }
    body.dark-mode .time-limit-box .form-control {
      background: #0f172a !important;
      border-color: #22c55e !important;
      color: #f1f5f9 !important;
    }
    body.dark-mode .time-limit-box .input-group-text {
      background: #166534 !important;
      border-color: #22c55e !important;
      color: white !important;
    }
    body.dark-mode .time-limit-box .hint {
      color: #4ade80 !important;
    }
    body.dark-mode .section-title {
      color: #f1f5f9 !important;
    }
  </style>
</head>
<body class="admin-theme <?= $darkMode ? 'dark-mode' : '' ?>">
  <div class="top-bar">
    <div class="container d-flex justify-content-between align-items-center">
      <div class="brand">⚡ QuickQuiz <small>ADMIN</small></div>
      <div class="d-flex gap-2 align-items-center">
        <button type="button" id="darkModeToggle" class="btn btn-action" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); padding: 0.5rem 1rem; font-size: 0.9rem; cursor: pointer;">
          <span id="darkModeIcon"><?= $darkMode ? '☀️' : '🌙' ?></span> <span id="darkModeText"><?= $darkMode ? 'Light' : 'Dark' ?></span>
        </button>
        <a class="btn btn-outline-light btn-action" href="admin_dashboard.php" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 12H5m7-7-7 7 7 7"/></svg>
          Back
        </a>
      </div>
    </div>
  </div>

  <div class="page-container">
    <div class="row justify-content-center">
      <div class="col-lg-6">
        <h4 class="section-title">
          <span class="icon">✨</span>
          Create New Quiz
        </h4>
        
        <div class="form-card">
          <?php if ($error): ?>
            <div class="alert alert-danger" style="border-radius: 12px; border: none;"><?=htmlspecialchars($error)?></div>
          <?php endif; ?>
          
          <form method="post">
            <!-- Quiz Title -->
            <div class="mb-4">
              <label class="form-label" style="font-weight: 600; color: #334155; font-size: 0.875rem;">Quiz Title</label>
              <input name="title" class="form-control" style="font-size: 1.125rem; font-weight: 600; border: 2px solid #e2e8f0; padding: 0.75rem 1rem;" placeholder="e.g., JavaScript Fundamentals" required autofocus>
              <small class="text-muted mt-2 d-block">Give your quiz a clear, descriptive title</small>
            </div>
            
            <!-- Description -->
            <div class="mb-4">
              <label class="form-label" style="font-weight: 600; color: #334155; font-size: 0.875rem;">Description <span class="text-muted fw-normal">(optional)</span></label>
              <textarea name="description" class="form-control" rows="3" style="border: 2px solid #e2e8f0; font-size: 0.9375rem; padding: 0.75rem 1rem;" placeholder="Describe what this quiz covers..."></textarea>
            </div>
            
            <!-- Time Limit -->
            <div class="mb-4 time-limit-box" style="background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border-radius: 12px; padding: 1rem; border: 1px solid #bbf7d0;">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                  <label class="form-label mb-1" style="font-weight: 600; color: #166534; font-size: 0.875rem;">⏱️ Time Limit (minutes)</label>
                  <div class="text-muted" style="font-size: 0.75rem;">Set how long students have to complete this quiz</div>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <input type="number" name="time_limit" class="form-control text-center" min="0" max="180" value="0" style="width: 100px; border: 2px solid #86efac; font-weight: 600; font-size: 1rem;">
                </div>
              </div>
              <div class="mt-2 hint" style="font-size: 0.75rem; color: #15803d;">
                💡 Set to <strong>0</strong> for auto-timing (1 minute per question)
              </div>
            </div>
            
            <!-- Create Button -->
            <div class="d-flex gap-3" style="margin-top: 2rem;">
              <button class="btn btn-primary btn-action flex-grow-1" style="background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%); border: none; padding: 1rem; font-size: 1rem; color: white; font-weight: 700; box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);">
                ✨ Create Quiz
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="ms-1"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
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
