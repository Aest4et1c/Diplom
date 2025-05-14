<?php
/**
 * news.php — список общих новостей сайта
 * Теперь показываются ТОЛЬКО статьи, у которых staff_id IS NULL
 * (то есть созданные администрацией, а не воспитателями).
 */

session_start();
require_once __DIR__.'/config.php';

$user    = $_SESSION['user'] ?? null;
$isAdmin = $user && $user['role']==='admin';

/* ── пагинация ── */
$perPage = 6;
$page    = max(1,(int)($_GET['page']??1));
$offset  = ($page-1)*$perPage;

/* ── сколько статей всего ── */
$totalSql = "
    SELECT COUNT(*)
      FROM articles
     WHERE staff_id IS NULL";
if(!$isAdmin) $totalSql .= " AND status = 1";
$total = $pdo->query($totalSql)->fetchColumn();
$pages = (int)ceil($total/$perPage);

/* ── выборка ── */
$sql = "
    SELECT id,title,cover_image,created_at,status,
           SUBSTRING(body,1,250) excerpt
      FROM articles
     WHERE staff_id IS NULL";
if(!$isAdmin) $sql .= " AND status = 1";
$sql .= "
  ORDER BY created_at DESC
     LIMIT :lim OFFSET :off";
$st = $pdo->prepare($sql);
$st->bindValue(':lim',$perPage,PDO::PARAM_INT);
$st->bindValue(':off',$offset ,PDO::PARAM_INT);
$st->execute();
$articles = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Новости | Детский сад «Ромашка»</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  .news-card:hover{text-decoration:none;box-shadow:0 .5rem 1rem rgba(0,0,0,.15);}
  .news-cover{width:100%;aspect-ratio:16/9;object-fit:cover;}
</style>
</head>
<body class="d-flex flex-column min-vh-100">
<!-- ── шапка ── -->
<header class="bg-success text-white py-2">
  <div class="container d-flex justify-content-between align-items-center">
    <h1 class="h5 m-0 fw-bold">ГКДОУ «Ромашка»</h1>
    <div>
      <?php if(!$user): ?>
        <a class="btn btn-light btn-sm" href="login.php">Вход</a>
      <?php else: ?>
        <span class="me-2">Здравствуйте, <strong><?=htmlspecialchars($user['name'])?></strong></span>
        <?php if($isAdmin): ?>
          <a href="/admin/" class="btn btn-warning btn-sm me-2">Админ‑панель</a>
        <?php else: ?>
          <a href="/profile.php" class="btn btn-primary btn-sm me-2">Личный кабинет</a>
        <?php endif;?>
        <a class="btn btn-light btn-sm" href="logout.php">Выход</a>
      <?php endif;?>
    </div>
  </div>
</header>

<!-- ── меню ── -->
<nav class="bg-white border-bottom">
 <div class="container">
  <ul class="nav nav-pills justify-content-center">
    <li class="nav-item"><a class="nav-link"        href="index.php">Наш детский сад</a></li>
    <li class="nav-item"><a class="nav-link active" href="news.php">Новости</a></li>
    <li class="nav-item"><a class="nav-link"        href="groups.php">Группы</a></li>
    <li class="nav-item"><a class="nav-link"        href="staff.php">Сотрудники</a></li>
    <li class="nav-item"><a class="nav-link"        href="contacts.php">Контакты</a></li>
  </ul>
 </div>
</nav>

<main class="container my-4 flex-grow-1">
 <h2 class="mb-4">Новости</h2>

 <?php if(!$articles): ?>
   <div class="alert alert-info">Публикаций пока нет.</div>
 <?php else: ?>
   <div class="row g-4">
   <?php foreach($articles as $a):
         $href = ($isAdmin && $a['status']==0)
               ? "/admin/news/edit.php?id={$a['id']}"
               : "article.php?id={$a['id']}";
   ?>
     <div class="col-md-6 col-lg-4">
       <a href="<?=$href?>" class="news-card card h-100 text-dark <?=($a['status']==0?'border-secondary':'')?>">
         <?php if($a['cover_image']): ?>
           <img src="/<?=htmlspecialchars($a['cover_image'])?>" class="news-cover card-img-top" alt="">
         <?php endif; ?>
         <div class="card-body">
           <h5 class="card-title mb-2">
             <?=htmlspecialchars($a['title'])?>
             <?php if($isAdmin && $a['status']==0): ?>
               <span class="badge bg-secondary">Черновик</span>
             <?php endif;?>
           </h5>
           <p class="card-text small"><?=htmlspecialchars($a['excerpt'])?>…</p>
         </div>
         <div class="card-footer small text-muted"><?=date('d.m.Y',strtotime($a['created_at']))?></div>
       </a>
     </div>
   <?php endforeach;?>
   </div>

   <?php if($pages>1): ?>
     <nav class="mt-4">
      <ul class="pagination justify-content-center">
        <?php for($p=1;$p<=$pages;$p++): ?>
           <li class="page-item <?=$p==$page?'active':''?>">
             <a class="page-link" href="?page=<?=$p?>"><?=$p?></a>
           </li>
        <?php endfor;?>
      </ul>
     </nav>
   <?php endif;?>
 <?php endif;?>
</main>

<footer class="bg-success text-white py-2 mt-auto">
 <div class="container small">© <?=date('Y')?> Детский сад «Ромашка»</div>
</footer>
</body>
</html>
