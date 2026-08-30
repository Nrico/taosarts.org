<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/markdown.php';
$pdo = db();

$stmt = $pdo->prepare("SELECT * FROM stories WHERE slug=? AND status='published'");
$stmt->execute([$_GET['slug'] ?? '']);
$s = $stmt->fetch();
if (!$s) { http_response_code(404); $title = 'Story not found'; require __DIR__ . '/_layout.php'; echo '<div class="narrow" style="padding:60px 56px;">Story not found.</div>'; require __DIR__ . '/_footer.php'; exit; }

$title = $s['title'];
$active = 'stories';
$ogType = 'article';
$ogDescription = $s['dek'] ?: '';
$ogUrl = BASE_URL . '/story.php?slug=' . urlencode($s['slug']);
$sharedImage = story_primary_image($pdo, $s['id']);
if ($sharedImage) $ogImage = $sharedImage['url'];
require __DIR__ . '/_layout.php';

$entities = story_related_entities($pdo, $s['id']);
$primary = story_primary_entity($pdo, $s, $entities);
$tagsByType = [];
foreach ($entities as $e) $tagsByType[$e['type']][] = $e;
?>

<div class="story-header">
  <?php if ($primary): ?><div class="story-kicker"><?= h(entity_type_label($primary['type'])) ?> &middot; <?= h($primary['name']) ?></div><?php endif; ?>
  <h1 class="story-title"><?= h($s['title']) ?></h1>
  <?php if ($s['dek']): ?><p class="story-dek"><?= h($s['dek']) ?></p><?php endif; ?>
  <p class="story-meta">
    <?= h(format_published_date($s['published_at'])) ?>
    <?php if ($s['spark_source']): ?> &middot; Source: <a href="<?= h($s['spark_url']) ?>" rel="noopener"><?= h($s['spark_source']) ?></a><?php endif; ?>
  </p>
</div>

<div class="story-body">
  <?= render_markdown($s['body_markdown']) ?>
</div>

<?php if ($tagsByType): ?>
<div class="related-wrap">
  <div class="related-label">Related</div>
  <?php foreach ($tagsByType as $type => $ents): ?>
    <?php foreach ($ents as $e): ?>
    <a class="type-pill <?= h($type) ?>" href="entity.php?slug=<?= h($e['slug']) ?>" style="margin:0 8px 8px 0;display:inline-block"><?= h($e['name']) ?></a>
    <?php endforeach; ?>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/_footer.php'; ?>
