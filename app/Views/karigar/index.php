<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Karigar</h5>
    <a href="<?= base_url('karigar/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Add Karigar</a>
</div>
<div class="table-responsive">
<table class="table table-sm table-bordered table-hover">
<thead class="table-dark"><tr>
    <th>#</th><th>Name</th><th>Tamil Name</th><th>Department</th><th>Cash Rate (&#8377;/kg)</th><th>Fine %</th><th>Actions</th>
</tr></thead>
<tbody>
<?php foreach ($items as $i => $row): ?>
<tr>
    <td><?= $i+1 ?></td>
    <td><?= esc($row['name']) ?></td>
    <td><?= esc($row['tamil_name']) ?></td>
    <td><?= esc($row['dept_name'] ?? '-') ?></td>
    <td><?= number_format($row['default_cash_rate'], 2) ?></td>
    <td><?= $row['default_fine_pct'] ?>%</td>
    <td>
        <a href="<?= base_url('karigar/edit/'.$row['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
        <a href="<?= base_url('karigar/delete/'.$row['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')">Del</a>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$items): ?><tr><td colspan="7" class="text-center text-muted">No karigar found</td></tr><?php endif; ?>
</tbody>
</table>
</div>
<?= $this->endSection() ?>
