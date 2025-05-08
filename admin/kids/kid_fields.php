<div class="d-flex justify-content-between align-items-center mb-2">
    <strong>Данные ребёнка</strong>
    <button type="button" class="btn-close remove-kid" aria-label="Удалить"></button>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <label class="form-label">ФИО *</label>
    <input type="text" name="kid_name[]" class="form-control" required>
  </div>
  <div class="col-md-3">
    <label class="form-label">Дата рождения *</label>
    <input type="date" name="kid_birth[]" class="form-control" required>
  </div>
  <div class="col-12">
    <label class="form-label">Мед. примечание</label>
    <textarea name="kid_note[]" class="form-control" rows="2"></textarea>
  </div>
</div>
