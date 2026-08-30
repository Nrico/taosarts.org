<?php
$title = 'Taos Arts';
$active = 'stories';
require_once __DIR__ . '/_layout.php';
$pdo = db();

// curate_homepage_feed() (includes/functions.php) guarantees at least one
// contemporary and one historical story make the cut, not just strict
// reverse-chronological — the newest overall still becomes the hero below,
// only the remaining grid slots can be swapped for era representation.
$stories = curate_homepage_feed($pdo, 10);
$hero = array_shift($stories);
?>

<?php if ($hero): $heroImage = story_primary_image($pdo, $hero['id']); $heroPrimary = story_primary_entity($pdo, $hero); ?>
<div class="hero">
  <?php if ($heroImage): ?><img src="<?= h($heroImage['url']) ?>" alt="" style="object-position:<?= h(image_object_position($heroImage)) ?>"><?php endif; ?>
  <div class="hero-caption">
    <?php if ($heroPrimary): ?><div class="caps"><?= h(entity_type_label($heroPrimary['type'])) ?> &middot; <?= h($heroPrimary['name']) ?></div><?php endif; ?>
    <div class="display"><a href="story.php?slug=<?= h($hero['slug']) ?>" style="color:inherit"><?= h($hero['title']) ?></a></div>
    <div class="dek"><?= h($hero['dek']) ?></div>
  </div>
</div>
<?php endif; ?>

<div class="section-eyebrow">Latest Stories</div>
<div class="container" style="margin-top:22px;">
  <div class="card-grid">
    <?php foreach ($stories as $s): echo render_story_card($pdo, $s); ?><?php endforeach; ?>
  </div>
  <?php if (!$hero): ?><p style="color:var(--muted)">No published stories yet.</p><?php endif; ?>
</div>

<div style="text-align:center;background:var(--teal);padding:26px 56px;margin-top:52px;">
  <div class="caps" style="font-size:19px;font-weight:600;color:var(--surface);letter-spacing:.03em;">
    Explore Taos Through Its <a href="people.php" style="color:inherit;text-decoration:underline">People</a>,
    <a href="places.php" style="color:inherit;text-decoration:underline">Places</a> &amp;
    <a href="traditions.php" style="color:inherit;text-decoration:underline">Traditions</a>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>
