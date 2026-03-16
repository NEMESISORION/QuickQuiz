<?php
session_start();
require_once __DIR__.'/../src/db.php';
require_once __DIR__.'/../src/migrations.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }

// Run all migrations
runMigrations($pdo);

$adminId = $_SESSION['user_id'];

// Get user's dark mode preference
$stmt = $pdo->prepare("SELECT dark_mode FROM users WHERE id = ?");
$stmt->execute([$adminId]);
$userPref = $stmt->fetch(PDO::FETCH_ASSOC);
$darkMode = isset($userPref['dark_mode']) && $userPref['dark_mode'];

$quiz_id = intval($_GET['id'] ?? 0);
if (!$quiz_id) { header("Location: admin_dashboard.php"); exit; }

// Handle dark mode toggle
if (isset($_POST['toggle_dark_mode'])) {
    $newMode = $darkMode ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE users SET dark_mode = ? WHERE id = ?");
    $stmt->execute([$newMode, $adminId]);
    header("Location: admin_edit_quiz.php?id=" . $quiz_id);
    exit;
}

// Handle publish/unpublish quiz
if (isset($_POST['toggle_publish'])) {
    // First check if quiz belongs to this admin
    $stmt = $pdo->prepare("SELECT is_published FROM quizzes WHERE id = ? AND created_by = ?");
    $stmt->execute([$quiz_id, $adminId]);
    $quizStatus = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($quizStatus !== false) {
        $newStatus = ($quizStatus['is_published'] ?? 0) ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE quizzes SET is_published = ? WHERE id = ?");
        $stmt->execute([$newStatus, $quiz_id]);
        $action = $newStatus ? 'published' : 'unpublished';
        header("Location: admin_edit_quiz.php?id=" . $quiz_id . "&" . $action . "=1");
        exit;
    }
}

// load quiz - only if created by this admin
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ? AND created_by = ?");
$stmt->execute([$quiz_id, $adminId]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$quiz) { 
    echo "<div style='text-align:center;padding:50px;font-family:sans-serif;'>";
    echo "<h2>⚠️ Access Denied</h2>";
    echo "<p>You can only edit quizzes you created.</p>";
    echo "<a href='admin_dashboard.php' style='color:#7c3aed;'>← Back to Dashboard</a>";
    echo "</div>";
    exit; 
}

$error = null;
$success = null;
$editQuestion = null;

// Handle delete question
if (isset($_GET['delete_q'])) {
    $deleteId = intval($_GET['delete_q']);
    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ? AND quiz_id = ?");
    $stmt->execute([$deleteId, $quiz_id]);
    header("Location: admin_edit_quiz.php?id=$quiz_id&deleted=1");
    exit;
}

// Handle edit question - load data
if (isset($_GET['edit_q'])) {
    $editId = intval($_GET['edit_q']);
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ? AND quiz_id = ?");
    $stmt->execute([$editId, $quiz_id]);
    $editQuestion = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle update quiz title/description
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quiz'])) {
    $newTitle = trim($_POST['quiz_title'] ?? '');
    $newDesc = trim($_POST['quiz_description'] ?? '');
    $newTimeLimit = intval($_POST['quiz_time_limit'] ?? 0);
    $randomize = isset($_POST['randomize_questions']) ? 1 : 0;
    $showAnswers = isset($_POST['show_answers']) ? 1 : 0;
    $passPercentage = intval($_POST['pass_percentage'] ?? 0);
    $negativeMarking = floatval($_POST['negative_marking'] ?? 0);
    $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $certificateEnabled = isset($_POST['certificate_enabled']) ? 1 : 0;
    
    if ($newTitle !== '') {
        $stmt = $pdo->prepare("UPDATE quizzes SET title = ?, description = ?, time_limit = ?, randomize_questions = ?, show_answers = ?, pass_percentage = ?, negative_marking = ?, start_date = ?, end_date = ?, certificate_enabled = ? WHERE id = ?");
        $stmt->execute([$newTitle, $newDesc, $newTimeLimit, $randomize, $showAnswers, $passPercentage, $negativeMarking, $startDate, $endDate, $certificateEnabled, $quiz_id]);
        header("Location: admin_edit_quiz.php?id=$quiz_id&updated=1");
        exit;
    }
}

// Handle update question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_question'])) {
    $qId = intval($_POST['question_id'] ?? 0);
    $qtext = trim($_POST['question_text'] ?? '');
    $a = trim($_POST['option_a'] ?? '');
    $b = trim($_POST['option_b'] ?? '');
    $c = trim($_POST['option_c'] ?? '');
    $d = trim($_POST['option_d'] ?? '');
    $correct = $_POST['correct_option'] ?? 'A';
    $qTimeLimit = intval($_POST['question_time_limit'] ?? 60);
    $qPoints = intval($_POST['question_points'] ?? 1);
    
    if ($qtext === '' || $a==='' || $b==='' || $c==='' || $d==='') {
        $error = "Fill all fields";
    } else {
        $stmt = $pdo->prepare("UPDATE questions SET question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ?, time_limit = ?, points = ? WHERE id = ? AND quiz_id = ?");
        $stmt->execute([$qtext, $a, $b, $c, $d, $correct, $qTimeLimit, $qPoints, $qId, $quiz_id]);
        header("Location: admin_edit_quiz.php?id=$quiz_id&updated=1");
        exit;
    }
}

// Handle add new question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $qtext = trim($_POST['question_text'] ?? '');
    $a = trim($_POST['option_a'] ?? '');
    $b = trim($_POST['option_b'] ?? '');
    $c = trim($_POST['option_c'] ?? '');
    $d = trim($_POST['option_d'] ?? '');
    $correct = $_POST['correct_option'] ?? 'A';
    $qTimeLimit = intval($_POST['question_time_limit'] ?? 60);
    $qPoints = intval($_POST['question_points'] ?? 1);
    if ($qtext === '' || $a==='' || $b==='' || $c==='' || $d==='') $error = "Fill all fields";
    if (!$error) {
        $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option, time_limit, points) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$quiz_id, $qtext, $a, $b, $c, $d, $correct, $qTimeLimit, $qPoints]);
        header("Location: admin_edit_quiz.php?id=$quiz_id&added=1");
        exit;
    }
}

// Check for success messages
if (isset($_GET['deleted'])) $success = "Question deleted successfully!";
if (isset($_GET['updated'])) $success = "Updated successfully!";
if (isset($_GET['added'])) $success = "Question added successfully!";
if (isset($_GET['published'])) $success = "🎉 Quiz published! Students can now take this quiz.";
if (isset($_GET['unpublished'])) $success = "Quiz unpublished. Students can no longer see this quiz.";

// Reload quiz data
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

// load questions
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?=htmlspecialchars($quiz['title'])?> — QuickQuiz Admin</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/quickquiz.css">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
  <style>
    .question-item {
      padding: 1.25rem;
      border-bottom: 1px solid rgba(0,0,0,0.06);
      transition: all 0.2s;
    }
    .question-item:hover {
      background: #f8fafc;
    }
    .question-item:last-child {
      border-bottom: none;
    }
    .question-actions {
      display: flex;
      gap: 0.5rem;
      opacity: 0;
      transition: opacity 0.2s;
    }
    .question-item:hover .question-actions {
      opacity: 1;
    }
    .btn-edit, .btn-delete {
      padding: 0.375rem 0.75rem;
      border-radius: 8px;
      font-size: 0.75rem;
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
    }
    .btn-edit {
      background: #e0e7ff;
      color: #4338ca;
    }
    .btn-edit:hover {
      background: #c7d2fe;
      color: #3730a3;
    }
    .btn-delete {
      background: #fee2e2;
      color: #dc2626;
    }
    .btn-delete:hover {
      background: #fecaca;
      color: #b91c1c;
    }
    .edit-modal {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0,0,0,0.5);
      backdrop-filter: blur(4px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 2000;
      padding: 1rem;
    }
    .edit-modal-content {
      background: white;
      border-radius: 20px;
      max-width: 600px;
      width: 100%;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
      animation: modalEnter 0.3s ease;
    }
    @keyframes modalEnter {
      from { opacity: 0; transform: scale(0.95) translateY(20px); }
      to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .edit-modal-header {
      padding: 1.5rem;
      border-bottom: 1px solid #e2e8f0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .edit-modal-header h5 {
      font-size: 1.125rem;
      font-weight: 700;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .edit-modal-body {
      padding: 1.5rem;
    }
    .edit-modal-close {
      background: #f1f5f9;
      border: none;
      width: 36px;
      height: 36px;
      border-radius: 10px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
    }
    .edit-modal-close:hover {
      background: #e2e8f0;
    }
    .success-toast {
      position: fixed;
      top: 100px;
      right: 20px;
      background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
      color: white;
      padding: 1rem 1.5rem;
      border-radius: 12px;
      font-weight: 600;
      box-shadow: 0 10px 40px rgba(34, 197, 94, 0.3);
      z-index: 3000;
      animation: toastEnter 0.3s ease, toastExit 0.3s ease 2.7s forwards;
    }
    @keyframes toastEnter {
      from { opacity: 0; transform: translateX(100px); }
      to { opacity: 1; transform: translateX(0); }
    }
    @keyframes toastExit {
      from { opacity: 1; transform: translateX(0); }
      to { opacity: 0; transform: translateX(100px); }
    }
    .quiz-title-edit {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .quiz-title-edit input {
      font-size: 1.25rem;
      font-weight: 700;
      border: 2px solid transparent;
      background: transparent;
      padding: 0.25rem 0.5rem;
      border-radius: 8px;
      transition: all 0.2s;
      width: 100%;
    }
    .quiz-title-edit input:hover {
      background: #f8fafc;
    }
    .quiz-title-edit input:focus {
      border-color: #7c3aed;
      background: white;
      outline: none;
    }
    .option-row {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 0.5rem;
    }
    .option-letter {
      width: 28px;
      height: 28px;
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.75rem;
      font-weight: 700;
      flex-shrink: 0;
    }
    .option-letter.correct {
      background: #dcfce7;
      color: #16a34a;
    }
    .option-letter.incorrect {
      background: #f1f5f9;
      color: #64748b;
    }
    .share-card {
      background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%);
      border-radius: 16px;
      padding: 1.25rem;
      margin-bottom: 1.5rem;
      border: 2px solid #c7d2fe;
    }
    .share-card h6 {
      font-weight: 700;
      color: #3730a3;
      margin-bottom: 0.75rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .share-link-box {
      display: flex;
      gap: 0.5rem;
      align-items: stretch;
    }
    .share-link-input {
      flex: 1;
      padding: 0.75rem 1rem;
      border: 2px solid #a5b4fc;
      border-radius: 10px;
      font-size: 0.875rem;
      font-family: monospace;
      background: white;
      color: #3730a3;
    }
    .share-link-input:focus {
      outline: none;
      border-color: #7c3aed;
    }
    .btn-copy {
      padding: 0.75rem 1.25rem;
      background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
      color: white;
      border: none;
      border-radius: 10px;
      font-weight: 600;
      font-size: 0.875rem;
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 0.375rem;
      white-space: nowrap;
    }
    .btn-copy:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(124, 58, 237, 0.3);
    }
    .btn-copy.copied {
      background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    }
    .share-hint {
      font-size: 0.75rem;
      color: #6366f1;
      margin-top: 0.5rem;
    }
  </style>
</head>
<body class="admin-theme <?= $darkMode ? 'dark-mode' : '' ?>">
  <?php if ($success): ?>
    <div class="success-toast">✓ <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="top-bar">
    <div class="container d-flex justify-content-between align-items-center">
      <div class="brand">⚡ QuickQuiz <small>ADMIN</small></div>
      <div class="d-flex gap-2 align-items-center">
        <?php $isPublished = $quiz['is_published'] ?? 0; ?>
        <form method="post" style="display: inline;">
          <input type="hidden" name="toggle_publish" value="1">
          <?php if ($isPublished): ?>
            <button type="submit" class="btn btn-action" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; padding: 0.5rem 1rem;" 
                    onclick="return confirm('Unpublish this quiz? Students will no longer be able to take it.');">
              📝 Unpublish
            </button>
          <?php else: ?>
            <button type="submit" class="btn btn-action" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; border: none; padding: 0.5rem 1rem;"
                    <?= count($questions) == 0 ? 'disabled title="Add at least one question first"' : '' ?>>
              🚀 Publish Quiz
            </button>
          <?php endif; ?>
        </form>
        <button type="button" id="darkModeToggle" class="btn btn-action" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2); padding: 0.5rem 1rem; cursor: pointer;">
          <span id="darkModeIcon"><?= $darkMode ? '☀️' : '🌙' ?></span> <span id="darkModeText"><?= $darkMode ? 'Light' : 'Dark' ?></span>
        </button>
        <a class="btn btn-action" href="admin_result.php?id=<?= $quiz_id ?>" style="background: rgba(255,255,255,0.1); color: white; border: 1px solid rgba(255,255,255,0.2);">
          📊 Results
        </a>
        <a class="btn btn-action" href="admin_dashboard.php?delete_quiz=<?= $quiz_id ?>" 
           onclick="return confirm('Are you sure you want to delete this quiz? This will also delete all questions and student results. This action cannot be undone!');"
           style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; border: none;">
          🗑️ Delete Quiz
        </a>
        <a class="btn btn-outline-light btn-action" href="admin_dashboard.php">
          ← Dashboard
        </a>
      </div>
    </div>
  </div>

  <div class="page-container">
    <!-- Quiz Header - Editable -->
    <form method="post" class="form-card mb-4">
      <input type="hidden" name="update_quiz" value="1">
      
      <!-- Top row: Header -->
      <div class="d-flex justify-content-between align-items-center mb-4 pb-3" style="border-bottom: 1px solid #e2e8f0;">
        <h6 class="mb-0" style="color: #64748b; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px;">📝 Quiz Details</h6>
        <span class="badge" style="background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); color: white; font-size: 0.875rem; padding: 0.5rem 1rem; border-radius: 20px;">
          <?= count($questions) ?> Questions
        </span>
      </div>
      
      <!-- Title -->
      <div class="mb-4" style="margin-top: 1rem;">
        <label class="form-label mb-2" style="font-weight: 600; color: #334155; font-size: 0.875rem;">Quiz Title</label>
        <input type="text" name="quiz_title" value="<?= htmlspecialchars($quiz['title']) ?>" 
               class="form-control" style="font-size: 1.125rem; font-weight: 600; border: 2px solid #e2e8f0; padding: 0.75rem 1rem;"
               placeholder="Enter quiz title...">
      </div>
      
      <!-- Description -->
      <div class="mb-4" style="margin-top: 1.5rem;">
        <label class="form-label mb-2" style="font-weight: 600; color: #334155; font-size: 0.875rem;">Description <span class="text-muted fw-normal">(optional)</span></label>
        <textarea name="quiz_description" class="form-control" rows="2" 
                  style="border: 2px solid #e2e8f0; font-size: 0.9375rem; padding: 0.75rem 1rem;"
                  placeholder="Describe what this quiz covers..."><?= htmlspecialchars($quiz['description'] ?? '') ?></textarea>
      </div>
      
      <!-- Time Limit -->
      <div class="mb-4" style="background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border-radius: 12px; padding: 1.25rem; border: 1px solid #bbf7d0;">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
          <div>
            <label class="form-label mb-1" style="font-weight: 600; color: #166534; font-size: 0.875rem;">⏱️ Time Limit</label>
            <div class="text-muted" style="font-size: 0.75rem;">Set how long students have to complete this quiz</div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <div class="input-group" style="width: 140px;">
              <input type="number" name="quiz_time_limit" class="form-control text-center" 
                     value="<?= intval($quiz['time_limit'] ?? 0) ?>" 
                     min="0" max="180" style="border: 2px solid #86efac; font-weight: 600; font-size: 1rem;">
              <span class="input-group-text" style="border: 2px solid #86efac; border-left: none; background: #dcfce7; color: #166534; font-weight: 600;">min</span>
            </div>
          </div>
        </div>
        <div class="mt-2" style="font-size: 0.75rem; color: #15803d;">
          💡 Set to <strong>0</strong> for auto-timing (sum of question times)
        </div>
      </div>

      <!-- Quiz Settings Grid -->
      <div class="mb-4" style="background: #f8fafc; border-radius: 12px; padding: 1.25rem; border: 1px solid #e2e8f0;">
        <h6 style="font-weight: 600; color: #334155; font-size: 0.875rem; margin-bottom: 1rem;">⚙️ Quiz Settings</h6>
        
        <div class="row g-3">
          <!-- Randomize Questions -->
          <div class="col-md-6">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="randomize_questions" id="randomize" <?= ($quiz['randomize_questions'] ?? 0) ? 'checked' : '' ?> style="width: 3rem; height: 1.5rem;">
              <label class="form-check-label" for="randomize" style="font-weight: 500; margin-left: 0.5rem;">
                🔀 Randomize Questions
              </label>
            </div>
            <small class="text-muted d-block mt-1" style="margin-left: 3.5rem;">Shuffle order for each student</small>
          </div>
          
          <!-- Show Answers -->
          <div class="col-md-6">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="show_answers" id="showAnswers" <?= ($quiz['show_answers'] ?? 1) ? 'checked' : '' ?> style="width: 3rem; height: 1.5rem;">
              <label class="form-check-label" for="showAnswers" style="font-weight: 500; margin-left: 0.5rem;">
                ✅ Show Correct Answers
              </label>
            </div>
            <small class="text-muted d-block mt-1" style="margin-left: 3.5rem;">After quiz submission</small>
          </div>
          
          <!-- Certificate -->
          <div class="col-md-6">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="certificate_enabled" id="certificate" <?= ($quiz['certificate_enabled'] ?? 0) ? 'checked' : '' ?> style="width: 3rem; height: 1.5rem;">
              <label class="form-check-label" for="certificate" style="font-weight: 500; margin-left: 0.5rem;">
                🎖️ Enable Certificate
              </label>
            </div>
            <small class="text-muted d-block mt-1" style="margin-left: 3.5rem;">Generate PDF on passing</small>
          </div>
        </div>
      </div>
      
      <!-- Scoring Settings -->
      <div class="mb-4" style="background: linear-gradient(135deg, #fef3c7 0%, #fef9c3 100%); border-radius: 12px; padding: 1.25rem; border: 1px solid #fcd34d;">
        <h6 style="font-weight: 600; color: #92400e; font-size: 0.875rem; margin-bottom: 1rem;">🎯 Scoring Settings</h6>
        
        <div class="row g-3">
          <!-- Pass Percentage -->
          <div class="col-md-6">
            <label class="form-label mb-1" style="font-weight: 500; color: #92400e; font-size: 0.8125rem;">Pass Threshold</label>
            <div class="input-group" style="max-width: 150px;">
              <input type="number" name="pass_percentage" class="form-control" 
                     value="<?= intval($quiz['pass_percentage'] ?? 0) ?>" 
                     min="0" max="100" style="border: 2px solid #fcd34d;">
              <span class="input-group-text" style="border: 2px solid #fcd34d; border-left: none; background: #fef3c7; color: #92400e;">%</span>
            </div>
            <small class="text-muted d-block mt-1">0 = No pass/fail</small>
          </div>
          
          <!-- Negative Marking -->
          <div class="col-md-6">
            <label class="form-label mb-1" style="font-weight: 500; color: #92400e; font-size: 0.8125rem;">➖ Negative Marking</label>
            <div class="input-group" style="max-width: 150px;">
              <span class="input-group-text" style="border: 2px solid #fcd34d; border-right: none; background: #fef3c7; color: #92400e;">-</span>
              <input type="number" name="negative_marking" class="form-control" 
                     value="<?= floatval($quiz['negative_marking'] ?? 0) ?>" 
                     min="0" max="1" step="0.25" style="border: 2px solid #fcd34d;">
            </div>
            <small class="text-muted d-block mt-1">Points deducted per wrong answer</small>
          </div>
        </div>
      </div>
      
      <!-- Scheduling -->
      <div class="mb-4" style="background: linear-gradient(135deg, #dbeafe 0%, #e0e7ff 100%); border-radius: 12px; padding: 1.25rem; border: 1px solid #93c5fd;">
        <h6 style="font-weight: 600; color: #1e40af; font-size: 0.875rem; margin-bottom: 1rem;">📅 Quiz Scheduling</h6>
        
        <div class="row g-3">
          <!-- Start Date -->
          <div class="col-md-6">
            <label class="form-label mb-1" style="font-weight: 500; color: #1e40af; font-size: 0.8125rem;">Available From</label>
            <input type="datetime-local" name="start_date" class="form-control" 
                   value="<?= $quiz['start_date'] ? date('Y-m-d\TH:i', strtotime($quiz['start_date'])) : '' ?>"
                   style="border: 2px solid #93c5fd;">
            <small class="text-muted d-block mt-1">Leave empty for immediate</small>
          </div>
          
          <!-- End Date -->
          <div class="col-md-6">
            <label class="form-label mb-1" style="font-weight: 500; color: #1e40af; font-size: 0.8125rem;">Available Until</label>
            <input type="datetime-local" name="end_date" class="form-control" 
                   value="<?= $quiz['end_date'] ? date('Y-m-d\TH:i', strtotime($quiz['end_date'])) : '' ?>"
                   style="border: 2px solid #93c5fd;">
            <small class="text-muted d-block mt-1">Leave empty for no deadline</small>
          </div>
        </div>
      </div>
      
      <!-- Save Button -->
      <div class="d-flex justify-content-end">
        <button type="submit" class="btn" style="background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); color: white; font-weight: 600; padding: 0.75rem 1.5rem; border-radius: 10px;">
          💾 Save Changes
        </button>
      </div>
    </form>

    <!-- Quiz Status & Publish Card -->
    <?php 
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $baseUrl = $protocol . '://' . $host . dirname($_SERVER['PHP_SELF']);
    $quizLink = $baseUrl . '/take_quiz.php?id=' . $quiz_id;
    $isPublished = $quiz['is_published'] ?? 0;
    $hasQuestions = count($questions) > 0;
    ?>
    
    <?php if (!$isPublished): ?>
    <!-- Draft Status - Show Finish Button -->
    <div class="share-card" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 2px solid #f59e0b;">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
          <h6 style="color: #92400e; margin-bottom: 0.25rem;">📝 Quiz is in Draft Mode</h6>
          <p style="color: #a16207; margin: 0; font-size: 0.875rem;">
            <?php if ($hasQuestions): ?>
              You have <?= count($questions) ?> question<?= count($questions) > 1 ? 's' : '' ?>. Ready to publish?
            <?php else: ?>
              Add questions to your quiz, then publish it for students.
            <?php endif; ?>
          </p>
        </div>
        <form method="post">
          <input type="hidden" name="toggle_publish" value="1">
          <button type="submit" class="btn" <?= !$hasQuestions ? 'disabled' : '' ?>
                  style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white; font-weight: 700; padding: 0.875rem 2rem; border-radius: 12px; border: none; font-size: 1rem; box-shadow: 0 4px 15px rgba(34, 197, 94, 0.4);">
            🚀 Finish & Publish Quiz
          </button>
        </form>
      </div>
      <?php if (!$hasQuestions): ?>
      <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid rgba(245, 158, 11, 0.3);">
        <small style="color: #b45309;">⚠️ You need to add at least one question before publishing.</small>
      </div>
      <?php endif; ?>
    </div>
    <?php else: ?>
    <!-- Published Status - Show Share Link -->
    <div class="share-card" style="background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); border: 2px solid #22c55e;">
      <div class="d-flex align-items-center gap-2 mb-3">
        <span style="background: #22c55e; color: white; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600;">✓ PUBLISHED</span>
        <span style="color: #166534; font-size: 0.875rem;">Students can now take this quiz!</span>
      </div>
      <h6 style="color: #166534;">🔗 Share Quiz Link</h6>
      <div class="share-link-box">
        <input type="text" class="share-link-input" id="quizLink" value="<?= htmlspecialchars($quizLink) ?>" readonly onclick="this.select()">
        <button type="button" class="btn-copy" id="copyBtn" onclick="copyLink()">
          <span id="copyIcon">📋</span>
          <span id="copyText">Copy Link</span>
        </button>
      </div>
      <div class="share-hint" style="color: #15803d;">
        ✅ Send this link to your students so they can take the quiz.
      </div>
    </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-danger" style="border-radius: 12px; border: none; margin-bottom: 1.5rem;"><?=htmlspecialchars($error)?></div>
    <?php endif; ?>

    <div class="row">
      <!-- Add Question Form -->
      <div class="col-lg-5 mb-4">
        <h5 class="section-title">
          <span class="icon">➕</span>
          Add Question
        </h5>
        <div class="form-card">
          <form method="post">
            <input type="hidden" name="add_question" value="1">
            <div class="mb-3">
              <label class="form-label">Question Text</label>
              <textarea name="question_text" class="form-control" rows="3" placeholder="Enter your question..." required></textarea>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label small text-muted">Option A</label>
                <input name="option_a" class="form-control" placeholder="First option" required>
              </div>
              <div class="col-6">
                <label class="form-label small text-muted">Option B</label>
                <input name="option_b" class="form-control" placeholder="Second option" required>
              </div>
              <div class="col-6">
                <label class="form-label small text-muted">Option C</label>
                <input name="option_c" class="form-control" placeholder="Third option" required>
              </div>
              <div class="col-6">
                <label class="form-label small text-muted">Option D</label>
                <input name="option_d" class="form-control" placeholder="Fourth option" required>
              </div>
            </div>
            <div class="row g-2 mb-3">
              <div class="col-4">
                <label class="form-label">Correct Answer</label>
                <select name="correct_option" class="form-select">
                  <option value="A">Option A</option>
                  <option value="B">Option B</option>
                  <option value="C">Option C</option>
                  <option value="D">Option D</option>
                </select>
              </div>
              <div class="col-4">
                <label class="form-label">⏱️ Time Limit</label>
                <div class="input-group">
                  <input type="number" name="question_time_limit" class="form-control" value="60" min="10" max="300" style="font-weight: 600;">
                  <span class="input-group-text">sec</span>
                </div>
              </div>
              <div class="col-4">
                <label class="form-label">⭐ Points</label>
                <div class="input-group">
                  <input type="number" name="points" class="form-control" value="1" min="1" max="100" style="font-weight: 600;">
                  <span class="input-group-text">pts</span>
                </div>
              </div>
            </div>
            <button class="btn w-100" style="background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); color: white; padding: 0.75rem; font-weight: 600;">
              ➕ Add Question</button>
          </form>
        </div>
      </div>

      <!-- Questions List -->
      <div class="col-lg-7">
        <h5 class="section-title">
          <span class="icon">📝</span>
          Questions
        </h5>
        <div class="table-container">
          <?php if (empty($questions)): ?>
            <div class="empty-state">
              <div class="icon">❓</div>
              <p>No questions yet.<br>Add your first question to get started!</p>
            </div>
          <?php else: ?>
            <?php foreach ($questions as $i=>$q): ?>
              <div class="question-item">
                <div class="d-flex gap-3">
                  <span class="question-number" style="background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);"><?= $i+1 ?></span>
                  <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <div>
                        <div class="fw-semibold text-dark"><?= htmlspecialchars($q['question_text']) ?></div>
                        <span class="badge mt-1" style="background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); color: white; font-size: 0.7rem; padding: 0.25rem 0.5rem;">
                          ⏱️ <?= isset($q['time_limit']) ? intval($q['time_limit']) : 60 ?>s
                        </span>
                        <span class="badge mt-1 ms-1" style="background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%); color: white; font-size: 0.7rem; padding: 0.25rem 0.5rem;">
                          ⭐ <?= isset($q['points']) ? intval($q['points']) : 1 ?> pts
                        </span>
                      </div>
                      <div class="question-actions">
                        <a href="?id=<?= $quiz_id ?>&edit_q=<?= $q['id'] ?>" class="btn-edit">
                          ✏️ Edit
                        </a>
                        <a href="?id=<?= $quiz_id ?>&delete_q=<?= $q['id'] ?>" class="btn-delete" 
                           onclick="return confirm('Delete this question?')">
                          🗑️ Delete
                        </a>
                      </div>
                    </div>
                    <div class="row g-2">
                      <div class="col-6">
                        <div class="option-row">
                          <span class="option-letter <?= $q['correct_option'] === 'A' ? 'correct' : 'incorrect' ?>">A</span>
                          <small class="<?= $q['correct_option'] === 'A' ? 'text-success fw-semibold' : 'text-muted' ?>">
                            <?=htmlspecialchars($q['option_a'])?> <?= $q['correct_option'] === 'A' ? '✓' : '' ?>
                          </small>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="option-row">
                          <span class="option-letter <?= $q['correct_option'] === 'B' ? 'correct' : 'incorrect' ?>">B</span>
                          <small class="<?= $q['correct_option'] === 'B' ? 'text-success fw-semibold' : 'text-muted' ?>">
                            <?=htmlspecialchars($q['option_b'])?> <?= $q['correct_option'] === 'B' ? '✓' : '' ?>
                          </small>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="option-row">
                          <span class="option-letter <?= $q['correct_option'] === 'C' ? 'correct' : 'incorrect' ?>">C</span>
                          <small class="<?= $q['correct_option'] === 'C' ? 'text-success fw-semibold' : 'text-muted' ?>">
                            <?=htmlspecialchars($q['option_c'])?> <?= $q['correct_option'] === 'C' ? '✓' : '' ?>
                          </small>
                        </div>
                      </div>
                      <div class="col-6">
                        <div class="option-row">
                          <span class="option-letter <?= $q['correct_option'] === 'D' ? 'correct' : 'incorrect' ?>">D</span>
                          <small class="<?= $q['correct_option'] === 'D' ? 'text-success fw-semibold' : 'text-muted' ?>">
                            <?=htmlspecialchars($q['option_d'])?> <?= $q['correct_option'] === 'D' ? '✓' : '' ?>
                          </small>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <?php if ($editQuestion): ?>
  <!-- Edit Question Modal -->
  <div class="edit-modal" id="editModal">
    <div class="edit-modal-content">
      <div class="edit-modal-header">
        <h5>✏️ Edit Question</h5>
        <a href="?id=<?= $quiz_id ?>" class="edit-modal-close">✕</a>
      </div>
      <div class="edit-modal-body">
        <form method="post">
          <input type="hidden" name="update_question" value="1">
          <input type="hidden" name="question_id" value="<?= $editQuestion['id'] ?>">
          
          <div class="mb-3">
            <label class="form-label">Question Text</label>
            <textarea name="question_text" class="form-control" rows="3" required><?= htmlspecialchars($editQuestion['question_text']) ?></textarea>
          </div>
          
          <div class="row g-3 mb-3">
            <div class="col-6">
              <label class="form-label small text-muted">Option A</label>
              <input name="option_a" class="form-control" value="<?= htmlspecialchars($editQuestion['option_a']) ?>" required>
            </div>
            <div class="col-6">
              <label class="form-label small text-muted">Option B</label>
              <input name="option_b" class="form-control" value="<?= htmlspecialchars($editQuestion['option_b']) ?>" required>
            </div>
            <div class="col-6">
              <label class="form-label small text-muted">Option C</label>
              <input name="option_c" class="form-control" value="<?= htmlspecialchars($editQuestion['option_c']) ?>" required>
            </div>
            <div class="col-6">
              <label class="form-label small text-muted">Option D</label>
              <input name="option_d" class="form-control" value="<?= htmlspecialchars($editQuestion['option_d']) ?>" required>
            </div>
          </div>
          
          <div class="row g-3 mb-4">
            <div class="col-4">
              <label class="form-label">Correct Answer</label>
              <select name="correct_option" class="form-select">
                <option value="A" <?= $editQuestion['correct_option'] === 'A' ? 'selected' : '' ?>>Option A</option>
                <option value="B" <?= $editQuestion['correct_option'] === 'B' ? 'selected' : '' ?>>Option B</option>
                <option value="C" <?= $editQuestion['correct_option'] === 'C' ? 'selected' : '' ?>>Option C</option>
                <option value="D" <?= $editQuestion['correct_option'] === 'D' ? 'selected' : '' ?>>Option D</option>
              </select>
            </div>
            <div class="col-4">
              <label class="form-label">⏱️ Time Limit</label>
              <div class="input-group">
                <input type="number" name="question_time_limit" class="form-control" 
                       value="<?= isset($editQuestion['time_limit']) ? intval($editQuestion['time_limit']) : 60 ?>" 
                       min="10" max="300" style="font-weight: 600;">
                <span class="input-group-text">sec</span>
              </div>
            </div>
            <div class="col-4">
              <label class="form-label">⭐ Points</label>
              <div class="input-group">
                <input type="number" name="points" class="form-control" 
                       value="<?= isset($editQuestion['points']) ? intval($editQuestion['points']) : 1 ?>" 
                       min="1" max="100" style="font-weight: 600;">
                <span class="input-group-text">pts</span>
              </div>
            </div>
          </div>
          
          <div class="d-flex gap-3">
            <a href="?id=<?= $quiz_id ?>" class="btn flex-grow-1" style="background: #f1f5f9; color: #334155; padding: 0.75rem; font-weight: 600;">
              Cancel
            </a>
            <button type="submit" class="btn flex-grow-1" style="background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%); color: white; padding: 0.75rem; font-weight: 600;">
              💾 Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <script>
    // Close modal on background click
    document.getElementById('editModal').addEventListener('click', function(e) {
      if (e.target === this) {
        window.location.href = '?id=<?= $quiz_id ?>';
      }
    });
    
    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        window.location.href = '?id=<?= $quiz_id ?>';
      }
    });
  </script>
  <?php endif; ?>

  <script>
    // Auto-hide success toast
    setTimeout(function() {
      var toast = document.querySelector('.success-toast');
      if (toast) toast.style.display = 'none';
    }, 3000);
    
    // Copy quiz link to clipboard
    function copyLink() {
      var linkInput = document.getElementById('quizLink');
      var copyBtn = document.getElementById('copyBtn');
      var copyIcon = document.getElementById('copyIcon');
      var copyText = document.getElementById('copyText');
      
      linkInput.select();
      linkInput.setSelectionRange(0, 99999); // For mobile
      
      navigator.clipboard.writeText(linkInput.value).then(function() {
        // Success feedback
        copyBtn.classList.add('copied');
        copyIcon.textContent = '✓';
        copyText.textContent = 'Copied!';
        
        setTimeout(function() {
          copyBtn.classList.remove('copied');
          copyIcon.textContent = '📋';
          copyText.textContent = 'Copy Link';
        }, 2000);
      }).catch(function() {
        // Fallback
        document.execCommand('copy');
        copyBtn.classList.add('copied');
        copyIcon.textContent = '✓';
        copyText.textContent = 'Copied!';
        
        setTimeout(function() {
          copyBtn.classList.remove('copied');
          copyIcon.textContent = '📋';
          copyText.textContent = 'Copy Link';
        }, 2000);
      });
    }
    
    // Dark mode toggle
    document.getElementById('darkModeToggle').addEventListener('click', function() {
      fetch('toggle_dark_mode.php', { method: 'POST' })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            document.body.classList.toggle('dark-mode');
            document.getElementById('darkModeIcon').textContent = data.darkMode ? '☀️' : '🌙';
            document.getElementById('darkModeText').textContent = data.darkMode ? 'Light' : 'Dark';
          }
        });
    });
  </script>
</body>
</html>
