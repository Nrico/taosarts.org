<?php
$title = 'Entities';
$active = 'entities';
require_once __DIR__ . '/_layout.php';
$rows = db()->query(
    'SELECT e.*, (SELECT COUNT(*) FROM story_entities se WHERE se.entity_id = e.id) story_count
     FROM entities e ORDER BY e.type, e.name'
)->fetchAll();
?>
<div class="topbar"><h1>Entities</h1><a class="btn" href="entity_edit.php">+ New entity</a></div>
<section class="panel">
  <table class="table">
    <tr><th>Name</th><th>Type</th><th>One-line ID</th><th>Appears in</th><th></th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= h($r['name']) ?></td>
      <td><span class="pill"><?= h($r['type']) ?></span></td>
      <td><?= h($r['one_line_id']) ?></td>
      <td><?= (int)$r['story_count'] ?> <?= $r['story_count'] == 1 ? 'story' : 'stories' ?></td>
      <td><a href="entity_edit.php?id=<?= (int)$r['id'] ?>">Edit</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
