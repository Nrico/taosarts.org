<?php $title='Questions'; $active='questions'; require_once __DIR__ . '/_layout.php';
$questions=db()->query('SELECT * FROM questions ORDER BY updated_at DESC')->fetchAll(); ?>
<div class="topbar"><h1>Question bank</h1><a class="btn" href="question_edit.php">+ Add question</a></div>
<section class="panel"><table class="table"><tr><th>Prompt</th><th>Type</th><th>Active</th><th></th></tr><?php foreach($questions as $q): ?><tr><td><?=h($q['prompt'])?></td><td><?=h($q['question_type'])?></td><td><?= $q['is_active']?'Yes':'No' ?></td><td><a href="question_edit.php?id=<?=$q['id']?>">Edit</a></td></tr><?php endforeach; ?></table></section>
<?php require __DIR__ . '/_footer.php'; ?>
