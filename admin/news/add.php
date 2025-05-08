<?php
ini_set('display_errors',1); error_reporting(E_ALL);
session_start();
if(!isset($_SESSION['user'])||$_SESSION['user']['role']!=='admin') exit('403');
require_once __DIR__.'/../../config.php';

$err=''; $uploadDir=__DIR__.'/../../image/';

if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    $title = trim($_POST['title'] ?? '');
    $body  = trim($_POST['body']  ?? '');
    $stat  = ($_POST['status'] ?? 'draft') === 'published' ? 1 : 0;   // 0 = draft, 1 = published
    if(!$title || !$body) throw new Exception('Заполните заголовок и текст.');

    /* cover */
    $cover='';
    if (!empty($_FILES['cover']['tmp_name'])) {
        $ext=strtolower(pathinfo($_FILES['cover']['name'],PATHINFO_EXTENSION));
        if(!in_array($ext,['jpg','jpeg','png'])) throw new Exception('Обложка: JPG/PNG.');
        if(!is_dir($uploadDir)) mkdir($uploadDir,0777,true);
        $cover='image/'.uniqid().'.'.$ext;
        move_uploaded_file($_FILES['cover']['tmp_name'],$uploadDir.basename($cover));
    }

    $pdo->beginTransaction();
    $pdo->prepare("INSERT INTO articles(title,body,cover_image,status) VALUES(?,?,?,?)")
        ->execute([$title,$body,$cover?:null,$stat]);
    $aid=$pdo->lastInsertId();

    /* media */
    if(!empty($_FILES['media_files']['tmp_name'][0])){
       $ins=$pdo->prepare("INSERT INTO media_files(file_url,caption,uploaded_at,article_id)
                           VALUES(?,?,NOW(),?)");
       foreach($_FILES['media_files']['tmp_name'] as $k=>$tmp){
           if(!$tmp) continue;
           $ext=strtolower(pathinfo($_FILES['media_files']['name'][$k],PATHINFO_EXTENSION));
           if(!in_array($ext,['jpg','jpeg','png'])) continue;
           $fname='image/'.uniqid().'.'.$ext;
           move_uploaded_file($tmp,$uploadDir.basename($fname));
           $capt=trim($_POST['media_captions'][$k]??'');
           $ins->execute([$fname,$capt,$aid]);
       }
    }
    $pdo->commit();
    header('Location: ../index.php?section=news'); exit;

  }catch(Exception $e){
    if($pdo->inTransaction()) $pdo->rollBack();
    $err=$e->getMessage();
  }
}
?>
<!doctype html><html lang="ru"><head>
<meta charset="utf-8"><title>Новая новость</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="p-4">
<a href="../index.php?section=news" class="d-block mb-2">&lArr; Назад</a>
<h2 class="mb-4">Добавить новость</h2>
<?php if($err):?><div class="alert alert-danger"><?=$err?></div><?php endif;?>
<form method="post" enctype="multipart/form-data">
  <div class="mb-3"><label class="form-label">Заголовок *</label>
     <input name="title" class="form-control" value="<?=htmlspecialchars($_POST['title']??'')?>"></div>
  <div class="mb-3"><label class="form-label">Текст *</label>
     <textarea name="body" rows="8" class="form-control"><?=htmlspecialchars($_POST['body']??'')?></textarea></div>
  <div class="mb-3"><label class="form-label">Обложка (jpg/png)</label>
     <input type="file" name="cover" class="form-control" accept=".jpg,.jpeg,.png"></div>
  <div class="mb-3"><label class="form-label">Статус</label>
     <select name="status" class="form-select">
       <option value="draft">Черновик</option>
       <option value="published" <?=($_POST['status']??'')==='published'?'selected':''?>>Опубликовано</option>
     </select></div>

  <hr><h5 class="mb-2">Доп. изображения</h5>
  <div id="medWrap"></div>
  <button type="button" id="addMed" class="btn btn-outline-secondary btn-sm mb-3">+ файл</button>
  <br><button class="btn btn-success">Создать</button>
</form>

<script>
addMed.onclick=()=>{
 const d=document.createElement('div');
 d.className='border rounded p-2 mb-2';
 d.innerHTML=`<input type="file" name="media_files[]" class="form-control mb-1" accept=".jpg,.jpeg,.png">
               <input type="text" name="media_captions[]" class="form-control" placeholder="Подпись">`;
 medWrap.appendChild(d);
};
</script>
</body></html>
