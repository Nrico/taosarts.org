<?php $title='Dashboard'; $active='dashboard'; require_once __DIR__ . '/_layout.php';
$pdo=db();
$counts=[
 'emails'=>$pdo->query('SELECT COUNT(*) c FROM newsletter_signups')->fetch()['c'],
 'questionnaires'=>$pdo->query('SELECT COUNT(*) c FROM questionnaires')->fetch()['c'],
 'stories'=>$pdo->query('SELECT COUNT(*) c FROM stories')->fetch()['c'],
 'galleries'=>$pdo->query('SELECT COUNT(*) c FROM galleries')->fetch()['c'],
];
$emails=$pdo->query('SELECT * FROM newsletter_signups ORDER BY created_at DESC LIMIT 5')->fetchAll();
$qs=$pdo->query('SELECT q.*, (SELECT COUNT(*) FROM questionnaire_responses r WHERE r.questionnaire_id=q.id) responses FROM questionnaires q ORDER BY updated_at DESC LIMIT 5')->fetchAll();
$stories=$pdo->query('SELECT * FROM stories ORDER BY created_at DESC LIMIT 4')->fetchAll();
$galleries=$pdo->query('SELECT g.*, (SELECT COUNT(*) FROM gallery_images gi WHERE gi.gallery_id=g.id) images FROM galleries g ORDER BY created_at DESC LIMIT 4')->fetchAll();
?>
<div class="topbar"><div><h1>Welcome back, Admin</h1><p>Manage surveys, newsletter sign-ups, stories and galleries.</p></div><a class="btn" href="story_edit.php">+ New story</a></div>
<div class="stats"><div class="stat"><strong><?=$counts['emails']?></strong><br>Newsletter sign-ups</div><div class="stat"><strong><?=$counts['questionnaires']?></strong><br>Questionnaires</div><div class="stat"><strong><?=$counts['stories']?></strong><br>Stories</div><div class="stat"><strong><?=$counts['galleries']?></strong><br>Galleries</div></div>
<div class="grid cards"><section class="panel"><h2>Recent sign-ups</h2><table class="table"><?php foreach($emails as $e): ?><tr><td><?=h($e['email'])?></td><td><?=h($e['created_at'])?></td></tr><?php endforeach; ?></table><p><a class="btn alt" href="emails_export.php">Export CSV</a></p></section><section class="panel"><h2>Questionnaires</h2><?php foreach($qs as $q): ?><p><b><?=h($q['title'])?></b> <span class="pill"><?=h($q['status'])?></span><br><?=h($q['responses'])?> responses</p><?php endforeach; ?><a href="questionnaires.php">Manage all</a></section><section class="panel"><h2>Stories</h2><?php foreach($stories as $s): ?><p><b><?=h($s['title'])?></b><br><?=h($s['status'])?></p><?php endforeach; ?><a href="stories.php">All stories</a></section></div>
<section class="panel"><h2>Galleries</h2><div class="gallery-grid"><?php foreach($galleries as $g): ?><div class="card"><h3><?=h($g['title'])?></h3><p><?=h($g['images'])?> images · <?=h($g['status'])?></p></div><?php endforeach; ?><a class="card" href="gallery_edit.php"><h3>+ Add new gallery</h3></a></div></section>
<?php require __DIR__ . '/_footer.php'; ?>
