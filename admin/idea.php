<?php
$title = 'Review idea';
$active = 'ideas';
require_once __DIR__ . '/_layout.php';
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect('ideas.php');

$stmt = $pdo->prepare(
    'SELECT si.*, e.name AS matched_entity_name, e.type AS matched_entity_type, e.one_line_id AS matched_entity_one_line_id
     FROM story_ideas si LEFT JOIN entities e ON e.id = si.matched_entity_id
     WHERE si.id = ?'
);
$stmt->execute([$id]);
$idea = $stmt->fetch();
if (!$idea) redirect('ideas.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'cull') {
        $pdo->prepare("UPDATE story_ideas SET status='culled' WHERE id=?")->execute([$id]);
        redirect('ideas.php');
    } elseif ($action === 'reject') {
        $pdo->prepare("UPDATE story_ideas SET status='rejected' WHERE id=?")->execute([$id]);
        redirect('ideas.php');
    } elseif ($action === 'start_writing') {
        $pdo->prepare("UPDATE story_ideas SET status='writing' WHERE id=?")->execute([$id]);
        redirect('editor.php?idea_id=' . $id);
    } elseif ($action === 'approve_entity') {
        // Explicit, human-reviewed creation — this is the only place a
        // proposed_new_entity is ever turned into a real entities row.
        $slug = $_POST['slug'] ?: slugify($_POST['name']);
        $toJson = fn($csv) => json_encode(array_values(array_filter(array_map('trim', explode(',', $csv)))));
        $ins = $pdo->prepare('INSERT INTO entities (type,name,slug,one_line_id,aliases,keywords) VALUES (?,?,?,?,?,?)');
        $ins->execute([$_POST['type'], $_POST['name'], $slug, $_POST['one_line_id'] ?: null, $toJson($_POST['aliases'] ?? ''), $toJson($_POST['keywords'] ?? '')]);
        $newEntityId = $pdo->lastInsertId();
        $pdo->prepare('UPDATE story_ideas SET matched_entity_id=?, proposed_new_entity=NULL WHERE id=?')->execute([$newEntityId, $id]);
        redirect('idea.php?id=' . $id);
    }
}

$images = json_decode($idea['suggested_images'] ?: '[]', true) ?: [];
$links = json_decode($idea['suggested_links'] ?: '[]', true) ?: [];
$proposed = json_decode($idea['proposed_new_entity'] ?: 'null', true);
?>
<div class="topbar">
  <h1><?= h($idea['spark_source']) ?> spark</h1>
  <span class="pill"><?= h($idea['status']) ?></span>
</div>

<section class="panel">
  <h2>Spark</h2>
  <p><a href="<?= h($idea['spark_url']) ?>" target="_blank" rel="noopener"><?= h($idea['spark_url']) ?></a></p>
  <p><?= nl2br(h($idea['spark_snippet'])) ?></p>
  <p style="color:#685f54">Detected via <?= h($idea['detected_via'] ?: 'unknown') ?> · <?= h($idea['created_at']) ?> · batch <?= h($idea['batch_id']) ?></p>
</section>

<section class="panel">
  <h2>Connection note</h2>
  <p><em>For review only — not story copy.</em></p>
  <p><?= nl2br(h($idea['connection_note'])) ?></p>
</section>

<section class="panel">
  <h2>Entity</h2>
  <?php if ($idea['matched_entity_name']): ?>
    <p><b><?= h($idea['matched_entity_name']) ?></b> <span class="pill"><?= h($idea['matched_entity_type']) ?></span></p>
    <p><?= h($idea['matched_entity_one_line_id']) ?></p>
  <?php elseif ($proposed): ?>
    <p>Agent proposed a new entity — nothing is created until you approve it below.</p>
    <form method="post" class="split">
      <input type="hidden" name="action" value="approve_entity">
      <div>
        <div class="form-row"><label>Type</label>
          <select name="type">
            <?php foreach (['person','place','tradition'] as $t): ?>
            <option value="<?= $t ?>" <?= ($proposed['type'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-row"><label>Name</label><input name="name" required value="<?= h($proposed['name'] ?? '') ?>"></div>
        <div class="form-row"><label>Slug</label><input name="slug" placeholder="auto from name if blank"></div>
      </div>
      <div>
        <div class="form-row"><label>One-line ID</label><input name="one_line_id" value="<?= h($proposed['one_line_id'] ?? '') ?>"></div>
        <div class="form-row"><label>Aliases (comma-separated)</label><input name="aliases" placeholder="Other names this is known by"></div>
        <div class="form-row"><label>Keywords (comma-separated)</label><input name="keywords" value="<?= h(implode(', ', $proposed['keywords'] ?? [])) ?>"></div>
        <button class="btn">Approve &amp; create entity</button>
      </div>
    </form>
  <?php else: ?>
    <p style="color:#685f54">No entity matched or proposed.</p>
  <?php endif; ?>
</section>

<?php if ($images): ?>
<section class="panel">
  <h2>Suggested images</h2>
  <div style="display:flex;gap:16px;flex-wrap:wrap">
    <?php foreach ($images as $img): ?>
    <div style="width:200px">
      <img src="<?= h($img['thumbnail_url'] ?? $img['url']) ?>" alt="" style="width:100%;height:130px;object-fit:cover;border-radius:6px;border:1px solid var(--line)">
      <p class="mono" style="font-size:11px;color:#685f54;margin:6px 0 0"><?= h($img['credit_text']) ?></p>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php if ($links): ?>
<section class="panel">
  <h2>Reference links</h2>
  <ul>
    <?php foreach ($links as $link): ?>
    <li><a href="<?= h($link['url']) ?>" target="_blank" rel="noopener"><?= h($link['title'] ?? $link['url']) ?></a> <?php if (!empty($link['type'])): ?><span class="pill"><?= h($link['type']) ?></span><?php endif; ?></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<?php if ($idea['status'] === 'new'): ?>
<section class="panel" style="display:flex;gap:10px">
  <form method="post"><input type="hidden" name="action" value="start_writing"><button class="btn">Start writing &rsaquo;</button></form>
  <form method="post"><input type="hidden" name="action" value="cull"><button class="btn alt">Cull (keep for later)</button></form>
  <form method="post" onsubmit="return confirm('Reject this idea?')"><input type="hidden" name="action" value="reject"><button class="btn alt">Reject</button></form>
</section>
<?php elseif ($idea['status'] === 'culled'): ?>
<section class="panel"><form method="post"><input type="hidden" name="action" value="start_writing"><button class="btn">Start writing &rsaquo;</button></form></section>
<?php endif; ?>

<?php require __DIR__ . '/_footer.php'; ?>
