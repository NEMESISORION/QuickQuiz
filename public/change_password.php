<?php
session_start();
require_once __DIR__.'/../src/db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'];
$error = '';
$success = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'All fields are required.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'New password must be at least 6 characters.';
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            $error = 'Current password is incorrect.';
        } else {
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            $success = 'Password changed successfully!';
        }
    }
}

// Get user info
$stmt = $pdo->prepare("SELECT username, dark_mode FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle dark mode toggle
if (isset($_POST['toggle_dark_mode'])) {
    $newMode = isset($userData['dark_mode']) && $userData['dark_mode'] ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE users SET dark_mode = ? WHERE id = ?");
    $stmt->execute([$newMode, $userId]);
    header("Location: change_password.php");
    exit;
}

$darkMode = isset($userData['dark_mode']) && $userData['dark_mode'];
$themeClass = $role === 'admin' ? 'admin-theme' : 'student-theme';
$dashboardUrl = $role === 'admin' ? 'admin_dashboard.php' : 'student_dashboard.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Settings — QuickQuiz</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/quickquiz.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
</head>
<body class="<?= $themeClass ?> <?= $darkMode ? 'dark-mode' : '' ?>">
  <div class="top-bar">
    <div class="container d-flex justify-content-between align-items-center">
      <a href="<?= $dashboardUrl ?>" class="brand" style="text-decoration: none;">
        ⚡ QuickQuiz
        <small><?= ucfirst($role) ?></small>
      </a>
      <div class="d-flex gap-2">
        <form method="post" style="display: inline;">
          <button type="submit" name="toggle_dark_mode" class="dark-mode-toggle" title="Toggle Dark Mode">
            <?= $darkMode ? '☀️' : '🌙' ?>
          </button>
        </form>
        <a class="btn btn-outline-light btn-action" href="<?= $dashboardUrl ?>">Dashboard</a>
      </div>
    </div>
  </div>

  <div class="page-container" style="max-width: 600px;">
    <div class="page-header">
      <h2>⚙️ Settings</h2>
      <p>Manage your account settings</p>
    </div>

    <!-- Account Info -->
    <div class="card-modern" style="margin-bottom: 1.5rem;">
      <div style="padding: 1.5rem;">
        <h5 style="margin: 0 0 1rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
          👤 Account Information
        </h5>
        <div style="display: grid; gap: 0.75rem;">
          <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: <?= $darkMode ? 'rgba(0,0,0,0.2)' : '#f8fafc' ?>; border-radius: 8px;">
            <span style="color: var(--text-muted);">Username</span>
            <strong><?= htmlspecialchars($userData['username']) ?></strong>
          </div>
          <div style="display: flex; justify-content: space-between; padding: 0.75rem; background: <?= $darkMode ? 'rgba(0,0,0,0.2)' : '#f8fafc' ?>; border-radius: 8px;">
            <span style="color: var(--text-muted);">Role</span>
            <strong><?= ucfirst($role) ?></strong>
          </div>
        </div>
      </div>
    </div>

    <!-- Dark Mode -->
    <div class="card-modern" style="margin-bottom: 1.5rem;">
      <div style="padding: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <div>
            <h5 style="margin: 0 0 0.25rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
              🌙 Dark Mode
            </h5>
            <p style="margin: 0; color: var(--text-muted); font-size: 0.875rem;">
              Switch between light and dark themes
            </p>
          </div>
          <form method="post">
            <button type="submit" name="toggle_dark_mode" 
                    class="btn" 
                    style="background: <?= $darkMode ? 'linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%)' : 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)' ?>; 
                           color: white; padding: 0.5rem 1.25rem; font-weight: 600;">
              <?= $darkMode ? '☀️ Light Mode' : '🌙 Dark Mode' ?>
            </button>
          </form>
        </div>
      </div>
    </div>

    <!-- Change Password -->
    <div class="card-modern">
      <div style="padding: 1.5rem;">
        <h5 style="margin: 0 0 1rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
          🔐 Change Password
        </h5>
        
        <?php if ($error): ?>
          <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.875rem;">
            ⚠️ <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
          <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: #22c55e; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.875rem;">
            ✅ <?= htmlspecialchars($success) ?>
          </div>
        <?php endif; ?>
        
        <form method="post">
          <div style="margin-bottom: 1rem;">
            <label class="form-label" style="font-weight: 600; font-size: 0.875rem;">Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
          </div>
          <div style="margin-bottom: 1rem;">
            <label class="form-label" style="font-weight: 600; font-size: 0.875rem;">New Password</label>
            <input type="password" name="new_password" class="form-control" required minlength="6">
            <small style="color: var(--text-muted);">Minimum 6 characters</small>
          </div>
          <div style="margin-bottom: 1.5rem;">
            <label class="form-label" style="font-weight: 600; font-size: 0.875rem;">Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
          </div>
          <button type="submit" name="change_password" class="btn w-100" 
                  style="background: <?= $role === 'admin' ? 'var(--admin-gradient)' : 'var(--student-gradient)' ?>; 
                         color: white; padding: 0.875rem; font-weight: 600;">
            🔒 Update Password
          </button>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
