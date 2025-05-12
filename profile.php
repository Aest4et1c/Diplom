<?php
/* profile.php  —  компактный, стилизованный профиль */

session_start();
require_once __DIR__.'/config.php';

$user = $_SESSION['user'] ?? null;
if (!$user) { header('Location: login.php'); exit; }

$role = $user['role'];                      // admin | teacher | parent
$staffId  = (int)($_GET['staff_id']  ?? 0);
$parentId = (int)($_GET['parent_id'] ?? 0);
if(!$staffId && !$parentId){
   if($role==='teacher' && $user['staff_id'])  $staffId  = $user['staff_id'];
   if($role==='parent'  && $user['parent_id']) $parentId = $user['parent_id'];
}

/* ---- сотрудник ---- */
if($staffId){
    $s = $pdo->prepare("
        SELECT s.full_name, s.position, s.photo_url,
               IFNULL(GROUP_CONCAT(g.name SEPARATOR ', '),'') groups
          FROM staff s
     LEFT JOIN group_staff gs ON gs.staff_id=s.id
     LEFT JOIN groups g ON g.id=gs.group_id
         WHERE s.id=? GROUP BY s.id");
    $s->execute([$staffId]);
    $staff=$s->fetch();

    $posts=[];
    if($staff){
        $p=$pdo->prepare("SELECT id,title,cover_image,created_at
                            FROM articles WHERE staff_id=? ORDER BY created_at DESC");
        $p->execute([$staffId]); $posts=$p->fetchAll();
    }
}

/* ---- родитель ---- */
if($parentId){
    $p=$pdo->prepare("
        SELECT p.full_name,p.phone,p.email,p.address,
               IFNULL(GROUP_CONCAT(k.full_name SEPARATOR ', '),'') kids
          FROM parents p
     LEFT JOIN parent_kid pk ON pk.parent_id=p.id
     LEFT JOIN kids k ON k.id=pk.kid_id
         WHERE p.id=? GROUP BY p.id");
    $p->execute([$parentId]);
    $parent=$p->fetch();
}

/* ---- fallback ---- */
$minimal=null;
if(!$staffId && !$parentId){
   $minimal=[
     'name'=>htmlspecialchars($user['name'] ?? $user['username']),
     'role'=>($role==='teacher'?'Воспитатель':($role==='parent'?'Родитель':ucfirst($role)))
   ];
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Профиль</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
 .avatar   {width:170px;height:170px;object-fit:cover;border-radius:.5rem;}
 .post-img {width:100%;aspect-ratio:16/9;object-fit:cover;border-radius:.35rem;}
 .post-card{border:0;border-left:4px solid #B36B32;background:#fff;}
 .post-card:hover{box-shadow:0 .5rem 1rem rgba(0,0,0,.10);text-decoration:none}
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

<?php if(!empty($staff)): /* ─────────────────── воспитатель ─────────────────── */ ?>
  <div class="row g-4 flex-lg-nowrap">
     <!-- левая колонка -->
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

     <!-- правая колонка -->
     <div class="col-lg">
       <?php if($posts): ?>
         <h5 class="mb-3">Записи</h5>
         <div class="row g-4">
         <?php foreach($posts as $pr): ?>
           <div class="col-md-6 col-lg-4">
             <a href="article.php?id=<?=$pr['id']?>" class="post-card d-block p-3 text-dark">
               <?php if($pr['cover_image']): ?>
                  <img src="/<?=htmlspecialchars($pr['cover_image'])?>" class="post-img mb-2" alt="">
               <?php endif;?>
               <h6 class="mb-1"><?=htmlspecialchars($pr['title'])?></h6>
               <span class="small text-muted"><?=date('d.m.Y',strtotime($pr['created_at']))?></span>
             </a>
           </div>
         <?php endforeach;?>
         </div>
       <?php else: ?>
         <div class="alert alert-info">Записей ещё нет.</div>
       <?php endif;?>
     </div>
  </div>

<?php elseif(!empty($parent)): /* ─────────────────── родитель ─────────────────── */ ?>
  <div class="row g-4 flex-lg-nowrap">
     <div class="col-lg-4">
        <div class="card shadow-sm">
           <div class="card-body text-center">
              <img src="/uploads/photos/placeholder.jpg" class="avatar mb-3" alt="">
              <h5 class="mb-0"><?=htmlspecialchars($parent['full_name'])?></h5>
              <p class="small text-muted mb-2">Родитель</p>
              <?php if($parent['kids']): ?><p class="small mb-1"><strong>Дети:</strong> <?=htmlspecialchars($parent['kids'])?></p><?php endif;?>
              <?php if($parent['phone']):?><p class="small mb-1"><strong>Телефон:</strong> <?=htmlspecialchars($parent['phone'])?></p><?php endif;?>
              <?php if($parent['email']):?><p class="small mb-1"><strong>Почта:</strong> <?=htmlspecialchars($parent['email'])?></p><?php endif;?>
           </div>
        </div>
     </div>
     <div class="col-lg d-flex align-items-center justify-content-center">
        <p class="text-muted">Дополнительные функции личного кабинета родителя появятся позднее.</p>
     </div>
  </div>

<?php else: /* ─────────────────── fallback ─────────────────── */ ?>
  <div class="card shadow-sm p-4 text-center">
      <h4 class="mb-1"><?= $minimal['name'] ?></h4>
      <p class="text-muted mb-3"><?= $minimal['role'] ?></p>
      <p class="small text-muted">Подробный профиль появится после привязки учётки к базе данных.</p>
  </div>
<?php endif; ?>

</main>

<footer class="bg-success text-white py-2 mt-auto">
 <div class="container small">© <?=date('Y')?> Детский сад «Ромашка»</div>
</footer>
</body>
</html>
