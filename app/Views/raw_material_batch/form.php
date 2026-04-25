<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="card" style="max-width:500px">
<div class="card-header"><strong>Add Raw Material Batch</strong></div>
<div class="card-body">
<form method="post" action="<?= base_url('raw-material-batches/store') ?>">
<?= csrf_field() ?>
<div class="mb-3">
    <label class="form-label">Batch Number *</label>
    <input type="text" name="batch_number" class="form-control" required placeholder="e.g. ZN1, AG5">
</div>
<div class="mb-3">
    <label class="form-label">Material Type *</label>
    <select name="material_type_id" class="form-select" required onchange="fillTouch(this)">
        <option value="">-- Select --</option>
        <?php foreach ($types as $t): ?>
        <option value="<?= $t['id'] ?>" data-touch="<?= $t['default_touch_pct'] ?>"><?= esc($t['name']) ?> (<?= $t['default_touch_pct'] ?>%)</option>
        <?php endforeach; ?>
    </select>
</div>
<div class="mb-3"><label class="form-label">Weight (g) *</label><input type="number" step="0.0001" name="weight_g" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Touch %</label><input type="number" step="0.0001" name="touch_pct" id="touchPct" class="form-control" value="0"></div>
<button type="submit" class="btn btn-primary">Save</button>
<a href="<?= base_url('raw-material-batches') ?>" class="btn btn-secondary ms-2">Cancel</a>
</form>
</div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function fillTouch(sel) {
    var opt = sel.options[sel.selectedIndex];
    document.getElementById('touchPct').value = opt.dataset.touch || 0;
}
</script>
<?= $this->endSection() ?>