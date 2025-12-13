<?php
session_start();
require_once __DIR__.'/../src/db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$resultId = isset($_GET['result_id']) ? intval($_GET['result_id']) : 0;

if (!$resultId) {
    die("Invalid certificate request");
}

// Get result with quiz and user info
$stmt = $pdo->prepare(
    "SELECT r.*, q.title as quiz_title, q.certificate_enabled, q.pass_percentage,
            u.username
     FROM results r
     JOIN quizzes q ON r.quiz_id = q.id
     JOIN users u ON r.user_id = u.id
     WHERE r.id = ? AND r.user_id = ?"
);
$stmt->execute([$resultId, $_SESSION['user_id']]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    die("Certificate not found or unauthorized");
}

// Check if certificate is enabled and user passed
if (!$result['certificate_enabled']) {
    die("Certificates are not enabled for this quiz");
}

if (!$result['passed']) {
    die("Certificate is only available for passing scores");
}

// Calculate percentage
$percentage = $result['total_points'] > 0 
    ? round(($result['score'] / $result['total_points']) * 100, 1) 
    : 0;

// Format date
$completionDate = date('F j, Y', strtotime($result['taken_at'] ?? 'now'));

// Generate a unique certificate ID
$certificateId = strtoupper(substr(md5($resultId . $result['user_id'] . $result['quiz_id']), 0, 12));
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Certificate — <?= htmlspecialchars($result['quiz_title']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body {
      font-family: 'Inter', sans-serif;
      background: #1e293b;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 2rem;
    }
    
    .controls {
      display: flex;
      gap: 1rem;
      margin-bottom: 2rem;
    }
    
    .btn {
      padding: 0.75rem 1.5rem;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.9rem;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
      color: white;
    }
    
    .btn-secondary {
      background: #334155;
      color: white;
    }
    
    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }
    
    .certificate {
      width: 900px;
      height: 636px;
      background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%);
      border-radius: 8px;
      position: relative;
      overflow: hidden;
      box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
    }
    
    .certificate::before {
      content: '';
      position: absolute;
      inset: 20px;
      border: 3px solid #d97706;
      border-radius: 4px;
    }
    
    .certificate::after {
      content: '';
      position: absolute;
      inset: 28px;
      border: 1px solid #f59e0b;
      border-radius: 4px;
    }
    
    .corner-ornament {
      position: absolute;
      width: 100px;
      height: 100px;
      opacity: 0.15;
    }
    
    .corner-ornament.top-left { top: 30px; left: 30px; }
    .corner-ornament.top-right { top: 30px; right: 30px; transform: rotate(90deg); }
    .corner-ornament.bottom-left { bottom: 30px; left: 30px; transform: rotate(-90deg); }
    .corner-ornament.bottom-right { bottom: 30px; right: 30px; transform: rotate(180deg); }
    
    .certificate-content {
      position: relative;
      z-index: 10;
      padding: 60px 80px;
      text-align: center;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    
    .header-logo {
      font-size: 1.5rem;
      font-weight: 700;
      color: #7c3aed;
      letter-spacing: 2px;
      margin-bottom: 0.5rem;
    }
    
    .certificate-title {
      font-family: 'Playfair Display', serif;
      font-size: 3rem;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 0.5rem;
      letter-spacing: 4px;
      text-transform: uppercase;
    }
    
    .subtitle {
      font-size: 0.95rem;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 3px;
      margin-bottom: 2rem;
    }
    
    .awarded-to {
      font-size: 0.9rem;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 2px;
      margin-bottom: 0.5rem;
    }
    
    .recipient-name {
      font-family: 'Playfair Display', serif;
      font-size: 2.5rem;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 1.5rem;
      border-bottom: 2px solid #d97706;
      display: inline-block;
      padding-bottom: 0.5rem;
    }
    
    .description {
      font-size: 1rem;
      color: #475569;
      line-height: 1.8;
      max-width: 600px;
      margin: 0 auto 2rem;
    }
    
    .quiz-name {
      font-family: 'Playfair Display', serif;
      font-size: 1.5rem;
      font-weight: 600;
      color: #7c3aed;
    }
    
    .score-badge {
      display: inline-block;
      background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
      color: white;
      padding: 0.5rem 1.5rem;
      border-radius: 25px;
      font-weight: 600;
      margin-top: 1rem;
      font-size: 1rem;
    }
    
    .footer {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      margin-top: auto;
    }
    
    .footer-item {
      text-align: center;
    }
    
    .footer-item .line {
      width: 150px;
      height: 1px;
      background: #94a3b8;
      margin-bottom: 0.5rem;
    }
    
    .footer-item .label {
      font-size: 0.8rem;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    
    .footer-item .value {
      font-weight: 600;
      color: #1e293b;
      font-size: 0.9rem;
    }
    
    .seal {
      width: 80px;
      height: 80px;
      border: 3px solid #d97706;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
    }
    
    @media print {
      body {
        background: white;
        padding: 0;
      }
      .controls { display: none; }
      .certificate {
        box-shadow: none;
        width: 100%;
        height: auto;
        aspect-ratio: 900/636;
      }
    }
  </style>
</head>
<body>
  <div class="controls">
    <button class="btn btn-primary" onclick="window.print()">
      🖨️ Print Certificate
    </button>
    <a href="student_dashboard.php" class="btn btn-secondary">
      ← Back to Dashboard
    </a>
  </div>

  <div class="certificate">
    <!-- Decorative corners -->
    <svg class="corner-ornament top-left" viewBox="0 0 100 100">
      <path d="M0,100 Q0,0 100,0" fill="none" stroke="#d97706" stroke-width="3"/>
      <circle cx="15" cy="15" r="8" fill="#d97706"/>
    </svg>
    <svg class="corner-ornament top-right" viewBox="0 0 100 100">
      <path d="M0,100 Q0,0 100,0" fill="none" stroke="#d97706" stroke-width="3"/>
      <circle cx="15" cy="15" r="8" fill="#d97706"/>
    </svg>
    <svg class="corner-ornament bottom-left" viewBox="0 0 100 100">
      <path d="M0,100 Q0,0 100,0" fill="none" stroke="#d97706" stroke-width="3"/>
      <circle cx="15" cy="15" r="8" fill="#d97706"/>
    </svg>
    <svg class="corner-ornament bottom-right" viewBox="0 0 100 100">
      <path d="M0,100 Q0,0 100,0" fill="none" stroke="#d97706" stroke-width="3"/>
      <circle cx="15" cy="15" r="8" fill="#d97706"/>
    </svg>

    <div class="certificate-content">
      <div>
        <div class="header-logo">⚡ QUICKQUIZ</div>
        <h1 class="certificate-title">Certificate</h1>
        <div class="subtitle">of Achievement</div>
      </div>

      <div>
        <div class="awarded-to">This is to certify that</div>
        <div class="recipient-name"><?= htmlspecialchars($result['username']) ?></div>
        <div class="description">
          has successfully completed the quiz<br>
          <span class="quiz-name">"<?= htmlspecialchars($result['quiz_title']) ?>"</span>
        </div>
        <div class="score-badge">
          Score: <?= number_format($result['score'], 1) ?>/<?= $result['total_points'] ?> (<?= $percentage ?>%)
        </div>
      </div>

      <div class="footer">
        <div class="footer-item">
          <div class="value"><?= $completionDate ?></div>
          <div class="line"></div>
          <div class="label">Date</div>
        </div>
        
        <div class="seal">🎓</div>
        
        <div class="footer-item">
          <div class="value"><?= $certificateId ?></div>
          <div class="line"></div>
          <div class="label">Certificate ID</div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
