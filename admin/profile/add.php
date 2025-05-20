<?php
/* admin/profile/add.php — добавление новости воспитателем + прикрепление документов */

ini_set('display_errors',1); error_reporting(E_ALL);
session_start();

/* ——— проверка роли ——— */
$sessionUser=$_SESSION['user']??null;
if(!is_array($sessionUser) || ($sessionUser['role']??'')!=='teacher') exit('403');

$staffId=(int)($sessionUser['staff_id']??0);
if($staffId<1) exit('Некорректный staff_id');

require_once __DIR__.'/../../config.php';

$err='';
$imgDir =__DIR__.'/../../image/';   // картинки
$fileDir=__DIR__.'/../../files/';   // документы

/* ─── POST ───────────────────────────────────────────────────────── */
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
   $title=trim($_POST['title']??'');
   $body =trim($_POST['body'] ??'');
   $stat =($_POST['status']??'draft')==='published'?1:0;
   if(!$title||!$body) throw new Exception('Заполните заголовок и текст.');

   /* обложка */
   $cover='';
   if(!empty($_FILES['cover']['tmp_name'])){
       $ext=strtolower(pathinfo($_FILES['cover']['name'],PATHINFO_EXTENSION));
       if(!in_array($ext,['jpg','jpeg','png'])) throw new Exception('Обложка — JPG/PNG.');
       if(!is_dir($imgDir)) mkdir($imgDir,0777,true);
       $cover='image/'.uniqid().'.'.$ext;
       move_uploaded_file($_FILES['cover']['tmp_name'],$imgDir.basename($cover));
   }

   $pdo->beginTransaction();

   /* статья */
   $pdo->prepare("INSERT INTO articles(staff_id,title,body,cover_image,status)
                  VALUES(?,?,?,?,?)")
       ->execute([$staffId,$title,$body,$cover?:null,$stat]);
   $aid=$pdo->lastInsertId();

   /* изображения */
   if(!empty($_FILES['media_files']['tmp_name'][0])){
       $ins=$pdo->prepare("
          INSERT INTO media_files(file_url,caption,uploaded_at,article_id)
          VALUES(?, ?, NOW(), ?)");
       foreach($_FILES['media_files']['tmp_name'] as $k=>$tmp){
           if(!$tmp) continue;
           $ext=strtolower(pathinfo($_FILES['media_files']['name'][$k],PATHINFO_EXTENSION));
           if(!in_array($ext,['jpg','jpeg','png'])) continue;
           if(!is_dir($imgDir)) mkdir($imgDir,0777,true);
           $fname='image/'.uniqid().'.'.$ext;
           move_uploaded_file($tmp,$imgDir.basename($fname));
           $capt=trim($_POST['media_captions'][$k]??'');
           $ins->execute([$fname,$capt,$aid]);
       }
   }

   /* документы */
   if(!empty($_FILES['docs']['tmp_name'][0])){
       $ins=$pdo->prepare("
          INSERT INTO media_files(file_url,caption,uploaded_at,article_id)
          VALUES(?, ?, NOW(), ?)");
       foreach($_FILES['docs']['tmp_name'] as $k=>$tmp){
           if(!$tmp) continue;
           $oname=$_FILES['docs']['name'][$k];
           $ext=strtolower(pathinfo($oname,PATHINFO_EXTENSION));
           if(in_array($ext,['jpg','jpeg','png','gif','webp'])) continue; // пропускаем картинки
           if($_FILES['docs']['size'][$k] > 15*1024*1024) continue;       // >15 МБ игнор
           if(!is_dir($fileDir)) mkdir($fileDir,0777,true);
           $fname='files/'.uniqid().'.'.$ext;
           move_uploaded_file($tmp,$fileDir.basename($fname));
           $ins->execute([$fname,$oname,$aid]);   // caption = оригинальное имя
       }
   }

   $pdo->commit();
   header('Location: /profile.php?staff_id='.$staffId); exit;

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
<title>Новая новость</title>
<link rel="icon" type="image/png" href="/image/web_logo.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>.thumb-new{max-width:120px;border-radius:.25rem;margin-bottom:.5rem}</style>
</head>
<body class="p-4">

<a href="/profile.php?staff_id=<?=$staffId?>" class="d-block mb-2">&lArr; Назад</a>
<h2 class="mb-4">Добавить новость</h2>

<?php if($err): ?><div class="alert alert-danger"><?=$err?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <!-- заголовок / текст -->
  <div class="mb-3">
     <label class="form-label">Заголовок *</label>
     <input name="title" class="form-control" value="<?=htmlspecialchars($_POST['title']??'')?>">
  </div>

  <div class="mb-3">
     <label class="form-label">Текст *</label>
     <textarea name="body" rows="8" class="form-control"><?=htmlspecialchars($_POST['body']??'')?></textarea>
  </div>

  <!-- обложка -->
  <div class="mb-3">
     <label class="form-label">Обложка (jpg/png)</label>
     <input type="file" name="cover" class="form-control" accept=".jpg,.jpeg,.png">
  </div>

  <!-- статус -->
  <div class="mb-3">
     <label class="form-label">Статус</label>
     <select name="status" class="form-select">
       <option value="draft">Черновик</option>
       <option value="published" <?=($_POST['status']??'')==='published'?'selected':''?>>Опубликовано</option>
     </select>
  </div>

  <!-- изображения -->
  <hr><h5 class="mb-2">Доп. изображения</h5>
  <div id="imgWrap"></div>
  <button type="button" id="addImg" class="btn btn-outline-secondary btn-sm mb-3">+ изображение</button>

  <!-- документы -->
  <hr><h5 class="mb-2">Файлы (PDF, DOCX, ZIP…)</h5>
  <div id="docWrap"></div>
  <button type="button" id="addDoc" class="btn btn-outline-secondary btn-sm mb-3">+ файл</button>

  <br><button class="btn btn-success">Создать</button>
</form>

<script>
/* изображения + превью */
addImg.onclick=()=>{
  const d=document.createElement('div');
  d.className='border rounded p-2 mb-2';
  d.innerHTML=`<input type="file" name="media_files[]" class="form-control mb-1 chooser" accept=".jpg,.jpeg,.png">
               <img class="thumb-new d-none">
               <input type="text" name="media_captions[]" class="form-control" placeholder="Подпись">`;
  imgWrap.appendChild(d);
};
document.addEventListener('change',e=>{
  const inp=e.target;
  if(!inp.matches('.chooser'))return;
  const f=inp.files[0]; if(!f)return;
  const url=URL.createObjectURL(f);
  const img=inp.nextElementSibling;
  img.src=url; img.classList.remove('d-none');
});

/* документы */
addDoc.onclick=()=>{
  const d=document.createElement('div');
  d.className='border rounded p-2 mb-2';
  d.innerHTML=`<input type="file" name="docs[]" class="form-control">`;
  docWrap.appendChild(d);
};
</script>
</body>
</html>
