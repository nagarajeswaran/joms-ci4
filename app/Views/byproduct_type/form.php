<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="card" style="max-width:500px">
<div class="card-header"><strong><?= esc($title) ?></strong></div>
<div class="card-body">
<form method="post" action="<?= isset($item) ? base_url('byproduct-types/update/'.$item['id']) : base_url('byproduct-types/store') ?>">
<?= csrf_field() ?>
<div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" value="<?= esc($item['name'] ?? '') ?>" required></div>
<button type="submit" class="btn btn-primary">Save</button>
<a href="<?= base_url('byproduct-types') ?>" class="btn btn-secondary ms-2">Cancel</a>
</form>
</div>
</div>
<?= $this->endSection() ?>
