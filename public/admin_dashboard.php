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
</head><body class="container mt-4">
  <div class="d-flex justify-content-between align-items-center">
    <h3>Admin Dashboard</h3>
    <a class="btn btn-secondary" href="logout.php">Logout</a>
  </div>
  <a class="btn btn-success mt-3 mb-3" href="admin_create_quiz.php">+ Create Quiz</a>

  <table class="table">
    <thead><tr><th>Title</th><th>Description</th><th>Actions</th></tr></thead>
    <tbody>
      <?php foreach ($quizzes as $q): ?>
        <tr>
          <td><?=htmlspecialchars($q['title'])?></td>
          <td><?=htmlspecialchars($q['description'])?></td>
          <td>
            <a class="btn btn-primary btn-sm" href="admin_edit_quiz.php?id=<?= $q['id'] ?>">Edit</a>
            <a class="btn btn-info btn-sm" href="admin_result.php?id=<?= $q['id'] ?>">Results</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body></html>
