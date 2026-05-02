<?php
session_start();
require_once __DIR__.'/../src/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') { header("Location: login.php"); exit; }

$quiz_id = intval($_GET['id'] ?? 0);
if (!$quiz_id) { header("Location: student_dashboard.php"); exit; }

// load an available published quiz
$stmt = $pdo->prepare("
    SELECT * FROM quizzes
    WHERE id = ?
      AND is_published = 1
      AND (start_date IS NULL OR start_date <= CURRENT_TIMESTAMP)
      AND (end_date IS NULL OR end_date >= CURRENT_TIMESTAMP)
");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$quiz) { echo "Quiz not found"; exit; }

$quizTimeLimitMinutes = max(0, (int)($quiz['time_limit'] ?? 0));
$totalSeconds = $quizTimeLimitMinutes > 0 ? $quizTimeLimitMinutes * 60 : 300;

$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html><head>
  <meta charset="utf-8"><title>Take Quiz</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <script>
    var totalSeconds = <?= $totalSeconds ?>;
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
</head><body>
  <div class="quiz-header">
    <div class="quiz-title">← QuickQuiz</div>
    <div class="quiz-timer">⏱ <span id="timer">--:--</span></div>
  </div>

  <div style="padding: 24px;">
    <div class="quiz-info-bar">
      <div class="quiz-info-item">
        <span class="icon">❓</span>
        <span class="label">2 Questions</span>
        <span class="value">2 Minutes (auto)</span>
      </div>
      <div class="quiz-info-item">
        <span class="icon">🎯</span>
        <span class="label">Attempt #6</span>
      </div>
      <div class="quiz-info-item">
        <span class="icon">⭐</span>
        <span class="label">Best: 2/2</span>
      </div>
    </div>

    <div class="question-navigator">
      <div class="question-navigator-label">QUESTION NAVIGATOR</div>
      <div class="question-numbers">
        <div class="question-num active">1</div>
        <div class="question-num">2</div>
      </div>
    </div>

    <form id="quiz-form" method="post" action="submit_quiz.php">
      <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
      <?php foreach ($questions as $i => $q): ?>
        <div class="question-card">
          <div class="question-text">
            <div class="question-number-badge"><?= $i+1 ?></div>
            <?= htmlspecialchars($q['question_text']) ?>
          </div>
          <div class="question-tags">
            <span class="question-tag">1 pts</span>
            <span class="question-tag correct">80%</span>
          </div>
          <div class="answer-options">
            <div class="answer-option">
              <input type="radio" name="q<?= $q['id'] ?>" id="q<?=$q['id']?>a" value="A" required>
              <label for="q<?=$q['id']?>a">
                <div class="answer-option-letter">A</div>
                <span><?=htmlspecialchars($q['option_a'])?></span>
              </label>
            </div>
            <div class="answer-option">
              <input type="radio" name="q<?= $q['id'] ?>" id="q<?=$q['id']?>b" value="B">
              <label for="q<?=$q['id']?>b">
                <div class="answer-option-letter">B</div>
                <span><?=htmlspecialchars($q['option_b'])?></span>
              </label>
            </div>
            <div class="answer-option">
              <input type="radio" name="q<?= $q['id'] ?>" id="q<?=$q['id']?>c" value="C">
              <label for="q<?=$q['id']?>c">
                <div class="answer-option-letter">C</div>
                <span><?=htmlspecialchars($q['option_c'])?></span>
              </label>
            </div>
            <div class="answer-option">
              <input type="radio" name="q<?= $q['id'] ?>" id="q<?=$q['id']?>d" value="D">
              <label for="q<?=$q['id']?>d">
                <div class="answer-option-letter">D</div>
                <span><?=htmlspecialchars($q['option_d'])?></span>
              </label>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

      <div style="text-align: center; padding: 24px;">
        <a href="student_dashboard.php" class="btn btn-secondary" style="margin-right: 12px;">← Back</a>
        <button class="btn btn-primary" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none;">Submit Quiz ✓</button>
      </div>
    </form>
  </div>
</body></html>
