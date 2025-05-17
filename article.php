<?php
session_start();
require_once __DIR__.'/config.php';

$id = (int)($_GET['id'] ?? 0);
if(!$id){ header('Location: news.php'); exit; }

$user    = $_SESSION['user'] ?? null;
$isAdmin = $user && $user['role']==='admin';

/* ── статья ───────────────────────────────────────────── */
$sql = "
   SELECT id, title, body, cover_image, created_at, status
     FROM articles
    WHERE id = ?
";
if(!$isAdmin) $sql .= " AND status = 1";   // гости видят лишь опубликованные
$sql .= " LIMIT 1";

$st = $pdo->prepare($sql);
$st->execute([$id]);
$art = $st->fetch();
if(!$art){ header('Location: news.php'); exit; }

/* ── галерея ──────────────────────────────────────────── */
$gallery = $pdo->prepare("
    SELECT file_url, caption
      FROM media_files
     WHERE article_id = ?
  ORDER BY uploaded_at
");
$gallery->execute([$id]);
$gallery = $gallery->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8">
<title><?=htmlspecialchars($art['title'])?> | Новости</title>
<link rel="icon" type="image/png" href="/image/web_logo.png">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.article-cover{width:100%;aspect-ratio:16/9;object-fit:cover;margin-bottom:1rem;cursor:pointer}
.gallery-img {width:100%;aspect-ratio:16/9;object-fit:cover;cursor:pointer}
.modal-dialog{max-width:100%;height:100%;margin:0}
.modal-content{background:transparent;border:0;height:100%}
.modal-body{display:flex;align-items:center;justify-content:center;padding:0}
.modal-body img{max-width:100%;max-height:100%}
</style></head>
<body class="d-flex flex-column min-vh-100">

<header class="bg-success text-white py-2">
  <div class="container d-flex justify-content-between align-items-center">
     <h1 class="h5 m-0 fw-bold">ГКДОУ «Ромашка»</h1>
     <a href="news.php" class="btn btn-light btn-sm">К новостям</a>
  </div>
</header>

<main class="container my-4 flex-grow-1">
 <h1 class="mb-1"><?=htmlspecialchars($art['title'])?></h1>
 <p class="small text-muted"><?=date('d.m.Y',strtotime($art['created_at']))?></p>

 <?php if($art['cover_image']): ?>
   <img src="/<?=htmlspecialchars($art['cover_image'])?>" class="article-cover rounded"
        data-bs-toggle="modal" data-bs-target="#lightboxModal" alt="">
 <?php endif; ?>

 <div><?=nl2br($art['body'])?></div>

 <?php if($gallery): ?>
   <hr><h5 class="mt-4 mb-3">Фотогалерея</h5>
   <div class="row g-3">
     <?php foreach($gallery as $g): ?>
       <div class="col-md-6 col-lg-4">
          <img src="/<?=htmlspecialchars($g['file_url'])?>" class="gallery-img rounded"
               data-caption="<?=htmlspecialchars($g['caption'])?>"
               data-bs-toggle="modal" data-bs-target="#lightboxModal" alt="">
          <?php if($g['caption']): ?>
              <p class="small mt-1"><?=htmlspecialchars($g['caption'])?></p>
          <?php endif; ?>
       </div>
     <?php endforeach;?>
   </div>
 <?php endif; ?>
</main>

<footer class="bg-success text-white py-2 mt-auto">
  <div class="container small">© <?=date('Y')?> Детский сад «Ромашка»</div>
</footer>

<!-- лайтбокс -->
<div class="modal fade" id="lightboxModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content" data-bs-dismiss="modal">
     <div class="modal-body p-0"><img src="" id="lightboxImage" alt=""></div>
  </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('click',e=>{
  if(e.target.dataset.bsTarget === '#lightboxModal'){
     const img=document.getElementById('lightboxImage');
     img.src=e.target.src;
     img.alt=e.target.dataset.caption||'';
  }
});
</script>
</body></html>
