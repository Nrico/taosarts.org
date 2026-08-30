<?php
$title = 'Images';
$active = 'images';
require_once __DIR__ . '/_layout.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'delete') {
        // FKs handle cleanup: entities.portrait_image_id -> SET NULL,
        // story_images row -> CASCADE. Nothing is left dangling.
        $pdo->prepare('DELETE FROM images WHERE id = ?')->execute([(int)$_POST['id']]);
        redirect('images.php?deleted=1');
    }
    $newId = create_image($pdo, $_POST, $_FILES['image_file'] ?? null);
    if ($newId) {
        redirect('image_edit.php?id=' . $newId . '&saved=1');
    }
    $error = 'Need either an uploaded file or a URL, plus credit text.';
}

$rows = $pdo->query(
    "SELECT i.*,
       (SELECT COUNT(*) FROM story_images si WHERE si.image_id = i.id) story_count,
       (SELECT COUNT(*) FROM entities e WHERE e.portrait_image_id = i.id) portrait_count
     FROM images i ORDER BY i.created_at DESC"
)->fetchAll();
?>
<div class="topbar"><h1>Images</h1><p>Credit, license, and crop focal point for every image in the library.</p></div>

<?php if (!empty($_GET['deleted'])): ?><div class="notice">Image deleted.</div><?php endif; ?>

<section class="panel">
  <h2>Add an image</h2>
  <?php if (!empty($error)): ?><div class="notice danger"><?= h($error) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data" class="split">
    <div>
      <div class="form-row"><label>Upload a file</label><input type="file" name="image_file" accept="image/*"></div>
      <div class="form-row"><label>…or an image URL</label><input name="url" placeholder="https://…"></div>
      <div class="form-row"><label>Source</label><input name="source" placeholder="e.g. Wikimedia Commons, or your own name"></div>
    </div>
    <div>
      <div class="form-row"><label>License</label><input name="license" placeholder="e.g. Public Domain, CC BY-NC, or leave blank for your own work"></div>
      <div class="form-row"><label>Credit text (required)</label><input name="credit_text" required placeholder="Full credit line shown beneath the image everywhere it's used"></div>
      <div class="form-row"><label>Original URL</label><input name="original_url" placeholder="Link to the source page, if any"></div>
    </div>
    <div style="grid-column:1/-1"><button class="btn">Add image</button></div>
  </form>
</section>

<section class="panel">
  <table class="table">
    <tr><th></th><th>Credit</th><th>License</th><th>Used</th><th></th><th></th></tr>
    <?php foreach ($rows as $img): ?>
    <?php
      $usedText = (int)$img['story_count'] . ' stor' . ($img['story_count'] == 1 ? 'y' : 'ies');
      if ($img['portrait_count']) $usedText .= ', ' . (int)$img['portrait_count'] . ' portrait' . ($img['portrait_count'] == 1 ? '' : 's');
      $inUse = $img['story_count'] > 0 || $img['portrait_count'] > 0;
      $confirmMsg = $inUse
        ? "Delete this image? It's used in $usedText — deleting it removes it from every story and clears any entity portrait using it. This can't be undone."
        : "Delete this image? This can't be undone.";
    ?>
    <tr>
      <td><img src="<?= h($img['thumbnail_url'] ?: $img['url']) ?>" alt="Failed to load — check the URL" style="width:70px;height:50px;object-fit:cover;object-position:<?= h(image_object_position($img)) ?>;border-radius:4px;border:1px solid var(--line)" onerror="this.classList.add('broken');this.closest('tr').querySelector('.broken-note').style.display='inline'"></td>
      <td style="max-width:360px"><?= h($img['credit_text']) ?><br><small class="broken-note" style="display:none;color:#9d2d20">⚠ Image failed to load</small></td>
      <td><span class="pill"><?= h($img['license']) ?></span></td>
      <td><?= h($usedText) ?></td>
      <td><a href="image_edit.php?id=<?= (int)$img['id'] ?>">Edit</a></td>
      <td>
        <form method="post" onsubmit="return confirm(<?= h(json_encode($confirmMsg)) ?>);" style="display:inline">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int)$img['id'] ?>">
          <button class="link-btn">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php if (!$rows): ?><p style="color:#685f54">No images yet.</p><?php endif; ?>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
