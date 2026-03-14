<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Raw Material Types</h5>
    <a href="<?= base_url('raw-material-types/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Add</a>
</div>
<div class="table-responsive">
<table class="table table-sm table-bordered table-hover">
<thead class="table-dark"><tr><th>#</th><th>Name</th><th>Default Touch%</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($items as $i => $row): ?>
<tr>
    <td><?= $i+1 ?></td>
    <td><?= esc($row['name']) ?></td>
    <td><?= $row['default_touch_pct'] ?>%</td>
    <td>
        <a href="<?= base_url('raw-material-types/edit/'.$row['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
        <a href="<?= base_url('raw-material-types/delete/'.$row['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')">Del</a>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$items): ?><tr><td colspan="4" class="text-center text-muted">No types found</td></tr><?php endif; ?>
</tbody>
</table>
</div>
<?= $this->endSection() ?>
