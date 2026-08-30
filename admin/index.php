<?php $title='Dashboard'; $active='dashboard'; require_once __DIR__ . '/_layout.php';
$pdo=db();
$counts=[
 'new_ideas'=>$pdo->query("SELECT COUNT(*) c FROM story_ideas WHERE status='new'")->fetch()['c'],
 'writing'=>$pdo->query("SELECT COUNT(*) c FROM story_ideas WHERE status='writing'")->fetch()['c'],
 'published'=>$pdo->query("SELECT COUNT(*) c FROM stories WHERE status='published'")->fetch()['c'],
 'drafts'=>$pdo->query("SELECT COUNT(*) c FROM stories WHERE status='draft'")->fetch()['c'],
 'entities'=>$pdo->query('SELECT COUNT(*) c FROM entities')->fetch()['c'],
 'emails'=>$pdo->query('SELECT COUNT(*) c FROM newsletter_signups')->fetch()['c'],
];
$ideas=$pdo->query("SELECT si.*, e.name AS matched_entity_name FROM story_ideas si LEFT JOIN entities e ON e.id=si.matched_entity_id WHERE si.status='new' ORDER BY si.created_at DESC LIMIT 5")->fetchAll();
$stories=$pdo->query('SELECT * FROM stories ORDER BY created_at DESC LIMIT 5')->fetchAll();
$emails=$pdo->query('SELECT * FROM newsletter_signups ORDER BY created_at DESC LIMIT 5')->fetchAll();
?>
<div class="topbar"><div><h1>Welcome back, Admin</h1><p>Review story ideas from the research agent, write, and publish.</p></div><a class="btn" href="ideas.php">Review ideas</a></div>
<div class="stats"><div class="stat"><strong><?=$counts['new_ideas']?></strong><br>New ideas</div><div class="stat"><strong><?=$counts['writing']?></strong><br>In progress</div><div class="stat"><strong><?=$counts['published']?></strong><br>Published stories</div><div class="stat"><strong><?=$counts['drafts']?></strong><br>Drafts</div><div class="stat"><strong><?=$counts['entities']?></strong><br>Entities</div><div class="stat"><strong><?=$counts['emails']?></strong><br>Newsletter sign-ups</div></div>
<div class="grid cards">
  <section class="panel">
    <h2>Waiting for review</h2>
    <?php foreach($ideas as $i): $proposed = json_decode($i['proposed_new_entity'] ?: 'null', true); ?><p><b><?=h($i['spark_source'])?></b><br><?=h(mb_strimwidth($i['spark_snippet'] ?? '', 0, 80, '…'))?><br><span style="color:#685f54">→ <?= $i['matched_entity_name'] ? h($i['matched_entity_name']) : ($proposed ? 'New: ' . h($proposed['name'] ?? '?') : 'unmatched') ?></span></p><?php endforeach; ?>
    <?php if (!$ideas): ?><p style="color:#685f54">Nothing new right now.</p><?php endif; ?>
    <a href="ideas.php">Review all</a>
  </section>
  <section class="panel">
    <h2>Recent stories</h2>
    <?php foreach($stories as $s): ?><p><b><?=h($s['title'])?></b><br><span class="pill"><?=h($s['status'])?></span></p><?php endforeach; ?>
    <a href="stories.php">All stories</a>
  </section>
  <section class="panel">
    <h2>Recent sign-ups</h2>
    <table class="table"><?php foreach($emails as $e): ?><tr><td><?=h($e['email'])?></td><td><?=h($e['created_at'])?></td></tr><?php endforeach; ?></table>
    <p><a class="btn alt" href="emails_export.php">Export CSV</a></p>
  </section>
</div>
<?php require __DIR__ . '/_footer.php'; ?>
