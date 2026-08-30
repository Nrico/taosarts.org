<?php
$title = 'Stories';
$active = 'stories';
require_once __DIR__ . '/_layout.php';
$rows = db()->query('SELECT * FROM stories ORDER BY created_at DESC')->fetchAll();
?>
<div class="topbar"><h1>Stories</h1><a class="btn" href="editor.php">+ New story</a></div>
<section class="panel">
  <table class="table">
    <tr><th>Title</th><th>Status</th><th>Published</th><th></th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= h($r['title']) ?></td>
      <td><span class="pill"><?= h($r['status']) ?></span></td>
      <td><?= h($r['published_at'] ?: '—') ?></td>
      <td><a href="editor.php?id=<?= (int)$r['id'] ?>">Edit</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
