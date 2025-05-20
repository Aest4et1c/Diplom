<?php
/**
 * Добавление ребёнка(-ов) и родителей.
 * • Существующий родитель (switchbox) → только связь parent_kid.
 */

session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    exit('Доступ только администратору');
}
require_once __DIR__.'/../../config.php';

/* список уже существующих родителей для выпадающего списка */
$parentList = $pdo->query("SELECT id,full_name FROM parents ORDER BY full_name")
                  ->fetchAll(PDO::FETCH_KEY_PAIR);          // [id=>'ФИО']

$ok = $err = '';

/* ---------------- POST-обработка ---------------- */
if ($_SERVER['REQUEST_METHOD']==='POST') {

    /* --- дети ------------------------------------------------------- */
    $kNames = $_POST['kid_name']  ?? [];
    $kDates = $_POST['kid_birth'] ?? [];
    $kNotes = $_POST['kid_note']  ?? [];

    /* --- родители --------------------------------------------------- */
    $rawExist = $_POST['parent_exists'] ?? [];   // id выбранного родителя или ''
    $pNames   = $_POST['parent_name']   ?? [];   // строка ФИО (только для «нового»)
    $pPhones  = $_POST['parent_phone']  ?? [];
    $pEmails  = $_POST['parent_email']  ?? [];
    $pAddrs   = $_POST['parent_address']?? [];
    $pCats    = $_POST['parent_soc']    ?? [];

    /* === 1. проверяем дубликаты ==================================== */
    $dupKids=$dupParents=[];

    /* дети (ФИО+дата) */
    $pairs=[];
    foreach($kNames as $i=>$n){
        if(!$n || !$kDates[$i]) continue;
        $pairs[]=$n; $pairs[]=$kDates[$i];
    }
    if($pairs){
        $in=rtrim(str_repeat('(?,?),',count($pairs)/2),',');
        $st=$pdo->prepare("SELECT full_name,birth_date FROM kids WHERE (full_name,birth_date) IN ($in)");
        $st->execute($pairs);
        $dupKids=$st->fetchAll(PDO::FETCH_ASSOC);
    }

    /* родители: дубли ищем только по новым, а не по существующим  */
    $newNames=[];
    foreach($pNames as $idx=>$nm){
        if(trim($nm) && empty($rawExist[$idx])) $newNames[] = trim($nm);
    }
    if($newNames){
        $in=rtrim(str_repeat('?,',count($newNames)),',');
        $st=$pdo->prepare("SELECT full_name FROM parents WHERE full_name IN ($in)");
        $st->execute($newNames);
        $dupParents=$st->fetchAll(PDO::FETCH_ASSOC);
    }

    if($dupKids || $dupParents){
        $msg=[];
        foreach($dupKids as $d)   $msg[]="Ребёнок «{$d['full_name']}» ({$d['birth_date']}) уже есть.";
        foreach($dupParents as $d)$msg[]="Родитель «{$d['full_name']}» уже есть.";
        $err = implode('<br>',$msg);
    }else{

        /* === 2. вставляем данные ==================================== */
        $pdo->beginTransaction();
        try{
            /* дети */
            $kidIds=[];
            foreach($kNames as $i=>$n){
                if(!$n || !$kDates[$i]) continue;
                $pdo->prepare("INSERT INTO kids(full_name,birth_date,medical_note)
                               VALUES(?,?,?)")
                    ->execute([trim($n),$kDates[$i],trim($kNotes[$i]??'')]);
                $kidIds[]=$pdo->lastInsertId();
            }
            if(!$kidIds) throw new Exception('Не введён ни один ребёнок.');

            /* родители */
            $parIds=[];
            foreach($rawExist as $idx=>$pid){
                $pid = (int)$pid;
                if($pid){              // ❶ уже существующий
                    $parIds[]=$pid;    //   → просто берём id
                    continue;
                }
                /* ❷ новый родитель (если введено ФИО) */
                $name=trim($pNames[$idx]??'');
                if(!$name) continue;   // пустой блок пропускаем

                $pdo->prepare("INSERT INTO parents(full_name,phone,email,address,social_category)
                               VALUES(?,?,?,?,?)")
                    ->execute([
                        $name,
                        trim($pPhones[$idx]  ?? ''),
                        trim($pEmails[$idx]  ?? ''),
                        trim($pAddrs[$idx]   ?? ''),
                        trim($pCats[$idx]    ?? '')
                    ]);
                $parIds[]=$pdo->lastInsertId();
            }
            if(!$parIds) throw new Exception('Не указан ни один родитель.');

            /* связи parent_kid */
            $link=$pdo->prepare("INSERT INTO parent_kid(parent_id,kid_id) VALUES(?,?)");
            foreach($kidIds as $k) foreach($parIds as $p) $link->execute([$p,$k]);

            $pdo->commit();
            $ok='Данные сохранены.';
        }catch(Exception $e){
            $pdo->rollBack();
            $err='Ошибка: '.$e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Добавить детей и родителей</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.kid-block,.parent-block{border:1px solid #dee2e6;border-radius:.5rem;padding:1rem;margin-bottom:1rem;position:relative;}
.btn-close{background-size:.75em;}
.parent-existing select{max-width:350px;}
</style>
</head>
<body class="p-4">

<h3 class="mb-4">Добавление детей и родителей</h3>
<a href="../index.php?section=kids" class="btn btn-link mb-3">← Назад</a>

<?php if($err):?><div class="alert alert-danger"><?=$err?></div><?php endif;?>
<?php if($ok):?><div class="alert alert-success"><?=$ok?></div><?php endif;?>

<form method="post" id="mainForm">
  <!-- дети -->
  <h5>Дети</h5>
  <div id="kidList">
    <div class="kid-block"><?php include __DIR__.'/kid_fields.php'; ?></div>
  </div>
  <button type="button" id="addKidBtn" class="btn btn-outline-secondary mb-4">+ Добавить ребёнка</button>

  <!-- родители -->
  <h5>Родители / законные представители</h5>
  <div id="parentList">
    <div class="parent-block"><?php include __DIR__.'/parent_fields_select.php'; ?></div>
  </div>
  <button type="button" id="addParentBtn" class="btn btn-outline-secondary mb-4">+ Добавить родителя</button><br>

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
addKidBtn.onclick=()=>kidList.appendChild(kidTemplate.content.cloneNode(true));
addParentBtn.onclick=()=>parentList.appendChild(parentTemplate.content.cloneNode(true));

/* toggle existing-parent combobox */
document.addEventListener('change',e=>{
  if(!e.target.classList.contains('chk-existing')) return;
  const block=e.target.closest('.parent-block');
  const comb  =block.querySelector('.parent-existing');
  const sel   =comb.querySelector('select');
  const show  =e.target.checked;
  comb.classList.toggle('d-none',!show);
  block.querySelector('.parent-new').classList.toggle('d-none',show);
  sel.disabled=!show;
});

/* удаление блоков */
document.addEventListener('click',e=>{
  if(e.target.classList.contains('remove-kid'))   e.target.closest('.kid-block').remove();
  if(e.target.classList.contains('remove-parent'))e.target.closest('.parent-block').remove();
});
</script>
</body>
</html>
