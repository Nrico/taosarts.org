<?php
$title = 'Stories';
$active = 'stories';
require_once __DIR__ . '/_layout.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    // FKs handle cleanup: story_entities/story_images rows -> CASCADE.
    // Nothing is left dangling. Images themselves aren't deleted — they're
    // shared library assets that may be used elsewhere.
    $pdo->prepare('DELETE FROM stories WHERE id = ?')->execute([(int)$_POST['id']]);
    redirect('stories.php?deleted=1');
}

$rows = $pdo->query('SELECT * FROM stories ORDER BY created_at DESC')->fetchAll();
?>
<div class="topbar"><h1>Stories</h1><a class="btn" href="editor.php">+ New story</a></div>

<?php if (!empty($_GET['deleted'])): ?><div class="notice">Story deleted.</div><?php endif; ?>

<section class="panel">
  <table class="table">
    <tr><th>Title</th><th>Status</th><th>Published</th><th></th><th></th></tr>
    <?php foreach ($rows as $r): ?>
    <?php
      $confirmMsg = $r['status'] === 'published'
        ? "Delete \"{$r['title']}\"? It's live on the site — deleting it removes it immediately. This can't be undone."
        : "Delete \"{$r['title']}\"? This can't be undone.";
    ?>
    <tr>
      <td><?= h($r['title']) ?></td>
      <td><span class="pill"><?= h($r['status']) ?></span></td>
      <td><?= h($r['published_at'] ?: '—') ?></td>
      <td><a href="editor.php?id=<?= (int)$r['id'] ?>">Edit</a></td>
      <td>
        <form method="post" onsubmit="return confirm(<?= h(json_encode($confirmMsg)) ?>);" style="display:inline">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
          <button class="link-btn">Delete</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  <?php if (!$rows): ?><p style="color:#685f54">No stories yet.</p><?php endif; ?>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
