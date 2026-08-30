<?php
$title = 'Edit entity';
$active = 'entities';
require_once __DIR__ . '/_layout.php';
$pdo = db();
$id = (int)($_GET['id'] ?? 0);

$e = [
    'type' => 'person', 'era_status' => 'historical', 'name' => '', 'slug' => '', 'one_line_id' => '',
    'date_start' => '', 'date_end' => '',
    'coordinates' => '', 'place_category' => '', 'cadence' => '', 'is_recurring' => 0,
    'medium' => '', 'gallery_affiliation' => '', 'website_url' => '', 'instagram_url' => '',
    'portrait_image_id' => null, 'bio_markdown' => '',
    'aliases' => '[]', 'keywords' => '[]',
];
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM entities WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) $e = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete' && $id) {
    // FKs handle cleanup: story_entities row -> CASCADE; stories.primary_
    // entity_id and story_ideas.matched_entity_id -> SET NULL. A story that
    // had this as its primary entity just loses that tag.
    $pdo->prepare('DELETE FROM entities WHERE id = ?')->execute([$id]);
    redirect('entities.php?deleted=1');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $portraitImageId = $_POST['portrait_image_id'] ?: null;

    // Optionally register a brand-new image inline (URL or upload) rather
    // than picking an existing one — goes through the same create_image()
    // every other image-creation path uses, so the rules (credit_text
    // required, etc.) can't drift between them.
    if (!empty($_POST['new_image_credit_text']) && (!empty($_POST['new_image_url']) || !empty($_FILES['new_image_file']['tmp_name']))) {
        $newId = create_image($pdo, [
            'url' => $_POST['new_image_url'] ?? '',
            'thumbnail_url' => $_POST['new_image_thumbnail_url'] ?? '',
            'source' => $_POST['new_image_source'] ?? '',
            'license' => $_POST['new_image_license'] ?? '',
            'credit_text' => $_POST['new_image_credit_text'] ?? '',
            'original_url' => $_POST['new_image_original_url'] ?? '',
        ], $_FILES['new_image_file'] ?? null);
        if ($newId) $portraitImageId = $newId;
    }

    $slug = $_POST['slug'] ?: slugify($_POST['name']);

    // Same shape as admin/idea.php's approve_entity — comma-separated input,
    // JSON column in the DB. These are what api/entities.php exports for
    // the research agent's matching, so they matter beyond just this form.
    $toJson = fn($csv) => json_encode(array_values(array_filter(array_map('trim', explode(',', $csv ?? '')))));

    $values = [
        $_POST['type'],
        $_POST['era_status'] ?: 'historical',
        $_POST['name'],
        $slug,
        $_POST['one_line_id'] ?: null,
        $toJson($_POST['aliases'] ?? ''),
        $toJson($_POST['keywords'] ?? ''),
        $_POST['date_start'] ?: null,
        $_POST['date_end'] ?: null,
        $_POST['coordinates'] ?: null,
        $_POST['place_category'] ?: null,
        $_POST['cadence'] ?: null,
        !empty($_POST['is_recurring']) ? 1 : 0,
        $_POST['medium'] ?: null,
        $_POST['gallery_affiliation'] ?: null,
        $_POST['website_url'] ?: null,
        $_POST['instagram_url'] ?: null,
        $portraitImageId ?: null,
        $_POST['bio_markdown'] ?: null,
    ];

    if ($id) {
        $stmt = $pdo->prepare('UPDATE entities SET type=?,era_status=?,name=?,slug=?,one_line_id=?,aliases=?,keywords=?,date_start=?,date_end=?,coordinates=?,place_category=?,cadence=?,is_recurring=?,medium=?,gallery_affiliation=?,website_url=?,instagram_url=?,portrait_image_id=?,bio_markdown=? WHERE id=?');
        $values[] = $id;
    } else {
        $stmt = $pdo->prepare('INSERT INTO entities (type,era_status,name,slug,one_line_id,aliases,keywords,date_start,date_end,coordinates,place_category,cadence,is_recurring,medium,gallery_affiliation,website_url,instagram_url,portrait_image_id,bio_markdown) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    }
    $stmt->execute($values);
    redirect('entities.php');
}

$images = $pdo->query('SELECT id, credit_text FROM images ORDER BY created_at DESC')->fetchAll();
?>
<h1><?= $id ? 'Edit' : 'Create' ?> entity</h1>
<form method="post" class="panel" enctype="multipart/form-data">
  <div class="split">
    <div>
      <div class="form-row"><label>Type</label>
        <select name="type" id="entity-type">
          <?php foreach (['person','place','tradition'] as $t): ?>
          <option value="<?= $t ?>" <?= $e['type'] === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row"><label>Era</label>
        <select name="era_status">
          <?php foreach (['historical' => 'Historical', 'contemporary' => 'Contemporary', 'both' => 'Both'] as $val => $label): ?>
          <option value="<?= $val ?>" <?= $e['era_status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row"><label>Name</label><input name="name" required value="<?= h($e['name']) ?>"></div>
      <div class="form-row"><label>Slug</label><input name="slug" value="<?= h($e['slug']) ?>" placeholder="auto from name if blank"></div>
      <div class="form-row"><label>One-line ID</label><input name="one_line_id" value="<?= h($e['one_line_id']) ?>" placeholder="Short description for cards and profile headers"></div>
      <div class="form-row"><label>Aliases (comma-separated)</label><input name="aliases" value="<?= h(implode(', ', json_decode($e['aliases'] ?: '[]', true) ?: [])) ?>" placeholder="Other names this is known by"></div>
      <div class="form-row"><label>Keywords (comma-separated)</label><input name="keywords" value="<?= h(implode(', ', json_decode($e['keywords'] ?: '[]', true) ?: [])) ?>" placeholder="Terms that signal a real connection — used for matching, not shown on the site"></div>
    </div>
    <div>
      <div class="form-row type-field type-person" style="display:none"><label>Born</label><input name="date_start" value="<?= h($e['date_start']) ?>" placeholder="e.g. 1902"></div>
      <div class="form-row type-field type-person" style="display:none"><label>Died (blank if unknown/living)</label><input name="date_end" value="<?= h($e['date_end']) ?>" placeholder="e.g. 1953"></div>
      <div class="form-row type-field type-place" style="display:none"><label>Coordinates</label><input name="coordinates" value="<?= h($e['coordinates']) ?>" placeholder="lat,lng"></div>
      <div class="form-row type-field type-place" style="display:none"><label>Place category</label>
        <select name="place_category">
          <option value="">— not set —</option>
          <option value="historic_site" <?= $e['place_category'] === 'historic_site' ? 'selected' : '' ?>>Historic site / museum</option>
          <option value="gallery_studio" <?= $e['place_category'] === 'gallery_studio' ? 'selected' : '' ?>>Gallery / studio</option>
        </select>
      </div>
      <div class="form-row type-field type-tradition" style="display:none"><label>Cadence</label><input name="cadence" value="<?= h($e['cadence']) ?>" placeholder="e.g. Seasonal, tied to plant harvests"></div>
      <div class="form-row type-field type-tradition" style="display:none">
        <label><input type="checkbox" name="is_recurring" value="1" <?= !empty($e['is_recurring']) ? 'checked' : '' ?>> Recurring — held annually/regularly</label>
      </div>
      <div class="form-row"><label>Portrait image</label>
        <select name="portrait_image_id">
          <option value="">— none —</option>
          <?php foreach ($images as $img): ?>
          <option value="<?= (int)$img['id'] ?>" <?= (int)$e['portrait_image_id'] === (int)$img['id'] ? 'selected' : '' ?>><?= h(mb_strimwidth($img['credit_text'], 0, 60, '…')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <details>
    <summary>Or add a new image for the portrait</summary>
    <div class="split">
      <div>
        <div class="form-row"><label>Upload a file</label><input type="file" name="new_image_file" accept="image/*"></div>
        <div class="form-row"><label>…or an image URL</label><input name="new_image_url" placeholder="https://…"></div>
        <div class="form-row"><label>Source</label><input name="new_image_source" placeholder="e.g. Millicent Rogers Museum Archive, or your own name"></div>
      </div>
      <div>
        <div class="form-row"><label>License</label><input name="new_image_license" placeholder="e.g. Public Domain, CC BY-NC, or leave blank for your own work"></div>
        <div class="form-row"><label>Credit text (required)</label><input name="new_image_credit_text" placeholder="Full credit line shown beneath the image"></div>
        <div class="form-row"><label>Original URL</label><input name="new_image_original_url" placeholder="Link to the source page, if any"></div>
      </div>
    </div>
  </details>

  <div class="type-field type-person" style="display:none">
    <h2 style="margin-top:22px">Living artist details (optional)</h2>
    <p style="color:#685f54;font-size:13px;margin-top:-8px">Leave blank for a historical figure. The one-line ID above doubles as the short line on their practice.</p>
    <div class="split">
      <div>
        <div class="form-row"><label>Medium</label><input name="medium" value="<?= h($e['medium']) ?>" placeholder="e.g. Painter, Weaver, Santero"></div>
        <div class="form-row"><label>Gallery affiliation</label><input name="gallery_affiliation" value="<?= h($e['gallery_affiliation']) ?>" placeholder="e.g. Wilder Nightingale Fine Art"></div>
      </div>
      <div>
        <div class="form-row"><label>Website</label><input name="website_url" value="<?= h($e['website_url']) ?>" placeholder="https://…"></div>
        <div class="form-row"><label>Instagram</label><input name="instagram_url" value="<?= h($e['instagram_url']) ?>" placeholder="https://instagram.com/…"></div>
      </div>
    </div>
  </div>

  <div class="form-row"><label>Bio (Markdown)</label><textarea name="bio_markdown" style="min-height:220px"><?= h($e['bio_markdown']) ?></textarea></div>
  <button class="btn">Save entity</button>
</form>

<?php if ($id): ?>
<?php
$linkedStories = $pdo->prepare(
    "SELECT s.id, s.title, s.status, s.published_at
     FROM story_entities se JOIN stories s ON s.id = se.story_id
     WHERE se.entity_id = ? ORDER BY COALESCE(s.published_at, s.created_at) DESC"
);
$linkedStories->execute([$id]);
$linkedStories = $linkedStories->fetchAll();
?>
<section class="panel">
  <h2>Appears in</h2>
  <?php if (!$linkedStories): ?>
  <p style="color:#685f54">Not linked to any story yet — tag it from the entity list in <a href="editor.php">the story editor</a>.</p>
  <?php else: ?>
  <table class="table">
    <?php foreach ($linkedStories as $s): ?>
    <tr>
      <td><?= h($s['title']) ?></td>
      <td><span class="pill"><?= h($s['status']) ?></span></td>
      <td><a href="editor.php?id=<?= (int)$s['id'] ?>">Edit</a></td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php endif; ?>
</section>

<?php
$primaryCount = $pdo->prepare('SELECT COUNT(*) c FROM stories WHERE primary_entity_id = ?');
$primaryCount->execute([$id]);
$primaryCount = (int)$primaryCount->fetch()['c'];
$usedText = count($linkedStories) . ' stor' . (count($linkedStories) == 1 ? 'y' : 'ies');
$inUse = count($linkedStories) > 0 || $primaryCount > 0;
$confirmMsg = $inUse
    ? "Delete \"{$e['name']}\"? It's linked to $usedText" . ($primaryCount ? " ($primaryCount as the primary entity)" : "") . " — deleting it unlinks it from those stories without deleting them. This can't be undone."
    : "Delete \"{$e['name']}\"? This can't be undone.";
?>
<section class="panel" style="margin-top:24px;border-color:#9d2d20">
  <h2>Delete this entity</h2>
  <p style="color:#685f54;font-size:13px">Stories linked to it aren't deleted — they're just unlinked (and lose it as their primary entity, if it was).</p>
  <form method="post" onsubmit="return confirm(<?= h(json_encode($confirmMsg)) ?>);">
    <input type="hidden" name="action" value="delete">
    <button class="btn alt">Delete entity</button>
  </form>
</section>
<?php endif; ?>

<?php require __DIR__ . '/_footer.php'; ?>

<script>
// Only show the fields that make sense for the selected type — a place
// doesn't need a birth year, a person doesn't need coordinates.
const typeSelect = document.getElementById('entity-type');
function syncTypeFields() {
  document.querySelectorAll('.type-field').forEach(function (row) { row.style.display = 'none'; });
  document.querySelectorAll('.type-' + typeSelect.value).forEach(function (row) { row.style.display = ''; });
}
typeSelect.addEventListener('change', syncTypeFields);
syncTypeFields();
</script>
