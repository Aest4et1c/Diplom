<?php
/* /admin/groups/edit.php */
session_start();
if(!isset($_SESSION['user'])||$_SESSION['user']['role']!=='admin') exit('403');
require_once __DIR__.'/../../config.php';

$id=(int)($_GET['id']??0);
$grp=$pdo->prepare("SELECT * FROM groups WHERE id=?");$grp->execute([$id]);$grp=$grp->fetch();
if(!$grp) exit('404');

/* ───────────── POST‑обработка ───────────── */
if($_SERVER['REQUEST_METHOD']==='POST'){
   $mode=$_POST['mode']??'';

   /* 1. педагоги */
   if($mode==='teachers'){
      $new=array_filter(array_map('intval',$_POST['teacher_ids']??[]));
      $pdo->beginTransaction();
      $pdo->prepare("DELETE FROM group_staff WHERE group_id=?")->execute([$id]);
      $ins=$pdo->prepare("INSERT INTO group_staff(group_id,staff_id) VALUES(?,?)");
      foreach($new as $sid) $ins->execute([$id,$sid]);
      $pdo->commit();
      header("Location: edit.php?id=$id"); exit;
   }

   /* 2. данные группы */
   if($mode==='group'){
      $pdo->prepare("UPDATE groups SET name=?,room_number=?,icon=?,age_from=?,age_to=?,description=? WHERE id=?")
          ->execute([$_POST['g_name_hidden'],$_POST['g_room_hidden'],$_POST['g_icon_hidden'],
                     $_POST['g_from_hidden'],$_POST['g_to_hidden'],$_POST['g_descr_hidden'],$id]);
      header("Location: edit.php?id=$id"); exit;
   }

   /* 3. дети */
   if($mode==='kids'){
      $ids=array_filter(array_map('intval',explode(',',$_POST['kid_ids']??'')));

      $pdo->beginTransaction();
      /* закрытие убранных */
      $in=$ids? rtrim(str_repeat('?,',count($ids)),','):'0';
      $pdo->prepare("UPDATE group_kid_history SET to_date=CURDATE() WHERE group_id=? AND to_date IS NULL AND kid_id NOT IN ($in)")
          ->execute([$id,...$ids]);

      /* добавление новых */
      if($ids){
         $have=$pdo->prepare("SELECT kid_id FROM group_kid_history WHERE group_id=? AND to_date IS NULL AND kid_id IN ($in)");
         $have->execute([$id,...$ids]); $present=$have->fetchAll(PDO::FETCH_COLUMN);
         $new=array_diff($ids,$present);
         $ins=$pdo->prepare("INSERT INTO group_kid_history(kid_id,group_id,from_date) VALUES(?,?,CURDATE())");
         foreach($new as $kid) $ins->execute([$kid,$id]);
      }
      $pdo->commit();
      header("Location: edit.php?id=$id"); exit;
   }
}

/* ───────────── данные для вывода ───────────── */
$teachers=$pdo->prepare("SELECT s.id,s.full_name,s.photo_url FROM staff s JOIN group_staff gs ON gs.staff_id=s.id WHERE gs.group_id=?");
$teachers->execute([$id]);$teachers=$teachers->fetchAll(PDO::FETCH_ASSOC);
$free=$pdo->query("SELECT id,full_name,photo_url FROM staff WHERE id NOT IN(SELECT staff_id FROM group_staff) ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
$teacherOptions=[...$teachers,...$free];

$kids=$pdo->prepare("SELECT k.id,k.full_name,k.birth_date FROM kids k JOIN group_kid_history gkh ON gkh.kid_id=k.id WHERE gkh.group_id=? AND gkh.to_date IS NULL");
$kids->execute([$id]);$kids=$kids->fetchAll(PDO::FETCH_ASSOC);
$freeKids=$pdo->query("SELECT id,full_name FROM kids WHERE id NOT IN(SELECT kid_id FROM group_kid_history WHERE to_date IS NULL) ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html><html lang="ru"><head>
<meta charset="utf-8"><title>Редактировать группу</title>
<link rel="icon" type="image/png" href="/image/web_logo.png">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>.photo{width:64px;height:85px;object-fit:cover;border-radius:.25rem} li.sel{color:#0d6efd;font-weight:500}</style>
</head><body class="p-4">

<a href="../index.php?section=groups" class="d-block mb-2">&lArr;&lArr; Назад в админ панель</a>
<h2 class="mb-1"><?=htmlspecialchars($grp['name'])?></h2>
<p class="text-muted"><?=$grp['age_from']?>‑<?=$grp['age_to']?> лет</p>

<!--************* ПЕДАГОГИ *************-->
<form method="post"><input type="hidden" name="mode" value="teachers">
<div class="card p-3 mb-4">
  <div class="d-flex justify-content-between">
     <h5>Педагоги</h5>
     <button type="button" id="teachEditBtn" class="btn btn-warning btn-sm">Изменить педагогов</button>
  </div>

  <div id="teachView" class="row g-3 mt-2">
     <?php foreach($teachers as $t): ?>
       <div class="col-auto d-flex align-items-center">
         <img src="/<?=htmlspecialchars($t['photo_url']?:'uploads/photos/placeholder.jpg')?>" class="photo me-2">
         <span><?=htmlspecialchars($t['full_name'])?></span>
       </div>
     <?php endforeach;?>
     <?php if(!$teachers): ?><p class="text-muted">Педагоги не назначены.</p><?php endif;?>
  </div>

  <div id="teachEdit" class="row g-3 d-none mt-2">
     <?php for($i=0;$i<2;$i++):$sel=$teachers[$i]['id']??'';?>
       <div class="col-md-4">
         <select name="teacher_ids[]" class="form-select">
           <option value="">— не выбрано —</option>
           <?php foreach($teacherOptions as $o): ?>
             <option value="<?=$o['id']?>" <?=$o['id']==$sel?'selected':''?>><?=htmlspecialchars($o['full_name'])?></option>
           <?php endforeach;?>
         </select>
       </div>
     <?php endfor;?>
     <div class="col-12">
        <button class="btn btn-success btn-sm">Сохранить педагогов</button>
        <button type="button" id="teachCancel" class="btn btn-secondary btn-sm ms-2">Отмена</button>
     </div>
  </div>
</div>
</form>

<!--************* ДАННЫЕ ГРУППЫ *************-->
<form method="post" id="groupForm"><input type="hidden" name="mode" value="group">
<div class="card p-3 mb-4">
  <div class="d-flex justify-content-between">
     <h5>Данные группы</h5>
     <button type="button" id="groupEditBtn" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square"></i> Изменить</button>
  </div>

  <?php
   // helper to echo input with hidden duplicate
   function fld($name,$val,$type='text',$extra=''){
      echo "<input type='$type' name='{$name}_visible' value='".htmlspecialchars($val)."' class='form-control mb-2' readonly $extra>";
      echo "<input type='hidden' name='{$name}_hidden' value='".htmlspecialchars($val)."'>";
   }
  ?>
  <?php fld('g_name',$grp['name']);?>
  <div class="row g-2">
    <div class="col-md"><?php fld('g_room',$grp['room_number']);?></div>
    <div class="col-md"><?php fld('g_icon',$grp['icon']);?></div>
  </div>
  <div class="row g-2">
    <div class="col-md"><?php fld('g_from',$grp['age_from'],'number');?></div>
    <div class="col-md"><?php fld('g_to',$grp['age_to'],'number');?></div>
  </div>
  <label class="form-label mt-2">Описание</label>
  <textarea name="g_descr_visible" class="form-control" rows="4" readonly><?=htmlspecialchars($grp['description'])?></textarea>
  <input type="hidden" name="g_descr_hidden" value="<?=htmlspecialchars($grp['description'])?>">

  <button id="groupSaveBtn" class="btn btn-success mt-3 d-none">Сохранить группу</button>
</div>
</form>

<!--************* ДЕТИ *************-->
<form method="post" id="kidsForm"><input type="hidden" name="mode" value="kids">
  <input type="hidden" name="kid_ids" id="kidIds">
<div class="card p-3">
  <div class="d-flex justify-content-between">
     <h5>Дети группы</h5>
     <button type="button" id="kidsEditBtn" class="btn btn-warning btn-sm">Изменить детей</button>
  </div>

  <ul id="kidList" class="mt-2">
    <?php foreach($kids as $k): ?>
      <li data-id="<?=$k['id']?>"><?=htmlspecialchars($k['full_name'])?> (<?=date('d.m.Y',strtotime($k['birth_date']))?>)</li>
    <?php endforeach;?>
  </ul>

  <div class="row g-2 align-items-end mt-2 d-none" id="kidsControls">
    <div class="col">
      <select id="kidSelect" class="form-select">
        <option value="">— выбрать ребёнка —</option>
        <?php foreach($freeKids as $f): ?>
          <option value="<?=$f['id']?>"><?=htmlspecialchars($f['full_name'])?></option>
        <?php endforeach;?>
      </select>
    </div>
    <div class="col-auto">
      <button id="addKid" class="btn btn-success btn-sm" disabled>Добавить</button>
      <button id="remKid" class="btn btn-danger btn-sm" disabled>Убрать</button>
    </div>
    <div class="col-12">
      <button class="btn btn-success btn-sm mt-2">Сохранить детей</button>
    </div>
  </div>
</div>
</form>

<script>
/* ==== педагоги ==== */
teachEditBtn.onclick=()=>{teachView.classList.add('d-none');teachEdit.classList.remove('d-none');};
teachCancel.onclick=()=>{teachEdit.classList.add('d-none');teachView.classList.remove('d-none');};

/* ==== группа ==== */
groupEditBtn.onclick=()=>{
  document.querySelectorAll('[name$=_visible]').forEach(i=>{i.removeAttribute('readonly');});
  groupSaveBtn.classList.remove('d-none');
};

/* ==== дети ==== */
const kidSelect=document.getElementById('kidSelect');
const addKid=document.getElementById('addKid');
const remKid=document.getElementById('remKid');
const kidList=document.getElementById('kidList');

kidsEditBtn.onclick=()=>{
  kidsControls.classList.remove('d-none');
};

kidSelect.onchange=()=>addKid.disabled=!kidSelect.value;
addKid.onclick=e=>{
  e.preventDefault();
  const val=kidSelect.value;if(!val)return;
  const li=document.createElement('li');
  li.textContent=kidSelect.selectedOptions[0].text;li.dataset.id=val;
  kidList.appendChild(li);
  kidSelect.selectedIndex=0;addKid.disabled=true;
};
kidList.onclick=e=>{
  if(e.target.tagName==='LI'){
     kidList.querySelectorAll('li').forEach(li=>li.classList.remove('sel'));
     e.target.classList.add('sel'); remKid.disabled=false;
  }
};
remKid.onclick=e=>{
  e.preventDefault();
  const sel=kidList.querySelector('li.sel'); if(sel) sel.remove(); remKid.disabled=true;
};
kidsForm.onsubmit=()=>{
  const ids=[...kidList.querySelectorAll('li')].map(li=>li.dataset.id);
  document.getElementById('kidIds').value=ids.join(',');
};
</script>
</body></html>
