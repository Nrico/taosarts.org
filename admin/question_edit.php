<?php $title='Edit question'; $active='questions'; require_once __DIR__ . '/_layout.php';
$pdo=db(); $id=(int)($_GET['id']??0); $q=['prompt'=>'','help_text'=>'','question_type'=>'textarea','options_json'=>'','is_active'=>1];
if($id){$st=$pdo->prepare('SELECT * FROM questions WHERE id=?');$st->execute([$id]);$q=$st->fetch() ?: $q;}
$options=json_decode($q['options_json'] ?? '[]', true) ?: [];
if($_SERVER['REQUEST_METHOD']==='POST'){
  $opts=array_values(array_filter(array_map('trim', $_POST['options'] ?? [])));
  $json=$opts ? json_encode($opts) : null;
  if($id){$st=$pdo->prepare('UPDATE questions SET prompt=?, help_text=?, question_type=?, options_json=?, is_active=? WHERE id=?');$st->execute([$_POST['prompt'],$_POST['help_text'],$_POST['question_type'],$json,isset($_POST['is_active'])?1:0,$id]);}
  else{$st=$pdo->prepare('INSERT INTO questions(prompt,help_text,question_type,options_json,is_active) VALUES(?,?,?,?,?)');$st->execute([$_POST['prompt'],$_POST['help_text'],$_POST['question_type'],$json,isset($_POST['is_active'])?1:0]);}
  redirect('questions.php');
}
?>
<h1><?= $id?'Edit':'Add' ?> question</h1><form method="post" class="panel"><div class="form-row"><label>Prompt</label><textarea name="prompt" required><?=h($q['prompt'])?></textarea></div><div class="form-row"><label>Help text</label><input name="help_text" value="<?=h($q['help_text'])?>"></div><div class="form-row"><label>Type</label><select name="question_type"><?php foreach(['text','textarea','email','select','checkbox','radio','number'] as $type): ?><option <?=$q['question_type']===$type?'selected':''?>><?=$type?></option><?php endforeach; ?></select></div><div class="form-row" id="optionsBox"><label>Options for select, checkbox or radio</label><?php foreach($options ?: [''] as $op): ?><input name="options[]" value="<?=h($op)?>" placeholder="Option label" style="margin-top:8px"><?php endforeach; ?></div><button type="button" class="btn alt" onclick="addOptionField()">+ Add option</button><p><label><input type="checkbox" name="is_active" <?=$q['is_active']?'checked':''?>> Active</label></p><button class="btn">Save question</button></form>
<?php require __DIR__ . '/_footer.php'; ?>
