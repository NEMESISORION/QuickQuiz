<?php
session_start();
require_once __DIR__.'/../src/db.php';

// Run migrations to ensure new columns exist
require_once __DIR__.'/../src/migrations.php';
runMigrations($pdo);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') { header("Location: login.php"); exit; }

$uid = $_SESSION['user_id'];
$quiz_id = intval($_POST['quiz_id'] ?? 0);
$timeTaken = intval($_POST['time_taken'] ?? 0); // Time taken in seconds
if (!$quiz_id) { header("Location: student_dashboard.php"); exit; }

// Get user's dark mode preference
$stmt = $pdo->prepare("SELECT dark_mode FROM users WHERE id = ?");
$stmt->execute([$uid]);
$userPref = $stmt->fetch(PDO::FETCH_ASSOC);
$darkMode = isset($userPref['dark_mode']) && $userPref['dark_mode'];

// Handle dark mode toggle
if (isset($_POST['toggle_dark_mode'])) {
    $newMode = $darkMode ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE users SET dark_mode = ? WHERE id = ?");
    $stmt->execute([$newMode, $uid]);
    header("Location: student_dashboard.php");
    exit;
}

// Load quiz settings
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$quiz) { header("Location: student_dashboard.php"); exit; }

// Get quiz settings with defaults
$passPercentage = isset($quiz['pass_percentage']) ? floatval($quiz['pass_percentage']) : 0;
$negativeMarking = isset($quiz['negative_marking']) ? floatval($quiz['negative_marking']) : 0;
$showAnswers = isset($quiz['show_answers']) ? intval($quiz['show_answers']) : 1;
$certificateEnabled = isset($quiz['certificate_enabled']) ? intval($quiz['certificate_enabled']) : 0;

// load questions for the quiz
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPoints = 0;
$earnedPoints = 0;
$correctCount = 0;
$wrongCount = 0;
$skippedCount = 0;
$answers = []; // Store user's answers for review

foreach ($questions as $q) {
    $qid = $q['id'];
    $field = 'q' . $qid;
    $userAnswer = $_POST[$field] ?? '';
    $questionPoints = isset($q['points']) ? intval($q['points']) : 1;
    $totalPoints += $questionPoints;
    
    $isCorrect = false;
    $isSkipped = empty($userAnswer);
    
    if ($isSkipped) {
        $skippedCount++;
    } elseif ($userAnswer === $q['correct_option']) {
        $isCorrect = true;
        $correctCount++;
        $earnedPoints += $questionPoints;
    } else {
        $wrongCount++;
        // Apply negative marking for wrong answers
        if ($negativeMarking > 0) {
            $earnedPoints -= ($questionPoints * $negativeMarking / 100);
        }
    }
    
    $answers[] = [
        'question_id' => $qid,
        'question' => $q['question_text'],
        'options' => [
            'A' => $q['option_a'],
            'B' => $q['option_b'],
            'C' => $q['option_c'],
            'D' => $q['option_d']
        ],
        'user_answer' => $userAnswer,
        'correct_answer' => $q['correct_option'],
        'is_correct' => $isCorrect,
        'is_skipped' => $isSkipped,
        'points' => $questionPoints
    ];
}

// Ensure earned points don't go below 0
$earnedPoints = max(0, $earnedPoints);

// Calculate percentage and pass/fail
$percentage = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100, 1) : 0;
$passed = $passPercentage > 0 ? ($percentage >= $passPercentage) : null; // null if no pass threshold set

// Legacy score (for backward compatibility) - number of correct answers
$score = $correctCount;
$max = count($questions);

// XP earned based on points
$xpEarned = round($earnedPoints * 10);

// Save to results with new fields
$answersJson = json_encode($answers);
$stmt = $pdo->prepare(
    "INSERT INTO results (user_id, quiz_id, score, total_points, correct_count, wrong_count, time_taken, passed, answers_json)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id"
);
$stmt->execute([$uid, $quiz_id, $earnedPoints, $totalPoints, $correctCount, $wrongCount, $timeTaken, $passed, $answersJson]);
$resultId = $stmt->fetchColumn();

// Get user's previous best
$stmt = $pdo->prepare("SELECT MAX(score) as best FROM results WHERE user_id = ? AND quiz_id = ? AND id != ?");
$stmt->execute([$uid, $quiz_id, $resultId]);
$prev = $stmt->fetch(PDO::FETCH_ASSOC);
$prevBest = $prev['best'] ?? 0;
$isNewBest = $earnedPoints > $prevBest;

// Get rank for this quiz
$stmt = $pdo->prepare(
    "SELECT COUNT(DISTINCT user_id) + 1 as rank 
     FROM results 
     WHERE quiz_id = ? AND score > ?"
);
$stmt->execute([$quiz_id, $earnedPoints]);
$rankData = $stmt->fetch(PDO::FETCH_ASSOC);
$rank = $rankData['rank'] ?? 1;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Results — QuickQuiz</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/quickquiz.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
  <style>
    .confetti { position: fixed; top: 0; left: 0; right: 0; bottom: 0; pointer-events: none; z-index: 1000; }
    
    .result-hero {
      background: linear-gradient(135deg, var(--student-primary) 0%, #14b8a6 50%, #2dd4bf 100%);
      border-radius: var(--radius-xl);
      padding: 3rem 2rem;
      text-align: center;
      color: white;
      position: relative;
      overflow: hidden;
      margin-bottom: 1.5rem;
    }
    
    .result-hero::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -20%;
      width: 400px;
      height: 400px;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
      border-radius: 50%;
    }
    
    .result-hero.excellent { background: linear-gradient(135deg, #22c55e 0%, #16a34a 50%, #15803d 100%); }
    .result-hero.good { background: linear-gradient(135deg, var(--student-primary) 0%, #14b8a6 50%, #2dd4bf 100%); }
    .result-hero.needs-work { background: linear-gradient(135deg, #f59e0b 0%, #d97706 50%, #b45309 100%); }
    
    .result-icon-big { font-size: 4rem; margin-bottom: 1rem; animation: bounce 1s ease infinite; }
    @keyframes bounce {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-10px); }
    }
    
    .result-score-big {
      font-size: 5rem;
      font-weight: 800;
      line-height: 1;
      margin-bottom: 0.5rem;
      text-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }
    
    .result-percentage { font-size: 1.5rem; opacity: 0.9; font-weight: 600; }
    
    .result-badges {
      display: flex;
      justify-content: center;
      gap: 1rem;
      margin-top: 1.5rem;
      flex-wrap: wrap;
    }
    
    .result-badge {
      background: rgba(255,255,255,0.2);
      backdrop-filter: blur(10px);
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 0.375rem;
    }
    
    .stats-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    
    @media (max-width: 768px) {
      .stats-row { grid-template-columns: repeat(2, 1fr); }
    }
    
    .stat-box {
      background: var(--white);
      border-radius: var(--radius-lg);
      padding: 1.25rem;
      text-align: center;
      box-shadow: var(--shadow);
    }
    
    .stat-box .value {
      font-size: 1.75rem;
      font-weight: 800;
      color: var(--text-dark);
    }
    
    .stat-box .label {
      font-size: 0.75rem;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .stat-box.correct .value { color: #22c55e; }
    .stat-box.incorrect .value { color: #ef4444; }
    
    /* Answer Review */
    .review-section {
      background: var(--white);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow);
      overflow: hidden;
    }
    
    .review-header {
      padding: 1.25rem 1.5rem;
      background: #f8fafc;
      border-bottom: 1px solid var(--border-light);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    
    .review-header h5 {
      font-size: 1rem;
      font-weight: 700;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .review-toggle {
      background: #e2e8f0;
      border: none;
      padding: 0.5rem 1rem;
      border-radius: var(--radius);
      font-size: 0.8125rem;
      font-weight: 600;
      color: var(--text-medium);
      cursor: pointer;
      transition: var(--transition);
    }
    
    .review-toggle:hover { background: #cbd5e1; }
    
    .review-list { display: none; }
    .review-list.show { display: block; }
    
    .review-item {
      padding: 1.5rem;
      border-bottom: 1px solid var(--border-light);
    }
    
    .review-item:last-child { border-bottom: none; }
    
    .review-question {
      display: flex;
      align-items: flex-start;
      gap: 1rem;
      margin-bottom: 1rem;
    }
    
    .review-status {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
      flex-shrink: 0;
    }
    
    .review-status.correct { background: rgba(34, 197, 94, 0.1); }
    .review-status.incorrect { background: rgba(239, 68, 68, 0.1); }
    
    .review-question-text {
      font-size: 0.9375rem;
      font-weight: 600;
      color: var(--text-dark);
      line-height: 1.5;
    }
    
    .review-options {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 0.75rem;
      margin-left: 2.75rem;
    }
    
    @media (max-width: 600px) {
      .review-options { grid-template-columns: 1fr; }
    }
    
    .review-option {
      padding: 0.75rem 1rem;
      border-radius: var(--radius);
      font-size: 0.8125rem;
      background: #f8fafc;
      color: var(--text-medium);
      border: 2px solid transparent;
    }
    
    .review-option.correct-answer {
      background: rgba(34, 197, 94, 0.1);
      border-color: #22c55e;
      color: #15803d;
    }
    
    .review-option.user-wrong {
      background: rgba(239, 68, 68, 0.1);
      border-color: #ef4444;
      color: #b91c1c;
      text-decoration: line-through;
    }
    
    .review-option strong { margin-right: 0.25rem; }
    
    /* Action Buttons */
    .action-buttons {
      display: flex;
      gap: 1rem;
      justify-content: center;
      flex-wrap: wrap;
      margin-top: 2rem;
    }
    
    .action-btn {
      padding: 1rem 2rem;
      border-radius: var(--radius);
      font-weight: 600;
      font-size: 0.9375rem;
      text-decoration: none;
      transition: var(--transition);
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .action-btn:hover { transform: translateY(-2px); }
    
    .action-btn.primary {
      background: var(--student-gradient);
      color: white;
      box-shadow: 0 4px 15px rgba(13, 148, 136, 0.3);
    }
    
    .action-btn.secondary {
      background: #f1f5f9;
      color: var(--text-medium);
    }
    
    /* Dark mode for submit page */
    body.dark-mode .action-btn.secondary {
      background: #334155;
      color: #e2e8f0;
    }
    body.dark-mode .review-option {
      background: #0f172a;
    }
  </style>
</head>
<body class="student-theme <?= $darkMode ? 'dark-mode' : '' ?>">
  <?php if ($percentage >= 80): ?><canvas class="confetti" id="confetti"></canvas><?php endif; ?>
  
  <div class="top-bar">
    <div class="container d-flex justify-content-between align-items-center">
      <div class="brand">⚡ QuickQuiz</div>
      <a class="btn btn-outline-light btn-action" href="student_dashboard.php">Dashboard</a>
    </div>
  </div>

  <div class="page-container" style="max-width: 800px;">
    <!-- Result Hero -->
    <div class="result-hero <?= $percentage >= 80 ? 'excellent' : ($percentage >= 50 ? 'good' : 'needs-work') ?>">
      <div class="result-icon-big">
        <?php if ($percentage >= 80): ?>
          🏆
        <?php elseif ($percentage >= 50): ?>
          🎯
        <?php else: ?>
          📖
        <?php endif; ?>
      </div>
      
      <div class="result-score-big"><?= number_format($earnedPoints, 1) ?>/<?= $totalPoints ?></div>
      <div class="result-percentage"><?= $percentage ?>% Score</div>
      
      <?php if ($passed !== null): ?>
        <div style="margin-top: 0.75rem;">
          <?php if ($passed): ?>
            <span style="background: rgba(255,255,255,0.3); padding: 0.5rem 1.5rem; border-radius: 20px; font-weight: 700; font-size: 1rem;">
              ✅ PASSED
            </span>
          <?php else: ?>
            <span style="background: rgba(255,255,255,0.3); padding: 0.5rem 1.5rem; border-radius: 20px; font-weight: 700; font-size: 1rem;">
              ❌ NOT PASSED (<?= $passPercentage ?>% required)
            </span>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      
      <p style="font-size: 1.125rem; margin: 1rem 0 0; opacity: 0.9;">
        <?php if ($percentage >= 80): ?>
          Outstanding! You've mastered this topic! 🌟
        <?php elseif ($percentage >= 50): ?>
          Good effort! Keep practicing to improve! 💪
        <?php else: ?>
          Keep learning! Every attempt makes you better! 📚
        <?php endif; ?>
      </p>
      
      <div class="result-badges">
        <span class="result-badge">⭐ +<?= $xpEarned ?> XP</span>
        <span class="result-badge">🏅 Rank #<?= $rank ?></span>
        <?php if ($isNewBest && $prevBest > 0): ?>
          <span class="result-badge">🎉 New Best!</span>
        <?php endif; ?>
        <?php if ($negativeMarking > 0): ?>
          <span class="result-badge">⚠️ -<?= $negativeMarking ?>% penalty</span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Stats Row -->
    <div class="stats-row">
      <div class="stat-box correct">
        <div class="value"><?= $correctCount ?></div>
        <div class="label">Correct</div>
      </div>
      <div class="stat-box incorrect">
        <div class="value"><?= $wrongCount ?></div>
        <div class="label">Wrong</div>
      </div>
      <div class="stat-box">
        <div class="value"><?= $skippedCount ?></div>
        <div class="label">Skipped</div>
      </div>
      <div class="stat-box">
        <div class="value"><?= $timeTaken > 0 ? gmdate("i:s", $timeTaken) : '--:--' ?></div>
        <div class="label">Time</div>
      </div>
    </div>

    <!-- Answer Review -->
    <?php if ($showAnswers): ?>
    <div class="review-section">
      <div class="review-header">
        <h5>📋 Answer Review</h5>
        <button class="review-toggle" onclick="toggleReview()">Show Answers</button>
      </div>
      <div class="review-list" id="review-list">
        <?php foreach ($answers as $i => $a): ?>
          <div class="review-item">
            <div class="review-question">
              <div class="review-status <?= $a['is_correct'] ? 'correct' : ($a['is_skipped'] ? 'skipped' : 'incorrect') ?>">
                <?= $a['is_correct'] ? '✓' : ($a['is_skipped'] ? '○' : '✗') ?>
              </div>
              <div class="review-question-text">
                <span style="color: var(--text-muted); font-weight: 500;">Q<?= $i + 1 ?>.</span>
                <?= htmlspecialchars($a['question']) ?>
                <span style="color: var(--text-muted); font-size: 0.75rem; margin-left: 0.5rem;">(<?= $a['points'] ?> pts)</span>
              </div>
            </div>
            <div class="review-options">
              <?php foreach ($a['options'] as $letter => $text): 
                $isCorrect = ($letter === $a['correct_answer']);
                $isUserAnswer = ($letter === $a['user_answer']);
                $class = '';
                if ($isCorrect) $class = 'correct-answer';
                elseif ($isUserAnswer && !$a['is_correct']) $class = 'user-wrong';
              ?>
                <div class="review-option <?= $class ?>">
                  <strong><?= $letter ?>.</strong> <?= htmlspecialchars($text) ?>
                  <?php if ($isCorrect): ?> ✓<?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php else: ?>
    <div class="review-section">
      <div class="review-header">
        <h5>📋 Answer Review</h5>
      </div>
      <div style="padding: 2rem; text-align: center; color: var(--text-muted);">
        <div style="font-size: 2rem; margin-bottom: 0.5rem;">🔒</div>
        <p>Answer review is not available for this quiz.</p>
      </div>
    </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div class="action-buttons">
      <?php if ($certificateEnabled && $passed): ?>
      <a class="action-btn secondary" href="certificate.php?result_id=<?= $resultId ?>" target="_blank">
        🎖️ Get Certificate
      </a>
      <?php endif; ?>
      <a class="action-btn primary" href="student_dashboard.php">
        📚 Back to Dashboard
      </a>
    </div>
  </div>

  <script>
    function toggleReview() {
      var list = document.getElementById('review-list');
      var btn = document.querySelector('.review-toggle');
      if (list.classList.contains('show')) {
        list.classList.remove('show');
        btn.textContent = 'Show Answers';
      } else {
        list.classList.add('show');
        btn.textContent = 'Hide Answers';
      }
    }
  </script>

  <?php if ($percentage >= 80): ?>
  <script>
    // Confetti effect for high scores
    var canvas = document.getElementById('confetti');
    var ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    
    var particles = [];
    var colors = ['#7c3aed', '#a855f7', '#0d9488', '#14b8a6', '#f59e0b', '#ef4444', '#22c55e', '#3b82f6'];
    
    for (var i = 0; i < 200; i++) {
      particles.push({
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height - canvas.height,
        size: Math.random() * 10 + 5,
        color: colors[Math.floor(Math.random() * colors.length)],
        speed: Math.random() * 4 + 2,
        angle: Math.random() * 360,
        spin: Math.random() * 0.2 - 0.1,
        shape: Math.random() > 0.5 ? 'circle' : 'rect'
      });
    }
    
    function animate() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      particles.forEach(function(p) {
        ctx.save();
        ctx.translate(p.x, p.y);
        ctx.rotate(p.angle * Math.PI / 180);
        ctx.fillStyle = p.color;
        
        if (p.shape === 'circle') {
          ctx.beginPath();
          ctx.arc(0, 0, p.size / 2, 0, Math.PI * 2);
          ctx.fill();
        } else {
          ctx.fillRect(-p.size / 2, -p.size / 4, p.size, p.size / 2);
        }
        
        ctx.restore();
        
        p.y += p.speed;
        p.x += Math.sin(p.angle * Math.PI / 180) * 2;
        p.angle += p.spin * 10;
        
        if (p.y > canvas.height + 20) {
          p.y = -20;
          p.x = Math.random() * canvas.width;
        }
      });
      requestAnimationFrame(animate);
    }
    animate();
    setTimeout(function() { canvas.style.opacity = '0'; canvas.style.transition = 'opacity 1s'; }, 4000);
    setTimeout(function() { canvas.style.display = 'none'; }, 5000);
  </script>
  <?php endif; ?>
</body>
</html>
