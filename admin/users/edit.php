<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') exit('Доступ запрещён');
require_once __DIR__.'/../../config.php';

$id = (int)($_GET['id'] ?? 0);
$user = $pdo->prepare("SELECT * FROM users WHERE id=?");
$user->execute([$id]);
$user = $user->fetch();
if (!$user)  exit('Пользователь не найден');

$roles   = [1=>'Администратор', 2=>'Воспитатель', 3=>'Родитель'];
$staff   = $pdo->query("SELECT id,full_name FROM staff   ORDER BY full_name")->fetchAll();
$parents = $pdo->query("SELECT id,full_name FROM parents ORDER BY full_name")->fetchAll();

$err='';
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $login = trim($_POST['username']??'');
    $role  = (int)($_POST['role_id']??0);
    $staff_id  = (int)($_POST['staff_id']??0);
    $parent_id = (int)($_POST['parent_id']??0);
    $active = isset($_POST['is_active'])?1:0;

    if($login==='' || !isset($roles[$role])) $err='Заполните обязательные поля.';
    else{
        if($_POST['password']!==''){
            $hash=password_hash($_POST['password'],PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET pass_hash=? WHERE id=?")->execute([$hash,$id]);
        }
        $pdo->prepare("UPDATE users SET username=?,role_id=?,staff_id=?,parent_id=?,is_active=? WHERE id=?")
            ->execute([$login,$role,$staff_id?:null,$parent_id?:null,$active,$id]);
        header('Location: ../index.php?section=users'); exit;
    }
}
?>
<!doctype html><html lang="ru"><head>
<meta charset="utf-8"><title>Редактирование пользователя</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>.hidden{display:none;}</style>
</head><body class="p-4">
<a href="../index.php?section=users">← К списку</a>
<h3 class="my-3">Редактирование: <?=htmlspecialchars($user['username'])?></h3>

<?php if($err):?><div class="alert alert-danger"><?=$err?></div><?php endif;?>
<form method="post" class="w-50" id="editForm">
  <div class="mb-2">
     <label class="form-label">Логин *</label>
     <input name="username" class="form-control" value="<?=htmlspecialchars($user['username'])?>" required>
  </div>

  <div class="mb-2">
     <label class="form-label">Новый пароль (если нужно сменить)</label>
     <input name="password" type="password" class="form-control">
  </div>

  <div class="mb-2">
     <label class="form-label">Роль *</label>
     <select name="role_id" id="roleSel" class="form-select" required>
        <?php foreach($roles as $rid=>$rname):?>
          <option value="<?=$rid?>" <?=$rid==$user['role_id']?'selected':''?>><?=$rname?></option>
        <?php endforeach;?>
     </select>
  </div>

  <div id="staffField" class="mb-2">
     <label class="form-label">Сотрудник (для роли «Воспитатель»)</label>
     <select name="staff_id" class="form-select">
        <option value="0">— не выбрано —</option>
        <?php foreach($staff as $s):?>
          <option value="<?=$s['id']?>" <?=$s['id']==$user['staff_id']?'selected':''?>><?=htmlspecialchars($s['full_name'])?></option>
        <?php endforeach;?>
     </select>
  </div>

  <div id="parentField" class="mb-2">
     <label class="form-label">Родитель (для роли «Родитель»)</label>
     <select name="parent_id" class="form-select">
        <option value="0">— не выбрано —</option>
        <?php foreach($parents as $p):?>
          <option value="<?=$p['id']?>" <?=$p['id']==$user['parent_id']?'selected':''?>><?=htmlspecialchars($p['full_name'])?></option>
        <?php endforeach;?>
     </select>
  </div>

  <div class="form-check mb-3">
     <input class="form-check-input" type="checkbox" name="is_active" id="act" <?=$user['is_active']?'checked':''?>>
     <label class="form-check-label" for="act">Учётка активна</label>
  </div>

  <button class="btn btn-warning">Сохранить</button>
</form>

<script>
const roleSel     = document.getElementById('roleSel');
const staffField  = document.getElementById('staffField');
const parentField = document.getElementById('parentField');

function toggleFields(){
   const role = parseInt(roleSel.value);
   staffField.classList.toggle('hidden',  role !== 2);
   parentField.classList.toggle('hidden', role !== 3);
}
toggleFields();            // первичная инициализация
roleSel.addEventListener('change', toggleFields);
</script>
</body></html>
