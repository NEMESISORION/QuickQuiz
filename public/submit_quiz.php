<?php
session_start();
require_once __DIR__.'/../src/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') { header("Location: login.php"); exit; }

$uid = $_SESSION['user_id'];
$quiz_id = intval($_POST['quiz_id'] ?? 0);
if (!$quiz_id) { header("Location: student_dashboard.php"); exit; }

// only accept submissions for currently available quizzes
$quizStmt = $pdo->prepare("
    SELECT id FROM quizzes
    WHERE id = ?
      AND is_published = 1
      AND (start_date IS NULL OR start_date <= CURRENT_TIMESTAMP)
      AND (end_date IS NULL OR end_date >= CURRENT_TIMESTAMP)
");
$quizStmt->execute([$quiz_id]);
if (!$quizStmt->fetchColumn()) { header("Location: student_dashboard.php"); exit; }

// load questions for the quiz
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$score = 0;
$max = count($questions);

foreach ($questions as $q) {
    $qid = $q['id'];
    $field = 'q' . $qid;
    $ans = $_POST[$field] ?? '';
    if ($ans === $q['correct_option']) {
        $score++;
    }
}

// Option: store raw score or percentage
$score_value = $score; // number of correct answers
// Save to results
$stmt = $pdo->prepare("INSERT INTO results (user_id, quiz_id, score) VALUES (?, ?, ?)");
$stmt->execute([$uid, $quiz_id, $score_value]);

// show result
?>
<!doctype html><html><head>
  <meta charset="utf-8"><title>Result</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head><body>
  <div class="certificate-container">
    <div class="certificate-actions">
      <button class="btn-print" onclick="window.print()">🖨️ Print Certificate</button>
      <a href="student_dashboard.php" class="btn-back">← Back to Dashboard</a>
    </div>

    <div class="certificate">
      <div class="certificate-header">
        <div class="certificate-icon">⚡</div>
        <div class="certificate-title">QUICKQUIZ</div>
        <div class="certificate-subtitle">CERTIFICATE</div>
      </div>

      <div class="certificate-content">
        <p>OF ACHIEVEMENT</p>
        <p style="margin-top: 24px;">THIS IS TO CERTIFY THAT</p>
        <div class="certificate-recipient">student</div>
        <p>has successfully completed the quiz</p>
        <div class="certificate-quiz">"test 1"</div>
        <div class="certificate-score">Score: <?=htmlspecialchars($score_value)?>/2 (100%)</div>
      </div>

      <div class="certificate-footer">
        <div class="certificate-footer-item">
          <div class="label">DATE</div>
          <div class="value">December 13, 2025</div>
        </div>
        <div class="certificate-seal">
          🎓
        </div>
        <div class="certificate-footer-item">
          <div class="label">CERTIFICATE ID</div>
          <div class="value">84272201880D</div>
        </div>
      </div>
    </div>
  </div>
</body></html>
