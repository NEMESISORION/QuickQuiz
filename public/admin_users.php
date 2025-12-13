<?php
session_start();
require_once __DIR__.'/../src/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { 
    header("Location: login.php"); 
    exit; 
}

$adminId = $_SESSION['user_id'];

// Add created_by_admin column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN created_by_admin INT DEFAULT NULL");
} catch (PDOException $e) {
    // Column already exists
}

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
    header("Location: admin_users.php");
    exit;
}

$success = null;
$error = null;

// Handle delete user - only if created by this admin or took this admin's quiz
if (isset($_GET['delete']) && $_GET['delete'] != $adminId) {
    $deleteId = intval($_GET['delete']);
    // Check if this admin can manage this user
    $stmt = $pdo->prepare("
        SELECT id FROM users 
        WHERE id = ? AND (
            created_by_admin = ? 
            OR id IN (SELECT DISTINCT r.user_id FROM results r JOIN quizzes q ON r.quiz_id = q.id WHERE q.created_by = ?)
        )
    ");
    $stmt->execute([$deleteId, $adminId, $adminId]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$deleteId]);
        header("Location: admin_users.php?deleted=1");
        exit;
    }
}

// Handle role change - only for users this admin can manage
if (isset($_GET['toggle_role'])) {
    $userId = intval($_GET['toggle_role']);
    if ($userId != $adminId) {
        // Check if this admin can manage this user
        $stmt = $pdo->prepare("
            SELECT role FROM users 
            WHERE id = ? AND (
                created_by_admin = ? 
                OR id IN (SELECT DISTINCT r.user_id FROM results r JOIN quizzes q ON r.quiz_id = q.id WHERE q.created_by = ?)
            )
        ");
        $stmt->execute([$userId, $adminId, $adminId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $newRole = $user['role'] === 'admin' ? 'student' : 'admin';
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $userId]);
        }
    }
    header("Location: admin_users.php?updated=1");
    exit;
}

// Handle add user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    
    if (strlen($username) < 3) {
        $error = "Username must be at least 3 characters";
    } elseif (strlen($password) < 4) {
        $error = "Password must be at least 4 characters";
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Username already exists";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, created_by_admin) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hashedPassword, $role, $adminId]);
            header("Location: admin_users.php?added=1");
            exit;
        }
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $userId = intval($_POST['user_id'] ?? 0);
    $newPassword = $_POST['new_password'] ?? '';
    
    if (strlen($newPassword) >= 4) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);
        header("Location: admin_users.php?reset=1");
        exit;
    } else {
        $error = "Password must be at least 4 characters";
    }
}

// Check for success messages
if (isset($_GET['deleted'])) $success = "User deleted successfully!";
if (isset($_GET['updated'])) $success = "User role updated!";
if (isset($_GET['added'])) $success = "User added successfully!";
if (isset($_GET['reset'])) $success = "Password reset successfully!";

// Get users: only those created by this admin OR students who took this admin's quizzes
// Also always include the current admin themselves
$stmt = $pdo->prepare("
    SELECT DISTINCT u.*, 
           (SELECT COUNT(*) FROM results r 
            JOIN quizzes q ON r.quiz_id = q.id 
            WHERE r.user_id = u.id AND q.created_by = ?) as quiz_count,
           (SELECT COALESCE(SUM(r.score), 0) FROM results r 
            JOIN quizzes q ON r.quiz_id = q.id 
            WHERE r.user_id = u.id AND q.created_by = ?) as total_score
    FROM users u 
    WHERE u.id = ?
       OR u.created_by_admin = ?
       OR u.id IN (
           SELECT DISTINCT r.user_id 
           FROM results r 
           JOIN quizzes q ON r.quiz_id = q.id 
           WHERE q.created_by = ?
       )
    ORDER BY u.role DESC, u.id DESC
");
$stmt->execute([$adminId, $adminId, $adminId, $adminId, $adminId]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count stats
$totalUsers = count($users);
$adminCount = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
$studentCount = $totalUsers - $adminCount;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>User Management — QuickQuiz Admin</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/quickquiz.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
  <style>
    .user-stats {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1rem;
      margin-bottom: 2rem;
    }
    
    .user-stat {
      background: white;
      border-radius: 16px;
      padding: 1.25rem;
      text-align: center;
      box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    
    .user-stat .number {
      font-size: 2rem;
      font-weight: 800;
      background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    
    .user-stat .label {
      font-size: 0.75rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #64748b;
      font-weight: 600;
    }
    
    .user-card {
      background: white;
      border-radius: 16px;
      padding: 1.25rem;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      transition: all 0.2s;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    
    .user-card:hover {
      box-shadow: 0 8px 25px rgba(0,0,0,0.08);
      transform: translateY(-2px);
    }
    
    .user-avatar {
      width: 50px;
      height: 50px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      flex-shrink: 0;
    }
    
    .user-avatar.admin {
      background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    }
    
    .user-avatar.student {
      background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    }
    
    .user-info {
      flex-grow: 1;
      min-width: 0;
    }
    
    .user-name {
      font-weight: 700;
      color: #0f172a;
      font-size: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .user-name .you-badge {
      font-size: 0.625rem;
      background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
      color: white;
      padding: 0.125rem 0.5rem;
      border-radius: 20px;
      font-weight: 600;
    }
    
    .user-meta {
      font-size: 0.8125rem;
      color: #64748b;
      display: flex;
      gap: 1rem;
      margin-top: 0.25rem;
    }
    
    .role-badge {
      font-size: 0.6875rem;
      padding: 0.25rem 0.625rem;
      border-radius: 20px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .role-badge.admin {
      background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
      color: white;
    }
    
    .role-badge.student {
      background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
      color: white;
    }
    
    /* Dark mode role badges */
    body.dark-mode .role-badge.admin {
      background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%);
      color: white;
    }
    
    body.dark-mode .role-badge.student {
      background: linear-gradient(135deg, #14b8a6 0%, #2dd4bf 100%);
      color: white;
    }
    
    .user-actions {
      display: flex;
      gap: 0.5rem;
      flex-shrink: 0;
    }
    
    .btn-action-sm {
      padding: 0.5rem 0.875rem;
      border-radius: 10px;
      font-size: 0.75rem;
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
    }
    
    .btn-toggle {
      background: #f1f5f9;
      color: #475569;
    }
    
    .btn-toggle:hover {
      background: #e2e8f0;
    }
    
    .btn-reset {
      background: #fef3c7;
      color: #b45309;
    }
    
    .btn-reset:hover {
      background: #fde68a;
    }
    
    .btn-danger-sm {
      background: #fee2e2;
      color: #dc2626;
    }
    
    .btn-danger-sm:hover {
      background: #fecaca;
    }
    
    .add-user-card {
      background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
      border: 2px dashed #e2e8f0;
      border-radius: 16px;
      padding: 1.5rem;
      margin-bottom: 2rem;
    }
    
    /* Dark mode overrides */
    body.dark-mode .add-user-card {
      background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%) !important;
      border-color: rgba(255, 255, 255, 0.2) !important;
    }
    
    body.dark-mode .user-stats {
      background: #1e293b !important;
    }
    
    body.dark-mode .user-stat {
      background: transparent !important;
    }
    
    body.dark-mode .user-stat .number {
      color: #f1f5f9 !important;
    }
    
    body.dark-mode .user-stat .label {
      color: #94a3b8 !important;
    }
    
    body.dark-mode .user-card {
      background: #1e293b !important;
      border-color: rgba(255, 255, 255, 0.1) !important;
    }
    
    body.dark-mode .user-card:hover {
      background: #334155 !important;
    }
    
    body.dark-mode .user-name {
      color: #f1f5f9 !important;
    }
    
    body.dark-mode .modal-content {
      background: #1e293b !important;
      color: #e2e8f0 !important;
    }
    
    body.dark-mode .fw-bold.text-dark {
      color: #f1f5f9 !important;
    }
    
    body.dark-mode h4.fw-bold,
    body.dark-mode h6.fw-bold {
      color: #f1f5f9 !important;
    }
    
    .modal-backdrop {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.5);
      backdrop-filter: blur(4px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 2000;
      padding: 1rem;
    }
    
    .modal-content {
      background: white;
      border-radius: 20px;
      max-width: 400px;
      width: 100%;
      padding: 1.5rem;
      box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
      animation: modalEnter 0.3s ease;
    }
    
    @keyframes modalEnter {
      from { opacity: 0; transform: scale(0.95) translateY(20px); }
      to { opacity: 1; transform: scale(1) translateY(0); }
    }
    
    .success-toast {
      position: fixed;
      top: 100px;
      right: 20px;
      background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
      color: white;
      padding: 1rem 1.5rem;
      border-radius: 12px;
      font-weight: 600;
      box-shadow: 0 10px 40px rgba(34, 197, 94, 0.3);
      z-index: 3000;
      animation: toastEnter 0.3s ease, toastExit 0.3s ease 2.7s forwards;
    }
    
    @keyframes toastEnter {
      from { opacity: 0; transform: translateX(100px); }
      to { opacity: 1; transform: translateX(0); }
    }
    
    @keyframes toastExit {
      from { opacity: 1; transform: translateX(0); }
      to { opacity: 0; transform: translateX(100px); }
    }
    
    @media (max-width: 768px) {
      .user-stats {
        grid-template-columns: 1fr;
      }
      
      .user-card {
        flex-direction: column;
        text-align: center;
      }
      
      .user-meta {
        justify-content: center;
        flex-wrap: wrap;
      }
      
      .user-actions {
        width: 100%;
        justify-content: center;
        flex-wrap: wrap;
      }
    }
  </style>
</head>
<body class="admin-theme <?= $darkMode ? 'dark-mode' : '' ?>">
  <?php if ($success): ?>
    <div class="success-toast">✓ <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="top-bar">
    <div class="container d-flex justify-content-between align-items-center">
      <div class="brand">⚡ QuickQuiz <small>ADMIN</small></div>
      <div class="d-flex gap-2 align-items-center">
        <button type="button" id="darkModeToggle" class="btn btn-action" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); padding: 0.5rem 1rem; cursor: pointer;">
          <span id="darkModeIcon"><?= $darkMode ? '☀️' : '🌙' ?></span> <span id="darkModeText"><?= $darkMode ? 'Light' : 'Dark' ?></span>
        </button>
        <a class="btn btn-outline-light btn-action" href="admin_dashboard.php">
          ← Dashboard
        </a>
      </div>
    </div>
  </div>

  <div class="page-container">
    <h4 class="fw-bold text-dark mb-1">👥 User Management</h4>
    <p class="text-muted mb-4">Manage all QuickQuiz users</p>

    <!-- User Stats -->
    <div class="user-stats">
      <div class="user-stat">
        <div class="number"><?= $totalUsers ?></div>
        <div class="label">Total Users</div>
      </div>
      <div class="user-stat">
        <div class="number"><?= $adminCount ?></div>
        <div class="label">Admins</div>
      </div>
      <div class="user-stat">
        <div class="number"><?= $studentCount ?></div>
        <div class="label">Students</div>
      </div>
    </div>

    <!-- Add User Form -->
    <div class="add-user-card">
      <h6 class="fw-bold mb-3">➕ Add New User</h6>
      
      <?php if ($error): ?>
        <div class="alert alert-danger" style="border-radius: 12px; border: none; margin-bottom: 1rem;"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      
      <form method="post" class="row g-3 align-items-end">
        <input type="hidden" name="add_user" value="1">
        <div class="col-md-3">
          <label class="form-label small">Username</label>
          <input type="text" name="username" class="form-control" placeholder="Username" required minlength="3">
        </div>
        <div class="col-md-3">
          <label class="form-label small">Password</label>
          <input type="password" name="password" class="form-control" placeholder="Password" required minlength="4">
        </div>
        <div class="col-md-3">
          <label class="form-label small">Role</label>
          <select name="role" class="form-select">
            <option value="student">Student</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="col-md-3">
          <button type="submit" class="btn w-100" style="background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); color: white; font-weight: 600;">
            Add User
          </button>
        </div>
      </form>
    </div>

    <!-- Users List -->
    <h5 class="section-title">
      <span class="icon">📋</span>
      All Users
    </h5>

    <?php foreach ($users as $user): ?>
      <div class="user-card">
        <div class="user-avatar <?= $user['role'] ?>">
          <?= $user['role'] === 'admin' ? '👨‍💼' : '👨‍🎓' ?>
        </div>
        <div class="user-info">
          <div class="user-name">
            <?= htmlspecialchars($user['username']) ?>
            <?php if ($user['id'] == $_SESSION['user_id']): ?>
              <span class="you-badge">YOU</span>
            <?php endif; ?>
            <span class="role-badge <?= $user['role'] ?>"><?= $user['role'] ?></span>
          </div>
          <div class="user-meta">
            <span>📅 Joined <?= isset($user['created_at']) ? date('M j, Y', strtotime($user['created_at'])) : 'N/A' ?></span>
            <span>📝 <?= $user['quiz_count'] ?> quizzes taken</span>
            <span>⭐ <?= $user['total_score'] ?? 0 ?> XP</span>
          </div>
        </div>
        <div class="user-actions">
          <?php if ($user['id'] != $_SESSION['user_id']): ?>
            <a href="?toggle_role=<?= $user['id'] ?>" class="btn-action-sm btn-toggle" 
               onclick="return confirm('Change role to <?= $user['role'] === 'admin' ? 'Student' : 'Admin' ?>?')">
              🔄 Toggle Role
            </a>
            <button type="button" class="btn-action-sm btn-reset" 
                    onclick="showResetModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
              🔑 Reset Password
            </button>
            <a href="?delete=<?= $user['id'] ?>" class="btn-action-sm btn-danger-sm" 
               onclick="return confirm('Delete user \'<?= htmlspecialchars($user['username']) ?>\'? This cannot be undone.')">
              🗑️ Delete
            </a>
          <?php else: ?>
            <span class="text-muted small">Current session</span>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Reset Password Modal -->
  <div class="modal-backdrop" id="resetModal" style="display: none;">
    <div class="modal-content">
      <h5 class="fw-bold mb-3">🔑 Reset Password</h5>
      <p class="text-muted mb-3">Enter new password for <strong id="resetUsername"></strong></p>
      <form method="post">
        <input type="hidden" name="reset_password" value="1">
        <input type="hidden" name="user_id" id="resetUserId">
        <div class="mb-3">
          <input type="password" name="new_password" class="form-control" placeholder="New password" required minlength="4">
        </div>
        <div class="d-flex gap-2">
          <button type="button" class="btn flex-grow-1" style="background: #f1f5f9; color: #334155;" onclick="hideResetModal()">
            Cancel
          </button>
          <button type="submit" class="btn flex-grow-1" style="background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); color: white; font-weight: 600;">
            Reset Password
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function showResetModal(userId, username) {
      document.getElementById('resetUserId').value = userId;
      document.getElementById('resetUsername').textContent = username;
      document.getElementById('resetModal').style.display = 'flex';
    }
    
    function hideResetModal() {
      document.getElementById('resetModal').style.display = 'none';
    }
    
    // Close modal on backdrop click
    document.getElementById('resetModal').addEventListener('click', function(e) {
      if (e.target === this) hideResetModal();
    });
    
    // Close on Escape
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') hideResetModal();
    });
    
    // Auto-hide toast
    setTimeout(function() {
      var toast = document.querySelector('.success-toast');
      if (toast) toast.style.display = 'none';
    }, 3000);
    
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
