<?php
/* admin/news/edit.php — редактирование новости с по‑штучным управлением медиа
   - существующие изображения остаются, можно менять подпись / файл / удалять
   - новые изображения добавляются с live‑превью
*/
ini_set('display_errors',1); error_reporting(E_ALL);
session_start();
if(!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') exit('403');

require_once __DIR__.'/../../config.php';

$id = (int)($_GET['id'] ?? 0);
$art = $pdo->prepare("SELECT * FROM articles WHERE id=?"); $art->execute([$id]); $art = $art->fetch();
if(!$art) exit('404');

$media = $pdo->prepare("SELECT id,file_url,caption FROM media_files WHERE article_id=? ORDER BY uploaded_at");
$media->execute([$id]); $media = $media->fetchAll(PDO::FETCH_ASSOC);

$uploadDir = __DIR__.'/../../image/';   // куда сохраняем файлы
$err = '';

/* ────────────────── обработка формы ────────────────── */
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
   $title = trim($_POST['title'] ?? '');
   $body  = trim($_POST['body']  ?? '');
   $stat  = ($_POST['status'] ?? 'draft') === 'published' ? 1 : 0;

   if(!$title || !$body) throw new Exception('Заполните заголовок и текст.');

   /* обложка */
   $cover = $art['cover_image'];
   if(!empty($_FILES['cover']['tmp_name'])){
       $ext = strtolower(pathinfo($_FILES['cover']['name'], PATHINFO_EXTENSION));
       if(!in_array($ext,['jpg','jpeg','png'])) throw new Exception('Обложка — только jpg / png');
       if(!is_dir($uploadDir)) mkdir($uploadDir,0777,true);
       $cover = 'image/'.uniqid().'.'.$ext;
       move_uploaded_file($_FILES['cover']['tmp_name'], $uploadDir.basename($cover));
   }

   $pdo->beginTransaction();

   /* обновляем саму статью */
   $pdo->prepare("UPDATE articles
                     SET title=?, body=?, cover_image=?, status=?, updated_at=NOW()
                   WHERE id=?")
       ->execute([$title,$body,$cover?:null,$stat,$id]);

   /* ── существующие медиа ── */
   $exist = $_POST['media_existing'] ?? [];
   foreach($exist as $mid=>$info){
        $mid = (int)$mid;

        /* удалить? */
        if(isset($info['delete'])){
            $pdo->prepare("DELETE FROM media_files WHERE id=?")->execute([$mid]);
            continue;
        }

        /* заменить файл? */
        if(!empty($_FILES['media_existing']['tmp_name'][$mid]['file'])){
            $tmp = $_FILES['media_existing']['tmp_name'][$mid]['file'];
            if($tmp){
                $ext = strtolower(pathinfo($_FILES['media_existing']['name'][$mid]['file'], PATHINFO_EXTENSION));
                if(in_array($ext,['jpg','jpeg','png'])){
                    $fname = 'image/'.uniqid().'.'.$ext;
                    move_uploaded_file($tmp, $uploadDir.basename($fname));
                    $pdo->prepare("UPDATE media_files SET file_url=?, caption=? WHERE id=?")
                        ->execute([$fname, trim($info['caption']), $mid]);
                    continue;
                }
            }
        }
        /* просто подпись */
        $pdo->prepare("UPDATE media_files SET caption=? WHERE id=?")
            ->execute([trim($info['caption']), $mid]);
   }

   /* ── новые файлы ── */
   if(!empty($_FILES['media_files']['tmp_name'][0])){
        $ins = $pdo->prepare("INSERT INTO media_files(file_url,caption,uploaded_at,article_id)
                              VALUES(?,?,NOW(),?)");
        foreach($_FILES['media_files']['tmp_name'] as $k=>$tmp){
            if(!$tmp) continue;
            $ext = strtolower(pathinfo($_FILES['media_files']['name'][$k], PATHINFO_EXTENSION));
            if(!in_array($ext,['jpg','jpeg','png'])) continue;
            $fname = 'image/'.uniqid().'.'.$ext;
            move_uploaded_file($tmp, $uploadDir.basename($fname));
            $capt = trim($_POST['media_captions'][$k] ?? '');
            $ins->execute([$fname,$capt,$id]);
        }
   }

   $pdo->commit();
   header('Location: ../index.php?section=news'); exit;

 } catch(Exception $e){
   if($pdo->inTransaction()) $pdo->rollBack();
   $err = $e->getMessage();
 }
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Редактировать новость</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
 .thumb     {max-width:120px;border-radius:.25rem}
 .thumb-new {max-width:120px;border-radius:.25rem;margin-bottom:.5rem}
</style>
</head>
<body class="p-4">

<a href="../index.php?section=news" class="d-block mb-2">&lArr; Назад</a>
<h2 class="mb-4">Редактировать новость</h2>
<?php if($err): ?><div class="alert alert-danger"><?=$err?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data">
 <!-- заголовок / текст -->
 <div class="mb-3"><label class="form-label">Заголовок *</label>
   <input name="title" class="form-control" value="<?=htmlspecialchars($art['title'])?>"></div>

 <div class="mb-3"><label class="form-label">Текст *</label>
   <textarea name="body" rows="8" class="form-control"><?=htmlspecialchars($art['body'])?></textarea></div>

 <!-- обложка -->
 <div class="mb-3">
    <label class="form-label">Обложка (заменить)</label><br>
    <?php if($art['cover_image']): ?>
      <img src="/<?=$art['cover_image']?>" class="thumb mb-2"><br>
    <?php endif; ?>
    <input type="file" name="cover" class="form-control" accept=".jpg,.jpeg,.png">
 </div>

 <!-- статус -->
 <div class="mb-3">
    <label class="form-label">Статус</label>
    <select name="status" class="form-select">
        <option value="draft"      <?=$art['status']==0?'selected':''?>>Черновик</option>
        <option value="published" <?=$art['status']==1?'selected':''?>>Опубликовано</option>
    </select>
 </div>

 <!-- существующие медиа -->
 <hr><h5 class="mb-2">Прикреплённые изображения</h5>
 <?php foreach($media as $m): $mid=$m['id']; ?>
   <div class="border rounded p-2 mb-2">
      <img src="/<?=$m['file_url']?>" class="thumb mb-2 d-block">
      <input type="hidden" name="media_existing[<?=$mid?>][id]" value="<?=$mid?>">
      <div class="mb-2">
         <label class="form-label small mb-1">Подпись</label>
         <input type="text" name="media_existing[<?=$mid?>][caption]" class="form-control"
                value="<?=htmlspecialchars($m['caption'])?>">
      </div>
      <button type="button"
              class="btn btn-warning btn-sm"
              onclick="this.nextElementSibling.classList.toggle('d-none')">
              ✏️ Изменить
      </button>
      <div class="d-none mt-2">
          <input type="file" name="media_existing[<?=$mid?>][file]" class="form-control mb-2 chooser" accept=".jpg,.jpeg,.png">
          <div class="form-check">
             <input class="form-check-input" type="checkbox" value="1"
                    name="media_existing[<?=$mid?>][delete]" id="del<?=$mid?>">
             <label class="form-check-label small" for="del<?=$mid?>">Удалить изображение</label>
          </div>
      </div>
   </div>
 <?php endforeach; ?>

 <!-- новые файлы -->
 <hr><h5 class="mb-2">Добавить новые изображения</h5>
 <div id="newWrap"></div>
 <button type="button" id="addNew" class="btn btn-outline-secondary btn-sm mb-3">
    + файл
 </button>

 <br><button class="btn btn-success">Сохранить</button>
</form>

<!-- live preview -->
<script>
/* добавить блок для нового файла */
addNew.onclick = () => {
   const d = document.createElement('div');
   d.className = 'border rounded p-2 mb-2';
   d.innerHTML = `
      <input type="file" name="media_files[]" class="form-control mb-1 chooser" accept=".jpg,.jpeg,.png">
      <img class="thumb-new d-none">
      <input type="text" name="media_captions[]" class="form-control" placeholder="Подпись">`;
   newWrap.appendChild(d);
};

/* превью выбранных картинок */
document.addEventListener('change', e => {
   const inp = e.target;
   if(!inp.matches('.chooser')) return;

   const file = inp.files[0]; if(!file) return;
   const url  = URL.createObjectURL(file);

   let img = inp.nextElementSibling;
   if(img && img.tagName !== 'IMG') img = img.nextElementSibling;
   if(img && img.tagName === 'IMG'){
       img.src = url;
       img.classList.remove('d-none');
   }
});
</script>
</body>
</html>
