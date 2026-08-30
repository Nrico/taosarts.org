<?php
$title = 'Story ideas';
$active = 'ideas';
require_once __DIR__ . '/_layout.php';
$pdo = db();

$rows = $pdo->query(
    "SELECT si.*, e.name AS matched_entity_name, e.type AS matched_entity_type
     FROM story_ideas si
     LEFT JOIN entities e ON e.id = si.matched_entity_id
     WHERE si.status = 'new'
     ORDER BY si.created_at DESC"
)->fetchAll();
?>
<div class="topbar"><h1>Story ideas</h1><p>New packages from the research agent, newest first.</p></div>

<?php if (!$rows): ?>
<section class="panel"><p>No new ideas waiting. Ideas arrive here from <code>/api/ingest.php</code>.</p></section>
<?php endif; ?>

<?php foreach ($rows as $r): ?>
<?php
$images = json_decode($r['suggested_images'] ?: '[]', true) ?: [];
$entityLabel = $r['matched_entity_name']
    ? h($r['matched_entity_name']) . ' <span class="pill">' . h($r['matched_entity_type']) . '</span>'
    : (($proposed = json_decode($r['proposed_new_entity'] ?: 'null', true))
        ? 'New: ' . h($proposed['name'] ?? '?') . ' <span class="pill">' . h($proposed['type'] ?? '?') . '</span>'
        : '<span class="pill">unmatched</span>');
?>
<section class="panel">
  <div class="topbar">
    <div>
      <p style="color:#685f54;margin:0 0 4px"><?= h($r['spark_source']) ?> · <?= h($r['created_at']) ?></p>
      <p style="margin:0 0 10px"><?= nl2br(h($r['spark_snippet'])) ?></p>
      <p style="margin:0">Entity: <?= $entityLabel ?></p>
    </div>
    <a class="btn" href="idea.php?id=<?= (int)$r['id'] ?>">Review &rsaquo;</a>
  </div>
  <?php if ($images): ?>
  <div style="display:flex;gap:8px;margin-top:12px">
    <?php foreach ($images as $img): ?>
    <img src="<?= h($img['thumbnail_url'] ?? $img['url']) ?>" alt="" style="width:80px;height:60px;object-fit:cover;border-radius:4px;border:1px solid var(--line)">
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
<?php endforeach; ?>

<?php require __DIR__ . '/_footer.php'; ?>
