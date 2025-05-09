<?php
// Единая админ‑панель
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') {
    exit('Доступ разрешён только администраторам');
}
require_once __DIR__.'/../config.php';

$username = $_SESSION['user']['username'];
$section  = $_GET['section'] ?? '';   // staff | news | groups | kids

/*──────────────────── ПРЕ‑действия (удаление детей / сотрудников) ───────────────────*/
if ($_SERVER['REQUEST_METHOD']==='POST') {

    /* дети */
    if ($section==='kids' && isset($_POST['delete_ids'])) {
        $ids=array_map('intval',$_POST['delete_ids']);
        if($ids){
            $in=rtrim(str_repeat('?,',count($ids)),',');
            $pdo->prepare("DELETE FROM parent_kid WHERE kid_id IN ($in)")->execute($ids);
            $pdo->prepare("DELETE FROM kids       WHERE id     IN ($in)")->execute($ids);
        }
        header('Location: index.php?section=kids'); exit;
    }

    /* сотрудники */
    if ($section==='staff' && isset($_POST['staff_delete_ids'])) {
        $ids=array_map('intval',$_POST['staff_delete_ids']);
        if($ids){
            $in=rtrim(str_repeat('?,',count($ids)),',');
            $pdo->prepare("DELETE FROM staff WHERE id IN ($in)")->execute($ids);
        }
        header('Location: index.php?section=staff'); exit;
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Админ‑панель | Ромашка</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>.nav-link.active{background:#0d6efd;color:#fff;} .table-hover tbody tr{cursor:pointer;}</style>
</head>
<body class="d-flex flex-column min-vh-100">

<header class="bg-success text-white py-2">
  <div class="container d-flex justify-content-between align-items-center">
     <h1 class="h5 m-0 fw-bold">ГКДОУ «Ромашка» — админ</h1>
     <div>
        Здравствуйте,&nbsp;<strong><?=htmlspecialchars($username)?></strong>
        <a href="/index.php"  class="btn btn-light btn-sm ms-3">← На сайт</a>
        <a href="/logout.php" class="btn btn-light btn-sm">Выход</a>
     </div>
  </div>
</header>

<nav class="bg-white border-bottom">
 <div class="container">
  <ul class="nav nav-pills justify-content-center py-2 fw-semibold">
     <li class="nav-item"><a class="nav-link <?=$section==='staff'?'active':''?>"  href="?section=staff">Сотрудники</a></li>
     <li class="nav-item"><a class="nav-link <?=$section==='news'?'active':''?>"   href="?section=news">Новости</a></li>
     <li class="nav-item"><a class="nav-link <?=$section==='groups'?'active':''?>" href="?section=groups">Группы</a></li>
     <li class="nav-item"><a class="nav-link <?=$section==='kids'?'active':''?>"   href="?section=kids">Дети</a></li>
  </ul>
 </div>
</nav>

<main class="container my-5 flex-grow-1">
<?php
switch($section){

/*──────────────────────────────── СОТРУДНИКИ ─────────────────────────────*/
case 'staff':
    $workers=$pdo->query("SELECT id,full_name,position,hire_date,photo_url FROM staff ORDER BY full_name")->fetchAll();
?>
<h2 class="mb-3">Сотрудники</h2>
<div class="mb-3">
   <a href="staff/add.php" class="btn btn-success">+ Добавить сотрудника</a>
   <button id="delStaffBtn" class="btn btn-danger ms-2" disabled>Удалить</button>
</div>
<form id="frmDelStaff" method="post">
<table class="table table-hover align-middle">
 <thead class="table-light"><tr>
   <th style="width:40px"><input type="checkbox" id="stAll"></th>
   <th style="width:60px"></th><th>ФИО</th><th>Должность</th><th>Дата найма</th>
 </tr></thead><tbody>
 <?php foreach($workers as $w): ?>
  <tr data-href="staff/edit.php?id=<?=$w['id']?>">
   <td><input type="checkbox" class="stRow" name="staff_delete_ids[]" value="<?=$w['id']?>"
              onclick="event.stopPropagation(); toggleSt()"></td>
   <td><?php if($w['photo_url']):?><img src="/<?=htmlspecialchars($w['photo_url'])?>" style="height:48px;object-fit:cover;border-radius:.25rem"><?php endif;?></td>
   <td><?=htmlspecialchars($w['full_name'])?></td>
   <td><?=htmlspecialchars($w['position'])?></td>
   <td><?=date('d.m.Y',strtotime($w['hire_date']))?></td>
  </tr>
 <?php endforeach;?>
 </tbody></table></form>

<script>
function toggleSt(){delStaffBtn.disabled=!document.querySelector('.stRow:checked');}
stAll.onchange=e=>{document.querySelectorAll('.stRow').forEach(c=>c.checked=e.target.checked);toggleSt();}
delStaffBtn.onclick=()=>{if(confirm('Удалить выбранных?')) frmDelStaff.submit();}
document.querySelectorAll('tbody tr').forEach(tr=>{tr.onclick=e=>{if(e.target.tagName!=='INPUT')location=tr.dataset.href;}});
</script>
<?php
break;

/*────────────────────────────────── НОВОСТИ ─────────────────────────────*/
case 'news':
  $rows = $pdo->query("
        SELECT id, title, cover_image, created_at, status
          FROM articles
      ORDER BY created_at DESC")->fetchAll();
?>
<h2 class="mb-3">Новости</h2>

<div class="mb-3">
   <a href="news/add.php" class="btn btn-success">+ Добавить новость</a>
   <button id="delN" class="btn btn-danger ms-2" disabled>Удалить</button>
</div>

<form id="frmN" method="post">
<table class="table table-hover align-middle">
 <thead class="table-light"><tr>
   <th style="width:40px"><input type="checkbox" id="selAllN"></th>
   <th style="width:70px"></th><th>Заголовок</th><th>Дата</th><th>Статус</th>
 </tr></thead><tbody>
 <?php foreach ($rows as $n): ?>
  <tr data-href="news/edit.php?id=<?=$n['id']?>">
    <td><input type="checkbox" class="rowN" name="news_delete_ids[]" value="<?=$n['id']?>"
               onclick="event.stopPropagation(); toggleN()"></td>
    <td><?php if($n['cover_image']): ?>
          <img src="/<?=$n['cover_image']?>" style="height:48px;object-fit:cover;border-radius:.25rem">
        <?php endif;?></td>
    <td><?=htmlspecialchars($n['title'])?></td>
    <td><?=date('d.m.Y H:i',strtotime($n['created_at']))?></td>
    <td><?=$n['status']==1 ? 'Опубликовано' : 'Черновик'?></td>
  </tr>
 <?php endforeach; ?>
 </tbody></table></form>

<script>
function toggleN(){delN.disabled=!document.querySelector('.rowN:checked');}
selAllN.onchange=e=>{
  document.querySelectorAll('.rowN').forEach(c=>c.checked=e.target.checked);
  toggleN();
};
delN.onclick=()=>{if(confirm('Удалить выбранные новости?')) frmN.submit();}
document.querySelectorAll('tbody tr').forEach(tr=>{
   tr.onclick=e=>{if(e.target.tagName!=='INPUT') location=tr.dataset.href;}
});
</script>
<?php
  /* удаление новостей */
  if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['news_delete_ids'])) {
      $ids=array_map('intval',$_POST['news_delete_ids']);
      if($ids){
          $in=rtrim(str_repeat('?,',count($ids)),',');
          $pdo->prepare("DELETE FROM articles WHERE id IN ($in)")->execute($ids);
      }
      header('Location: index.php?section=news'); exit;
  }
break;
/*──────────────────────────────  ГРУППЫ  ────────────────────────────*/
case 'groups':
  /* — список групп — */
  $groups = $pdo->query("
      SELECT id, name, icon, age_from, age_to
        FROM groups
    ORDER BY name
  ")->fetchAll();
?>
<h2 class="mb-3">Группы</h2>

<div class="d-flex justify-content-between align-items-center mb-3">
 <a href="groups/add.php" class="btn btn-success">+ Добавить группу</a>
 <button id="delGroupBtn" class="btn btn-danger" style="display:none">
     <i class="bi bi-trash"></i> <!-- bootstrap icons -->
 </button>
</div>

<!-- карточки групп -->
<form id="frmDelGroup" method="post">
<div class="row g-4">
<?php foreach($groups as $g): ?>
   <div class="col-6 col-md-3">
      <div class="card text-center h-100 group-card position-relative"
           data-href="groups/edit.php?id=<?=$g['id']?>">
          <!-- чек‑бокс поверх карточки -->
          <input type="checkbox" class="g-check form-check-input position-absolute"
                 style="top:6px;left:6px;transform:scale(1.3)"
                 name="group_delete_ids[]" value="<?=$g['id']?>"
                 onclick="event.stopPropagation(); toggleGroupDel()">
          <div class="card-body">
              <div style="font-size:42px"><?=htmlspecialchars($g['icon'])?></div>
              <h6 class="card-title"><?=htmlspecialchars($g['name'])?></h6>
              <small class="text-muted">Возраст: <?= (int)$g['age_from'] ?>‑<?= (int)$g['age_to'] ?></small>
          </div>
      </div>
   </div>
<?php endforeach;?>
</div>
</form>

<script>
/* —‑ кликабельность карточки —‑ */
document.querySelectorAll('.group-card').forEach(card=>{
 card.addEventListener('click', e=>{
   if(e.target.classList.contains('g-check')) return; // клик по чек-боксу
   location.href = card.dataset.href;
 });
});

/* —‑ управление кнопкой удаления —‑ */
function toggleGroupDel(){
 const btn = document.getElementById('delGroupBtn');
 btn.style.display = document.querySelector('.g-check:checked') ? 'inline-block' : 'none';
}
document.getElementById('delGroupBtn').onclick = ()=>{
 if(confirm('Если уверены, что хотите удалить выбранные группы?')) {
     document.getElementById('frmDelGroup').submit();
 }
};
</script>
<?php
  /* -------- УДАЛЕНИЕ групп -------- */
  if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['group_delete_ids'])) {
      $ids=array_map('intval',$_POST['group_delete_ids']);
      if($ids){
          $in=rtrim(str_repeat('?,',count($ids)),',');
          /* 1. отвязываем детей (ставим NULL в истории или просто DELETE связей) */
          $pdo->prepare("DELETE FROM group_kid_history WHERE group_id IN ($in)")->execute($ids);
          /* 2. отвязываем воспитателей */
          $pdo->prepare("DELETE FROM group_staff WHERE group_id IN ($in)")->execute($ids);
          /* 3. удаляем сами группы */
          $pdo->prepare("DELETE FROM groups WHERE id IN ($in)")->execute($ids);
      }
      header('Location: index.php?section=groups'); exit;
  }
break;


/*────────────────────────────────  ДЕТИ  ────────────────────────────*/
case 'kids':
  $kids=$pdo->query("
      SELECT k.id,k.full_name,k.birth_date,
             GROUP_CONCAT(DISTINCT p.full_name SEPARATOR ', ') parents,
             GROUP_CONCAT(DISTINCT IFNULL(p.social_category,'') SEPARATOR ', ') cats
        FROM kids k
   LEFT JOIN parent_kid pk ON pk.kid_id=k.id
   LEFT JOIN parents    p  ON p.id=pk.parent_id
    GROUP BY k.id ORDER BY k.full_name")->fetchAll();
?>
<h2 class="mb-3">Дети</h2>
<div class="mb-3">
 <a href="kids/add.php" class="btn btn-success">+ Добавить ребёнка</a>
 <button id="delK" class="btn btn-danger ms-2" disabled>Удалить</button>
</div>
<form id="frmK" method="post">
<table class="table table-hover align-middle">
<thead class="table-light"><tr>
 <th style="width:40px"><input type="checkbox" id="kAll"></th>
 <th>ФИО</th><th>Дата рождения</th><th>Родители</th><th>Соц. категория</th>
</tr></thead><tbody>
<?php foreach($kids as $row): ?>
 <tr data-href="kids/edit.php?id=<?=$row['id']?>">
   <td><input type="checkbox" class="rowK" name="delete_ids[]" value="<?=$row['id']?>"
              onclick="event.stopPropagation(); toggleK()"></td>
   <td><?=htmlspecialchars($row['full_name'])?></td>
   <td><?=date('d.m.Y',strtotime($row['birth_date']))?></td>
   <td><?=htmlspecialchars($row['parents'])?></td>
   <td><?=htmlspecialchars($row['cats'])?></td>
 </tr>
<?php endforeach;?>
</tbody></table></form>

<script>
function toggleK(){delK.disabled=!document.querySelector('.rowK:checked');}
kAll.onchange=e=>{document.querySelectorAll('.rowK').forEach(c=>c.checked=e.target.checked);toggleK();}
delK.onclick=()=>{if(confirm('Удалить?')) frmK.submit();}
document.querySelectorAll('tbody tr').forEach(tr=>{tr.onclick=e=>{if(e.target.tagName!=='INPUT')location=tr.dataset.href;}});
</script>
<?php
break;

/*──────────────────────────── Заглушка по умолчанию ───────────────*/
default:
  echo '<p class="lead text-center">Выберите раздел в меню выше.</p>';
}
?>
</main>

<footer class="bg-success text-white py-2 mt-auto">
<div class="container small">© <?=date('Y')?> Детский сад «Ромашка»</div>
</footer>
</body></html>