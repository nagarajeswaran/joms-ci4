<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="card" style="max-width:500px">
<div class="card-header"><strong><?= esc($title) ?></strong></div>
<div class="card-body">
<form method="post" action="<?= isset($item) ? base_url('raw-material-types/update/'.$item['id']) : base_url('raw-material-types/store') ?>">
<?= csrf_field() ?>
<div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" value="<?= esc($item['name'] ?? '') ?>" required></div>
<div class="mb-3"><label class="form-label">Default Touch %</label><input type="number" step="0.0001" name="default_touch_pct" class="form-control" value="<?= $item['default_touch_pct'] ?? 0 ?>"><small class="text-muted">Use 0 for copper, zinc etc.</small></div>
<div class="mb-3">
    <label class="form-label">Material Group *</label>
    <select name="material_group" class="form-select" id="materialGroupSelect">
        <option value="silver" <?= (($item['material_group'] ?? '') === 'silver') ? 'selected' : '' ?>>Silver Group (BAR, LOTUS, SWASTHIK...)</option>
        <option value="alloy" <?= (($item['material_group'] ?? '') === 'alloy') ? 'selected' : '' ?>>Alloy Group (COPPER, ZINC...)</option>
        <option value="other" <?= (($item['material_group'] ?? 'other') === 'other') ? 'selected' : '' ?>>Other</option>
    </select>
</div>
<div class="mb-3" id="defaultAlloyDiv" style="display:none">
    <div class="form-check">
        <input type="checkbox" name="is_default_alloy" value="1" class="form-check-input" id="isDefaultAlloy" <?= (($item['is_default_alloy'] ?? 0) == 1) ? 'checked' : '' ?>>
        <label class="form-check-label" for="isDefaultAlloy">Use as default alloy for touch suggestions</label>
    </div>
    <small class="text-muted">Only one alloy can be the default. Setting this will clear the flag from others.</small>
</div>
<button type="submit" class="btn btn-primary">Save</button>
<a href="<?= base_url('raw-material-types') ?>" class="btn btn-secondary ms-2">Cancel</a>
</form>
</div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function toggleAlloyDiv() {
    var sel = document.getElementById('materialGroupSelect');
    document.getElementById('defaultAlloyDiv').style.display = sel.value === 'alloy' ? '' : 'none';
}
document.getElementById('materialGroupSelect').addEventListener('change', toggleAlloyDiv);
toggleAlloyDiv();
</script>
<?= $this->endSection() ?>
