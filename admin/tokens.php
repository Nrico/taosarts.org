<?php
$title = 'API tokens';
$active = 'tokens';
require_once __DIR__ . '/_layout.php';
$pdo = db();

// Web-UI equivalent of scripts/create_api_token.php — added so a token can
// be minted from cPanel hosting without shell access. The plaintext token is
// only ever available once, right after creation.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'create') {
        $label = trim($_POST['label'] ?? '') ?: 'unlabeled';
        $token = bin2hex(random_bytes(32));
        $hash = password_hash($token, PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO api_tokens (token_hash, label) VALUES (?, ?)')->execute([$hash, $label]);
        $_SESSION['flash_new_token'] = $token;
    } elseif (($_POST['action'] ?? '') === 'revoke') {
        $pdo->prepare('DELETE FROM api_tokens WHERE id = ?')->execute([(int)$_POST['id']]);
    }
    redirect('tokens.php');
}

$newToken = $_SESSION['flash_new_token'] ?? null;
unset($_SESSION['flash_new_token']);

$tokens = $pdo->query('SELECT * FROM api_tokens ORDER BY created_at DESC')->fetchAll();
?>
<div class="topbar"><h1>API tokens</h1></div>

<?php if ($newToken): ?>
<div class="notice">
  <strong>Copy this token now — it will not be shown again.</strong>
  <p><code style="user-select:all"><?= h($newToken) ?></code></p>
  <p>Use it as: <code>Authorization: Bearer <?= h($newToken) ?></code> when POSTing to <code>/api/ingest.php</code>.</p>
</div>
<?php endif; ?>

<section class="panel">
  <h2>Issue a new token</h2>
  <form method="post" class="form-row" style="display:flex;gap:10px;align-items:flex-end">
    <input type="hidden" name="action" value="create">
    <div><label>Label</label><input name="label" placeholder="e.g. research-agent"></div>
    <button class="btn">Generate token</button>
  </form>
</section>

<section class="panel">
  <h2>Existing tokens</h2>
  <table class="table">
    <tr><th>Label</th><th>Created</th><th>Last used</th><th></th></tr>
    <?php foreach ($tokens as $t): ?>
    <tr>
      <td><?= h($t['label']) ?></td>
      <td><?= h($t['created_at']) ?></td>
      <td><?= h($t['last_used_at'] ?: 'never') ?></td>
      <td>
        <form method="post" onsubmit="return confirm('Revoke this token? Anything using it will stop working immediately.');" style="display:inline">
          <input type="hidden" name="action" value="revoke">
          <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
          <button class="btn alt">Revoke</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
