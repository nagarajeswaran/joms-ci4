<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Raw Material Stock</h5>
    <a href="<?= base_url('raw-materials/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Add Stock</a>
</div>
<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Types</option>
            <?php foreach ($types as $t): ?>
            <option value="<?= $t['id'] ?>" <?= $typeFilter == $t['id'] ? 'selected' : '' ?>><?= esc($t['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>
<div class="table-responsive">
<table class="table table-sm table-bordered table-hover">
<thead class="table-dark"><tr><th>#</th><th>Type</th><th>Weight (g)</th><th>Touch %</th><th>Fine (g)</th><th>Notes</th><th>Added At</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($items as $i => $row): ?>
<tr>
    <td><?= $i+1 ?></td>
    <td><?= esc($row['type_name']) ?></td>
    <td><?= number_format($row['weight_g'], 4) ?></td>
    <td><?= $row['touch_pct'] ?>%</td>
    <td><?= number_format($row['weight_g'] * $row['touch_pct'] / 100, 4) ?></td>
    <td><?= esc($row['notes']) ?></td>
    <td><?= $row['added_at'] ?></td>
    <td><a href="<?= base_url('raw-materials/delete/'.$row['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')">Del</a></td>
</tr>
<?php endforeach; ?>
<?php if (!$items): ?><tr><td colspan="8" class="text-center text-muted">No stock entries</td></tr><?php endif; ?>
</tbody>
</table>
</div>
<?= $this->endSection() ?>
