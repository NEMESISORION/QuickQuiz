<?php
session_start();
require_once __DIR__.'/../src/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }

$quiz_id = intval($_GET['id'] ?? 0);
if (!$quiz_id) { header("Location: admin_dashboard.php"); exit; }

$stmt = $pdo->prepare("SELECT r.*, u.username FROM results r JOIN users u ON r.user_id = u.id WHERE r.quiz_id = ? ORDER BY r.taken_at DESC");
$stmt->execute([$quiz_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html><head>
  <meta charset="utf-8"><title>Results</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head><body>
  <div style="padding: 24px; max-width: 1000px;">
    <a href="admin_dashboard.php" class="btn btn-secondary mb-3" style="margin-bottom: 24px;">← Back</a>
    <h3 style="margin-bottom: 24px;">Results for Quiz #<?= $quiz_id ?></h3>
    <table class="table">
      <thead><tr><th>User</th><th>Score</th><th>When</th></tr></thead>
      <tbody>
        <?php foreach ($results as $r): ?>
          <tr>
            <td><?=htmlspecialchars($r['username'])?></td>
            <td><?=htmlspecialchars($r['score'])?></td>
            <td><?=htmlspecialchars($r['taken_at'])?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</body></html>
