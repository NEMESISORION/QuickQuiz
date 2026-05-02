<?php
session_start();
require_once __DIR__.'/../src/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }

$quiz_id = intval($_GET['id'] ?? 0);
if (!$quiz_id) { header("Location: admin_dashboard.php"); exit; }

// load quiz
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$quiz) { echo "Quiz not found"; exit; }

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qtext = trim($_POST['question_text'] ?? '');
    $a = trim($_POST['option_a'] ?? '');
    $b = trim($_POST['option_b'] ?? '');
    $c = trim($_POST['option_c'] ?? '');
    $d = trim($_POST['option_d'] ?? '');
    $correct = $_POST['correct_option'] ?? 'A';
    if ($qtext === '' || $a==='' || $b==='' || $c==='' || $d==='') $error = "Fill all fields";
    if (!$error) {
        $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$quiz_id, $qtext, $a, $b, $c, $d, $correct]);
        header("Location: admin_edit_quiz.php?id=$quiz_id");
        exit;
    }
}

// load questions
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html><head>
  <meta charset="utf-8"><title>Edit Quiz</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head><body>
  <div style="padding: 24px; max-width: 1000px;">
    <a href="admin_dashboard.php" class="btn btn-secondary mb-3" style="margin-bottom: 24px;">← Back</a>
    <h3 style="margin-bottom: 8px;">Edit Quiz: <?=htmlspecialchars($quiz['title'])?></h3>
    <p style="color: #6b7280; margin-bottom: 24px;"><?=nl2br(htmlspecialchars($quiz['description']))?></p>

    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06); margin-bottom: 24px;">
      <h5 style="margin-bottom: 16px;">➕ Add Question</h5>
      <?php if ($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
      <form method="post">
        <div class="mb-3"><label>Question text</label><textarea name="question_text" class="form-control" required></textarea></div>
        <div class="row">
          <div class="col"><input name="option_a" class="form-control" placeholder="Option A" required></div>
          <div class="col"><input name="option_b" class="form-control" placeholder="Option B" required></div>
        </div>
        <div class="row mt-2">
          <div class="col"><input name="option_c" class="form-control" placeholder="Option C" required></div>
          <div class="col"><input name="option_d" class="form-control" placeholder="Option D" required></div>
        </div>
        <div class="mt-3">
          <label>Correct option</label>
          <select name="correct_option" class="form-control" style="width:120px;">
            <option>A</option><option>B</option><option>C</option><option>D</option>
          </select>
        </div>
        <button class="btn btn-primary mt-3">Add Question</button>
      </form>
    </div>

    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);">
      <h5 style="margin-bottom: 16px;">📋 Questions</h5>
      <table class="table">
        <thead><tr><th>#</th><th>Question</th><th>Correct</th></tr></thead>
        <tbody>
          <?php foreach ($questions as $i=>$q): ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><?= htmlspecialchars($q['question_text']) ?><br>
                <small>A: <?=htmlspecialchars($q['option_a'])?> • B: <?=htmlspecialchars($q['option_b'])?> • C: <?=htmlspecialchars($q['option_c'])?> • D: <?=htmlspecialchars($q['option_d'])?></small>
              </td>
              <td><?= htmlspecialchars($q['correct_option']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</body></html>
