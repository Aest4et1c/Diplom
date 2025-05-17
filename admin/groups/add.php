<?php
// admin/groups/add.php  —  создание новой группы
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') exit('403');
require_once __DIR__.'/../../config.php';

/* свободные педагоги (не прикреплены ни к какой группе) */
$free = $pdo->query("
   SELECT id, full_name
     FROM staff
    WHERE id NOT IN (SELECT staff_id FROM group_staff)
 ORDER BY full_name
")->fetchAll(PDO::FETCH_ASSOC);

$err = '';

/* ───── POST: сохраняем ───── */
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $name  = trim($_POST['g_name']  ?? '');
    $icon  = trim($_POST['g_icon']  ?? '');
    $room  = trim($_POST['g_room']  ?? '');
    $ageF  = (int)($_POST['g_from'] ?? 0);
    $ageT  = (int)($_POST['g_to']   ?? 0);
    $descr = trim($_POST['g_descr'] ?? '');

    $t1 = (int)($_POST['teacher1'] ?? 0);
    $t2 = (int)($_POST['teacher2'] ?? 0);
    $teachers = array_unique(array_filter([$t1,$t2]));

    /* минимальная валидация */
    if(!$name)           $err='Укажите название.';
    elseif($ageF>=$ageT) $err='Возраст "от" должен быть меньше "до".';

    if(!$err){
        try{
            $pdo->beginTransaction();

            /* группа */
            $ins=$pdo->prepare("
               INSERT INTO groups(name,icon,room_number,age_from,age_to,description)
               VALUES(?,?,?,?,?,?)");
            $ins->execute([$name,$icon,$room,$ageF,$ageT,$descr]);
            $gid = $pdo->lastInsertId();

            /* педагоги */
            if($teachers){
               $gs=$pdo->prepare("INSERT INTO group_staff(group_id,staff_id) VALUES(?,?)");
               foreach($teachers as $sid) $gs->execute([$gid,$sid]);
            }
            $pdo->commit();
            header('Location: ../index.php?section=groups'); exit;
        }catch(Exception $e){
            $pdo->rollBack();
            $err='Ошибка: '.$e->getMessage();
        }
    }
}
?>
<!doctype html><html lang="ru"><head>
<meta charset="utf-8"><title>Новая группа</title>
<link rel="icon" type="image/png" href="/image/web_logo.png">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head><body class="p-4">

<a href="../index.php?section=groups" class="d-block mb-2">&lArr; Назад к списку групп</a>

<h2 class="mb-4">Добавить новую группу</h2>

<?php if($err): ?><div class="alert alert-danger"><?=$err?></div><?php endif;?>

<form method="post" novalidate>
 <div class="row g-4">
   <div class="col-md-6">
     <div class="card p-3">
        <h5 class="mb-3">Основная информация</h5>

        <div class="mb-3"><label class="form-label">Название *</label>
          <input name="g_name" class="form-control" required value="<?=htmlspecialchars($_POST['g_name']??'')?>"></div>

        <div class="row g-2">
          <div class="col-md"><label class="form-label">Комната</label>
              <input name="g_room" class="form-control" value="<?=htmlspecialchars($_POST['g_room']??'')?>"></div>
          <div class="col-md"><label class="form-label">Icon (эмодзи)</label>
              <input name="g_icon" class="form-control" value="<?=htmlspecialchars($_POST['g_icon']??'')?>"></div>
        </div>

        <div class="row g-2 mt-2">
          <div class="col-md"><label class="form-label">Возраст от *</label>
              <input name="g_from" type="number" class="form-control" required value="<?=htmlspecialchars($_POST['g_from']??'')?>"></div>
          <div class="col-md"><label class="form-label">До *</label>
              <input name="g_to"   type="number" class="form-control" required value="<?=htmlspecialchars($_POST['g_to']??'')?>"></div>
        </div>

        <div class="mt-3"><label class="form-label">Описание</label>
            <textarea name="g_descr" rows="4" class="form-control"><?=htmlspecialchars($_POST['g_descr']??'')?></textarea></div>
     </div>
   </div>

   <div class="col-md-6">
     <div class="card p-3">
       <h5 class="mb-3">Педагоги (необязательно)</h5>

       <?php for($i=1;$i<=2;$i++): ?>
         <div class="mb-3">
           <label class="form-label">Воспитатель <?=$i?></label>
           <select name="teacher<?=$i?>" class="form-select">
              <option value="">— не выбрано —</option>
              <?php foreach($free as $t): ?>
                 <option value="<?=$t['id']?>" <?=($t['id']==($_POST["teacher$i"]??0))?'selected':''?>>
                    <?=htmlspecialchars($t['full_name'])?>
                 </option>
              <?php endforeach;?>
           </select>
         </div>
       <?php endfor;?>
     </div>
   </div>
 </div>

 <button class="btn btn-success mt-4">Создать группу</button>
</form>
</body></html>
