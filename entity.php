<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/markdown.php';
$pdo = db();

$stmt = $pdo->prepare('SELECT * FROM entities WHERE slug = ?');
$stmt->execute([$_GET['slug'] ?? '']);
$e = $stmt->fetch();
if (!$e) { http_response_code(404); $title = 'Not found'; require __DIR__ . '/_layout.php'; echo '<div class="narrow" style="padding:60px 56px;">Entity not found.</div>'; require __DIR__ . '/_footer.php'; exit; }

$portrait = null;
if ($e['portrait_image_id']) {
    $img = $pdo->prepare('SELECT * FROM images WHERE id = ?');
    $img->execute([$e['portrait_image_id']]);
    $portrait = $img->fetch();
}

$title = $e['name'];
$active = $e['type'] === 'person' ? 'people' : ($e['type'] === 'place' ? 'places' : 'traditions');
$ogDescription = $e['one_line_id'] ?: '';
$ogUrl = BASE_URL . '/entity.php?slug=' . urlencode($e['slug']);
if ($portrait) $ogImage = $portrait['url'];
require __DIR__ . '/_layout.php';

$stories = $pdo->prepare(
    "SELECT s.* FROM story_entities se JOIN stories s ON s.id = se.story_id
     WHERE se.entity_id = ? AND s.status = 'published' ORDER BY s.published_at DESC"
);
$stories->execute([$e['id']]);
$stories = $stories->fetchAll();
?>

<div class="entity-layout">
  <div class="entity-photo">
    <div class="entity-photo-frame">
      <?php if ($portrait): ?>
      <img src="<?= h($portrait['url']) ?>" alt="<?= h($e['name']) ?>" style="object-position:<?= h(image_object_position($portrait)) ?>">
      <?php endif; ?>
    </div>
    <?php if ($portrait): ?><figcaption><?= h($portrait['credit_text']) ?></figcaption><?php endif; ?>
  </div>
  <div class="entity-main">
    <span class="type-pill <?= h($e['type']) ?>"><?= h(entity_type_label($e['type'])) ?></span>
    <h1 class="entity-name"><?= h($e['name']) ?></h1>
    <?php if ($e['one_line_id']): ?><p class="entity-oneliner"><?= h($e['one_line_id']) ?></p><?php endif; ?>
    <?php if ($e['date_start'] || $e['date_end']): ?>
    <p class="entity-dates"><?= h($e['date_start']) ?><?= $e['date_end'] ? ' – ' . h($e['date_end']) : '' ?></p>
    <?php endif; ?>
    <?php if ($e['cadence']): ?><p class="entity-dates"><?= h($e['cadence']) ?></p><?php endif; ?>
    <?php if ($e['coordinates']): ?>
    <p class="entity-dates"><?= h($e['coordinates']) ?> &middot; <a href="https://www.google.com/maps?q=<?= h($e['coordinates']) ?>" target="_blank" rel="noopener">View on map</a></p>
    <?php endif; ?>
  </div>
</div>

<?php if ($e['bio_markdown']): ?>
<div class="entity-bio"><?= render_markdown($e['bio_markdown']) ?></div>
<?php endif; ?>

<div class="narrow" style="margin-top:44px;">
  <div class="related-label" style="border-top:1px solid var(--line);padding-top:20px;">Appears In &middot; Newest First</div>
  <?php if (!$stories): ?>
  <p style="color:var(--sage);font-size:14px">No published stories reference this yet.</p>
  <?php else: ?>
  <div class="row-list">
    <?php foreach ($stories as $s): ?>
    <div class="row-item">
      <div class="row-date"><?= h(format_published_date($s['published_at'])) ?></div>
      <div>
        <a class="row-title" href="story.php?slug=<?= h($s['slug']) ?>"><?= h($s['title']) ?></a>
        <div class="row-dek"><?= h($s['dek']) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
