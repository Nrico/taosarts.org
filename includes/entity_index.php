<?php
// Shared body for people.php / places.php / traditions.php — set $entityType,
// $pageTitle, $pageLead before requiring this file.
$pdo = db();
$rows = $pdo->prepare(
    "SELECT e.*, (SELECT COUNT(*) FROM story_entities se JOIN stories s ON s.id = se.story_id
       WHERE se.entity_id = e.id AND s.status = 'published') story_count
     FROM entities e WHERE e.type = ? ORDER BY e.name"
);
$rows->execute([$entityType]);
$rows = $rows->fetchAll();
?>
<div class="page-header">
  <h1 class="page-title"><?= h($pageTitle) ?></h1>
  <p class="page-lead"><?= h($pageLead) ?></p>
</div>
<div class="container" style="margin-top:30px;">
  <div class="card-grid">
    <?php foreach ($rows as $e): $portrait = null;
      if ($e['portrait_image_id']) {
          $img = $pdo->prepare('SELECT * FROM images WHERE id = ?');
          $img->execute([$e['portrait_image_id']]);
          $portrait = $img->fetch();
      }
    ?>
    <div class="card">
      <div class="card-image portrait">
        <?php if ($portrait): ?><img src="<?= h($portrait['url']) ?>" alt="" loading="lazy" style="object-position:<?= h(image_object_position($portrait)) ?>"><?php endif; ?>
      </div>
      <div class="card-body">
        <div class="card-title"><a href="entity.php?slug=<?= h($e['slug']) ?>"><?= h($e['name']) ?></a></div>
        <?php if ($metaLine = entity_meta_line($e)): ?><div class="mono" style="font-size:11px;color:var(--sage);margin-top:6px;"><?= h($metaLine) ?></div><?php endif; ?>
        <div class="card-dek"><?= h($e['one_line_id']) ?></div>
        <div class="card-meta"><?= (int)$e['story_count'] ?> <?= $e['story_count'] == 1 ? 'story' : 'stories' ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if (!$rows): ?><p style="color:var(--muted)">Nothing here yet.</p><?php endif; ?>
</div>
