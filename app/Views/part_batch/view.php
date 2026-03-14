<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="card" style="max-width:500px">
<div class="card-header d-flex justify-content-between">
    <strong>Batch: <?= esc($batch['batch_number']) ?></strong>
    <span class="badge <?= $batch['qty_in_stock'] > 0 ? 'bg-success' : 'bg-secondary' ?>">Stock: <?= $batch['qty_in_stock'] ?></span>
</div>
<div class="card-body">
<table class="table table-sm table-borderless mb-0">
<tr><th>Part Name</th><td><?= esc($batch['part_name']) ?></td></tr>
<tr><th>Tamil Name</th><td><?= esc($batch['part_tamil'] ?? '-') ?></td></tr>
<tr><th>Batch Number</th><td><strong><?= esc($batch['batch_number']) ?></strong></td></tr>
<tr><th>Piece Weight</th><td><?= $batch['piece_weight_g'] ? number_format($batch['piece_weight_g'],4).' g' : '<span class="text-warning">Not set yet</span>' ?></td></tr>
<tr><th>Touch %</th><td><?= $batch['touch_pct'] ?>%</td></tr>
<tr><th>Qty in Stock</th><td><strong><?= $batch['qty_in_stock'] ?></strong></td></tr>
<tr><th>Stamp</th><td><?= esc($batch['stamp_name'] ?? '-') ?></td></tr>
<tr><th>Received At</th><td><?= $batch['received_at'] ?? 'Not received yet' ?></td></tr>
<tr><th>Created</th><td><?= $batch['created_at'] ?></td></tr>
</table>
</div>
</div>
<a href="<?= base_url('part-stock') ?>" class="btn btn-secondary btn-sm mt-3">Back to Stock</a>
<?= $this->endSection() ?>
