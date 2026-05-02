<?php
session_start();
require_once __DIR__.'/../src/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if ($title === '') $error = "Title required";
    if (!$error) {
      $stmt = $pdo->prepare("INSERT INTO quizzes (title, description, created_by) VALUES (?, ?, ?)");
      $stmt->execute([$title, $desc, $_SESSION['user_id']]);
      $id = (int)$pdo->lastInsertId();
        header("Location: admin_edit_quiz.php?id=$id");
        exit;
    }
}
?>
<!doctype html><html><head>
  <meta charset="utf-8"><title>Create Quiz</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head><body>
  <div style="padding: 24px; max-width: 800px;">
    <a href="admin_dashboard.php" class="btn btn-secondary mb-3" style="margin-bottom: 24px;">← Back</a>
    <h3 style="margin-bottom: 24px;">Create Quiz</h3>
    <?php if ($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
    <form method="post" class="quizzes-section">
      <div class="mb-3"><label>Title</label><input name="title" class="form-control" required></div>
      <div class="mb-3"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
      <div>
        <button class="btn btn-primary">Create Quiz</button>
        <a class="btn btn-secondary" href="admin_dashboard.php">Cancel</a>
      </div>
    </form>
  </div>
</body></html>
