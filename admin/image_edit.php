<?php
$title = 'Edit image';
$active = 'images';
require_once __DIR__ . '/_layout.php';
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('images.php');

$stmt = $pdo->prepare('SELECT * FROM images WHERE id = ?');
$stmt->execute([$id]);
$img = $stmt->fetch();
if (!$img) redirect('images.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $focalX = max(0, min(100, (int)($_POST['focal_x'] ?? 50)));
    $focalY = max(0, min(100, (int)($_POST['focal_y'] ?? 50)));
    $pdo->prepare('UPDATE images SET thumbnail_url=?, source=?, license=?, credit_text=?, original_url=?, focal_x=?, focal_y=? WHERE id=?')
        ->execute([
            $_POST['thumbnail_url'] ?: null,
            $_POST['source'],
            $_POST['license'],
            $_POST['credit_text'],
            $_POST['original_url'] ?: null,
            $focalX, $focalY, $id,
        ]);
    redirect('image_edit.php?id=' . $id . '&saved=1');
}
?>
<div class="topbar">
  <h1>Edit image</h1>
  <?php if (!empty($_GET['saved'])): ?><span class="pill">Saved</span><?php endif; ?>
</div>

<div class="split" style="align-items:flex-start">
  <form method="post" class="panel" style="flex:1">
    <div class="form-row"><label>URL (fixed — set at ingest, not editable here)</label><input value="<?= h($img['url']) ?>" disabled></div>
    <div class="form-row"><label>Thumbnail URL</label><input name="thumbnail_url" value="<?= h($img['thumbnail_url']) ?>"></div>
    <div class="form-row"><label>Source</label><input name="source" required value="<?= h($img['source']) ?>"></div>
    <div class="form-row"><label>License</label><input name="license" required value="<?= h($img['license']) ?>"></div>
    <div class="form-row"><label>Credit text (shown beneath the image everywhere it's used)</label><input name="credit_text" required value="<?= h($img['credit_text']) ?>"></div>
    <div class="form-row"><label>Original URL</label><input name="original_url" value="<?= h($img['original_url']) ?>"></div>
    <input type="hidden" name="focal_x" id="focal_x" value="<?= (int)$img['focal_x'] ?>">
    <input type="hidden" name="focal_y" id="focal_y" value="<?= (int)$img['focal_y'] ?>">
    <button class="btn">Save image</button>
  </form>

  <div class="panel" style="flex:1">
    <h2>Focal point</h2>
    <p style="color:#685f54;font-size:13px">Click where the subject is. Used as the crop center wherever this image gets cropped into a card or hero.</p>
    <div id="focal-picker" style="position:relative;cursor:crosshair;max-width:100%;">
      <img id="focal-img" src="<?= h($img['url']) ?>" alt="" style="width:100%;display:block;border-radius:6px">
      <div id="focal-marker" style="position:absolute;width:18px;height:18px;border-radius:50%;background:#b4572f;border:2px solid white;box-shadow:0 0 0 1px rgba(0,0,0,.3);transform:translate(-50%,-50%);pointer-events:none;left:<?= (int)$img['focal_x'] ?>%;top:<?= (int)$img['focal_y'] ?>%"></div>
    </div>

    <h2 style="margin-top:22px">Card crop preview</h2>
    <div id="crop-preview" style="height:160px;border-radius:8px;border:1px solid var(--line);background-image:url('<?= h($img['url']) ?>');background-size:cover;background-position:<?= (int)$img['focal_x'] ?>% <?= (int)$img['focal_y'] ?>%"></div>
  </div>
</div>

<?php require __DIR__ . '/_footer.php'; ?>

<script>
const picker = document.getElementById('focal-picker');
const marker = document.getElementById('focal-marker');
const preview = document.getElementById('crop-preview');
const focalX = document.getElementById('focal_x');
const focalY = document.getElementById('focal_y');

picker.addEventListener('click', function (evt) {
  const rect = picker.getBoundingClientRect();
  const x = Math.round(((evt.clientX - rect.left) / rect.width) * 100);
  const y = Math.round(((evt.clientY - rect.top) / rect.height) * 100);
  const clampedX = Math.max(0, Math.min(100, x));
  const clampedY = Math.max(0, Math.min(100, y));
  focalX.value = clampedX;
  focalY.value = clampedY;
  marker.style.left = clampedX + '%';
  marker.style.top = clampedY + '%';
  preview.style.backgroundPosition = clampedX + '% ' + clampedY + '%';
});
</script>
