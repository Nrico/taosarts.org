<?php
// Shared header for public pages. Set $title and $active before requiring
// this file, matching the pattern admin/_layout.php already uses. All
// public pages live at the site root, so links here are plain relative
// paths (no subdirectory nesting to account for).
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = ($title ?? 'Taos Arts') . (isset($title) ? ' | Taos Arts' : '');
$ogDescription = $ogDescription ?? 'Stories about Taos art, artists, culture, and history.';
$ogUrl = $ogUrl ?? (BASE_URL . '/' . ltrim($_SERVER['REQUEST_URI'] ?? '', '/'));
?><!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($pageTitle) ?></title>
<meta name="description" content="<?= h($ogDescription) ?>">
<meta property="og:type" content="<?= h($ogType ?? 'website') ?>">
<meta property="og:site_name" content="Taos Arts">
<meta property="og:title" content="<?= h($title ?? 'Taos Arts') ?>">
<meta property="og:description" content="<?= h($ogDescription) ?>">
<meta property="og:url" content="<?= h($ogUrl) ?>">
<?php if (!empty($ogImage)): ?><meta property="og:image" content="<?= h($ogImage) ?>">
<meta name="twitter:card" content="summary_large_image"><?php else: ?>
<meta name="twitter:card" content="summary"><?php endif; ?>
<meta name="twitter:title" content="<?= h($title ?? 'Taos Arts') ?>">
<meta name="twitter:description" content="<?= h($ogDescription) ?>">
<?php if (!empty($ogImage)): ?><meta name="twitter:image" content="<?= h($ogImage) ?>"><?php endif; ?>
<link rel="stylesheet" href="assets/css/magazine.css">
</head>
<body class="mag">
<nav class="site-nav">
  <a class="wordmark" href="index.php">Taos Arts</a>
  <div class="nav-links">
    <a href="stories.php" class="<?= ($active ?? '') === 'stories' ? 'active' : '' ?>">Stories</a>
    <a href="people.php" class="<?= ($active ?? '') === 'people' ? 'active' : '' ?>">People</a>
    <a href="places.php" class="<?= ($active ?? '') === 'places' ? 'active' : '' ?>">Places</a>
    <a href="traditions.php" class="<?= ($active ?? '') === 'traditions' ? 'active' : '' ?>">Traditions</a>
    <a href="about.php" class="<?= ($active ?? '') === 'about' ? 'active' : '' ?>">About</a>
  </div>
</nav>
