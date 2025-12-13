<?php
/**
 * QuickQuiz - Header Template
 * Include this at the top of pages after setting required variables
 * 
 * Required variables:
 * - $pageTitle: Page title
 * - $bodyClass: 'admin-theme' or 'student-theme'
 * - $darkMode: boolean
 * 
 * Optional variables:
 * - $extraStyles: Additional CSS
 * - $extraHead: Additional head content
 */

if (!isset($pageTitle)) $pageTitle = 'QuickQuiz';
if (!isset($bodyClass)) $bodyClass = '';
if (!isset($darkMode)) $darkMode = false;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="<?= strpos($bodyClass, 'admin') !== false ? '#7c3aed' : '#0d9488' ?>">
  <title><?= htmlspecialchars($pageTitle) ?> — QuickQuiz</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/quickquiz.css?v=<?= filemtime(__DIR__ . '/../assets/css/quickquiz.css') ?>">
  <link rel="manifest" href="manifest.json">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
  <?php if (isset($extraStyles)): ?>
  <style><?= $extraStyles ?></style>
  <?php endif; ?>
  <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body class="<?= $bodyClass ?> <?= $darkMode ? 'dark-mode' : '' ?>">
