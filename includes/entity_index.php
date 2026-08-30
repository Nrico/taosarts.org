<?php
// Shared body for people.php / places.php / traditions.php — set $entityType,
// $pageTitle, $pageLead before requiring this file.
$pdo = db();

// Optional sub-filter, meaning depends on $entityType:
//   person:    ?era=contemporary|historical
//   place:     ?category=gallery_studio|historic_site
//   tradition: ?recurring=1|0
$where = 'e.type = ?';
$params = [$entityType];
$filterTabs = [];
$filterParamName = null;
$activeFilter = '';

if ($entityType === 'person') {
    $filterParamName = 'era';
    $filterTabs = ['' => 'All', 'contemporary' => 'Contemporary', 'historical' => 'Historical'];
    $activeFilter = $_GET['era'] ?? '';
    if (in_array($activeFilter, ['contemporary', 'historical'], true)) {
        // era_status='both' belongs under either filter, not just an exact match.
        $where .= " AND (e.era_status = ? OR e.era_status = 'both')";
        $params[] = $activeFilter;
    } else {
        $activeFilter = '';
    }
} elseif ($entityType === 'place') {
    $filterParamName = 'category';
    $filterTabs = ['' => 'All', 'historic_site' => 'Historic Sites', 'gallery_studio' => 'Galleries & Studios'];
    $activeFilter = $_GET['category'] ?? '';
    if (in_array($activeFilter, ['historic_site', 'gallery_studio'], true)) {
        $where .= ' AND e.place_category = ?';
        $params[] = $activeFilter;
    } else {
        $activeFilter = '';
    }
} elseif ($entityType === 'tradition') {
    $filterParamName = 'recurring';
    $filterTabs = ['' => 'All', '1' => 'Recurring', '0' => 'One-off'];
    $activeFilter = $_GET['recurring'] ?? '';
    if (in_array($activeFilter, ['0', '1'], true)) {
        $where .= ' AND e.is_recurring = ?';
        $params[] = $activeFilter;
    } else {
        $activeFilter = '';
    }
}

$rows = $pdo->prepare(
    "SELECT e.*, (SELECT COUNT(*) FROM story_entities se JOIN stories s ON s.id = se.story_id
       WHERE se.entity_id = e.id AND s.status = 'published') story_count
     FROM entities e WHERE $where ORDER BY e.name"
);
$rows->execute($params);
$rows = $rows->fetchAll();
?>
<div class="page-header">
  <h1 class="page-title"><?= h($pageTitle) ?></h1>
  <p class="page-lead"><?= h($pageLead) ?></p>
</div>
<?php if ($filterParamName): ?>
<div class="container" style="margin-top:10px;">
  <nav class="filter-tabs">
    <?php foreach ($filterTabs as $val => $label): ?>
    <a href="?<?= $val !== '' ? h($filterParamName) . '=' . h($val) : '' ?>" class="filter-tab<?= $activeFilter === $val ? ' active' : '' ?>"><?= h($label) ?></a>
    <?php endforeach; ?>
  </nav>
</div>
<?php endif; ?>
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
        <?php if ($subLabel = entity_sub_label($e)):
            $subClass = $e['type'] === 'place' ? $e['place_category'] : ($e['type'] === 'tradition' ? 'recurring' : $e['era_status']);
        ?><span class="era-pill <?= h($subClass) ?>"><?= h($subLabel) ?></span><?php endif; ?>
        <div class="card-dek"><?= h($e['one_line_id']) ?></div>
        <div class="card-meta"><?= (int)$e['story_count'] ?> <?= $e['story_count'] == 1 ? 'story' : 'stories' ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if (!$rows): ?><p style="color:var(--muted)">Nothing here yet.</p><?php endif; ?>
</div>
