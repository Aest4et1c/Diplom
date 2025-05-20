<?php
/* admin/profile/edit.php — воспитатель редактирует СВОЮ новость
   + теперь можно прикреплять и документы (pdf/doc/zip…)
*/

ini_set('display_errors',1); error_reporting(E_ALL);
session_start();

/* — проверка прав — */
$u=$_SESSION['user']??null;
if(!$u || $u['role']!=='teacher' || empty($u['staff_id'])) exit('403');

require_once __DIR__.'/../../config.php';

$id=(int)($_GET['id']??0);

/* — статья — */
$art=$pdo->prepare("SELECT * FROM articles WHERE id=?"); $art->execute([$id]);
$art=$art->fetch();
if(!$art) exit('404');
if($art['staff_id']!=$u['staff_id']) exit('403');

/* — все медиа — */
$media=$pdo->prepare("
  SELECT id,file_url,caption
    FROM media_files
   WHERE article_id=?
ORDER BY uploaded_at");
$media->execute([$id]); $media=$media->fetchAll(PDO::FETCH_ASSOC);

/* разбиваем: images vs docs */
$imgMedia=$docMedia=[];
foreach($media as $m){
   if(preg_match('/\.(jpe?g|png|gif|webp)$/i',$m['file_url']))
       $imgMedia[]=$m;
   else
       $docMedia[]=$m;
}

$imgDir = __DIR__.'/../../image/';
$fileDir= __DIR__.'/../../files/';
$err='';

/* ——— обработка формы ——— */
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
   $title=trim($_POST['title']??'');
   $body =trim($_POST['body'] ??'');
   $stat =($_POST['status']??'draft')==='published'?1:0;
   if(!$title||!$body) throw new Exception('Заполните заголовок и текст.');

   /* обложка */
   $cover=$art['cover_image'];
   if(!empty($_FILES['cover']['tmp_name'])){
       $ext=strtolower(pathinfo($_FILES['cover']['name'],PATHINFO_EXTENSION));
       if(!in_array($ext,['jpg','jpeg','png'])) throw new Exception('Обложка — JPG/PNG');
       if(!is_dir($imgDir)) mkdir($imgDir,0777,true);
       $cover='image/'.uniqid().'.'.$ext;
       move_uploaded_file($_FILES['cover']['tmp_name'],$imgDir.basename($cover));
   }

   $pdo->beginTransaction();

   /* статья */
   $pdo->prepare("UPDATE articles
                     SET title=?,body=?,cover_image=?,status=?,updated_at=NOW()
                   WHERE id=?")
       ->execute([$title,$body,$cover?:null,$stat,$id]);

   /* существующие медиа (только подпись/удаление; замена не трогаем для docs) */
   $exist=$_POST['media_existing']??[];
   foreach($exist as $mid=>$info){
        $mid=(int)$mid;
        if(isset($info['delete'])){
            $pdo->prepare("DELETE FROM media_files WHERE id=?")->execute([$mid]);
            continue;
        }
        $pdo->prepare("UPDATE media_files SET caption=? WHERE id=?")
            ->execute([trim($info['caption']),$mid]);
   }

   /* новые изображения */
   if(!empty($_FILES['media_files']['tmp_name'][0])){
        $ins=$pdo->prepare("
          INSERT INTO media_files(file_url,caption,uploaded_at,article_id)
          VALUES(?,?,NOW(),?)");
        foreach($_FILES['media_files']['tmp_name'] as $k=>$tmp){
            if(!$tmp) continue;
            $ext=strtolower(pathinfo($_FILES['media_files']['name'][$k],PATHINFO_EXTENSION));
            if(!in_array($ext,['jpg','jpeg','png'])) continue;
            if(!is_dir($imgDir)) mkdir($imgDir,0777,true);
            $fname='image/'.uniqid().'.'.$ext;
            move_uploaded_file($tmp,$imgDir.basename($fname));
            $capt=trim($_POST['media_captions'][$k]??'');
            $ins->execute([$fname,$capt,$id]);
        }
   }

   /* новые документы */
   if(!empty($_FILES['docs']['tmp_name'][0])){
        $ins=$pdo->prepare("
          INSERT INTO media_files(file_url,caption,uploaded_at,article_id)
          VALUES(?,?,NOW(),?)");
        foreach($_FILES['docs']['tmp_name'] as $k=>$tmp){
            if(!$tmp) continue;
            $oname=$_FILES['docs']['name'][$k];
            $ext=strtolower(pathinfo($oname,PATHINFO_EXTENSION));
            if(in_array($ext,['jpg','jpeg','png','gif','webp'])) continue;
            if($_FILES['docs']['size'][$k]>15*1024*1024) continue;
            if(!is_dir($fileDir)) mkdir($fileDir,0777,true);
            $fname='files/'.uniqid().'.'.$ext;
            move_uploaded_file($tmp,$fileDir.basename($fname));
            $ins->execute([$fname,$oname,$id]);
        }
   }

   $pdo->commit();
   header('Location: /admin/profile/article.php?id='.$id); exit;

 }catch(Exception $e){
   if($pdo->inTransaction()) $pdo->rollBack();
   $err=$e->getMessage();
 }
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Редактировать новость</title>
<link rel="icon" type="image/png" href="/image/web_logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
 .thumb{max-width:120px;border-radius:.25rem}
 .thumb-new{max-width:120px;border-radius:.25rem;margin-bottom:.5rem}
 .file-block{background:#f8f9fa;padding:.5rem .75rem;border-radius:.25rem;font-size:.9rem}
</style>
</head>
<body class="p-4">

<a href="/profile.php?staff_id=<?=$u['staff_id']?>" class="d-block mb-2">&lArr; Назад</a>
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

 <!-- существующие изображения -->
 <hr><h5 class="mb-2">Прикреплённые изображения</h5>
 <?php foreach($imgMedia as $m): $mid=$m['id']; ?>
   <div class="border rounded p-2 mb-2">
      <img src="/<?=$m['file_url']?>" class="thumb mb-2 d-block">
      <input type="hidden" name="media_existing[<?=$mid?>][id]" value="<?=$mid?>">
      <div class="mb-2">
         <label class="form-label small mb-1">Подпись</label>
         <input type="text" name="media_existing[<?=$mid?>][caption]" class="form-control"
                value="<?=htmlspecialchars($m['caption'])?>">
      </div>
      <div class="form-check mb-2">
         <input class="form-check-input" type="checkbox" value="1"
                name="media_existing[<?=$mid?>][delete]" id="del<?=$mid?>">
         <label class="form-check-label small" for="del<?=$mid?>">Удалить</label>
      </div>
   </div>
 <?php endforeach; ?>

 <!-- существующие документы -->
 <?php if($docMedia): ?>
   <hr><h5 class="mb-2">Прикреплённые файлы</h5>
   <?php foreach($docMedia as $d): ?>
     <div class="file-block d-flex justify-content-between align-items-center mb-2">
        <span><?=htmlspecialchars($d['caption'] ?: basename($d['file_url']))?></span>
        <div class="form-check ms-3">
           <input class="form-check-input" type="checkbox" value="1"
                  name="media_existing[<?=$d['id']?>][delete]" id="del<?=$d['id']?>">
           <label class="form-check-label small" for="del<?=$d['id']?>">Удалить</label>
        </div>
     </div>
   <?php endforeach; ?>
 <?php endif; ?>

 <!-- новые изображения -->
 <hr><h5 class="mb-2">Добавить изображения</h5>
 <div id="newImgWrap"></div>
 <button type="button" id="addImg" class="btn btn-outline-secondary btn-sm mb-3">+ изображение</button>

 <!-- новые документы -->
 <hr><h5 class="mb-2">Добавить файлы (PDF, DOCX…)</h5>
 <div id="newDocWrap"></div>
 <button type="button" id="addDoc" class="btn btn-outline-secondary btn-sm mb-3">+ файл</button>

 <br><button class="btn btn-success">Сохранить</button>
</form>

<script>
/* новые изображения */
addImg.onclick=()=>{
  const d=document.createElement('div');
  d.className='border rounded p-2 mb-2';
  d.innerHTML=`<input type="file" name="media_files[]" class="form-control mb-1 chooser" accept=".jpg,.jpeg,.png">
               <img class="thumb-new d-none">
               <input type="text" name="media_captions[]" class="form-control" placeholder="Подпись">`;
  newImgWrap.appendChild(d);
};
document.addEventListener('change',e=>{
  const inp=e.target;
  if(!inp.matches('.chooser'))return;
  const f=inp.files[0]; if(!f)return;
  const url=URL.createObjectURL(f);
  const img=inp.nextElementSibling;
  img.src=url; img.classList.remove('d-none');
});

/* новые документы */
addDoc.onclick=()=>{
  const d=document.createElement('div');
  d.className='border rounded p-2 mb-2';
  d.innerHTML=`<input type="file" name="docs[]" class="form-control">`;
  newDocWrap.appendChild(d);
};
</script>
</body>
</html>
