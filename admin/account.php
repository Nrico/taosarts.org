<?php
$title = 'My account';
$active = 'account';
require_once __DIR__ . '/_layout.php';
$pdo = db();

// Re-fetch the full row — the session only carries id/name/email/role,
// never the password hash (see includes/auth.php's login_user()).
$me = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$me->execute([current_user()['id']]);
$me = $me->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $me['password_hash'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New password and confirmation do not match.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $me['id']]);
        $success = 'Password changed.';
    }
}
?>
<div class="topbar"><h1>My account</h1></div>

<section class="panel">
  <h2>Change password</h2>
  <p style="color:#685f54"><?= h($me['email']) ?></p>
  <?php if ($error): ?><div class="notice danger"><?= h($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="notice"><?= h($success) ?></div><?php endif; ?>
  <form method="post" style="max-width:360px">
    <div class="form-row"><label>Current password</label><input type="password" name="current_password" required></div>
    <div class="form-row"><label>New password</label><input type="password" name="new_password" required minlength="8"></div>
    <div class="form-row"><label>Confirm new password</label><input type="password" name="confirm_password" required minlength="8"></div>
    <button class="btn">Change password</button>
  </form>
</section>
<?php require __DIR__ . '/_footer.php'; ?>
