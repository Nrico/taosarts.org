<?php
$title = 'Stories';
$active = 'stories';
require_once __DIR__ . '/_layout.php';
$pdo = db();
$stories = $pdo->query("SELECT * FROM stories WHERE status='published' ORDER BY published_at DESC")->fetchAll();
?>
<div class="page-header">
  <h1 class="page-title">Stories</h1>
  <p class="page-lead">Every published story — art, artists, and history in and around Taos.</p>
</div>
<div class="container" style="margin-top:30px;">
  <div class="card-grid">
    <?php foreach ($stories as $s): echo render_story_card($pdo, $s); ?><?php endforeach; ?>
  </div>
  <?php if (!$stories): ?><p style="color:var(--muted)">No published stories yet.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
