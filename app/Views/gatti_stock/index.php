<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h5 class="mb-3">Gatti Stock</h5>
<div class="table-responsive">
<table class="table table-sm table-bordered table-hover">
<thead class="table-dark"><tr><th>#</th><th>Melt Job</th><th>Weight (g)</th><th>Touch%</th><th>Fine (g)</th><th>Issued (g)</th><th>Balance (g)</th><th>Created</th></tr></thead>
<tbody>
<?php foreach ($items as $i => $row): ?>
<tr>
    <td><?= $i+1 ?></td>
    <td><?= $row['job_number'] ? '<a href="'.base_url('melt-jobs/view/'.$row['melt_job_id']).'">'.esc($row['job_number']).'</a>' : '-' ?></td>
    <td><?= number_format($row['weight_g'],4) ?></td>
    <td><?= $row['touch_pct'] ?>%</td>
    <td><?= number_format($row['weight_g']*$row['touch_pct']/100,4) ?></td>
    <td><?= number_format($row['qty_issued_g'],4) ?></td>
    <td><strong><?= number_format($row['weight_g']-$row['qty_issued_g'],4) ?></strong></td>
    <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$items): ?><tr><td colspan="8" class="text-center text-muted">No gatti stock</td></tr><?php endif; ?>
</tbody>
</table>
</div>
<?= $this->endSection() ?>
