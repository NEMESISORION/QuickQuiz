<?php
session_start();
require_once __DIR__.'/../src/db.php';

// Run migrations to add new columns
require_once __DIR__.'/../src/migrations.php';
runMigrations($pdo);

$quiz_id = intval($_GET['id'] ?? 0);

// If not logged in, redirect to login with return URL
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php?redirect=" . urlencode("take_quiz.php?id=" . $quiz_id)); 
    exit; 
}

// If logged in but not a student, redirect to login
if ($_SESSION['role'] !== 'student') { 
    // Logout and redirect to login with the quiz link
    session_destroy();
    header("Location: login.php?redirect=" . urlencode("take_quiz.php?id=" . $quiz_id)); 
    exit; 
}

// Get user's dark mode preference
$userId = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT dark_mode FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userPref = $stmt->fetch(PDO::FETCH_ASSOC);
$darkMode = isset($userPref['dark_mode']) && $userPref['dark_mode'];

// Handle dark mode toggle
if (isset($_POST['toggle_dark_mode'])) {
    $newMode = $darkMode ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE users SET dark_mode = ? WHERE id = ?");
    $stmt->execute([$newMode, $userId]);
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

if (!$quiz_id) { header("Location: student_dashboard.php"); exit; }

// load quiz and questions
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$quiz) { echo "Quiz not found"; exit; }

// Check if quiz is published
if (!isset($quiz['is_published']) || $quiz['is_published'] != 1) {
    echo "<div style='text-align:center;padding:50px;font-family:sans-serif;'>";
    echo "<h2>⏳ Quiz Not Available</h2>";
    echo "<p>This quiz is not yet published. Please check back later.</p>";
    echo "<a href='student_dashboard.php' style='color:#7c3aed;'>← Back to Dashboard</a>";
    echo "</div>";
    exit;
}

// Check scheduling - is quiz available?
$now = new DateTime();
$quizNotStarted = false;
$quizEnded = false;
$startDate = null;
$endDate = null;

if (!empty($quiz['start_date'])) {
    $startDate = new DateTime($quiz['start_date']);
    if ($now < $startDate) {
        $quizNotStarted = true;
    }
}

if (!empty($quiz['end_date'])) {
    $endDate = new DateTime($quiz['end_date']);
    if ($now > $endDate) {
        $quizEnded = true;
    }
}

// If quiz is scheduled and not available, show message
if ($quizNotStarted || $quizEnded):
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?=htmlspecialchars($quiz['title'])?> — QuickQuiz</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/quickquiz.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
</head>
<body class="student-theme <?= $darkMode ? 'dark-mode' : '' ?>">
  <div class="top-bar">
    <div class="container d-flex justify-content-between align-items-center">
      <a href="student_dashboard.php" class="brand" style="text-decoration: none;">⚡ QuickQuiz</a>
    </div>
  </div>
  <div class="page-container" style="max-width: 600px;">
    <div class="card-modern" style="text-align: center; padding: 3rem;">
      <?php if ($quizNotStarted): ?>
        <div style="font-size: 4rem; margin-bottom: 1rem;">⏰</div>
        <h3 style="color: var(--text-dark); margin-bottom: 0.5rem;">Quiz Not Yet Available</h3>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
          This quiz will be available starting:<br>
          <strong style="color: var(--student-primary);"><?= $startDate->format('F j, Y \a\t g:i A') ?></strong>
        </p>
      <?php else: ?>
        <div style="font-size: 4rem; margin-bottom: 1rem;">🔒</div>
        <h3 style="color: var(--text-dark); margin-bottom: 0.5rem;">Quiz Has Ended</h3>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
          This quiz was available until:<br>
          <strong style="color: #ef4444;"><?= $endDate->format('F j, Y \a\t g:i A') ?></strong>
        </p>
      <?php endif; ?>
      <a href="student_dashboard.php" class="btn" style="background: var(--student-gradient); color: white; padding: 0.75rem 2rem; font-weight: 600;">
        ← Back to Dashboard
      </a>
    </div>
  </div>
</body>
</html>
<?php
exit;
endif;

$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Randomize questions if enabled
if (!empty($quiz['randomize_questions'])) {
    shuffle($questions);
}

$totalQuestions = count($questions);

// Calculate total time from individual question times or quiz time limit
$quizTimeLimit = isset($quiz['time_limit']) ? intval($quiz['time_limit']) : 0;

// Get per-question time limits and calculate total
$questionTimes = [];
$totalQuestionTime = 0;
foreach ($questions as $q) {
    $qTime = isset($q['time_limit']) ? intval($q['time_limit']) : 60;
    $questionTimes[] = $qTime;
    $totalQuestionTime += $qTime;
}

// Use quiz time limit if set, otherwise use sum of question times
if ($quizTimeLimit > 0) {
    $timeLimit = $quizTimeLimit * 60; // Convert minutes to seconds
    $timeLimitDisplay = $quizTimeLimit;
} else {
    $timeLimit = $totalQuestionTime; // Sum of all question times
    $timeLimitDisplay = ceil($totalQuestionTime / 60); // Display in minutes
}

// Get user's previous attempts
$stmt = $pdo->prepare("SELECT COUNT(*) as attempts, MAX(score) as best FROM results WHERE user_id = ? AND quiz_id = ?");
$stmt->execute([$_SESSION['user_id'], $quiz_id]);
$prev = $stmt->fetch(PDO::FETCH_ASSOC);
$prevAttempts = $prev['attempts'] ?? 0;
$bestScore = $prev['best'] ?? 0;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?=htmlspecialchars($quiz['title'])?> — QuickQuiz</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/quickquiz.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
  <style>
    .progress-bar-container { position: fixed; top: 0; left: 0; right: 0; height: 4px; background: rgba(0,0,0,0.1); z-index: 1000; }
    .progress-bar-fill { height: 100%; background: var(--student-gradient); transition: width 0.3s; }
    
    /* Question Navigation */
    .question-nav { 
      position: sticky; 
      top: 80px; 
      background: var(--white); 
      border-radius: var(--radius-xl); 
      padding: 1.25rem; 
      box-shadow: var(--shadow); 
      margin-bottom: 1.5rem;
      z-index: 50;
    }
    .question-nav h6 { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin: 0 0 0.75rem; }
    .nav-dots { display: flex; flex-wrap: wrap; gap: 0.5rem; }
    .nav-dot { 
      width: 36px; height: 36px; border-radius: 8px; 
      border: 2px solid #e2e8f0; background: white;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.8125rem; font-weight: 600; color: var(--text-muted);
      cursor: pointer; transition: var(--transition);
      text-decoration: none;
    }
    .nav-dot:hover { border-color: var(--student-primary-light); color: var(--student-primary); }
    .nav-dot.answered { background: var(--student-gradient); border-color: var(--student-primary); color: white; }
    .nav-dot.current { border-color: var(--student-primary); box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.2); }
    
    /* Quiz Info Bar */
    .quiz-info-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: var(--white);
      border-radius: var(--radius-xl);
      padding: 1rem 1.5rem;
      box-shadow: var(--shadow);
      margin-bottom: 1.5rem;
      flex-wrap: wrap;
      gap: 1rem;
    }
    .quiz-info-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.875rem; color: var(--text-medium); }
    .quiz-info-item .icon { font-size: 1.125rem; }
    
    /* Enhanced Question Card */
    .question-card { scroll-margin-top: 200px; }
    .question-card.highlighted { animation: highlight 0.5s ease; }
    @keyframes highlight {
      0%, 100% { box-shadow: var(--shadow); }
      50% { box-shadow: 0 0 0 4px rgba(13, 148, 136, 0.3); }
    }
    
    /* Review Modal */
    .review-overlay {
      position: fixed; top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
      display: none; align-items: center; justify-content: center;
      z-index: 2000; padding: 1rem;
    }
    .review-overlay.show { display: flex; }
    .review-modal {
      background: white; border-radius: var(--radius-xl);
      max-width: 500px; width: 100%; padding: 2rem;
      box-shadow: var(--shadow-xl); animation: slideUp 0.3s ease;
    }
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .review-modal h4 { font-size: 1.25rem; font-weight: 700; margin: 0 0 0.5rem; color: var(--text-dark); }
    .review-modal p { color: var(--text-muted); margin: 0 0 1.5rem; font-size: 0.9375rem; }
    .review-summary { background: #f8fafc; border-radius: var(--radius); padding: 1rem; margin-bottom: 1.5rem; }
    .review-summary-item { display: flex; justify-content: space-between; padding: 0.5rem 0; }
    .review-summary-item:not(:last-child) { border-bottom: 1px solid var(--border-light); }
    .review-summary-item .label { color: var(--text-muted); font-size: 0.875rem; }
    .review-summary-item .value { font-weight: 600; color: var(--text-dark); }
    .review-actions { display: flex; gap: 1rem; }
    .review-actions button { flex: 1; padding: 0.875rem; border-radius: var(--radius); font-weight: 600; font-size: 0.9375rem; cursor: pointer; transition: var(--transition); }
    .btn-review { background: #f1f5f9; border: none; color: var(--text-medium); }
    .btn-review:hover { background: #e2e8f0; }
    .btn-submit-final { background: var(--student-gradient); border: none; color: white; }
    .btn-submit-final:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(13, 148, 136, 0.3); }
    /* Dark mode for review modal */
    body.dark-mode .review-summary { background: #0f172a; }
    body.dark-mode .btn-review { background: #334155; color: #e2e8f0; }
    body.dark-mode .btn-review:hover { background: #475569; }
  </style>
</head>
<body class="student-theme <?= $darkMode ? 'dark-mode' : '' ?>">
  <div class="progress-bar-container">
    <div class="progress-bar-fill" id="progress" style="width: 0%"></div>
  </div>

  <div class="top-bar">
    <div class="container d-flex justify-content-between align-items-center">
      <a href="student_dashboard.php" class="brand" style="text-decoration: none;">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="opacity: 0.8; margin-right: 0.25rem;"><path d="M19 12H5m7-7-7 7 7 7"/></svg>
        ⚡ QuickQuiz
      </a>
      <div class="timer-badge" id="timer-badge">
        <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        <span id="timer">--:--</span>
      </div>
    </div>
  </div>

  <div class="page-container" style="max-width: 900px;">
    <!-- Quiz Header -->
    <div class="quiz-header">
      <h3><?=htmlspecialchars($quiz['title'])?></h3>
      <?php if (isset($quiz['description']) && $quiz['description']): ?>
        <p class="text-muted mb-1"><?=htmlspecialchars($quiz['description'])?></p>
      <?php endif; ?>
    </div>

    <!-- Quiz Info Bar -->
    <div class="quiz-info-bar">
      <div class="quiz-info-item">
        <span class="icon">❓</span>
        <span><strong><?= $totalQuestions ?></strong> Questions</span>
      </div>
      <div class="quiz-info-item">
        <span class="icon">⏱️</span>
        <span><strong id="time-display"><?= $timeLimitDisplay ?></strong> Minutes<?= $quizTimeLimit == 0 ? ' <small>(auto)</small>' : '' ?></span>
      </div>
      <?php if ($prevAttempts > 0): ?>
        <div class="quiz-info-item">
          <span class="icon">🔄</span>
          <span>Attempt <strong>#<?= $prevAttempts + 1 ?></strong></span>
        </div>
        <div class="quiz-info-item">
          <span class="icon">🏆</span>
          <span>Best: <strong><?= $bestScore ?>/<?= $totalQuestions ?></strong></span>
        </div>
      <?php endif; ?>
    </div>

    <!-- Question Navigation -->
    <div class="question-nav">
      <h6>Question Navigator</h6>
      <div class="nav-dots">
        <?php foreach ($questions as $i => $q): ?>
          <a href="#q<?= $i + 1 ?>" class="nav-dot" data-question="<?= $i + 1 ?>" onclick="scrollToQuestion(<?= $i + 1 ?>); return false;">
            <?= $i + 1 ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <form id="quiz-form" method="post" action="submit_quiz.php">
      <input type="hidden" name="quiz_id" value="<?= $quiz_id ?>">
      <input type="hidden" name="time_taken" id="time_taken" value="0">
      
      <?php foreach ($questions as $i => $q): ?>
        <div class="question-card" data-question="<?= $i + 1 ?>" data-time="<?= isset($q['time_limit']) ? intval($q['time_limit']) : 60 ?>" data-points="<?= isset($q['points']) ? intval($q['points']) : 1 ?>" id="q<?= $i + 1 ?>">
          <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="mb-0">
              <span class="question-number"><?= $i+1 ?></span>
              <span><?= htmlspecialchars($q['question_text']) ?></span>
            </h6>
            <div>
              <span class="badge me-1" style="background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); color: white; font-size: 0.7rem; padding: 0.3rem 0.5rem; border-radius: 6px;">
                ⭐ <?= isset($q['points']) ? intval($q['points']) : 1 ?> pts
              </span>
              <span class="badge question-timer-badge" data-qid="<?= $q['id'] ?>" style="background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); color: white; font-size: 0.75rem; padding: 0.35rem 0.6rem; border-radius: 6px; white-space: nowrap;">
                ⏱️ <?= isset($q['time_limit']) ? intval($q['time_limit']) : 60 ?>s
              </span>
            </div>
          </div>
          
          <div class="options-list mt-4" style="margin-top: 1.5rem !important;">
            <input class="form-check-input" type="radio" name="q<?= $q['id'] ?>" id="q<?=$q['id']?>a" value="A" required style="display:none;" data-qnum="<?= $i + 1 ?>">
            <label class="option-label" for="q<?=$q['id']?>a">
              <strong>A.</strong> <?=htmlspecialchars($q['option_a'])?>
            </label>
            
            <input class="form-check-input" type="radio" name="q<?= $q['id'] ?>" id="q<?=$q['id']?>b" value="B" style="display:none;" data-qnum="<?= $i + 1 ?>">
            <label class="option-label" for="q<?=$q['id']?>b">
              <strong>B.</strong> <?=htmlspecialchars($q['option_b'])?>
            </label>
            
            <input class="form-check-input" type="radio" name="q<?= $q['id'] ?>" id="q<?=$q['id']?>c" value="C" style="display:none;" data-qnum="<?= $i + 1 ?>">
            <label class="option-label" for="q<?=$q['id']?>c">
              <strong>C.</strong> <?=htmlspecialchars($q['option_c'])?>
            </label>
            
            <input class="form-check-input" type="radio" name="q<?= $q['id'] ?>" id="q<?=$q['id']?>d" value="D" style="display:none;" data-qnum="<?= $i + 1 ?>">
            <label class="option-label" for="q<?=$q['id']?>d">
              <strong>D.</strong> <?=htmlspecialchars($q['option_d'])?>
            </label>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="text-center mt-4 mb-5">
        <button type="button" onclick="showReview()" class="btn btn-lg" style="background: var(--student-gradient); color: white; padding: 1rem 3rem; font-weight: 600; border-radius: 12px; box-shadow: 0 4px 15px rgba(13, 148, 136, 0.3);">
          Review & Submit
          <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="ms-1"><path d="M5 12h14m-7-7 7 7-7 7"/></svg>
        </button>
      </div>
    </form>
  </div>

  <!-- Review Modal -->
  <div class="review-overlay" id="review-modal">
    <div class="review-modal">
      <h4>📋 Review Your Answers</h4>
      <p>Make sure you've answered all questions before submitting.</p>
      
      <div class="review-summary">
        <div class="review-summary-item">
          <span class="label">Total Questions</span>
          <span class="value"><?= $totalQuestions ?></span>
        </div>
        <div class="review-summary-item">
          <span class="label">Answered</span>
          <span class="value" id="answered-count">0</span>
        </div>
        <div class="review-summary-item">
          <span class="label">Unanswered</span>
          <span class="value" id="unanswered-count" style="color: #ef4444;"><?= $totalQuestions ?></span>
        </div>
        <div class="review-summary-item">
          <span class="label">Time Remaining</span>
          <span class="value" id="review-time">--:--</span>
        </div>
      </div>
      
      <div class="review-actions">
        <button type="button" class="btn-review" onclick="hideReview()">← Go Back</button>
        <button type="button" class="btn-submit-final" onclick="submitQuiz()">Submit Quiz ✓</button>
      </div>
    </div>
  </div>

  <script>
    var totalSeconds = <?= $timeLimit ?>;
    var totalQuestions = <?= $totalQuestions ?>;
    var questionTimes = <?= json_encode($questionTimes) ?>; // Per-question time limits in seconds
    var answered = 0;
    
    // Timer
    var t = totalSeconds;
    var el = document.getElementById('timer');
    var timerBadge = document.getElementById('timer-badge');
    
    function updateTimer() {
      var mm = Math.floor(t/60), ss = t % 60;
      ss = ss < 10 ? '0'+ss : ss;
      el.innerText = mm + ":" + ss;
      document.getElementById('review-time').innerText = mm + ":" + ss;
      
      if (t <= 60) {
        timerBadge.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
        timerBadge.style.animation = 'pulse 0.5s infinite';
      } else if (t <= 120) {
        timerBadge.style.background = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';
      }
      
      if (t <= 0) {
        document.getElementById('quiz-form').submit();
      }
      t--;
    }
    
    updateTimer();
    setInterval(updateTimer, 1000);
    
    // Progress tracking & Navigation
    var navDots = document.querySelectorAll('.nav-dot');
    
    document.querySelectorAll('input[type="radio"]').forEach(function(radio) {
      radio.addEventListener('change', function() {
        var answeredCount = document.querySelectorAll('input[type="radio"]:checked').length;
        var progress = (answeredCount / totalQuestions) * 100;
        document.getElementById('progress').style.width = progress + '%';
        
        // Update nav dot
        var qnum = this.dataset.qnum;
        navDots.forEach(function(dot) {
          if (dot.dataset.question === qnum) {
            dot.classList.add('answered');
          }
        });
        
        // Update counts
        document.getElementById('answered-count').innerText = answeredCount;
        document.getElementById('unanswered-count').innerText = totalQuestions - answeredCount;
        if (totalQuestions - answeredCount === 0) {
          document.getElementById('unanswered-count').style.color = '#22c55e';
        }
      });
    });
    
    // Scroll to question
    function scrollToQuestion(num) {
      var card = document.getElementById('q' + num);
      if (card) {
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        card.classList.add('highlighted');
        setTimeout(function() { card.classList.remove('highlighted'); }, 600);
      }
      
      // Update current indicator
      navDots.forEach(function(dot) {
        dot.classList.remove('current');
        if (parseInt(dot.dataset.question) === num) {
          dot.classList.add('current');
        }
      });
    }
    
    // Intersection observer for current question
    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          var qnum = entry.target.dataset.question;
          navDots.forEach(function(dot) {
            dot.classList.remove('current');
            if (dot.dataset.question === qnum) {
              dot.classList.add('current');
            }
          });
        }
      });
    }, { threshold: 0.5 });
    
    document.querySelectorAll('.question-card').forEach(function(card) {
      observer.observe(card);
    });
    
    // Review modal
    function showReview() {
      document.getElementById('review-modal').classList.add('show');
      document.body.style.overflow = 'hidden';
    }
    
    function hideReview() {
      document.getElementById('review-modal').classList.remove('show');
      document.body.style.overflow = '';
    }
    
    function submitQuiz() {
      // Set the time taken before submission
      document.getElementById('time_taken').value = totalSeconds - t;
      document.getElementById('quiz-form').submit();
    }
    
    // Close modal on overlay click
    document.getElementById('review-modal').addEventListener('click', function(e) {
      if (e.target === this) hideReview();
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') hideReview();
    });
  </script>
</body>
</html>
