<?php
/* admin/kids/edit.php — редактирование ребёнка и всех его родителей
 *   • каждый родитель сразу открыт для редактирования
 *   • можно добавить / удалить родителя
 *   • данные ребёнка и родителей сохраняются одной транзакцией
 */
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') exit('403');

require_once __DIR__ . '/../../config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) exit('no id');

/* ---------- cправочник родителей (нужен для выпадающего списка «добавить существующего») ---------- */
$parentList = $pdo->query("SELECT id, full_name FROM parents ORDER BY full_name")
                  ->fetchAll(PDO::FETCH_KEY_PAIR);            // [id => ФИО]

/* ---------- ребёнок ---------- */
$kid = $pdo->prepare("SELECT * FROM kids WHERE id=?");
$kid->execute([$id]);
$kid = $kid->fetch();
if (!$kid) exit('not found');

/* ---------- все родители ребёнка (0-N) ---------- */
$parents = $pdo->prepare("
      SELECT p.*
        FROM parents p
   JOIN parent_kid pk ON pk.parent_id = p.id
       WHERE pk.kid_id = ?
");
$parents->execute([$id]);
$parents = $parents->fetchAll(PDO::FETCH_ASSOC);

$err = $ok = '';

/* =================================================================== */
/*                               С О Х Р А Н Я Е М                     */
/* =================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        /* ─ 1. проверяем данные ребёнка ─ */
        $kName = trim($_POST['kid_name'] ?? '');
        $kDate = $_POST['kid_birth'] ?? '';
        $kNote = trim($_POST['kid_note'] ?? '');
        if (!$kName || !$kDate) throw new Exception('Заполните ФИО и дату рождения ребёнка.');

        /* ─ 2. получаем массивы родителей ─ */
        $pIds   = $_POST['parent_id']   ?? [];     // '' для новых
        $pNames = $_POST['parent_name'] ?? [];
        $phones = $_POST['parent_phone']?? [];
        $emails = $_POST['parent_email']?? [];
        $addrs  = $_POST['parent_addr'] ?? [];
        $cats   = $_POST['parent_soc']  ?? [];

        /* хотя бы один родитель должен быть */
        if (!array_filter($pNames)) throw new Exception('Не указан ни один родитель.');

        $pdo->beginTransaction();

        /* ─ 3. ребёнок ─ */
        $pdo->prepare("UPDATE kids
                          SET full_name = ?, birth_date = ?, medical_note = ?
                        WHERE id = ?")
            ->execute([$kName, $kDate, $kNote, $id]);

        /* ─ 4. родители ─ */
        $newParentIds = [];          // id родителей, которые будут связаны с ребёнком

        foreach ($pNames as $idx => $nm) {
            $nm = trim($nm);
            if ($nm === '') continue;            // пропускаем пустой блок

            $pid = (int)$pIds[$idx];
            $data = [
                $nm,
                trim($phones[$idx]),
                trim($emails[$idx]),
                trim($addrs[$idx]),
                trim($cats[$idx])
            ];

            if ($pid) {
                /* обновляем существующего */
                $pdo->prepare("
                    UPDATE parents
                       SET full_name = ?, phone = ?, email = ?, address = ?, social_category = ?
                     WHERE id = ?")
                    ->execute(array_merge($data, [$pid]));
                $newParentIds[] = $pid;
            } else {
                /* создаём нового */
                $pdo->prepare("
                    INSERT INTO parents(full_name, phone, email, address, social_category)
                    VALUES(?,?,?,?,?)")->execute($data);
                $newParentIds[] = $pdo->lastInsertId();
            }
        }

        if (!$newParentIds) throw new Exception('Не указан ни один родитель.');

        /* ─ 5. пересоздаём связи parent_kid ─ */
        $pdo->prepare("DELETE FROM parent_kid WHERE kid_id = ?")->execute([$id]);
        $link = $pdo->prepare("INSERT INTO parent_kid(parent_id, kid_id) VALUES(?, ?)");
        foreach ($newParentIds as $pid) $link->execute([$pid, $id]);

        $pdo->commit();
        $ok = 'Данные сохранены.';
        /* чтобы увидеть изменения сразу после F5 */
        header('Location: edit.php?id=' . $id); exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $err = $e->getMessage();
    }
}
?>
<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8">
<title>Редактировать ребёнка</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.kid-block,.parent-block{border:1px solid #dee2e6;border-radius:.5rem;padding:1rem;margin-bottom:1rem;position:relative}
.btn-close{background-size:.75em}
</style></head>
<body class="p-4">

<h3 class="mb-4">Редактирование данных (ID <?=$id?>)</h3>
<a href="../index.php?section=kids" class="btn btn-link mb-3">← Назад к списку</a>

<?php if($err):?><div class="alert alert-danger"><?=$err?></div><?php endif;?>
<?php if($ok ):?><div class="alert alert-success"><?=$ok?></div><?php endif;?>

<form method="post" id="mainForm">
  <!-- ребёнок ------------------------------------------------------->
  <h5>Данные ребёнка</h5>
  <div class="kid-block">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">ФИО *</label>
        <input name="kid_name" class="form-control" required
               value="<?=htmlspecialchars($kid['full_name'])?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Дата рождения *</label>
        <input type="date" name="kid_birth" class="form-control" required
               value="<?=$kid['birth_date']?>">
      </div>
      <div class="col-12">
        <label class="form-label">Мед. примечание</label>
        <textarea name="kid_note" rows="2"
                  class="form-control"><?=htmlspecialchars($kid['medical_note'])?></textarea>
      </div>
    </div>
  </div>

  <!-- родители ------------------------------------------------------>
  <h5>Родители / законные представители</h5>
  <div id="parentList">
    <?php if ($parents): ?>
        <?php foreach ($parents as $par): ?>
            <div class="parent-block">
              <?php /* — блок родителя — */ ?>
              <input type="hidden" name="parent_id[]" value="<?=$par['id']?>">
              <button type="button" class="btn-close remove-parent" aria-label="Удалить"></button>

              <div class="row g-3 align-items-end">
                <div class="col-md-6">
                  <label class="form-label">ФИО *</label>
                  <input type="text" name="parent_name[]" class="form-control"
                         value="<?=htmlspecialchars($par['full_name'])?>" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Телефон</label>
                  <input type="text" name="parent_phone[]" class="form-control"
                         value="<?=htmlspecialchars($par['phone'])?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label">E-mail</label>
                  <input type="email" name="parent_email[]" class="form-control"
                         value="<?=htmlspecialchars($par['email'])?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Адрес</label>
                  <input type="text" name="parent_addr[]" class="form-control"
                         value="<?=htmlspecialchars($par['address'])?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Социальная категория</label>
                  <input type="text" name="parent_soc[]" class="form-control"
                         value="<?=htmlspecialchars($par['social_category'])?>">
                </div>
              </div>
            </div>
        <?php endforeach; ?>
    <?php else: /* если нет ни одного — показываем пустой блок */ ?>
        <div class="parent-block">
          <input type="hidden" name="parent_id[]" value="">
          <button type="button" class="btn-close remove-parent" aria-label="Удалить"></button>

          <div class="row g-3 align-items-end">
            <div class="col-md-6">
              <label class="form-label">ФИО *</label>
              <input type="text" name="parent_name[]" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Телефон</label>
              <input type="text" name="parent_phone[]" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label">E-mail</label>
              <input type="email" name="parent_email[]" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Адрес</label>
              <input type="text" name="parent_addr[]" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Социальная категория</label>
              <input type="text" name="parent_soc[]" class="form-control">
            </div>
          </div>
        </div>
    <?php endif; ?>
  </div>

  <button type="button" id="addParentBtn" class="btn btn-outline-secondary mb-4">
      + Добавить родителя
  </button><br>

  <button class="btn btn-warning">Сохранить изменения</button>
</form>

<!-- шаблон пустого блока родителя -->
<template id="parentTemplate">
  <div class="parent-block">
    <input type="hidden" name="parent_id[]" value="">
    <button type="button" class="btn-close remove-parent" aria-label="Удалить"></button>

    <div class="row g-3 align-items-end">
      <div class="col-md-6">
        <label class="form-label">ФИО *</label>
        <input type="text" name="parent_name[]" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Телефон</label>
        <input type="text" name="parent_phone[]" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label">E-mail</label>
        <input type="email" name="parent_email[]" class="form-control">
      </div>
      <div class="col-md-6">
        <label class="form-label">Адрес</label>
        <input type="text" name="parent_addr[]" class="form-control">
      </div>
      <div class="col-md-6">
        <label class="form-label">Социальная категория</label>
        <input type="text" name="parent_soc[]" class="form-control">
      </div>
    </div>
  </div>
</template>

<script>
/* + новый родитель */
addParentBtn.onclick = () =>
    parentList.appendChild(parentTemplate.content.cloneNode(true));

/* удалить родителя */
document.addEventListener('click', e=>{
  if (e.target.classList.contains('remove-parent')) {
      e.target.closest('.parent-block').remove();
  }
});
</script>
</body></html>
