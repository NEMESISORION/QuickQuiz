<?php
session_start();
require_once __DIR__.'/../src/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') { header("Location: login.php"); exit; }

$quiz_id = intval($_GET['id'] ?? 0);
if (!$quiz_id) { header("Location: student_dashboard.php"); exit; }

// load quiz and questions
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$quiz) { echo "Quiz not found"; exit; }

$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html><head>
  <meta charset="utf-8"><title>Take Quiz</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <script>
    // Basic timer (60s per quiz for demo) — change if needed
    var totalSeconds = 300; // 5 minutes default
    window.onload = function(){
      var t = totalSeconds;
      var el = document.getElementById('timer');
      setInterval(function(){
        var mm = Math.floor(t/60), ss = t % 60; ss = ss < 10 ? '0'+ss : ss;
        el.innerText = mm + ":" + ss;
        if (t<=0) {
          document.getElementById('quiz-form').submit();
        }
        t--;
      }, 1000);
    };
  </script>
</head><body class="container mt-4">
  <a href="student_dashboard.php" class="btn btn-secondary mb-3">Back</a>
  <h3><?=htmlspecialchars($quiz['title'])?></h3>
  <p><?=nl2br(htmlspecialchars($quiz['description']))?></p>
  <div class="mb-3"><strong>Time left: <span id="timer">--:--</span></strong></div>

  <form id="quiz-form" method="post" action="submit_quiz.php">
    <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
    <?php foreach ($questions as $i => $q): ?>
      <div class="card mb-3">
        <div class="card-body">
          <h6>Q<?= $i+1 ?>. <?= htmlspecialchars($q['question_text']) ?></h6>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="q<?= $q['id'] ?>" id="q<?=$q['id']?>a" value="A" required>
            <label class="form-check-label" for="q<?=$q['id']?>a"><?=htmlspecialchars($q['option_a'])?></label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="q<?= $q['id'] ?>" id="q<?=$q['id']?>b" value="B">
            <label class="form-check-label" for="q<?=$q['id']?>b"><?=htmlspecialchars($q['option_b'])?></label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="q<?= $q['id'] ?>" id="q<?=$q['id']?>c" value="C">
            <label class="form-check-label" for="q<?=$q['id']?>c"><?=htmlspecialchars($q['option_c'])?></label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="q<?= $q['id'] ?>" id="q<?=$q['id']?>d" value="D">
            <label class="form-check-label" for="q<?=$q['id']?>d"><?=htmlspecialchars($q['option_d'])?></label>
          </div>
        </div>
      </div>
    <?php endforeach; ?>

    <button class="btn btn-success">Submit Quiz</button>
  </form>
</body></html>
