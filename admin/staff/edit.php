<?php
session_start();
if(!isset($_SESSION['user'])||$_SESSION['user']['role']!=='admin')exit('403');
require_once __DIR__.'/../../config.php';

$id=(int)($_GET['id']??0);
$emp=$pdo->prepare("SELECT * FROM staff WHERE id=?");$emp->execute([$id]);$emp=$emp->fetch();
if(!$emp)exit('404');

$err=$ok='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $name = trim($_POST['full_name']??'');
    $pos  = trim($_POST['position'] ??'');
    $hDate= $_POST['hire_date'] ??'';
    $fDate= $_POST['fire_date'] ??null;

    if(!$name||!$pos||!$hDate)$err='Заполните обязательные поля.';
    /* — фото — */
    $photo=$emp['photo_url'];
    if(!$err && !empty($_FILES['photo']['tmp_name'])){
        $tmp=$_FILES['photo']['tmp_name'];
        $ext=strtolower(pathinfo($_FILES['photo']['name'],PATHINFO_EXTENSION));
        if(!in_array($ext,['jpg','jpeg'])) $err='Только JPG.';
        else{
            $fname=uniqid().'.jpg';
            $dest=__DIR__.'/../../image/'.$fname;
            if(move_uploaded_file($tmp,$dest)) $photo='image/'.$fname;
            else $err='Не сохранилось фото.';
        }
    }
    if(!$err){
        $pdo->prepare("
            UPDATE staff
               SET full_name=?, position=?, hire_date=?, fire_date=?, photo_url=?
             WHERE id=?")
            ->execute([$name,$pos,$hDate,$fDate?:null,$photo,$id]);
        header('Location: ../index.php?section=staff');exit;
    }
}
?>
<!doctype html><html lang="ru"><head>
<meta charset="utf-8"><title>Редактировать сотрудника</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>.preview{width:120px;height:160px;object-fit:cover;border:1px solid #ccc;border-radius:.25rem;}</style>
</head><body class="p-4">
<h3 class="mb-4">Редактирование сотрудника (ID <?=$id?>)</h3>
<a href="../index.php?section=staff" class="btn btn-link mb-3">← Назад к списку</a>
<?php if($err):?><div class="alert alert-danger"><?=$err?></div><?php endif;?>
<form method="post" enctype="multipart/form-data" novalidate>
<div class="row">
  <div class="col-md-4">
     <?php if($emp['photo_url']):?>
       <img src="/<?=$emp['photo_url']?>" id="previewImg" class="preview mb-2">
     <?php else: ?>
       <img src="" id="previewImg" class="preview d-none mb-2">
     <?php endif;?>
     <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg" onchange="readURL(this);">
  </div>
  <div class="col-md-8">
     <div class="mb-3"><label class="form-label">ФИО *</label>
        <input type="text" name="full_name" class="form-control" required
               value="<?=htmlspecialchars($emp['full_name'])?>"></div>
     <div class="mb-3"><label class="form-label">Должность *</label>
        <input type="text" name="position" class="form-control" required
               value="<?=htmlspecialchars($emp['position'])?>"></div>
     <div class="mb-3"><label class="form-label">Дата приёма *</label>
        <input type="date" name="hire_date" class="form-control" required
               value="<?=$emp['hire_date']?>"></div>
     <div class="mb-3"><label class="form-label">Дата увольнения</label>
        <input type="date" name="fire_date" class="form-control"
               value="<?=$emp['fire_date']?>"></div>
     <button class="btn btn-warning">Сохранить</button>
  </div>
</div>
</form>
<script src="field_photo.js"></script>
</body></html>
