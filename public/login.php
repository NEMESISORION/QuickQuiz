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

    if ($user && $user['password'] === md5($password)) {
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
</head>
<body class="container mt-5">
  <h2>QuizQuick — Login</h2>
  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="post" class="col-md-4">
    <div class="mb-3">
      <label>Username</label>
      <input name="username" class="form-control" required>
    </div>
    <div class="mb-3">
      <label>Password</label>
      <input name="password" type="password" class="form-control" required>
    </div>
    <button class="btn btn-primary mt-3">Login</button>
  </form>
  <p class="mt-3"><strong>Demo accounts:</strong> admin / 1234  •  student / 1234</p>
</body>
</html>
