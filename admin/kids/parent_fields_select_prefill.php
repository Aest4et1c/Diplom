<?php
/**
 * Блок родителя для edit.php с предзаполненными данными.
 *
 * Доступные переменные:
 *   $par         — данные конкретного родителя
 *   $parentList  — массив [id => 'ФИО'] для <select>
 *
 * Логика:
 *   ▸ Если $par['id'] есть в $parentList → считаем «существующим»
 *     (чек-бокс включён, показан <select>).
 *   ▸ Если нет — открываем режим «новый родитель»
 *     (чек-бокс выключен, открыты текстовые поля).
 */
$isExisting = isset($parentList[$par['id']]);
?>
<div class="d-flex justify-content-between align-items-center mb-2">
    <strong>Родитель</strong>
    <button type="button" class="btn-close remove-parent" aria-label="Удалить"></button>
</div>

<!-- идентификатор старого родителя ("" если новый блок) -->
<input type="hidden" name="parent_old_id[]" value="<?=$par['id']?>">

<div class="form-check form-switch mb-2">
  <input class="form-check-input chk-existing" type="checkbox" <?=$isExisting?'checked':''?>>
  <label class="form-check-label">Родитель уже есть</label>
</div>

<!-- ▸ выбор существующего -->
<div class="parent-existing <?=$isExisting?'':'d-none'?>">
    <select name="parent_exists[]" class="form-select mb-3" <?= $isExisting ? '' : 'disabled' ?>>
      <option value="">— выберите родителя —</option>
      <?php foreach ($parentList as $pid => $pname): ?>
          <option value="<?=$pid?>" <?=$pid==$par['id']?'selected':''?>>
              <?=htmlspecialchars($pname)?>
          </option>
      <?php endforeach; ?>
  </select>
</div>

<!-- ▸ ввод нового / редактирование -->
<div class="parent-new <?=$isExisting?'d-none':''?>">
  <div class="row g-3 align-items-end">
    <div class="col-md-6">
      <label class="form-label">ФИО *</label>
      <input type="text"  name="parent_name[]" class="form-control"
             value="<?=htmlspecialchars($par['full_name'])?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Телефон</label>
      <input type="text"  name="parent_phone[]" class="form-control"
             value="<?=htmlspecialchars($par['phone'])?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">E-mail</label>
      <input type="email" name="parent_email[]" class="form-control"
             value="<?=htmlspecialchars($par['email'])?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Адрес</label>
      <input type="text" name="parent_address[]" class="form-control"
             value="<?=htmlspecialchars($par['address'])?>">
    </div>
    <div class="col-md-6">
      <label class="form-label">Социальная категория</label>
      <input type="text" name="parent_soc[]" class="form-control"
             value="<?=htmlspecialchars($par['social_category'])?>">
    </div>
  </div>
</div>
