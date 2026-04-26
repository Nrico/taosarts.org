<?php $title='Questionnaires'; $active='questionnaires'; require_once __DIR__ . '/_layout.php';
$rows=db()->query('SELECT q.*, (SELECT COUNT(*) FROM questionnaire_responses r WHERE r.questionnaire_id=q.id) responses FROM questionnaires q ORDER BY updated_at DESC')->fetchAll(); ?>
<div class="topbar"><h1>Questionnaires</h1><a class="btn" href="questionnaire_edit.php">+ New questionnaire</a></div>
<section class="panel"><table class="table"><tr><th>Title</th><th>Status</th><th>Responses</th><th>Public link</th><th></th></tr><?php foreach($rows as $r): ?><tr><td><?=h($r['title'])?></td><td><span class="pill"><?=h($r['status'])?></span></td><td><?=h($r['responses'])?></td><td><a href="../questionnaire.php?slug=<?=h($r['slug'])?>">Open</a></td><td><a href="questionnaire_edit.php?id=<?=$r['id']?>">Edit</a></td></tr><?php endforeach; ?></table></section>
<?php require __DIR__ . '/_footer.php'; ?>
