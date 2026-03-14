<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="card" style="max-width:600px">
<div class="card-header"><strong><?= esc($title) ?></strong></div>
<div class="card-body">
<form method="post" action="<?= isset($item) ? base_url('karigar/update/'.$item['id']) : base_url('karigar/store') ?>">
<?= csrf_field() ?>
<div class="mb-3">
    <label class="form-label">Name *</label>
    <input type="text" name="name" class="form-control" value="<?= esc($item['name'] ?? '') ?>" required>
</div>
<div class="mb-3">
    <label class="form-label">Tamil Name</label>
    <input type="text" name="tamil_name" class="form-control" value="<?= esc($item['tamil_name'] ?? '') ?>">
</div>
<div class="mb-3">
    <label class="form-label">Department</label>
    <select name="department_id" class="form-select">
        <option value="">-- Select --</option>
        <?php foreach ($departments as $d): ?>
        <option value="<?= $d['id'] ?>" <?= ($item['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="row">
<div class="col mb-3">
    <label class="form-label">Default Cash Rate (&#8377;/kg)</label>
    <input type="number" step="0.01" name="default_cash_rate" class="form-control" value="<?= $item['default_cash_rate'] ?? 0 ?>">
</div>
<div class="col mb-3">
    <label class="form-label">Default Fine %</label>
    <input type="number" step="0.0001" name="default_fine_pct" class="form-control" value="<?= $item['default_fine_pct'] ?? 0 ?>">
</div>
</div>
<div class="mb-3">
    <label class="form-label">Notes</label>
    <textarea name="notes" class="form-control" rows="2"><?= esc($item['notes'] ?? '') ?></textarea>
</div>
<button type="submit" class="btn btn-primary">Save</button>
<a href="<?= base_url('karigar') ?>" class="btn btn-secondary ms-2">Cancel</a>
</form>
</div>
</div>
<?= $this->endSection() ?>
