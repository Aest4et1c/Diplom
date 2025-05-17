<?php
// /admin/kids/edit.php  – редактирование ребёнка + родителей (фикс обновления родителей)
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') exit('403');
require_once __DIR__.'/../../config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) exit('no id');

/* ▸ список всех родителей для выпадающего списка */
$parentList = $pdo->query("SELECT id, full_name FROM parents ORDER BY full_name")
                  ->fetchAll(PDO::FETCH_KEY_PAIR);

/* ▸ данные ребёнка */
$kid = $pdo->prepare("SELECT * FROM kids WHERE id=?");
$kid->execute([$id]);
$kid = $kid->fetch();   if (!$kid) exit('not found');

/* ▸ родители, привязанные к ребёнку */
$parents = $pdo->prepare("
   SELECT p.*
     FROM parents p
     JOIN parent_kid pk ON pk.parent_id = p.id
    WHERE pk.kid_id = ?
");
$parents->execute([$id]);
$parents = $parents->fetchAll(PDO::FETCH_ASSOC);

$err = '';

/*─────────────────────  С О Х Р А Н Я Е М  ─────────────────────*/
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $kName = trim($_POST['kid_name'] ?? '');
    $kDate = $_POST['kid_birth'] ?? '';
    $kNote = trim($_POST['kid_note'] ?? '');

    if(!$kName || !$kDate) $err = 'Заполните ФИО и дату рождения ребёнка.';

    /* массивы из формы  (все три гарантированно одинаковой длины) */
    $oldIds = $_POST['parent_old_id'] ?? [];
    $exists = $_POST['parent_exists'] ?? [];
    $pNames = $_POST['parent_name']   ?? [];
    $phones = $_POST['parent_phone']  ?? [];
    $emails = $_POST['parent_email']  ?? [];
    $addrs  = $_POST['parent_address']?? [];
    $cats   = $_POST['parent_soc']    ?? [];

    $newParIds = [];

    if (!$err){
        $pdo->beginTransaction();
        try{
            /* 1. ОБНОВЛЯЕМ ребёнка */
            $pdo->prepare("UPDATE kids SET full_name=?, birth_date=?, medical_note=? WHERE id=?")
                ->execute([$kName, $kDate, $kNote, $id]);

            /* 2. ОБРАБАТЫВАЕМ каждый блок родителя */
            $total = count($oldIds);   // все массивы одинаковой длины
            for($i=0;$i<$total;$i++){
                $oldId   = (int)$oldIds[$i];
                $existId = (int)$exists[$i];
                $name    = trim($pNames[$i]);
                $phone   = trim($phones[$i]);
                $email   = trim($emails[$i]);
                $addr    = trim($addrs[$i]);
                $cat     = trim($cats[$i]);

                /* A. выбран существующий родитель из списка */
                if ($existId){
                    $newParIds[] = $existId;
                    continue;
                }

                /* B. редактируем имеющегося (oldId>0) */
                if ($oldId){
                    // если админ оставил ФИО пустым – берём старое ФИО
                    if (!$name){
                        $name = $pdo->query("SELECT full_name FROM parents WHERE id=$oldId")
                                    ->fetchColumn();
                    }
                    $pdo->prepare("
                       UPDATE parents
                          SET full_name=?, phone=?, email=?, address=?, social_category=?
                        WHERE id=?
                    ")->execute([$name,$phone,$email,$addr,$cat,$oldId]);
                    $newParIds[] = $oldId;
                    continue;
                }

                /* C. добавляем нового родителя */
                if ($name){            // нужен хотя бы ФИО
                    $pdo->prepare("
                       INSERT INTO parents(full_name,phone,email,address,social_category)
                       VALUES(?,?,?,?,?)
                    ")->execute([$name,$phone,$email,$addr,$cat]);
                    $newParIds[] = $pdo->lastInsertId();
                }
            }

            if (!$newParIds) throw new Exception('Не указан ни один родитель.');

            /* 3. ПЕРЕСОЗДАЁМ связи parent_kid */
            $pdo->prepare("DELETE FROM parent_kid WHERE kid_id=?")->execute([$id]);
            $link = $pdo->prepare("INSERT INTO parent_kid(parent_id,kid_id) VALUES(?,?)");
            foreach ($newParIds as $pid) $link->execute([$pid,$id]);

            $pdo->commit();
            header('Location: ../index.php?section=kids'); exit;
        }catch(Exception $e){
            $pdo->rollBack();
            $err = 'Ошибка: '.$e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Редактировать ребёнка</title>
<link rel="icon" type="image/png" href="/image/web_logo.png">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.kid-block,.parent-block{border:1px solid #dee2e6;border-radius:.5rem;padding:1rem;margin-bottom:1rem;position:relative;}
.btn-close{background-size:.75em;}
.parent-existing select{max-width:350px;}
</style>
</head>
<body class="p-4">

<h3 class="mb-4">Редактирование данных (ID <?=$id?>)</h3>
<a href="../index.php?section=kids" class="btn btn-link mb-3">← Назад к списку</a>

<?php if($err):?><div class="alert alert-danger"><?=$err?></div><?php endif;?>

<form method="post" id="mainForm">
    <!-- ребёнок -->
    <h5>Данные ребёнка</h5>
    <div class="kid-block">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">ФИО *</label>
                <input type="text" name="kid_name" class="form-control" required
                       value="<?=htmlspecialchars($kid['full_name'])?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Дата рождения *</label>
                <input type="date" name="kid_birth" class="form-control" required
                       value="<?=$kid['birth_date']?>">
            </div>
            <div class="col-12">
                <label class="form-label">Мед. примечание</label>
                <textarea name="kid_note" class="form-control" rows="2"><?=htmlspecialchars($kid['medical_note'])?></textarea>
            </div>
        </div>
    </div>

    <!-- родители -->
    <h5>Родители / законные представители</h5>
    <div id="parentList">
        <?php foreach($parents as $par): ?>
           <div class="parent-block">
             <?php include __DIR__.'/parent_fields_select_prefill.php'; ?>
           </div>
        <?php endforeach; ?>
    </div>

    <button type="button" class="btn btn-outline-secondary mb-4" id="addParentBtn">+ Добавить родителя</button><br>
    <button class="btn btn-warning">Редактировать</button>
</form>

<!-- шаблон нового родителя -->
<template id="parentTemplate">
  <div class="parent-block"><?php include __DIR__.'/parent_fields_select.php'; ?></div>
</template>

<script>
/* ── добавить нового родителя ─────────────────────────────── */
document.getElementById('addParentBtn').onclick = () => {
    const c = document.getElementById('parentTemplate').content.cloneNode(true);
    document.getElementById('parentList').appendChild(c);
};

/* ── переключатель “родитель уже есть” ─────────────────────── */
document.addEventListener('change', e => {
    if (e.target.classList.contains('chk-existing')) {
        const block  = e.target.closest('.parent-block');
        const exist  = block.querySelector('.parent-existing');
        const select = exist.querySelector('select');
        const isOn   = e.target.checked;          // true → «родитель уже есть»
        exist.classList.toggle('d-none', !isOn);
        block.querySelector('.parent-new')
             .classList.toggle('d-none',  isOn);

        /* КЛЮЧЕВОЕ: отключаем <select> когда чек-бокс снят */
        select.disabled = !isOn;
    }
});

/* ── удалить блок родителя ────────────────────────────────── */
document.addEventListener('click', e => {
    if (e.target.classList.contains('remove-parent')) {
        e.target.closest('.parent-block').remove();
    }
});
</script>
</body>
</html>
