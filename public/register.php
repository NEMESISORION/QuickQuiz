<?php
session_start();
require_once __DIR__ . '/../src/db.php';

// Add admin_code column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN admin_code VARCHAR(100) DEFAULT NULL");
} catch (PDOException $e) {
    // Column already exists, ignore
}

$error = null;
$success = null;
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $admin_code = trim($_POST['admin_code'] ?? '');
    
    // Validation
    if (strlen($username) < 3) {
        $error = "Username must be at least 3 characters";
    } elseif (strlen($password) < 4) {
        $error = "Password must be at least 4 characters";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif ($role === 'admin' && strlen($admin_code) < 4) {
        $error = "Admin code must be at least 4 characters";
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Username already taken";
        } else {
            // Create user with secure password hash
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $hashedAdminCode = ($role === 'admin' && $admin_code) ? password_hash($admin_code, PASSWORD_DEFAULT) : null;
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role, admin_code) VALUES (?, ?, ?, ?) RETURNING id");
            $stmt->execute([$username, $hashedPassword, $role, $hashedAdminCode]);
            $newUserId = $stmt->fetchColumn();

            // Auto-login for students with redirect
            if ($role === 'student' && !empty($redirect)) {
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['role'] = $role;
                $_SESSION['username'] = $username;
                header("Location: " . $redirect);
                exit;
            }
            
            $success = "Account created successfully! You can now sign in.";
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>QuickQuiz — Create Account</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/quickquiz.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
  <style>
    body.register-theme {
      background: linear-gradient(135deg, #1e1b4b 0%, #312e81 25%, #4c1d95 50%, #6d28d9 75%, #7c3aed 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
      position: relative;
      overflow: hidden;
    }
    
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
      background: radial-gradient(circle, rgba(236, 72, 153, 0.12) 0%, transparent 70%);
      bottom: -100px;
      left: -100px;
      animation-delay: -5s;
    }
    
    .bg-shape:nth-child(3) {
      width: 300px;
      height: 300px;
      background: radial-gradient(circle, rgba(34, 197, 94, 0.1) 0%, transparent 70%);
      top: 50%;
      left: 50%;
      animation-delay: -10s;
    }
    
    @keyframes float {
      0%, 100% { transform: translate(0, 0) scale(1); }
      25% { transform: translate(30px, -30px) scale(1.05); }
      50% { transform: translate(-20px, 20px) scale(0.95); }
      75% { transform: translate(20px, 10px) scale(1.02); }
    }
    
    .register-container {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 480px;
    }
    
    .register-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      padding: 2.5rem;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      animation: slideUp 0.5s ease;
    }
    
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(30px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .register-header {
      text-align: center;
      margin-bottom: 2rem;
    }
    
    .register-logo {
      font-size: 3rem;
      margin-bottom: 0.5rem;
      animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }
    
    .register-title {
      font-size: 1.75rem;
      font-weight: 800;
      background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 0.25rem;
    }
    
    .register-subtitle {
      color: #64748b;
      font-size: 0.9375rem;
    }
    
    .form-label {
      font-weight: 600;
      color: #334155;
      margin-bottom: 0.5rem;
    }
    
    .form-control {
      padding: 0.875rem 1rem;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      font-size: 1rem;
      transition: all 0.2s;
    }
    
    .form-control:focus {
      border-color: #7c3aed;
      box-shadow: 0 0 0 4px rgba(124, 58, 237, 0.1);
    }
    
    .input-icon {
      position: relative;
    }
    
    .input-icon .icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1.125rem;
      z-index: 1;
    }
    
    .input-icon .form-control {
      padding-left: 3rem;
    }
    
    .btn-register {
      width: 100%;
      padding: 1rem;
      background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
      border: none;
      border-radius: 12px;
      color: white;
      font-weight: 700;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s;
      position: relative;
      overflow: hidden;
    }
    
    .btn-register:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 40px rgba(124, 58, 237, 0.4);
    }
    
    .btn-register:active {
      transform: translateY(0);
    }
    
    .role-selector {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.75rem;
      margin-bottom: 1rem;
    }
    
    .role-option {
      position: relative;
    }
    
    .role-option input {
      position: absolute;
      opacity: 0;
      pointer-events: none;
    }
    
    .role-option label {
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 1rem;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.2s;
      text-align: center;
    }
    
    .role-option label:hover {
      border-color: #c4b5fd;
      background: #faf5ff;
    }
    
    .role-option input:checked + label {
      border-color: #7c3aed;
      background: linear-gradient(135deg, rgba(124, 58, 237, 0.1) 0%, rgba(168, 85, 247, 0.1) 100%);
    }
    
    .role-option .icon {
      font-size: 1.5rem;
      margin-bottom: 0.25rem;
    }
    
    .role-option .name {
      font-weight: 600;
      color: #334155;
      font-size: 0.875rem;
    }
    
    .role-option .desc {
      font-size: 0.75rem;
      color: #94a3b8;
    }
    
    .admin-code-field {
      display: none;
      animation: fadeIn 0.3s ease;
    }
    
    .admin-code-field.show {
      display: block;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .alert {
      border: none;
      border-radius: 12px;
      padding: 1rem;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }
    
    .alert-danger {
      background: #fef2f2;
      color: #dc2626;
    }
    
    .alert-success {
      background: #f0fdf4;
      color: #16a34a;
    }
    
    .login-link {
      text-align: center;
      margin-top: 1.5rem;
      padding-top: 1.5rem;
      border-top: 1px solid #e2e8f0;
    }
    
    .login-link a {
      color: #7c3aed;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.2s;
    }
    
    .login-link a:hover {
      color: #6d28d9;
    }
    
    .password-strength {
      height: 4px;
      border-radius: 2px;
      background: #e2e8f0;
      margin-top: 0.5rem;
      overflow: hidden;
    }
    
    .password-strength-bar {
      height: 100%;
      width: 0;
      transition: all 0.3s;
      border-radius: 2px;
    }
    
    .strength-weak { width: 33%; background: #ef4444; }
    .strength-medium { width: 66%; background: #f59e0b; }
    .strength-strong { width: 100%; background: #22c55e; }
    
    .password-hint {
      font-size: 0.75rem;
      color: #94a3b8;
      margin-top: 0.25rem;
    }
  </style>
</head>
<body class="register-theme">
  <!-- Animated Background -->
  <div class="bg-shapes">
    <div class="bg-shape"></div>
    <div class="bg-shape"></div>
    <div class="bg-shape"></div>
  </div>

  <div class="register-container">
    <div class="register-card">
      <div class="register-header">
        <div class="register-logo">⚡</div>
        <h1 class="register-title">Create Account</h1>
        <p class="register-subtitle">Join QuickQuiz today</p>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-danger">
          <span>⚠️</span>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success">
          <span>✓</span>
          <?= htmlspecialchars($success) ?>
          <a href="login.php<?= $redirect ? '?redirect=' . urlencode($redirect) : '' ?>" style="margin-left: auto; color: #16a34a; font-weight: 600;">Sign In →</a>
        </div>
      <?php endif; ?>

      <?php if (!empty($redirect)): ?>
        <div class="alert" style="background: #f0fdf4; color: #16a34a; border-radius: 12px; border: none; padding: 0.75rem 1rem; margin-bottom: 1rem; font-size: 0.875rem;">
          📝 Create an account to access your quiz
        </div>
      <?php endif; ?>

      <form method="post" id="registerForm">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <div class="input-icon">
            <span class="icon">👤</span>
            <input type="text" name="username" class="form-control" placeholder="Choose a username" 
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required minlength="3">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">Password</label>
          <div class="input-icon">
            <span class="icon">🔒</span>
            <input type="password" name="password" id="password" class="form-control" 
                   placeholder="Create a password" required minlength="4">
          </div>
          <div class="password-strength">
            <div class="password-strength-bar" id="strengthBar"></div>
          </div>
          <div class="password-hint" id="strengthHint">At least 4 characters required</div>
        </div>

        <div class="mb-4">
          <label class="form-label">Confirm Password</label>
          <div class="input-icon">
            <span class="icon">🔐</span>
            <input type="password" name="confirm_password" id="confirmPassword" class="form-control" 
                   placeholder="Confirm your password" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label">I am a...</label>
          <div class="role-selector">
            <div class="role-option">
              <input type="radio" name="role" id="roleStudent" value="student" checked>
              <label for="roleStudent">
                <span class="icon">📚</span>
                <span class="name">Student</span>
                <span class="desc">Take quizzes</span>
              </label>
            </div>
            <div class="role-option">
              <input type="radio" name="role" id="roleAdmin" value="admin">
              <label for="roleAdmin">
                <span class="icon">👨‍🏫</span>
                <span class="name">Admin</span>
                <span class="desc">Create quizzes</span>
              </label>
            </div>
          </div>
        </div>

        <div class="mb-4 admin-code-field" id="adminCodeField">
          <label class="form-label">Create Your Admin Code</label>
          <div class="input-icon">
            <span class="icon">🔑</span>
            <input type="text" name="admin_code" class="form-control" placeholder="Create a secret code" minlength="4">
          </div>
          <div class="password-hint">You'll need this code every time you sign in (min 4 characters)</div>
        </div>

        <button type="submit" class="btn-register">
          Create Account ✨
        </button>
      </form>

      <div class="login-link">
        Already have an account? <a href="login.php<?= $redirect ? '?redirect=' . urlencode($redirect) : '' ?>">Sign In</a>
      </div>
    </div>
  </div>

  <script>
    // Role selector - show admin code field
    const roleAdmin = document.getElementById('roleAdmin');
    const roleStudent = document.getElementById('roleStudent');
    const adminCodeField = document.getElementById('adminCodeField');
    
    roleAdmin.addEventListener('change', function() {
      if (this.checked) adminCodeField.classList.add('show');
    });
    
    roleStudent.addEventListener('change', function() {
      if (this.checked) adminCodeField.classList.remove('show');
    });
    
    // Password strength indicator
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('strengthBar');
    const strengthHint = document.getElementById('strengthHint');
    
    passwordInput.addEventListener('input', function() {
      const password = this.value;
      let strength = 0;
      
      if (password.length >= 4) strength++;
      if (password.length >= 8) strength++;
      if (/[A-Z]/.test(password) && /[0-9]/.test(password)) strength++;
      
      strengthBar.className = 'password-strength-bar';
      
      if (password.length === 0) {
        strengthBar.style.width = '0';
        strengthHint.textContent = 'At least 4 characters required';
      } else if (strength === 1) {
        strengthBar.classList.add('strength-weak');
        strengthHint.textContent = 'Weak - try adding more characters';
      } else if (strength === 2) {
        strengthBar.classList.add('strength-medium');
        strengthHint.textContent = 'Medium - good, but could be stronger';
      } else {
        strengthBar.classList.add('strength-strong');
        strengthHint.textContent = 'Strong - excellent password!';
      }
    });
    
    // Password confirmation check
    const confirmInput = document.getElementById('confirmPassword');
    const form = document.getElementById('registerForm');
    
    form.addEventListener('submit', function(e) {
      if (passwordInput.value !== confirmInput.value) {
        e.preventDefault();
        alert('Passwords do not match!');
        confirmInput.focus();
      }
    });
  </script>
</body>
</html>
