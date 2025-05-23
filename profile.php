<?php
/* profile.php — кабинет воспитателя + родитель, с возможностью смены пароля */

session_start();
require_once __DIR__.'/config.php';

$user = $_SESSION['user'] ?? null;
if (!$user) { header('Location: login.php'); exit; }

$role     = $user['role'];                 // admin | teacher | parent
$staffId  = (int)($_GET['staff_id']  ?? 0);
$parentId = (int)($_GET['parent_id'] ?? 0);

/* если id не передан — открываем собственный профиль */
if (!$staffId && !$parentId) {
    if ($role==='teacher' && $user['staff_id'])  $staffId  = $user['staff_id'];
    if ($role==='parent'  && $user['parent_id']) $parentId = $user['parent_id'];
}

/* может ли пользователь управлять новостями? (только свой воспитатель) */
$canManage = ($role==='teacher' && $user['staff_id'] && $user['staff_id']===$staffId);

/* ───── смена пароля родителем ───── */
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='change_pass') {
    if ($role!=='parent' || $parentId!=$user['parent_id']) {
        exit('Недостаточно прав');
    }
    $p1 = $_POST['pass1'] ?? '';
    $p2 = $_POST['pass2'] ?? '';
    if (strlen($p1)<6)            $msg = 'Пароль должен быть не короче 6 символов.';
    elseif ($p1!==$p2)            $msg = 'Пароли не совпадают.';
    else {
        $hash = password_hash($p1, PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET pass_hash=? WHERE id=?")
            ->execute([$hash,$user['id']]);
        $msg = 'Пароль успешно изменён.';
    }
}

/* ───── Профиль воспитателя ───── */
$staff = $posts = null;
if ($staffId) {
    $s = $pdo->prepare("
        SELECT s.full_name,s.position,s.photo_url,
               IFNULL(GROUP_CONCAT(g.name SEPARATOR ', '),'') groups
          FROM staff s
     LEFT JOIN group_staff gs ON gs.staff_id=s.id
     LEFT JOIN groups      g  ON g.id       =gs.group_id
         WHERE s.id=? GROUP BY s.id");
    $s->execute([$staffId]);
    $staff = $s->fetch();

    if($staff){
        $sql="
          SELECT id,title,cover_image,created_at,status,
                 SUBSTRING(body,1,250) excerpt
            FROM articles
           WHERE staff_id=?";
        if(!$canManage) $sql.=" AND status=1";
        $sql.=" ORDER BY created_at DESC";
        $p=$pdo->prepare($sql);
        $p->execute([$staffId]);
        $posts=$p->fetchAll(PDO::FETCH_ASSOC);
    }
}

/* ───── Профиль родителя ───── */
$parent = null;
if ($parentId){
    $p=$pdo->prepare("
        SELECT p.full_name,p.phone,p.email,p.address,
               IFNULL(GROUP_CONCAT(k.full_name SEPARATOR ', '),'') kids
          FROM parents p
     LEFT JOIN parent_kid pk ON pk.parent_id=p.id
     LEFT JOIN kids      k  ON k.id         =pk.kid_id
         WHERE p.id=? GROUP BY p.id");
    $p->execute([$parentId]);
    $parent=$p->fetch();
}

/* fallback */
$minimal=null;
if(!$staffId && !$parentId){
    $minimal=[
       'name'=>htmlspecialchars($user['name'] ?? $user['username']),
       'role'=>ucfirst($role)
    ];
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Профиль</title>
<link rel="icon" type="image/png" href="/image/web_logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.avatar          {width:170px;height:170px;object-fit:cover;border-radius:.5rem}
.news-card:hover {text-decoration:none;box-shadow:0 .5rem 1rem rgba(0,0,0,.15)}
.news-cover      {width:100%;aspect-ratio:16/9;object-fit:cover}
.manage-btns     {position:absolute;top:.5rem;right:.5rem;z-index:2}
.manage-btns button,.manage-btns a{padding:.25rem .35rem;font-size:.95rem}
</style>
</head>
<body class="d-flex flex-column min-vh-100">

<header class="bg-success text-white py-2">
  <div class="container d-flex justify-content-between">
     <h1 class="h5 m-0 fw-bold">Профиль</h1>
     <a href="index.php" class="btn btn-light btn-sm">На сайт</a>
  </div>
</header>

<main class="container my-4 flex-grow-1">

<?php if($staff): /* ───────── ВОСПИТАТЕЛЬ ──────── */ ?>
<div class="row g-4 flex-lg-nowrap">

  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body text-center">
        <img src="/<?=htmlspecialchars($staff['photo_url']?:'uploads/photos/placeholder.jpg')?>"
             class="avatar mb-3" alt="">
        <h5 class="mb-0"><?=htmlspecialchars($staff['full_name'])?></h5>
        <p class="text-muted small mb-2"><?=htmlspecialchars($staff['position'])?></p>
        <?php if($staff['groups']): ?>
          <p class="small mb-0"><strong>Группа:</strong> <?=htmlspecialchars($staff['groups'])?></p>
        <?php endif;?>
      </div>
    </div>
  </div>

  <div class="col-lg">
    <?php if($canManage): ?>
      <div class="d-flex justify-content-end mb-3">
        <a href="/admin/profile/add.php" class="btn btn-sm btn-primary">Добавить новость</a>
      </div>
    <?php endif; ?>

    <?php if($posts): ?>
      <div class="row g-4">
      <?php foreach($posts as $a): ?>
        <div class="col-md-6 col-lg-4 position-relative">
          <div class="card news-card h-100 <?=($a['status']==0?'border-secondary':'')?>">
            <?php if($a['cover_image']): ?>
              <img src="/<?=htmlspecialchars($a['cover_image'])?>" class="news-cover card-img-top" alt="">
            <?php endif; ?>

            <?php if($canManage): ?>
              <div class="manage-btns d-flex gap-1">
                 <a href="/admin/profile/edit.php?id=<?=$a['id']?>" class="btn btn-light border" title="Изменить">✏️</a>
                 <form method="post" class="d-inline" onsubmit="return confirm('Удалить новость?');">
                   <input type="hidden" name="del_article_id" value="<?=$a['id']?>">
                   <button class="btn btn-light border" title="Удалить">🗑</button>
                 </form>
              </div>
            <?php endif; ?>

            <a href="/admin/profile/article.php?id=<?=$a['id']?>" class="stretched-link"></a>
            <div class="card-body">
              <h5 class="card-title mb-2">
                <?=htmlspecialchars($a['title'])?>
                <?php if($canManage && $a['status']==0): ?>
                  <span class="badge bg-secondary">Черновик</span>
                <?php endif;?>
              </h5>
              <p class="card-text small"><?=htmlspecialchars($a['excerpt'])?>…</p>
            </div>
            <div class="card-footer small text-muted"><?=date('d.m.Y',strtotime($a['created_at']))?></div>
          </div>
        </div>
      <?php endforeach;?>
      </div>
    <?php else: ?>
      <div class="alert alert-info">
        <?php if($canManage): ?>Записей ещё нет. Нажмите «Добавить новость», чтобы опубликовать первую.
        <?php else: ?>Записей ещё нет.<?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php elseif($parent): /* ───────── РОДИТЕЛЬ ──────── */ ?>
<?php $showForm = isset($msg) || ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='change_pass'); ?>
<div class="row g-4 flex-lg-nowrap">

  <div class="col-lg-4">
    <div class="card shadow-sm">
      <div class="card-body text-center">
        
        <h5 class="mb-0"><?=htmlspecialchars($parent['full_name'])?></h5>
        <p class="small text-muted mb-2">Родитель</p>
        <?php if($parent['kids']): ?><p class="small mb-1"><strong>Дети:</strong> <?=htmlspecialchars($parent['kids'])?></p><?php endif;?>
        <?php if($parent['phone']):?><p class="small mb-1"><strong>Телефон:</strong> <?=htmlspecialchars($parent['phone'])?></p><?php endif;?>
        <?php if($parent['email']):?><p class="small mb-1"><strong>Почта:</strong>   <?=htmlspecialchars($parent['email'])?></p><?php endif;?>
      </div>
    </div>

    <!-- смена пароля -->
    <div class="card mt-3">
      <div class="card-body">
        <h6 class="card-title">Смена пароля</h6>
        <?php if(isset($msg)): ?><div class="alert alert-info py-2"><?=$msg?></div><?php endif;?>

        <!-- кнопка -->
        <button id="showPassForm" class="btn btn-sm btn-outline-primary <?= $showForm?'d-none':''?>">Сменить пароль</button>

        <!-- форма -->
        <form id="passForm" class="<?= $showForm?'':'d-none'?> mt-3" method="post" autocomplete="off">
          <input type="hidden" name="action" value="change_pass">
          <div class="mb-2">
             <input type="password" name="pass1" class="form-control form-control-sm" placeholder="Новый пароль" required>
          </div>
          <div class="mb-2">
             <input type="password" name="pass2" class="form-control form-control-sm" placeholder="Повторите пароль" required>
          </div>
          <div class="d-flex justify-content-end gap-2">
             <button type="button" id="cancelPass" class="btn btn-sm btn-secondary">Отмена</button>
             <button class="btn btn-sm btn-primary">Сменить</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg d-flex align-items-center justify-content-center">
    <p class="text-muted">Вы можете посещать страницы воспитателей, чтобы читать новости группы.</p>
  </div>
</div>

<script>
/* показать / скрыть форму смены пароля */
const showBtn  = document.getElementById('showPassForm');
const formBox  = document.getElementById('passForm');
const cancelBt = document.getElementById('cancelPass');

if(showBtn){
  showBtn.addEventListener('click', ()=>{
     showBtn.classList.add('d-none');
     formBox.classList.remove('d-none');
     formBox.querySelector('input[name="pass1"]').focus();
  });
}
cancelBt.addEventListener('click', ()=>{
     formBox.classList.add('d-none');
     showBtn.classList.remove('d-none');
});
</script>

<?php elseif($minimal): /* fallback */ ?>
<div class="card shadow-sm p-4 text-center">
  <h4 class="mb-1"><?=$minimal['name']?></h4>
  <p class="text-muted mb-3"><?=$minimal['role']?></p>
  <p class="small text-muted">Подробный профиль появится после привязки учётки к базе данных.</p>
</div>
<?php endif; ?>

</main>

<footer class="bg-success text-white py-2 mt-auto">
  <div class="container small">© <?=date('Y')?> Детский сад «Ромашка»</div>
</footer>
</body>
</html>
