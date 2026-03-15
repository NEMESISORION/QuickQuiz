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
        $stmt = $pdo->prepare("INSERT INTO quizzes (title, description, created_by) VALUES (?, ?, ?) RETURNING id");
        $stmt->execute([$title, $desc, $_SESSION['user_id']]);
        $id = $stmt->fetchColumn();
        header("Location: admin_edit_quiz.php?id=$id");
        exit;
    }
}
?>
<!doctype html><html><head>
  <meta charset="utf-8"><title>Create Quiz</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head><body class="container mt-4">
  <h3>Create Quiz</h3>
  <?php if ($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
  <form method="post">
    <div class="mb-3"><label>Title</label><input name="title" class="form-control" required></div>
    <div class="mb-3"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
    <button class="btn btn-success">Create</button>
    <a class="btn btn-secondary" href="admin_dashboard.php">Back</a>
  </form>
</body></html>
