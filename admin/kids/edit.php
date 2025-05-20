<?php
// admin/kids/edit.php — редактирование ребёнка + родителей
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') exit('403');
require_once __DIR__.'/../../config.php';

$id = (int)($_GET['id'] ?? 0);
if(!$id) exit('no id');

/* ► список всех родителей для combobox */
$parentList = $pdo->query("SELECT id,full_name FROM parents ORDER BY full_name")
                  ->fetchAll(PDO::FETCH_KEY_PAIR);

/* ► ребёнок */
$kid=$pdo->prepare("SELECT * FROM kids WHERE id=?");
$kid->execute([$id]); $kid=$kid->fetch();  if(!$kid) exit('not found');

/* ► родители ребёнка */
$parents=$pdo->prepare("
    SELECT p.* FROM parents p
     JOIN parent_kid pk ON pk.parent_id=p.id
    WHERE pk.kid_id=?");
$parents->execute([$id]);
$parents=$parents->fetchAll(PDO::FETCH_ASSOC);

$err='';

/* ───────────────────────────  С О Х Р А Н Я Е М  ───────────────────────── */
if($_SERVER['REQUEST_METHOD']==='POST'){
   $kName=trim($_POST['kid_name'] ?? '');
   $kDate=$_POST['kid_birth'] ?? '';
   $kNote=trim($_POST['kid_note'] ?? '');

   if(!$kName || !$kDate) $err='Заполните ФИО и дату рождения ребёнка.';

   /* массивы одинаковой длины */
   $oldIds = $_POST['parent_old_id'] ?? [];   // id привязанных раньше (0 для нового блока)
   $exist   = $_POST['parent_exists'] ?? [];  // id выбранного «существ.» родителя или ''
   $pNames  = $_POST['parent_name']   ?? [];  // ФИО (только для новых/редактируемых)
   $phones  = $_POST['parent_phone']  ?? [];
   $emails  = $_POST['parent_email']  ?? [];
   $addrs   = $_POST['parent_address']?? [];
   $cats    = $_POST['parent_soc']    ?? [];

   /* —- проверяем дубликат ФИО среди НОВЫХ родителей —- */
   $newNames=[];
   foreach($pNames as $i=>$nm){
       if(trim($nm) && empty($exist[$i]) && empty($oldIds[$i])) $newNames[] = trim($nm);
   }
   if($newNames){
       $in=rtrim(str_repeat('?,',count($newNames)),',');
       $st=$pdo->prepare("SELECT full_name FROM parents WHERE full_name IN ($in)");
       $st->execute($newNames);
       if($dups=$st->fetchAll(PDO::FETCH_COLUMN)){
           $err='Родитель(и) «'.implode(', ',$dups).'» уже существуют.';
       }
   }

   /* ─────────  БАЗА  ───────── */
   if(!$err){
     $pdo->beginTransaction();
     try{
        /* 1. ребёнок */
        $pdo->prepare("UPDATE kids SET full_name=?,birth_date=?,medical_note=? WHERE id=?")
            ->execute([$kName,$kDate,$kNote,$id]);

        /* 2. родители */
        $newParIds=[];
        $total=count($oldIds);

        for($i=0;$i<$total;$i++){
            $oldId  = (int)$oldIds[$i];
            $exId   = (int)$exist[$i];             // выбран «уже есть»
            $name   = trim($pNames[$i]);
            $phone  = trim($phones[$i]);
            $email  = trim($emails[$i]);
            $addr   = trim($addrs[$i]);
            $cat    = trim($cats[$i]);

            /* A) выбран существующий родитель — только создаём связь */
            if($exId){
                $newParIds[]=$exId;
                continue;
            }

            /* B) редактируем старого, если блок был привязан и не переключён */
            if($oldId){
                if(!$name){
                    $name=$pdo->query("SELECT full_name FROM parents WHERE id=$oldId")
                              ->fetchColumn();
                }
                $pdo->prepare("UPDATE parents
                                  SET full_name=?,phone=?,email=?,address=?,social_category=?
                                WHERE id=?")
                    ->execute([$name,$phone,$email,$addr,$cat,$oldId]);
                $newParIds[]=$oldId;
                continue;
            }

            /* C) абсолютно новый родитель */
            if($name){
                $pdo->prepare("INSERT INTO parents(full_name,phone,email,address,social_category)
                               VALUES(?,?,?,?,?)")
                    ->execute([$name,$phone,$email,$addr,$cat]);
                $newParIds[]=$pdo->lastInsertId();
            }
        }

        if(!$newParIds) throw new Exception('Не указан ни один родитель.');

        /* 3. пересоздаём связи */
        $pdo->prepare("DELETE FROM parent_kid WHERE kid_id=?")->execute([$id]);
        $lnk=$pdo->prepare("INSERT INTO parent_kid(parent_id,kid_id) VALUES(?,?)");
        foreach($newParIds as $pid) $lnk->execute([$pid,$id]);

        $pdo->commit();
        header('Location: ../index.php?section=kids'); exit;
     }catch(Exception $e){
        $pdo->rollBack();
        $err='Ошибка: '.$e->getMessage();
     }
   }
}
?>
<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8">
<title>Редактировать ребёнка</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.kid-block,.parent-block{border:1px solid #dee2e6;border-radius:.5rem;padding:1rem;margin-bottom:1rem;position:relative;}
.btn-close{background-size:.75em;}
.parent-existing select{max-width:350px;}
</style></head>
<body class="p-4">

<h3 class="mb-4">Редактирование данных (ID <?=$id?>)</h3>
<a href="../index.php?section=kids" class="btn btn-link mb-3">← Назад к списку</a>

<?php if($err):?><div class="alert alert-danger"><?=$err?></div><?php endif;?>

<form method="post" id="mainForm">
  <!-- ребёнок -->
  <h5>Данные ребёнка</h5>
  <div class="kid-block">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">ФИО *</label>
        <input name="kid_name" class="form-control" required
               value="<?=htmlspecialchars($kid['full_name'])?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Дата рождения *</label>
        <input type="date" name="kid_birth" class="form-control" required
               value="<?=$kid['birth_date']?>">
      </div>
      <div class="col-12">
        <label class="form-label">Мед. примечание</label>
        <textarea name="kid_note" rows="2" class="form-control"><?=htmlspecialchars($kid['medical_note'])?></textarea>
      </div>
    </div>
  </div>

  <!-- родители -->
  <h5>Родители / законные представители</h5>
  <div id="parentList">
    <?php foreach($parents as $p): ?>
      <div class="parent-block">
        <?php include __DIR__.'/parent_fields_select_prefill.php'; ?>
      </div>
    <?php endforeach;?>
  </div>

  <button type="button" id="addParentBtn" class="btn btn-outline-secondary mb-4">+ Добавить родителя</button><br>
  <button class="btn btn-warning">Сохранить изменения</button>
</form>

<!-- шаблон пустого блока родителя -->
<template id="parentTemplate">
  <div class="parent-block"><?php include __DIR__.'/parent_fields_select.php'; ?></div>
</template>

<script>
addParentBtn.onclick=()=>parentList.appendChild(parentTemplate.content.cloneNode(true));

/* переключатель «родитель уже есть» */
document.addEventListener('change',e=>{
  if(!e.target.classList.contains('chk-existing')) return;
  const block=e.target.closest('.parent-block');
  const exist=block.querySelector('.parent-existing');
  const sel  =exist.querySelector('select');
  const on   =e.target.checked;
  exist.classList.toggle('d-none',!on);
  block.querySelector('.parent-new').classList.toggle('d-none',on);
  sel.disabled=!on;
});

/* удалить блок родителя */
document.addEventListener('click',e=>{
  if(e.target.classList.contains('remove-parent')){
      e.target.closest('.parent-block').remove();
  }
});
</script>
</body></html>
