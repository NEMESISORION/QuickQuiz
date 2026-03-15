<?php
session_start();
require_once __DIR__.'/../src/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') { header("Location: login.php"); exit; }

$uid = $_SESSION['user_id'];
$quiz_id = intval($_POST['quiz_id'] ?? 0);
if (!$quiz_id) { header("Location: student_dashboard.php"); exit; }

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
</head><body class="container mt-4">
  <h3>Quiz Result</h3>
  <p>You scored <strong><?=htmlspecialchars($score_value)?></strong> out of <?= $max ?></p>
  <a class="btn btn-primary" href="student_dashboard.php">Back to Dashboard</a>
</body></html>
