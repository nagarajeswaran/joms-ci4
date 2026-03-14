<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h5 class="mb-3">Karigar Ledger Summary</h5>
<div class="table-responsive">
<table class="table table-sm table-bordered table-hover">
<thead class="table-dark"><tr><th>Karigar</th><th>Department</th><th>Fine Balance (g)</th><th>Cash Balance (Rs)</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($karigars as $row): ?>
<tr>
    <td><?= esc($row['name']) ?><?= $row['tamil_name'] ? '<br><small class="text-muted">'.esc($row['tamil_name']).'</small>' : '' ?></td>
    <td><?= esc($row['dept_name'] ?? '-') ?></td>
    <td class="<?= $row['fine_balance'] > 0 ? 'text-danger' : 'text-success' ?>"><strong><?= number_format($row['fine_balance'],4) ?></strong></td>
    <td class="<?= $row['cash_balance'] > 0 ? 'text-success' : 'text-danger' ?>"><strong><?= number_format($row['cash_balance'],2) ?></strong></td>
    <td>
        <a href="<?= base_url('karigar-ledger/'.$row['id']) ?>" class="btn btn-sm btn-outline-primary">Ledger</a>
        <a href="<?= base_url('karigar-ledger/'.$row['id'].'/convert') ?>" class="btn btn-sm btn-outline-secondary">Convert</a>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$karigars): ?><tr><td colspan="5" class="text-center text-muted">No karigars found</td></tr><?php endif; ?>
</tbody>
</table>
</div>
<?= $this->endSection() ?>
