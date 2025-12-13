<?php
session_start();
require_once __DIR__ . '/../src/db.php';

$error = null;
$needAdminCode = false;
$savedUsername = '';

// Get redirect - prioritize POST (from form) over GET (from URL)
$redirect = '';
if (isset($_POST['redirect']) && !empty($_POST['redirect'])) {
    $redirect = $_POST['redirect'];
} elseif (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
    $redirect = $_GET['redirect'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  $admin_code = trim($_POST['admin_code'] ?? '');
  $savedUsername = $username;

  $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
  $stmt->execute([$username]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  $verified = false;
  $needsRehash = false;

  if ($user) {
    $stored = $user['password'];
    // Detect modern hashes; otherwise treat as legacy MD5
    if (preg_match('/^\$(2y|2a|2b|argon2i|argon2id)\$/', $stored)) {
      if (password_verify($password, $stored)) {
        $verified = true;
        if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
          $needsRehash = true;
        }
      }
    } elseif ($stored === md5($password)) {
      $verified = true;
      $needsRehash = true;
    }
  }

  if ($verified) {
    // Check admin code for admin users
    if ($user['role'] === 'admin' && !empty($user['admin_code'])) {
      if (empty($admin_code)) {
        $needAdminCode = true;
        $error = "Admin code required";
        $verified = false;
      } elseif (!password_verify($admin_code, $user['admin_code'])) {
        $error = "Incorrect admin code";
        $verified = false;
      }
    }
    
    if ($verified) {
      if ($needsRehash) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->execute([$newHash, $user['id']]);
      }

      $_SESSION['user_id'] = $user['id'];
      $_SESSION['role'] = $user['role'];
      $_SESSION['username'] = $user['username'];

      // Redirect to original page if set, otherwise to dashboard
      if (!empty($redirect) && $user['role'] === 'student' && strpos($redirect, 'take_quiz.php') !== false) {
        header("Location: " . $redirect);
        exit;
      } elseif ($user['role'] === 'admin') {
        header("Location: admin_dashboard.php");
        exit;
      } else {
        header("Location: student_dashboard.php");
        exit;
      }
    }
  } else if (!$error) {
    $error = "Invalid username or password";
  }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>QuickQuiz — Sign In</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/quickquiz.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
  <style>
    /* Enhanced Login Styles */
    body.login-theme {
      background: linear-gradient(135deg, #1e1b4b 0%, #312e81 25%, #4c1d95 50%, #6d28d9 75%, #7c3aed 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
      position: relative;
      overflow: hidden;
    }
    
    /* Animated background shapes */
    .bg-shapes {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      overflow: hidden;
      z-index: 0;
    }
    
    .bg-shape {
      position: absolute;
      border-radius: 50%;
      animation: float 20s infinite ease-in-out;
    }
    
    .bg-shape:nth-child(1) {
      width: 600px;
      height: 600px;
      background: radial-gradient(circle, rgba(14, 165, 233, 0.15) 0%, transparent 70%);
      top: -200px;
      right: -200px;
      animation-delay: 0s;
    }
    
    .bg-shape:nth-child(2) {
      width: 400px;
      height: 400px;
      background: radial-gradient(circle, rgba(236, 72, 153, 0.15) 0%, transparent 70%);
      bottom: -100px;
      left: -100px;
      animation-delay: -5s;
    }
    
    .bg-shape:nth-child(3) {
      width: 300px;
      height: 300px;
      background: radial-gradient(circle, rgba(34, 211, 238, 0.1) 0%, transparent 70%);
      top: 50%;
      left: 10%;
      animation-delay: -10s;
    }
    
    .bg-shape:nth-child(4) {
      width: 250px;
      height: 250px;
      background: radial-gradient(circle, rgba(251, 191, 36, 0.1) 0%, transparent 70%);
      bottom: 20%;
      right: 15%;
      animation-delay: -15s;
    }
    
    @keyframes float {
      0%, 100% { transform: translate(0, 0) scale(1); }
      25% { transform: translate(30px, -30px) scale(1.05); }
      50% { transform: translate(-20px, 20px) scale(0.95); }
      75% { transform: translate(20px, 30px) scale(1.02); }
    }
    
    /* Floating particles */
    .particles {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      overflow: hidden;
      z-index: 0;
    }
    
    .particle {
      position: absolute;
      width: 4px;
      height: 4px;
      background: rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      animation: rise 15s infinite ease-in;
    }
    
    @keyframes rise {
      0% { transform: translateY(100vh) scale(0); opacity: 0; }
      10% { opacity: 1; }
      90% { opacity: 1; }
      100% { transform: translateY(-100vh) scale(1); opacity: 0; }
    }
    
    /* Login card enhancements */
    .login-card {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 24px;
      padding: 3rem;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      max-width: 440px;
      width: 100%;
      position: relative;
      z-index: 1;
      backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.2);
      animation: cardEnter 0.6s ease forwards;
    }
    
    @keyframes cardEnter {
      from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }
    
    .login-card .logo {
      width: 90px;
      height: 90px;
      background: linear-gradient(135deg, #7c3aed 0%, #a855f7 50%, #c084fc 100%);
      border-radius: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.75rem;
      margin: 0 auto 1.5rem;
      box-shadow: 0 15px 50px rgba(124, 58, 237, 0.4);
      animation: logoPulse 3s infinite ease-in-out;
    }
    
    @keyframes logoPulse {
      0%, 100% { transform: scale(1); box-shadow: 0 15px 50px rgba(124, 58, 237, 0.4); }
      50% { transform: scale(1.05); box-shadow: 0 20px 60px rgba(124, 58, 237, 0.5); }
    }
    
    .login-card h2 {
      text-align: center;
      font-weight: 800;
      font-size: 2.25rem;
      color: #0f172a;
      margin-bottom: 0.5rem;
      letter-spacing: -1px;
    }
    
    .login-card .subtitle {
      text-align: center;
      color: #64748b;
      margin-bottom: 2rem;
      font-size: 1rem;
    }
    
    .login-card .form-label {
      font-weight: 600;
      font-size: 0.875rem;
      color: #334155;
      margin-bottom: 0.5rem;
    }
    
    .login-card .form-control {
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      padding: 0.875rem 1rem;
      font-size: 1rem;
      transition: all 0.2s ease;
      background: #f8fafc;
    }
    
    .login-card .form-control:focus {
      border-color: #7c3aed;
      box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1);
      outline: none;
      background: white;
    }
    
    .login-card .form-control::placeholder {
      color: #94a3b8;
    }
    
    .login-card .btn-login {
      background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
      border: none;
      border-radius: 12px;
      padding: 1rem;
      font-weight: 700;
      font-size: 1rem;
      width: 100%;
      color: white;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
      position: relative;
      overflow: hidden;
    }
    
    .login-card .btn-login::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.5s;
    }
    
    .login-card .btn-login:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 30px rgba(124, 58, 237, 0.4);
    }
    
    .login-card .btn-login:hover::before {
      left: 100%;
    }
    
    .login-card .btn-login:active {
      transform: translateY(-1px);
    }
    
    /* Alert styling */
    .login-card .alert {
      border-radius: 12px;
      border: none;
      padding: 1rem;
      font-size: 0.875rem;
      font-weight: 500;
      animation: shake 0.5s ease;
    }
    
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }
    
    /* Demo info card */
    .login-card .demo-info {
      background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
      border-radius: 16px;
      padding: 1.5rem;
      margin-top: 2rem;
      border: 1px solid #e2e8f0;
    }
    
    .login-card .demo-info strong {
      color: #0f172a;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.8125rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 1rem;
    }
    
    .demo-credentials {
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }
    
    .demo-credential {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.75rem 1rem;
      background: white;
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.2s;
      border: 1px solid #e2e8f0;
    }
    
    .demo-credential:hover {
      border-color: #7c3aed;
      box-shadow: 0 2px 8px rgba(124, 58, 237, 0.1);
    }
    
    .demo-credential .role {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.875rem;
      font-weight: 600;
      color: #334155;
    }
    
    .demo-credential .creds {
      font-size: 0.8125rem;
      color: #64748b;
    }
    
    .demo-credential code {
      background: #f1f5f9;
      padding: 0.25rem 0.5rem;
      border-radius: 6px;
      font-weight: 600;
      color: #7c3aed;
      font-size: 0.8125rem;
    }
    
    /* Features list */
    .features {
      display: flex;
      justify-content: center;
      gap: 2rem;
      margin-top: 2rem;
      padding-top: 1.5rem;
      border-top: 1px solid #e2e8f0;
    }
    
    .feature {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.8125rem;
      color: #64748b;
    }
    
    .feature .icon {
      font-size: 1rem;
    }
    
    /* Responsive */
    @media (max-width: 480px) {
      .login-card {
        padding: 2rem;
        margin: 1rem;
      }
      
      .login-card h2 {
        font-size: 1.75rem;
      }
      
      .features {
        flex-direction: column;
        gap: 0.75rem;
        align-items: center;
      }
    }
  </style>
</head>
<body class="login-theme">
  <!-- Background shapes -->
  <div class="bg-shapes">
    <div class="bg-shape"></div>
    <div class="bg-shape"></div>
    <div class="bg-shape"></div>
    <div class="bg-shape"></div>
  </div>
  
  <!-- Floating particles -->
  <div class="particles">
    <?php for ($i = 0; $i < 30; $i++): ?>
      <div class="particle" style="left: <?= rand(0, 100) ?>%; animation-delay: <?= rand(0, 15) ?>s; animation-duration: <?= rand(10, 20) ?>s;"></div>
    <?php endfor; ?>
  </div>
  
  <div class="login-card">
    <div class="logo">⚡</div>
    <h2>QuickQuiz</h2>
    <p class="subtitle">Fast. Simple. Effective Learning.</p>
    
    <?php if ($error): ?>
      <div class="alert alert-danger">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right: 0.5rem;"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($redirect)): ?>
      <div class="alert" style="background: #f0fdf4; color: #16a34a; border-radius: 12px; border: none; padding: 0.75rem 1rem; margin-bottom: 1rem; font-size: 0.875rem;">
        📝 Sign in to access your quiz
      </div>
    <?php endif; ?>
    
    <form method="post" id="login-form">
      <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
      <div class="mb-4">
        <label class="form-label">Username</label>
        <input name="username" class="form-control" placeholder="Enter your username" required autocomplete="username" id="username" value="<?= htmlspecialchars($savedUsername) ?>">
      </div>
      <div class="mb-4">
        <label class="form-label">Password</label>
        <input name="password" type="password" class="form-control" placeholder="Enter your password" required autocomplete="current-password" id="password">
      </div>
      <div class="mb-4 admin-code-login" id="adminCodeLogin" style="display: <?= $needAdminCode ? 'block' : 'none' ?>;">
        <label class="form-label">Admin Code 🔑</label>
        <input name="admin_code" type="password" class="form-control" placeholder="Enter your admin code" id="adminCode">
        <small class="text-muted" style="font-size: 0.75rem;">Required for admin accounts</small>
      </div>
      <button class="btn btn-login" type="submit">
        Sign In
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-left: 0.5rem;"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
      </button>
    </form>
    
    <div class="demo-info">
      <strong>🔐 Demo Credentials</strong>
      <div class="demo-credentials">
        <div class="demo-credential" onclick="fillCredentials('admin', '1234')">
          <span class="role">👨‍💼 Admin</span>
          <span class="creds"><code>admin</code> / <code>1234</code></span>
        </div>
        <div class="demo-credential" onclick="fillCredentials('student', '1234')">
          <span class="role">👨‍🎓 Student</span>
          <span class="creds"><code>student</code> / <code>1234</code></span>
        </div>
      </div>
    </div>
    
    <div class="register-link" style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0;">
      <span style="color: #64748b;">Don't have an account?</span>
      <a href="register.php<?= $redirect ? '?redirect=' . urlencode($redirect) : '' ?>" style="color: #7c3aed; text-decoration: none; font-weight: 600; margin-left: 0.5rem; transition: color 0.2s;">
        Create Account →
      </a>
    </div>

    <div class="features">
      <div class="feature">
        <span class="icon">🚀</span>
        <span>Fast Quizzes</span>
      </div>
      <div class="feature">
        <span class="icon">📊</span>
        <span>Track Progress</span>
      </div>
      <div class="feature">
        <span class="icon">🏆</span>
        <span>Earn Achievements</span>
      </div>
    </div>
  </div>
  
  <script>
    function fillCredentials(username, password) {
      document.getElementById('username').value = username;
      document.getElementById('password').value = password;
      // Show admin code field for admin users
      if (username === 'admin') {
        document.getElementById('adminCodeLogin').style.display = 'block';
      }
      // Add visual feedback
      document.getElementById('username').style.borderColor = '#22c55e';
      document.getElementById('password').style.borderColor = '#22c55e';
      setTimeout(() => {
        document.getElementById('username').style.borderColor = '';
        document.getElementById('password').style.borderColor = '';
      }, 500);
    }
    
    // Show/hide admin code field based on username
    document.getElementById('username').addEventListener('input', function() {
      // Show admin code field hint
      const adminField = document.getElementById('adminCodeLogin');
      // Will show after first failed attempt if admin, or user can show it manually
    });
    
    // Toggle admin code visibility
    document.getElementById('login-form').addEventListener('submit', function(e) {
      // Form will submit and show admin code field if needed on error
    });
  </script>
</body>
</html>
