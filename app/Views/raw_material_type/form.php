<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="card" style="max-width:500px">
<div class="card-header"><strong><?= esc($title) ?></strong></div>
<div class="card-body">
<form method="post" action="<?= isset($item) ? base_url('raw-material-types/update/'.$item['id']) : base_url('raw-material-types/store') ?>">
<?= csrf_field() ?>
<div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" value="<?= esc($item['name'] ?? '') ?>" required></div>
<div class="mb-3"><label class="form-label">Default Touch %</label><input type="number" step="0.0001" name="default_touch_pct" class="form-control" value="<?= $item['default_touch_pct'] ?? 0 ?>"><small class="text-muted">Use 0 for copper, zinc etc.</small></div>
<button type="submit" class="btn btn-primary">Save</button>
<a href="<?= base_url('raw-material-types') ?>" class="btn btn-secondary ms-2">Cancel</a>
</form>
</div>
</div>
<?= $this->endSection() ?>
