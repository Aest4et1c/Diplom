<?php
// /admin/index.php — единая админ‑панель (с правками для «Пользователей»)

session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    exit('Доступ разрешён только администраторам');
}

require_once __DIR__ . '/../config.php';

$username = $_SESSION['user']['username'];
$section  = $_GET['section'] ?? '';          // staff | news | groups | kids | users

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ДЕТИ ------------------------------------------------------------- */
    if ($section === 'kids' && isset($_POST['delete_ids'])) {
        $ids = array_map('intval', $_POST['delete_ids']);
        if ($ids) {
            $in = rtrim(str_repeat('?,', count($ids)), ',');
            $pdo->prepare("DELETE FROM parent_kid WHERE kid_id IN ($in)")->execute($ids);
            $pdo->prepare("DELETE FROM kids       WHERE id     IN ($in)")->execute($ids);
        }
        header('Location: index.php?section=kids'); exit;
    }

    /* СОТРУДНИКИ ------------------------------------------------------- */
    if ($section === 'staff' && isset($_POST['staff_delete_ids'])) {
        $ids = array_map('intval', $_POST['staff_delete_ids']);
        if ($ids) {
            $in = rtrim(str_repeat('?,', count($ids)), ',');
            $pdo->prepare("DELETE FROM staff WHERE id IN ($in)")->execute($ids);
        }
        header('Location: index.php?section=staff'); exit;
    }

    /* ПОЛЬЗОВАТЕЛИ ----------------------------------------------------- */
    if ($section === 'users' && isset($_POST['del_ids'])) {
        $ids = array_map('intval', $_POST['del_ids']);
        if ($ids) {
            $in = rtrim(str_repeat('?,', count($ids)), ',');
            $pdo->prepare("DELETE FROM users WHERE id IN ($in)")->execute($ids);
        }
        header('Location: index.php?section=users'); exit;
    }

    /* НОВОСТИ ---------------------------------------------------------- */
    if ($section === 'news' && isset($_POST['news_delete_ids'])) {
        $ids = array_map('intval', $_POST['news_delete_ids']);
        if ($ids) {
            $in = rtrim(str_repeat('?,', count($ids)), ',');
            $pdo->prepare("DELETE FROM articles WHERE id IN ($in)")->execute($ids);
        }
        header('Location: index.php?section=news'); exit;   // ← будет выполнен ДО вывода HTML
    }

    /* ГРУППЫ ----------------------------------------------------------- */
    if ($section === 'groups' && isset($_POST['group_delete_ids'])) {
        $ids = array_map('intval', $_POST['group_delete_ids']);
        if ($ids) {
            $in = rtrim(str_repeat('?,', count($ids)), ',');

            /* 1 — отвязываем детей */
            $pdo->prepare("DELETE FROM group_kid_history WHERE group_id IN ($in)")->execute($ids);
            /* 2 — отвязываем воспитателей */
            $pdo->prepare("DELETE FROM group_staff WHERE group_id IN ($in)")->execute($ids);
            /* 3 — удаляем группы */
            $pdo->prepare("DELETE FROM groups WHERE id IN ($in)")->execute($ids);
        }
        header('Location: index.php?section=groups'); exit;
    }
}

/* Справочник ролей */
$roleName = [1=>'Администратор', 2=>'Воспитатель', 3=>'Родитель'];
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Админ‑панель | Ромашка</title>
<link rel="icon" type="image/png" href="/image/web_logo.png">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>.nav-link.active{background:#0d6efd;color:#fff;} .table-hover tbody tr{cursor:pointer;}</style>
</head>
<body class="d-flex flex-column min-vh-100">

<header class="bg-success text-white py-2">
  <div class="container d-flex justify-content-between align-items-center">
    <h1 class="h5 m-0 fw-bold">ГКДОУ «Ромашка» — админ</h1>
    <div>
      Здравствуйте, <strong><?=htmlspecialchars($username)?></strong>
      <a href="/index.php" class="btn btn-light btn-sm ms-3">← На сайт</a>
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
      <li class="nav-item"><a class="nav-link <?=$section==='users'?'active':''?>"  href="?section=users">Пользователи</a></li>
    </ul>
  </div>
</nav>

<main class="container my-5 flex-grow-1">
<?php
switch ($section) {

/* ───────────────────────────  ПОЛЬЗОВАТЕЛИ  ─────────────────────────── */
case 'users':
    /* исключаем учётку admin из списка */
    $users = $pdo->query("
        SELECT u.id, u.username, u.role_id, u.staff_id, u.parent_id, u.is_active,
               s.full_name AS staff_name,
               p.full_name AS parent_name
          FROM users u
     LEFT JOIN staff   s ON s.id = u.staff_id
     LEFT JOIN parents p ON p.id = u.parent_id
         WHERE u.username <> 'admin'
      ORDER BY u.username
    ")->fetchAll(PDO::FETCH_ASSOC);
?>
<h2 class="mb-3">Пользователи</h2>

<div class="mb-3">
  <a href="users/add.php" class="btn btn-success">+ Добавить пользователя</a>
  <button id="delUserBtn" class="btn btn-danger ms-2" disabled>Удалить</button>
</div>

<form id="frmDelUser" method="post">
<table class="table table-hover align-middle">
  <thead class="table-light">
    <tr>
      <th style="width:40px"><input type="checkbox" id="uCheckAll"></th>
      <th>Логин</th>
      <th>Роль</th>
      <th>Связь</th>
      <th>Активен</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($users as $u): ?>
    <tr data-href="users/edit.php?id=<?=$u['id']?>">
      <td><input type="checkbox" class="u-row" name="del_ids[]" value="<?=$u['id']?>" onclick="event.stopPropagation(); toggleUserDel()"></td>
      <td><?=htmlspecialchars($u['username'])?></td>
      <td><?=$roleName[(int)$u['role_id']] ?? '—'?></td>
      <td>
        <?php
          if ($u['staff_id'])      echo htmlspecialchars($u['staff_name']);
          elseif ($u['parent_id']) echo htmlspecialchars($u['parent_name']);
          else echo '—';
        ?>
      </td>
      <td><?=$u['is_active'] ? 'Да' : 'Нет'?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</form>

<script>
function toggleUserDel(){
  document.getElementById('delUserBtn').disabled = !document.querySelector('.u-row:checked');
}
document.getElementById('uCheckAll').onchange = e=>{
  document.querySelectorAll('.u-row').forEach(c=>c.checked = e.target.checked);
  toggleUserDel();
};
document.getElementById('delUserBtn').onclick = ()=>{
  if(confirm('Удалить выбранных пользователей?')) document.getElementById('frmDelUser').submit();
};
document.querySelectorAll('tbody tr').forEach(tr=>{
  tr.addEventListener('click', e=>{
    if(e.target.tagName === 'INPUT') return;
    location.href = tr.dataset.href;
  });
});
</script>
<?php
break;

/*────────────────────────── СОТРУДНИКИ ──────────────────────────*/
case 'staff':

    /* ── фильтр: действующие | уволенные ─────────────────────── */
    $show = ($_GET['show'] ?? 'active') === 'fire' ? 'fire' : 'active';

    $cond = ($show === 'active')
          ? '(fire_date IS NULL OR fire_date > CURDATE())'
          : '(fire_date IS NOT NULL AND fire_date <= CURDATE())';

    $workers = $pdo->query("
        SELECT id, full_name, position, hire_date, fire_date, photo_url
          FROM staff
         WHERE $cond
      ORDER BY full_name
    ")->fetchAll();
?>
<h2 class="mb-3">Сотрудники</h2>

<!-- ───── панель действий ───── -->
<div class="row g-2 align-items-center mb-3">
  <div class="col-auto">
      <a href="staff/add.php" class="btn btn-success me-2">+ Добавить сотрудника</a>
      <button id="delStaffBtn" class="btn btn-danger" disabled>Удалить</button>
  </div>

  <!-- переключатель активные / уволенные -->
  <div class="col-auto">
    <form method="get" class="m-0">
      <input type="hidden" name="section" value="staff">
      <select name="show" class="form-select" onchange="this.form.submit()">
          <option value="active" <?=$show==='active'?'selected':''?>>Действующие</option>
          <option value="fire"   <?=$show==='fire'  ?'selected':''?>>Уволенные</option>
      </select>
    </form>
  </div>

  <!-- живой поиск по фамилии / имени -->
  <div class="col-auto flex-grow-1" style="min-width:200px">
      <input type="search" id="staffSearch" class="form-control"
             placeholder="Поиск сотрудника">
  </div>
</div>

<form id="frmDelStaff" method="post">
<table class="table table-hover align-middle">
  <thead class="table-light">
    <tr>
      <th style="width:40px"><input type="checkbox" id="stAll"></th>
      <th style="width:60px"></th>
      <th>ФИО</th>
      <th>Должность</th>
      <th>Дата найма</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($workers as $w): ?>
    <tr data-href="staff/edit.php?id=<?=$w['id']?>">
      <td>
        <input type="checkbox" class="stRow" name="staff_delete_ids[]" value="<?=$w['id']?>"
               onclick="event.stopPropagation(); toggleSt()">
      </td>
      <td>
        <?php if ($w['photo_url']): ?>
            <img src="/<?=htmlspecialchars($w['photo_url'])?>" style="height:48px;object-fit:cover;border-radius:.25rem">
        <?php endif; ?>
      </td>
      <td><?=htmlspecialchars($w['full_name'])?></td>
      <td><?=htmlspecialchars($w['position'])?></td>
      <td><?=date('d.m.Y', strtotime($w['hire_date']))?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</form>

<script>
/* ───── кнопка «Удалить» ───── */
function toggleSt(){ delStaffBtn.disabled = !document.querySelector('.stRow:checked'); }
stAll.onchange = e => {
    document.querySelectorAll('.stRow').forEach(c => c.checked = e.target.checked);
    toggleSt();
};
delStaffBtn.onclick = () => {
    if (confirm('Удалить выбранных?')) frmDelStaff.submit();
};

/* ───── переход к карточке сотрудника ───── */
document.querySelectorAll('tbody tr').forEach(tr => {
    tr.onclick = e => {
        if (e.target.tagName !== 'INPUT') location = tr.dataset.href;
    };
});

/* ───── живой поиск (фамилия + имя в любом порядке) ───── */
const searchInput = document.getElementById('staffSearch');
searchInput.addEventListener('input', function(){
    const terms = this.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
    document.querySelectorAll('tbody tr').forEach(row => {
        const text = row.innerText.toLowerCase();
        const show = terms.every(t => text.includes(t));
        row.style.display = show ? '' : 'none';
    });
});
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
/*──────────────────────────────  ДЕТИ  ───────────────────────────*/

case 'kids':

    /* данные */
    $kids = $pdo->query("
        SELECT k.id,
               k.full_name,
               k.birth_date,
               GROUP_CONCAT(p.full_name ORDER BY pk.parent_id SEPARATOR ', ')        AS parents,
               GROUP_CONCAT(IFNULL(p.social_category,'') ORDER BY pk.parent_id SEPARATOR ', ') AS cats
          FROM kids k
     LEFT JOIN parent_kid pk ON pk.kid_id = k.id
     LEFT JOIN parents    p  ON p.id      = pk.parent_id
      GROUP BY k.id
      ORDER BY k.full_name
    ")->fetchAll(PDO::FETCH_ASSOC);

    /* категории для селекта */
    $catList = [];
    foreach ($kids as $r) {
        foreach (explode(', ', $r['cats']) as $c) {
            $c = trim($c); if ($c !== '') $catList[$c] = true;
        }
    }
    ksort($catList);
?>
<h2 class="mb-3">Дети</h2>

<div class="d-flex flex-wrap align-items-center mb-3 gap-2">
  <a href="kids/add.php" class="btn btn-success">+ Добавить ребёнка</a>
  <button id="delBtn" class="btn btn-danger" disabled>Удалить</button>

  <button id="searchToggle" class="btn btn-outline-secondary ms-2">
      🔍 Найти ребёнка / родителя
  </button>

  <select id="catFilter" class="form-select form-select-sm ms-auto" style="max-width:200px">
      <option value="">Все соц. категории</option>
      <?php foreach ($catList as $c=>$_): ?>
          <option value="<?=htmlspecialchars(strtolower($c))?>"><?=htmlspecialchars($c)?></option>
      <?php endforeach; ?>
  </select>
</div>

<div id="searchBox" class="mb-3 d-none">
   <input type="search" id="liveSearch" class="form-control" placeholder="Введите фамилию / имя…">
</div>

<form id="frmKids" method="post">
<table class="table table-hover align-middle" id="kidTable">
 <thead class="table-light">
  <tr>
    <th style="width:40px"><input type="checkbox" id="checkAll"></th>
    <th>ФИО ребёнка</th><th>Дата рождения</th><th>Родители</th><th>Соц. категория</th>
  </tr>
 </thead>
 <tbody>
 <?php foreach ($kids as $row): ?>
   <tr data-href="kids/edit.php?id=<?=$row['id']?>"
       data-cat="<?=strtolower(str_replace(',',' ', $row['cats']))?>">
     <td><input type="checkbox" class="row-check" name="delete_ids[]" value="<?=$row['id']?>"
                onclick="event.stopPropagation(); toggleDel()"></td>
     <td><?=htmlspecialchars($row['full_name'])?></td>
     <td><?=date('d.m.Y',strtotime($row['birth_date']))?></td>
     <td><?=htmlspecialchars($row['parents'])?></td>
     <td><?=htmlspecialchars($row['cats'])?></td>
   </tr>
 <?php endforeach; ?>
 </tbody>
</table>
</form>

<script>
/* ─── массовое удаление ─── */
function toggleDel(){ delBtn.disabled = !document.querySelector('.row-check:checked'); }
checkAll.onchange = e=>{
    document.querySelectorAll('.row-check').forEach(c=>c.checked = e.target.checked);
    toggleDel();
};
delBtn.onclick = ()=>{ if(confirm('Удалить выбранные данные?')) frmKids.submit(); };

/* ─── переход к редактированию ─── */
document.querySelectorAll('#kidTable tbody tr').forEach(tr=>{
    tr.onclick = e=>{ if(e.target.tagName!=='INPUT') location = tr.dataset.href; };
});

/* ─── показать / скрыть строку поиска ─── */
const searchBox  = document.getElementById('searchBox');
const searchBtn  = document.getElementById('searchToggle');
const searchInput= document.getElementById('liveSearch');
searchBtn.onclick = ()=>{
    searchBox.classList.toggle('d-none');
    if(!searchBox.classList.contains('d-none')) searchInput.focus();
};

/* ─── живой фильтр ─── */
const catSelect = document.getElementById('catFilter');
function liveFilter(){
    const terms  = searchInput.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
    const catVal = catSelect.value;      // '' или выбранная категория

    document.querySelectorAll('#kidTable tbody tr').forEach(row=>{
        const rowText = row.innerText.toLowerCase();     // ребёнок + родители
        const txtOk   = terms.every(t=>rowText.includes(t));
        const catOk   = !catVal || row.dataset.cat.includes(catVal);

        row.style.display = (txtOk && catOk) ? '' : 'none';
    });
}
searchInput.addEventListener('input', liveFilter);
catSelect  .addEventListener('change', liveFilter);
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