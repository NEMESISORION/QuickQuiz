<?php
session_start();
require_once __DIR__ . '/../src/db.php';

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $validPassword = false;
    if ($user) {
        if (password_verify($password, $user['password'])) {
            $validPassword = true;
        } elseif (hash_equals($user['password'], md5($password))) {
            // Seamless migration for old MD5 passwords after a successful login.
            $validPassword = true;
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $upgradeStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upgradeStmt->execute([$newHash, $user['id']]);
        }
    }

    if ($validPassword) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['username'];

        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: student_dashboard.php");
        }
        exit;
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>QuizQuick - Login</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
  <div class="login-container">
    <div class="login-header">
      <div class="login-logo">⚡</div>
      <h1>QuickQuiz</h1>
      <p>Fast. Simple. Effective Learning.</p>
    </div>

    <div class="login-form">
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="post">
        <div class="form-group">
          <label>Username</label>
          <input name="username" class="form-control" placeholder="Enter your username" required>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input name="password" type="password" class="form-control" placeholder="Enter your password" required>
        </div>
        <button class="btn-login">Sign In →</button>
      </form>
    </div>

    <div class="demo-credentials">
      <h5>🎓 Demo Credentials</h5>
      <div class="demo-credentials-item">
        <span>👨 Admin</span>
        <span><code>admin</code> / <code>1234</code></span>
      </div>
      <div class="demo-credentials-item">
        <span>👨 Student</span>
        <span><code>student</code> / <code>1234</code></span>
      </div>
    </div>

    <div style="text-align: center; margin-top: 24px; font-size: 13px; color: #6b7280;">
      <span style="display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
        <span>🚀 Fast Quizzes</span>
        <span>📚 Track Progress</span>
        <span>🏆 Earn Achievements</span>
      </span>
    </div>
  </div>
</body>
</html>
