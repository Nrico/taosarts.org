<?php
$title = 'Entities';
$active = 'entities';
require_once __DIR__ . '/_layout.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    // FKs handle cleanup: story_entities row -> CASCADE; stories.primary_
    // entity_id and story_ideas.matched_entity_id -> SET NULL. Nothing is
    // left dangling, but a story that had this as its primary entity loses
    // that tag (the story itself isn't touched otherwise).
    $pdo->prepare('DELETE FROM entities WHERE id = ?')->execute([(int)$_POST['id']]);
    redirect('entities.php?deleted=1');
}

$rows = $pdo->query(
    'SELECT e.*, (SELECT COUNT(*) FROM story_entities se WHERE se.entity_id = e.id) story_count,
       (SELECT COUNT(*) FROM stories s WHERE s.primary_entity_id = e.id) primary_count
     FROM entities e ORDER BY e.type, e.name'
)->fetchAll();
?>
<div class="topbar"><h1>Entities</h1><a class="btn" href="entity_edit.php">+ New entity</a></div>

<?php if (!empty($_GET['deleted'])): ?><div class="notice">Entity deleted.</div><?php endif; ?>

<section class="panel">
  <table class="table">
    <tr><th>Name</th><th>Type</th><th>One-line ID</th><th>Appears in</th><th></th><th></th></tr>
    <?php foreach ($rows as $r): ?>
    <?php
      $usedText = (int)$r['story_count'] . ' stor' . ($r['story_count'] == 1 ? 'y' : 'ies');
      $inUse = $r['story_count'] > 0 || $r['primary_count'] > 0;
      $confirmMsg = $inUse
        ? "Delete \"{$r['name']}\"? It's linked to $usedText" . ($r['primary_count'] ? " ({$r['primary_count']} as the primary entity)" : "") . " — deleting it unlinks it from those stories without deleting them. This can't be undone."
        : "Delete \"{$r['name']}\"? This can't be undone.";
    ?>
    <tr>
      <td><?= h($r['name']) ?></td>
      <td><span class="pill"><?= h($r['type']) ?></span></td>
      <td><?= h($r['one_line_id']) ?></td>
      <td><?= h($usedText) ?></td>
      <td><a href="entity_edit.php?id=<?= (int)$r['id'] ?>">Edit</a></td>
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
  <?php if (!$rows): ?><p style="color:#685f54">No entities yet.</p><?php endif; ?>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
