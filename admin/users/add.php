<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') exit('Доступ запрещён');

require_once __DIR__.'/../../config.php';

/* словари */
$roles   = [1=>'Администратор', 2=>'Воспитатель', 3=>'Родитель'];
$staff   = $pdo->query("SELECT id,full_name FROM staff   ORDER BY full_name")->fetchAll();
$parents = $pdo->query("SELECT id,full_name FROM parents ORDER BY full_name")->fetchAll();

$err='';
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $login = trim($_POST['username']??'');
    $pass  = $_POST['password']??'';
    $role  = (int)($_POST['role_id']??0);
    $staff_id  = (int)($_POST['staff_id']??0);
    $parent_id = (int)($_POST['parent_id']??0);
    $active = isset($_POST['is_active'])?1:0;

    if($login===''||$pass===''||!isset($roles[$role])) $err='Заполните все обязательные поля.';
    else{
        $hash=password_hash($pass,PASSWORD_BCRYPT);
        $stmt=$pdo->prepare("INSERT INTO users (username,pass_hash,role_id,staff_id,parent_id,is_active)
                             VALUES (?,?,?,?,?,?)");
        $stmt->execute([$login,$hash,$role,$staff_id?:null,$parent_id?:null,$active]);
        header('Location: ../index.php?section=users'); exit;
    }
}
?>
<!doctype html><html lang="ru"><head>
<meta charset="utf-8"><title>Новый пользователь</title>
<link rel="icon" type="image/png" href="/image/web_logo.png">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  .hidden{display:none;}
</style>
</head><body class="p-4">
<a href="../index.php?section=users">← К списку</a>
<h3 class="my-3">Новый пользователь</h3>

<?php if($err):?><div class="alert alert-danger"><?=$err?></div><?php endif;?>
<form method="post" class="w-50" id="userForm">
  <!-- выбор роли (показывается сразу) -->
  <div class="mb-3">
     <label class="form-label">Роль *</label>
     <select name="role_id" id="roleSel" class="form-select" required>
        <option value="">— выберите роль —</option>
        <?php foreach($roles as $id=>$name):?>
           <option value="<?=$id?>"><?=$name?></option>
        <?php endforeach;?>
     </select>
  </div>

  <!-- остальные поля скрыты до выбора роли -->
  <div id="commonFields" class="hidden">
     <div class="mb-2">
        <label class="form-label">Логин *</label>
        <input name="username" class="form-control">
     </div>
     <div class="mb-2">
        <label class="form-label">Пароль *</label>
        <input name="password" type="password" class="form-control">
     </div>
  </div>

  <div id="staffField" class="mb-2 hidden">
     <label class="form-label">Сотрудник (для роли «Воспитатель»)</label>
     <select name="staff_id" class="form-select">
        <option value="0">— не выбрано —</option>
        <?php foreach($staff as $s):?>
           <option value="<?=$s['id']?>"><?=htmlspecialchars($s['full_name'])?></option>
        <?php endforeach;?>
     </select>
  </div>

  <div id="parentField" class="mb-2 hidden">
     <label class="form-label">Родитель (для роли «Родитель»)</label>
     <select name="parent_id" class="form-select">
        <option value="0">— не выбрано —</option>
        <?php foreach($parents as $p):?>
           <option value="<?=$p['id']?>"><?=htmlspecialchars($p['full_name'])?></option>
        <?php endforeach;?>
     </select>
  </div>

  <div id="activeChk" class="form-check mb-3 hidden">
     <input class="form-check-input" type="checkbox" name="is_active" id="act" checked>
     <label class="form-check-label" for="act">Учётка активна</label>
  </div>

  <button id="submitBtn" class="btn btn-success hidden">Создать</button>
</form>

<script>
const roleSel      = document.getElementById('roleSel');
const commonFields = document.getElementById('commonFields');
const staffField   = document.getElementById('staffField');
const parentField  = document.getElementById('parentField');
const activeChk    = document.getElementById('activeChk');
const submitBtn    = document.getElementById('submitBtn');

roleSel.addEventListener('change', () => {
   const role = parseInt(roleSel.value);
   const show = role>0;
   commonFields.classList.toggle('hidden', !show);
   activeChk.classList.toggle('hidden', !show);
   submitBtn.classList.toggle('hidden', !show);

   // показываем нужные списки
   staffField.classList.add('hidden');
   parentField.classList.add('hidden');
   if(role === 2) staffField.classList.remove('hidden');
   if(role === 3) parentField.classList.remove('hidden');
});
</script>
</body></html>
