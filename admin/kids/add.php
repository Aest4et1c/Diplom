<?php
/**
 * Добавление ребёнка(-ов) и родителей за один шаг.
 * ─ Проверка: если ФИО+дата ребёнка ИЛИ родитель совпадают с базой — ошибка.
 * ─ Можно добавить/удалить блоки детей и родителей на лету.
 * ─ «Родитель уже есть» → combobox со списком parents.
 */

session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    exit('Доступ только администратору');
}
require_once __DIR__ . '/../../config.php';

/* ---------- список существующих родителей для combobox ---------- */
$parentList = $pdo->query("
    SELECT id, full_name
      FROM parents
  ORDER BY full_name
")->fetchAll(PDO::FETCH_KEY_PAIR);   // [id => 'ФИО …']

$ok = $err = '';

/* ---------- обработка формы ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ==== собираем данные из формы ==== */
    $kNames = $_POST['kid_name']  ?? [];
    $kDates = $_POST['kid_birth'] ?? [];
    $kNotes = $_POST['kid_note']  ?? [];

    $existIds = array_filter($_POST['parent_exists'] ?? []);  // ID родителей-из-списка
    $pNames   = $_POST['parent_name'] ?? [];                  // новые родители

    /* ==== 1. проверяем дубли ==== */
    $dupKids = $dupParents = [];

    /* --- дети (ФИО + дата) --- */
    $pairs = [];
    foreach ($kNames as $i => $n) {
        if (!$n || !$kDates[$i]) continue;
        $pairs[] = $n;
        $pairs[] = $kDates[$i];
    }
    if ($pairs) {
        $place = rtrim(str_repeat('(?,?),', count($pairs)/2), ',');
        $st = $pdo->prepare("SELECT full_name,birth_date FROM kids WHERE (full_name,birth_date) IN ($place)");
        $st->execute($pairs);
        $dupKids = $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /* --- родители: ID + ФИО --- */
    if ($existIds) {
        $in  = rtrim(str_repeat('?,', count($existIds)), ',');
        $st  = $pdo->prepare("SELECT id, full_name FROM parents WHERE id IN ($in)");
        $st->execute($existIds);
        $dupParents = $st->fetchAll(PDO::FETCH_ASSOC);
    }
    $newNames = array_filter(array_map('trim', $pNames));
    if ($newNames) {
        $in  = rtrim(str_repeat('?,', count($newNames)), ',');
        $st  = $pdo->prepare("SELECT full_name FROM parents WHERE full_name IN ($in)");
        $st->execute($newNames);
        $dupParents = array_merge($dupParents, $st->fetchAll(PDO::FETCH_ASSOC));
    }

    if ($dupKids || $dupParents) {
        $msg = [];
        foreach ($dupKids as $d) {
            $msg[] = "Ребёнок «{$d['full_name']}» ({$d['birth_date']}) уже есть.";
        }
        foreach ($dupParents as $d) {
            $id = $d['id'] ?? '—';
            $msg[] = "Родитель «{$d['full_name']}» (ID $id) уже есть.";
        }
        $err = implode('<br>', $msg);
    } else {
        /* ==== 2. вставляем данные (транзакция) ==== */
        $pdo->beginTransaction();
        try {
            /* дети */
            $kidIds = [];
            foreach ($kNames as $i => $n) {
                if (!$n || !$kDates[$i]) continue;
                $pdo->prepare("
                    INSERT INTO kids(full_name,birth_date,medical_note)
                    VALUES(?,?,?)
                ")->execute([trim($n), $kDates[$i], trim($kNotes[$i]??'')]);
                $kidIds[] = $pdo->lastInsertId();
            }
            if (!$kidIds) throw new Exception('Не введён ни один ребёнок.');

            /* родители: существующие + новые */
            $parIds = array_map('intval', $existIds);

            foreach ($pNames as $j => $pName) {
                if (!$pName) continue;
                $pdo->prepare("
                    INSERT INTO parents(full_name,phone,email,address,social_category)
                    VALUES(?,?,?,?,?)
                ")->execute([
                    trim($pName),
                    trim($_POST['parent_phone'][$j]   ?? ''),
                    trim($_POST['parent_email'][$j]   ?? ''),
                    trim($_POST['parent_address'][$j] ?? ''),
                    trim($_POST['parent_soc'][$j]     ?? '')
                ]);
                $parIds[] = $pdo->lastInsertId();
            }
            if (!$parIds) throw new Exception('Не указан ни один родитель.');

            /* связи parent_kid */
            $link = $pdo->prepare("INSERT INTO parent_kid(parent_id,kid_id) VALUES(?,?)");
            foreach ($kidIds as $k) foreach ($parIds as $p) $link->execute([$p,$k]);

            $pdo->commit();
            $ok = 'Данные сохранены.';
        } catch (Exception $e) {
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
<title>Добавить детей и родителей</title>
<link rel="icon" type="image/png" href="/image/web_logo.png">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">


<style>
.kid-block,.parent-block{border:1px solid #dee2e6;border-radius:.5rem;padding:1rem;margin-bottom:1rem;position:relative;}
.btn-close{background-size:.75em;}
.parent-existing select{max-width:350px;}
</style>
</head>
<body class="p-4">

<h3 class="mb-4">Добавление детей и родителей</h3>
<a href="../index.php" class="btn btn-link mb-3">← В админ-панель</a>

<?php if($err):?><div class="alert alert-danger"><?=$err?></div><?php endif;?>
<?php if($ok):?><div class="alert alert-success"><?=$ok?></div><?php endif;?>

<form method="post" id="mainForm">
    <!-- ░░░ Блоки детей ░░░ -->
    <h5>Дети</h5>
    <div id="kidList">
        <div class="kid-block"><?php include __DIR__.'/kid_fields.php'; ?></div>
    </div>
    <button type="button" class="btn btn-outline-secondary mb-4" id="addKidBtn">
        + Добавить ребёнка
    </button>

    <!-- ░░░ Блоки родителей ░░░ -->
    <h5>Родители / законные представители</h5>
    <div id="parentList">
        <div class="parent-block"><?php include __DIR__.'/parent_fields_select.php'; ?></div>
    </div>
    <button type="button" class="btn btn-outline-secondary mb-4" id="addParentBtn">
        + Добавить родителя
    </button><br>

    <button class="btn btn-success">Сохранить всё</button>
</form>

<!-- шаблоны -->
<template id="kidTemplate">
  <div class="kid-block"><?php include __DIR__.'/kid_fields.php'; ?></div>
</template>

<template id="parentTemplate">
  <div class="parent-block"><?php include __DIR__.'/parent_fields_select.php'; ?></div>
</template>

<script>
/* ── добавить ребёнка / родителя ─────────────────────────── */
document.getElementById('addKidBtn').onclick = () => {
    const t = document.getElementById('kidTemplate').content.cloneNode(true);
    document.getElementById('kidList').appendChild(t);
};
document.getElementById('addParentBtn').onclick = () => {
    const t = document.getElementById('parentTemplate').content.cloneNode(true);
    document.getElementById('parentList').appendChild(t);
};

/* ── переключатель «родитель уже есть» ───────────────────── */
document.addEventListener('change', e => {
    if (e.target.classList.contains('chk-existing')) {
        const block  = e.target.closest('.parent-block');
        const extDiv = block.querySelector('.parent-existing');
        const sel    = extDiv.querySelector('select');
        const isOn   = e.target.checked;          // true → комбобокс

        extDiv.classList.toggle('d-none', !isOn);
        block.querySelector('.parent-new')
             .classList.toggle('d-none',  isOn);

        /* КЛЮЧЕВОЕ — включаем / отключаем <select> */
        sel.disabled = !isOn;
    }
});

/* ── удалить блок ребёнка / родителя ─────────────────────── */
document.addEventListener('click', e => {
    if (e.target.classList.contains('remove-kid')) {
        e.target.closest('.kid-block').remove();
    }
    if (e.target.classList.contains('remove-parent')) {
        e.target.closest('.parent-block').remove();
    }
});
</script>
</body>
</html>
