<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h5 class="mb-3">Byproduct Stock</h5>
<div class="table-responsive">
<table class="table table-sm table-bordered table-hover">
<thead class="table-dark"><tr><th>#</th><th>Type</th><th>Weight (g)</th><th>Touch%</th><th>Fine (g)</th><th>Source</th><th>Added At</th></tr></thead>
<tbody>
<?php foreach ($items as $i => $row): ?>
<tr>
    <td><?= $i+1 ?></td>
    <td><?= esc($row['type_name']) ?></td>
    <td><?= number_format($row['weight_g'],4) ?></td>
    <td><?= $row['touch_pct'] ?>%</td>
    <td><?= number_format($row['weight_g']*$row['touch_pct']/100,4) ?></td>
    <td><?= $row['source_job_type'] ? ucfirst($row['source_job_type']).' #'.$row['source_job_id'] : '-' ?></td>
    <td><?= date('d/m/Y', strtotime($row['added_at'])) ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$items): ?><tr><td colspan="7" class="text-center text-muted">No byproduct stock</td></tr><?php endif; ?>
</tbody>
</table>
</div>
<?= $this->endSection() ?>
