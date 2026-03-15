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
</head><body class="container mt-4">
  <a href="admin_dashboard.php" class="btn btn-secondary mb-3">Back</a>
  <h3>Edit Quiz: <?=htmlspecialchars($quiz['title'])?></h3>
  <p><?=nl2br(htmlspecialchars($quiz['description']))?></p>

  <h5 class="mt-4">Add Question</h5>
  <?php if ($error): ?><div class="alert alert-danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
  <form method="post">
    <div class="mb-2"><label>Question text</label><textarea name="question_text" class="form-control" required></textarea></div>
    <div class="row">
      <div class="col"><input name="option_a" class="form-control" placeholder="Option A" required></div>
      <div class="col"><input name="option_b" class="form-control" placeholder="Option B" required></div>
    </div>
    <div class="row mt-2">
      <div class="col"><input name="option_c" class="form-control" placeholder="Option C" required></div>
      <div class="col"><input name="option_d" class="form-control" placeholder="Option D" required></div>
    </div>
    <div class="mt-2">
      <label>Correct option</label>
      <select name="correct_option" class="form-control" style="width:120px;">
        <option>A</option><option>B</option><option>C</option><option>D</option>
      </select>
    </div>
    <button class="btn btn-primary mt-3">Add Question</button>
  </form>

  <h5 class="mt-4">Questions</h5>
  <table class="table"><thead><tr><th>#</th><th>Question</th><th>Correct</th></tr></thead>
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
  </tbody></table>
</body></html>
