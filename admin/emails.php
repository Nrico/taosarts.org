<?php $title='Email sign-ups'; $active='emails'; require_once __DIR__ . '/_layout.php';
$emails=db()->query('SELECT * FROM newsletter_signups ORDER BY created_at DESC')->fetchAll(); ?>
<div class="topbar"><h1>Email sign-ups</h1><a class="btn" href="emails_export.php">Export CSV</a></div>
<section class="panel"><table class="table"><tr><th>Email</th><th>Source</th><th>Signed up</th></tr><?php foreach($emails as $e): ?><tr><td><?=h($e['email'])?></td><td><?=h($e['source'])?></td><td><?=h($e['created_at'])?></td></tr><?php endforeach; ?></table></section>
<?php require __DIR__ . '/_footer.php'; ?>
