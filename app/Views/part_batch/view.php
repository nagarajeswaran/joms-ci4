<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible"><button type="button" class="btn-close" data-bs-dismiss="alert"></button><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible"><button type="button" class="btn-close" data-bs-dismiss="alert"></button><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="card mb-3" style="max-width:550px">
<div class="card-header d-flex justify-content-between align-items-center">
    <strong>Batch: <?= esc($batch['batch_number']) ?></strong>
    <span class="badge <?= $batch['weight_in_stock_g'] > 0 ? 'bg-success' : 'bg-secondary' ?> fs-6">
        <?= number_format($batch['weight_in_stock_g'], 4) ?> g
    </span>
</div>
<div class="card-body">
<table class="table table-sm table-borderless mb-0">
<tr><th>Part Name</th><td><?= esc($batch['part_name'] ?? '-') ?></td></tr>
<tr><th>Tamil Name</th><td><?= esc($batch['part_tamil'] ?? '-') ?></td></tr>
<tr><th>Batch Number</th><td><strong><?= esc($batch['batch_number']) ?></strong></td></tr>
<tr><th>Piece Weight</th><td><?= $batch['piece_weight_g'] ? number_format($batch['piece_weight_g'],4).' g' : '<span class="text-warning">Not set yet</span>' ?></td></tr>
<tr><th>Touch %</th><td><?= $batch['touch_pct'] ?>%</td></tr>
<tr><th>Stock Weight</th><td><strong><?= number_format($batch['weight_in_stock_g'],4) ?> g</strong> <small class="text-muted">(≈ <?= $batch['qty_in_stock'] ?> pcs)</small></td></tr>
<tr><th>Stamp</th><td><?= esc($batch['stamp_name'] ?? '-') ?></td></tr>
<tr><th>Received At</th><td><?= $batch['received_at'] ?? 'Not received yet' ?></td></tr>
<tr><th>Created</th><td><?= $batch['created_at'] ?></td></tr>
</table>
</div>
</div>

<div class="d-flex gap-2 mb-4 mt-1">
    <a href="<?= base_url('part-stock/entry?batch=' . urlencode($batch['batch_number'])) ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle"></i> Stock Entry for this Batch
    </a>
    <a href="<?= base_url('part-stock') ?>" class="btn btn-secondary btn-sm">Back to Stock</a>
</div>

<!-- Stock History -->
<h6 class="fw-bold mb-2"><i class="bi bi-clock-history"></i> Stock History</h6>
<?php if (empty($history)): ?>
<p class="text-muted">No stock movements yet.</p>
<?php else: ?>
<div class="table-responsive">
<table class="table table-sm table-bordered table-hover">
<thead class="table-dark">
<tr>
    <th>Date</th>
    <th>Direction</th>
    <th>Source</th>
    <th>Reason</th>
    <th>Weight (g)</th>
    <th>Pcs</th>
    <th>Touch%</th>
    <th>Notes</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($history as $row): ?>
<tr style="border-left: 4px solid <?= $row['entry_type'] === 'in' ? '#198754' : '#dc3545' ?>">
    <td class="text-nowrap"><?= date('d/m/y H:i', strtotime($row['created_at'])) ?></td>
    <td>
        <?php if ($row['entry_type'] === 'in'): ?>
        <span class="badge bg-success">▲ IN</span>
        <?php else: ?>
        <span class="badge bg-danger">▼ OUT</span>
        <?php endif; ?>
    </td>
    <td>
        <?php if ($row['source'] === 'part_order'): ?>
        <a href="<?= base_url('part-orders/view/' . $row['part_order_id']) ?>">Part Order #<?= $row['part_order_id'] ?></a>
        <?php else: ?>
        <span class="text-muted">Manual</span>
        <?php endif; ?>
    </td>
    <td><?= esc(ucwords(str_replace('_', ' ', $row['reason']))) ?></td>
    <td><?= number_format((float)$row['weight_g'], 4) ?></td>
    <td><?= (int)$row['qty'] ?></td>
    <td><?= $row['touch_pct'] ? $row['touch_pct'].'%' : '-' ?></td>
    <td><?= esc($row['notes'] ?? '') ?></td>
    <td class="text-nowrap">
        <?php if ($row['source'] === 'manual'): ?>
        <a href="<?= base_url('part-stock/stock-log/' . $row['id'] . '/edit') ?>" class="btn btn-outline-primary btn-sm py-0 px-1">Edit</a>
        <a href="<?= base_url('part-stock/stock-log/' . $row['id'] . '/delete') ?>"
           class="btn btn-outline-danger btn-sm py-0 px-1"
           onclick="return confirm('Delete this entry? Stock weight will be reversed.')">Del</a>
        <?php else: ?>
        <span class="text-muted">—</span>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
