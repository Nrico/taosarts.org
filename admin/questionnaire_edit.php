<?php $title='Edit questionnaire'; $active='questionnaires'; require_once __DIR__ . '/_layout.php';
$pdo=db(); $id=(int)($_GET['id']??0); $item=['title'=>'','slug'=>'','intro'=>'','status'=>'draft'];
if($id){$st=$pdo->prepare('SELECT * FROM questionnaires WHERE id=?');$st->execute([$id]);$item=$st->fetch() ?: $item;}
$questions=$pdo->query('SELECT * FROM questions WHERE is_active=1 ORDER BY prompt')->fetchAll();
$selected=[]; if($id){$st=$pdo->prepare('SELECT * FROM questionnaire_questions WHERE questionnaire_id=?');$st->execute([$id]);foreach($st->fetchAll() as $x){$selected[$x['question_id']]=$x;}}
if($_SERVER['REQUEST_METHOD']==='POST'){
  $slug=$_POST['slug'] ?: slugify($_POST['title']);
  if($id){$st=$pdo->prepare('UPDATE questionnaires SET title=?,slug=?,intro=?,status=? WHERE id=?');$st->execute([$_POST['title'],$slug,$_POST['intro'],$_POST['status'],$id]);}
  else{$st=$pdo->prepare('INSERT INTO questionnaires(title,slug,intro,status) VALUES(?,?,?,?)');$st->execute([$_POST['title'],$slug,$_POST['intro'],$_POST['status']]);$id=(int)$pdo->lastInsertId();}
  $pdo->prepare('DELETE FROM questionnaire_questions WHERE questionnaire_id=?')->execute([$id]);
  foreach($_POST['question_ids'] ?? [] as $i=>$qid){$pdo->prepare('INSERT INTO questionnaire_questions(questionnaire_id,question_id,sort_order,is_required) VALUES(?,?,?,?)')->execute([$id,$qid,(int)($_POST['sort'][$qid]??$i),isset($_POST['required'][$qid])?1:0]);}
  redirect('questionnaires.php');
}
?>
<h1><?= $id?'Edit':'Create' ?> questionnaire</h1><form method="post" class="panel"><div class="split"><div><div class="form-row"><label>Title</label><input name="title" required value="<?=h($item['title'])?>"></div><div class="form-row"><label>Slug</label><input name="slug" value="<?=h($item['slug'])?>"></div><div class="form-row"><label>Intro</label><textarea name="intro"><?=h($item['intro'])?></textarea></div><div class="form-row"><label>Status</label><select name="status"><?php foreach(['draft','active','closed'] as $s): ?><option <?=$item['status']===$s?'selected':''?>><?=$s?></option><?php endforeach; ?></select></div></div><div><h2>Questions in this questionnaire</h2><?php foreach($questions as $q): $is=isset($selected[$q['id']]); ?><div class="card" style="margin-bottom:10px"><label><input type="checkbox" name="question_ids[]" value="<?=$q['id']?>" <?=$is?'checked':''?>> <?=h($q['prompt'])?></label><div class="actions"><input style="max-width:90px" type="number" name="sort[<?=$q['id']?>]" value="<?=h($selected[$q['id']]['sort_order'] ?? 0)?>" placeholder="Order"><label><input type="checkbox" name="required[<?=$q['id']?>]" <?=$is && $selected[$q['id']]['is_required']?'checked':''?>> Required</label></div></div><?php endforeach; ?></div></div><button class="btn">Save questionnaire</button></form>
<?php require __DIR__ . '/_footer.php'; ?>
