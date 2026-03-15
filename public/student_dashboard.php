<?php
session_start();
require_once __DIR__.'/../src/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') { header("Location: login.php"); exit; }
$uid = $_SESSION['user_id'];
$uname = $_SESSION['username'];

// list quizzes
$stmt = $pdo->query("SELECT * FROM quizzes ORDER BY created_at DESC");
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// optionally load user's previous results
$stmt2 = $pdo->prepare("SELECT quiz_id, score FROM results WHERE user_id = ?");
$stmt2->execute([$uid]);
$have = [];
while ($row = $stmt2->fetch(PDO::FETCH_ASSOC)) { $have[$row['quiz_id']] = $row['score']; }
?>
<!doctype html><html><head>
  <meta charset="utf-8"><title>Student Dashboard</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head><body class="container mt-4">
  <div class="d-flex justify-content-between align-items-center">
    <h3>Welcome, <?=htmlspecialchars($uname)?></h3>
    <a class="btn btn-secondary" href="logout.php">Logout</a>
  </div>

  <h5 class="mt-4">Available Quizzes</h5>
  <table class="table">
    <thead><tr><th>Title</th><th>Actions</th><th>Your Score</th></tr></thead>
    <tbody>
      <?php foreach ($quizzes as $q): ?>
        <tr>
          <td><?=htmlspecialchars($q['title'])?></td>
          <td><a class="btn btn-primary btn-sm" href="take_quiz.php?id=<?= $q['id'] ?>">Take Quiz</a></td>
          <td><?= isset($have[$q['id']]) ? htmlspecialchars($have[$q['id']]) : '-' ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body></html>
