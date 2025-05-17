<?php
/**
 * /admin/staff/add.php
 * ─ Проверка на дубликат ФИО
 * ─ Принимает только .jpg / .jpeg
 * ─ Сохраняет файл в /image/  и пишет в БД  image/имя_файла.jpg
 */

session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    exit('Доступ только для администратора');
}
require_once __DIR__ . '/../../config.php';

$err = $ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name  = trim($_POST['full_name'] ?? '');
    $pos   = trim($_POST['position']  ?? '');
    $hDate = $_POST['hire_date'] ?? '';
    $fDate = $_POST['fire_date'] ?? null;
    $photo = '';

    /* 1. базовая валидация */
    if (!$name || !$pos || !$hDate) {
        $err = 'Заполните ФИО, должность и дату приёма.';
    }

    /* 2. дубль ФИО */
    if (!$err) {
        $stmt = $pdo->prepare("SELECT 1 FROM staff WHERE full_name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn()) $err = 'Сотрудник с таким ФИО уже есть.';
    }

    /* 3. фото */
    if (!$err && !empty($_FILES['photo']['tmp_name'])) {
        $tmp = $_FILES['photo']['tmp_name'];
        if (is_uploaded_file($tmp)) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg'])) {
                $err = 'Разрешён только формат JPG.';
            } else {
                $fname = uniqid() . '.jpg';
                $dest  = __DIR__ . '/../../image/' . $fname;
                if (!move_uploaded_file($tmp, $dest)) {
                    $err = 'Не удалось сохранить файл.';
                } else {
                    $photo = 'image/' . $fname;      // ← полный относительный путь
                }
            }
        }
    }

    /* 4. запись */
    if (!$err) {
        $stmt = $pdo->prepare("
            INSERT INTO staff (full_name, position, hire_date, fire_date, photo_url)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $pos, $hDate, $fDate ?: null, $photo ?: null]);
        $ok = 'Сотрудник добавлен.';
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Добавить сотрудника</title>
<link rel="icon" type="image/png" href="/image/web_logo.png">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>.preview{width:120px;height:160px;object-fit:cover;border:1px solid #ccc;border-radius:.25rem;}</style>
</head>
<body class="p-4">

<h3 class="mb-4">Добавление сотрудника</h3>
<a href="../index.php" class="btn btn-link mb-3">← В админ-панель</a>

<?php if ($err): ?><div class="alert alert-danger"><?= $err ?></div><?php endif; ?>
<?php if ($ok):  ?><div class="alert alert-success"><?= $ok ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" novalidate>
  <div class="row">
    <!-- фото -->
    <div class="col-md-4">
      <label class="form-label">Фото (.jpg, 3×4)</label><br>
      <img src="" id="previewImg" class="preview d-none mb-2">
      <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg" onchange="readURL(this);">
    </div>

    <!-- текст -->
    <div class="col-md-8">
      <div class="mb-3">
        <label class="form-label">ФИО *</label>
        <input type="text" name="full_name" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Должность *</label>
        <input type="text" name="position" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Дата приёма *</label>
        <input type="date" name="hire_date" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Дата увольнения</label>
        <input type="date" name="fire_date" class="form-control">
      </div>
      <button class="btn btn-success">Сохранить</button>
    </div>
  </div>
</form>

<script>
function readURL(inp){
  if(inp.files && inp.files[0]){
     const r = new FileReader();
     r.onload = e=>{
       const img=document.getElementById('previewImg');
       img.src=e.target.result;
       img.classList.remove('d-none');
     };
     r.readAsDataURL(inp.files[0]);
  }
}
</script>
</body>
</html>
