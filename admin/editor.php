<?php
$title = 'Story editor';
$active = 'stories';
require_once __DIR__ . '/_layout.php';
$pdo = db();

$storyId = (int)($_GET['id'] ?? $_POST['story_id'] ?? 0);
$ideaIdParam = (int)($_GET['idea_id'] ?? $_POST['idea_id'] ?? 0) ?: null;

function unique_slug(PDO $pdo, string $slug, ?int $excludeId): string {
    $base = $slug;
    $n = 2;
    $check = $pdo->prepare('SELECT id FROM stories WHERE slug = ? AND id != ?');
    while (true) {
        $check->execute([$slug, $excludeId ?: 0]);
        if (!$check->fetch()) return $slug;
        $slug = $base . '-' . $n++;
    }
}

function materialize_suggested_images(PDO $pdo, array $suggested): array {
    // Turns the idea's raw suggested_images JSON into real `images` rows
    // (find-by-url so re-visiting the editor doesn't duplicate them), and
    // returns [url => id] so callers can look ids up.
    $urlToId = [];
    $find = $pdo->prepare('SELECT id FROM images WHERE url = ? LIMIT 1');
    $insert = $pdo->prepare('INSERT INTO images (url, thumbnail_url, source, license, credit_text, original_url) VALUES (?,?,?,?,?,?)');
    foreach ($suggested as $img) {
        if (empty($img['url'])) continue;
        $find->execute([$img['url']]);
        $row = $find->fetch();
        if ($row) {
            $urlToId[$img['url']] = $row['id'];
        } else {
            $insert->execute([$img['url'], $img['thumbnail_url'] ?? null, $img['source'], $img['license'], $img['credit_text'], $img['original_url'] ?? null]);
            $urlToId[$img['url']] = $pdo->lastInsertId();
        }
    }
    return $urlToId;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete' && $storyId) {
    // FKs handle cleanup: story_entities/story_images rows -> CASCADE.
    // Images themselves aren't deleted — they're shared library assets.
    $pdo->prepare('DELETE FROM stories WHERE id = ?')->execute([$storyId]);
    redirect('stories.php?deleted=1');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postTitle = trim($_POST['title'] ?? '');
    $dek = trim($_POST['dek'] ?? '');
    $body = $_POST['body_markdown'] ?? '';
    $slug = unique_slug($pdo, trim($_POST['slug'] ?? '') ?: slugify($postTitle), $storyId ?: null);
    $action = $_POST['action'] ?? 'draft';
    $status = $action === 'publish' ? 'published' : 'draft';
    $entityIds = array_map('intval', $_POST['entity_ids'] ?? []);
    $primaryEntityId = (int)($_POST['primary_entity_id'] ?? 0) ?: null;

    if ($storyId) {
        $existing = $pdo->prepare('SELECT * FROM stories WHERE id = ?');
        $existing->execute([$storyId]);
        $existing = $existing->fetch();
        $publishedAt = $existing['published_at'] ?? null;
        if ($status === 'published' && !$publishedAt) $publishedAt = date('Y-m-d H:i:s');
        $pdo->prepare('UPDATE stories SET title=?, dek=?, slug=?, body_markdown=?, primary_entity_id=?, status=?, published_at=? WHERE id=?')
            ->execute([$postTitle, $dek, $slug, $body, $primaryEntityId, $status, $publishedAt, $storyId]);
    } else {
        $sparkUrl = null; $sparkSource = null;
        if ($ideaIdParam) {
            $ideaRow = $pdo->prepare('SELECT spark_url, spark_source FROM story_ideas WHERE id = ?');
            $ideaRow->execute([$ideaIdParam]);
            $ideaRow = $ideaRow->fetch();
            if ($ideaRow) { $sparkUrl = $ideaRow['spark_url']; $sparkSource = $ideaRow['spark_source']; }
        }
        $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;
        $pdo->prepare('INSERT INTO stories (story_idea_id, title, dek, slug, body_markdown, spark_url, spark_source, primary_entity_id, status, published_at) VALUES (?,?,?,?,?,?,?,?,?,?)')
            ->execute([$ideaIdParam, $postTitle, $dek, $slug, $body, $sparkUrl, $sparkSource, $primaryEntityId, $status, $publishedAt]);
        $storyId = $pdo->lastInsertId();
        if ($ideaIdParam) {
            $pdo->prepare("UPDATE story_ideas SET status=? WHERE id=?")->execute([$status === 'published' ? 'published' : 'writing', $ideaIdParam]);
        }
    }

    $pdo->prepare('DELETE FROM story_entities WHERE story_id = ?')->execute([$storyId]);
    if ($entityIds) {
        $insEnt = $pdo->prepare('INSERT IGNORE INTO story_entities (story_id, entity_id) VALUES (?,?)');
        foreach (array_unique($entityIds) as $eid) $insEnt->execute([$storyId, $eid]);
    }

    // Link every image whose URL actually appears in the body — this is the
    // enforcement point for "no image without credit_text": the only way an
    // image's URL gets into body_markdown at all is via the sidebar inserter,
    // which always writes the credit line right after it (see insertImage()
    // below), and every row in `images` has a NOT NULL credit_text.
    $pdo->prepare('DELETE FROM story_images WHERE story_id = ?')->execute([$storyId]);
    $allImages = $pdo->query('SELECT id, url FROM images')->fetchAll();
    $insImg = $pdo->prepare('INSERT IGNORE INTO story_images (story_id, image_id, sort_order) VALUES (?,?,?)');
    $order = 0;
    foreach ($allImages as $img) {
        if (strpos($body, $img['url']) !== false) {
            $insImg->execute([$storyId, $img['id'], $order++]);
        }
    }

    redirect('editor.php?id=' . $storyId . '&saved=1');
}

// ---- GET: load state for the form ----------------------------------

$story = ['title' => '', 'dek' => '', 'slug' => '', 'body_markdown' => '', 'status' => 'draft', 'story_idea_id' => $ideaIdParam, 'primary_entity_id' => null];
$prefilledEntityIds = [];
$idea = null;

if ($storyId) {
    $stmt = $pdo->prepare('SELECT * FROM stories WHERE id = ?');
    $stmt->execute([$storyId]);
    $found = $stmt->fetch();
    if ($found) {
        $story = $found;
        $ents = $pdo->prepare('SELECT entity_id FROM story_entities WHERE story_id = ?');
        $ents->execute([$storyId]);
        $prefilledEntityIds = array_column($ents->fetchAll(), 'entity_id');
        if ($story['story_idea_id']) $ideaIdParam = $story['story_idea_id'];
    }
}

if ($ideaIdParam) {
    $ideaStmt = $pdo->prepare('SELECT * FROM story_ideas WHERE id = ?');
    $ideaStmt->execute([$ideaIdParam]);
    $idea = $ideaStmt->fetch();
    if ($idea) {
        if (!$storyId && $idea['matched_entity_id']) {
            $prefilledEntityIds = [$idea['matched_entity_id']];
            $story['primary_entity_id'] = $idea['matched_entity_id'];
        }
        // A research agent can submit a full draft alongside an idea — if
        // one's there and we're not already editing a saved story, open
        // the editor with it instead of a blank textarea.
        if (!$storyId && !empty($idea['draft_markdown'])) {
            $story['body_markdown'] = $idea['draft_markdown'];
        }
        $suggested = json_decode($idea['suggested_images'] ?: '[]', true) ?: [];
        materialize_suggested_images($pdo, $suggested);
    }
}

$suggestedImageRows = [];
if ($idea) {
    $suggested = json_decode($idea['suggested_images'] ?: '[]', true) ?: [];
    $findImg = $pdo->prepare('SELECT * FROM images WHERE url = ? LIMIT 1');
    foreach ($suggested as $img) {
        $findImg->execute([$img['url']]);
        if ($row = $findImg->fetch()) $suggestedImageRows[] = $row;
    }
}
$suggestedIds = array_column($suggestedImageRows, 'id');
$otherImages = $pdo->query('SELECT * FROM images ORDER BY created_at DESC LIMIT 60')->fetchAll();
$otherImages = array_filter($otherImages, fn($img) => !in_array($img['id'], $suggestedIds));

$suggestedLinkRows = $idea ? (json_decode($idea['suggested_links'] ?: '[]', true) ?: []) : [];

$entities = $pdo->query('SELECT id, name, type, slug FROM entities ORDER BY name')->fetchAll();
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">

<div class="topbar">
  <h1><?= $storyId ? 'Edit story' : 'New story' ?></h1>
  <?php if (!empty($_GET['saved'])): ?><span class="pill">Saved</span><?php endif; ?>
</div>

<?php if ($idea): ?>
<p style="color:#685f54">From idea: <?= h($idea['spark_source']) ?> — <a href="idea.php?id=<?= (int)$idea['id'] ?>">view original spark &rsaquo;</a></p>
<?php endif; ?>

<form method="post" style="display:flex;gap:24px;align-items:flex-start">
  <input type="hidden" name="story_id" value="<?= (int)$storyId ?>">
  <input type="hidden" name="idea_id" value="<?= (int)($ideaIdParam ?? 0) ?>">

  <div style="flex:2">
    <div class="panel">
      <div class="form-row"><label>Title</label><input name="title" required value="<?= h($story['title']) ?>"></div>
      <div class="form-row"><label>Dek</label><input name="dek" value="<?= h($story['dek']) ?>"></div>
      <div class="form-row"><label>Slug</label><input name="slug" value="<?= h($story['slug']) ?>" placeholder="auto from title if blank"></div>
      <div class="form-row">
        <label>Body (Markdown)</label>
        <textarea id="body_markdown" name="body_markdown"><?= h($story['body_markdown']) ?></textarea>
      </div>
    </div>

    <div class="panel">
      <h2>Entities</h2>
      <input type="text" id="entity-filter" placeholder="Filter entities…" style="margin-bottom:10px;width:100%">
      <div id="entity-list" style="max-height:220px;overflow:auto">
        <?php foreach ($entities as $e): ?>
        <label class="entity-row" data-id="<?= (int)$e['id'] ?>" data-name="<?= h(strtolower($e['name'])) ?>" data-label="<?= h($e['name']) ?>" style="display:flex;align-items:center;gap:8px;padding:6px 0">
          <input type="checkbox" name="entity_ids[]" value="<?= (int)$e['id'] ?>" <?= in_array($e['id'], $prefilledEntityIds) ? 'checked' : '' ?>>
          <span><?= h($e['name']) ?></span> <span class="pill"><?= h($e['type']) ?></span>
        </label>
        <?php endforeach; ?>
      </div>
      <div class="form-row" style="margin-top:14px">
        <label>Primary topic (sets the card's color tag)</label>
        <select id="primary-entity-select" name="primary_entity_id"></select>
      </div>
    </div>

    <div class="panel" style="display:flex;gap:10px">
      <button class="btn" name="action" value="draft">Save as draft</button>
      <button class="btn" name="action" value="publish">Publish</button>
    </div>
  </div>

  <aside style="flex:1">
    <div class="panel">
      <h2>Images</h2>
      <p style="color:#685f54;font-size:13px">Click one to insert it — the credit line is added automatically.</p>
      <input type="text" id="image-filter" placeholder="Filter images…" style="margin-bottom:10px;width:100%">
      <div id="image-list" style="max-height:280px;overflow:auto">
        <?php foreach ($suggestedImageRows as $img): ?>
        <div class="image-pick" data-url="<?= h($img['url']) ?>" data-credit="<?= h($img['credit_text']) ?>" style="cursor:pointer;margin-bottom:12px">
          <img src="<?= h($img['thumbnail_url'] ?: $img['url']) ?>" alt="" style="width:100%;border-radius:6px;border:1px solid var(--line)">
          <p class="mono" style="font-size:10.5px;color:#685f54;margin:4px 0 0"><span class="pill" style="margin-right:4px">Suggested</span><?= h($img['credit_text']) ?></p>
        </div>
        <?php endforeach; ?>
        <?php foreach ($otherImages as $img): ?>
        <div class="image-pick" data-url="<?= h($img['url']) ?>" data-credit="<?= h($img['credit_text']) ?>" style="cursor:pointer;margin-bottom:12px">
          <img src="<?= h($img['thumbnail_url'] ?: $img['url']) ?>" alt="" style="width:100%;border-radius:6px;border:1px solid var(--line)">
          <p class="mono" style="font-size:10.5px;color:#685f54;margin:4px 0 0"><?= h($img['credit_text']) ?></p>
        </div>
        <?php endforeach; ?>
      </div>

      <details style="margin-top:14px">
        <summary>+ Add a new image</summary>
        <div class="form-row"><label>Upload a file</label><input type="file" id="new-image-file" accept="image/*"></div>
        <div class="form-row"><label>…or an image URL</label><input id="new-image-url" placeholder="https://…"></div>
        <div class="form-row"><label>Source</label><input id="new-image-source" placeholder="e.g. Wikimedia Commons"></div>
        <div class="form-row"><label>License</label><input id="new-image-license" placeholder="e.g. Public Domain"></div>
        <div class="form-row"><label>Credit text (required)</label><input id="new-image-credit" placeholder="Full credit line"></div>
        <div class="form-row"><label>Original URL</label><input id="new-image-original" placeholder="Link to source, if any"></div>
        <button type="button" class="btn" id="add-image-btn">Add &amp; insert</button>
        <p id="add-image-error" style="color:#a33;font-size:12px;margin-top:6px"></p>
      </details>
    </div>

    <div class="panel">
      <h2>Link to a topic</h2>
      <p style="color:#685f54;font-size:13px">Click a name to insert a link to its page.</p>
      <?php if ($suggestedLinkRows): ?>
      <p class="mono" style="font-size:10px;color:#685f54;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Suggested references</p>
      <?php foreach ($suggestedLinkRows as $link): if (empty($link['url'])) continue; ?>
      <div class="link-pick" data-label="<?= h($link['title'] ?? $link['url']) ?>" data-url="<?= h($link['url']) ?>" style="cursor:pointer;padding:5px 0;color:#b4572f;text-decoration:underline"><?= h($link['title'] ?? $link['url']) ?></div>
      <?php endforeach; ?>
      <div style="border-top:1px solid var(--line);margin:12px 0"></div>
      <?php endif; ?>
      <input type="text" id="topic-filter" placeholder="Filter topics…" style="margin-bottom:10px;width:100%">
      <div id="topic-list" style="max-height:220px;overflow:auto">
        <?php foreach ($entities as $e): ?>
        <div class="link-pick" data-name="<?= h(strtolower($e['name'])) ?>" data-label="<?= h($e['name']) ?>" data-url="entity.php?slug=<?= h($e['slug']) ?>" style="cursor:pointer;padding:5px 0">
          <?= h($e['name']) ?> <span class="pill"><?= h($e['type']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </aside>
</form>

<?php if ($storyId): ?>
<?php
$confirmMsg = $story['status'] === 'published'
    ? "Delete \"{$story['title']}\"? It's live on the site — deleting it removes it immediately. This can't be undone."
    : "Delete \"{$story['title']}\"? This can't be undone.";
?>
<section class="panel" style="margin-top:24px;border-color:#9d2d20">
  <h2>Delete this story</h2>
  <p style="color:#685f54;font-size:13px">Images used in it aren't deleted — they stay in the library.</p>
  <form method="post" onsubmit="return confirm(<?= h(json_encode($confirmMsg)) ?>);">
    <input type="hidden" name="action" value="delete">
    <button class="btn alt">Delete story</button>
  </form>
</section>
<?php endif; ?>

<?php require __DIR__ . '/_footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
<script>
const easymde = new EasyMDE({ element: document.getElementById('body_markdown'), spellChecker: false, minHeight: '360px' });

function insertAtCursor(text) {
  easymde.codemirror.replaceSelection(text);
  easymde.codemirror.focus();
}

function insertImage(url, credit) {
  const alt = credit.split(/[.,]/)[0];
  insertAtCursor('\n\n![' + alt + '](' + url + ')\n\n*' + credit + '*\n\n');
}

function insertLink(label, url) {
  insertAtCursor('[' + label + '](' + url + ')');
}

// Delegated so images added by the "+ Add a new image" widget below (no
// page reload) are clickable immediately, same as the ones rendered by PHP.
// Every insertable image already carries its own credit line (data-credit
// comes straight from images.credit_text, a NOT NULL column) — there is no
// path here that inserts an image without one.
document.getElementById('image-list').addEventListener('click', function (evt) {
  const el = evt.target.closest('.image-pick');
  if (!el) return;
  insertImage(el.dataset.url, el.dataset.credit);
});

document.querySelectorAll('.link-pick').forEach(function (el) {
  el.addEventListener('click', function () {
    insertLink(el.dataset.label, el.dataset.url);
  });
});

document.getElementById('image-filter').addEventListener('input', function () {
  const q = this.value.trim().toLowerCase();
  document.querySelectorAll('#image-list .image-pick').forEach(function (row) {
    row.style.display = row.dataset.credit.toLowerCase().includes(q) ? '' : 'none';
  });
});

document.getElementById('topic-filter').addEventListener('input', function () {
  const q = this.value.trim().toLowerCase();
  document.querySelectorAll('#topic-list .link-pick').forEach(function (row) {
    row.style.display = row.dataset.name.includes(q) ? '' : 'none';
  });
});

// Add an image without leaving the page or losing the draft in progress —
// a full-page form submit here would wipe out unsaved title/body/entities.
document.getElementById('add-image-btn').addEventListener('click', async function () {
  const errorEl = document.getElementById('add-image-error');
  errorEl.textContent = '';
  const fd = new FormData();
  const fileInput = document.getElementById('new-image-file');
  if (fileInput.files[0]) fd.append('image_file', fileInput.files[0]);
  fd.append('url', document.getElementById('new-image-url').value);
  fd.append('source', document.getElementById('new-image-source').value);
  fd.append('license', document.getElementById('new-image-license').value);
  fd.append('credit_text', document.getElementById('new-image-credit').value);
  fd.append('original_url', document.getElementById('new-image-original').value);

  let data;
  try {
    const res = await fetch('image_create_ajax.php', { method: 'POST', body: fd });
    data = await res.json();
    if (!res.ok) { errorEl.textContent = data.error || 'Could not add image.'; return; }
  } catch (e) {
    errorEl.textContent = 'Could not reach the server.';
    return;
  }

  const wrap = document.createElement('div');
  wrap.className = 'image-pick';
  wrap.style.cursor = 'pointer';
  wrap.style.marginBottom = '12px';
  wrap.dataset.url = data.url;
  wrap.dataset.credit = data.credit_text;
  const img = document.createElement('img');
  img.src = data.thumbnail_url || data.url;
  img.alt = '';
  img.style.cssText = 'width:100%;border-radius:6px;border:1px solid var(--line)';
  const caption = document.createElement('p');
  caption.className = 'mono';
  caption.style.cssText = 'font-size:10.5px;color:#685f54;margin:4px 0 0';
  caption.textContent = data.credit_text;
  wrap.appendChild(img);
  wrap.appendChild(caption);
  document.getElementById('image-list').prepend(wrap);

  insertImage(data.url, data.credit_text);

  fileInput.value = '';
  ['new-image-url', 'new-image-source', 'new-image-license', 'new-image-credit', 'new-image-original'].forEach(function (id) {
    document.getElementById(id).value = '';
  });
});

document.getElementById('entity-filter').addEventListener('input', function () {
  const q = this.value.trim().toLowerCase();
  document.querySelectorAll('#entity-list .entity-row').forEach(function (row) {
    row.style.display = row.dataset.name.includes(q) ? '' : 'none';
  });
});

// The "primary topic" dropdown only ever offers entities that are actually
// checked, so it can't drift from what the story is really tagged with.
const initialPrimaryEntityId = <?= (int)($story['primary_entity_id'] ?? 0) ?>;
const primarySelect = document.getElementById('primary-entity-select');

function refreshPrimaryOptions() {
  const checked = Array.from(document.querySelectorAll('#entity-list input:checked'))
    .map(function (cb) { return cb.closest('.entity-row'); });
  const previous = primarySelect.value;
  primarySelect.innerHTML = '';
  if (!checked.length) {
    primarySelect.innerHTML = '<option value="">— tag an entity first —</option>';
    return;
  }
  checked.forEach(function (row) {
    const opt = document.createElement('option');
    opt.value = row.dataset.id;
    opt.textContent = row.dataset.label;
    primarySelect.appendChild(opt);
  });
  const stillChecked = checked.some(function (row) { return row.dataset.id === previous; });
  primarySelect.value = stillChecked ? previous
    : (checked.some(function (row) { return row.dataset.id === String(initialPrimaryEntityId); }) ? initialPrimaryEntityId : checked[0].dataset.id);
}

document.querySelectorAll('#entity-list input[type=checkbox]').forEach(function (cb) {
  cb.addEventListener('change', refreshPrimaryOptions);
});
refreshPrimaryOptions();
</script>
