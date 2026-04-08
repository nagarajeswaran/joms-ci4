<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Part Batch Stock</h5>
    <div class="d-flex gap-2">
        <a href="<?= base_url('part-stock/entry') ?>" class="btn btn-success btn-sm"><i class="bi bi-plus-circle"></i> Stock Entry</a>
        <a href="<?= base_url('part-stock/labels') ?>" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> Generate Labels</a>
    </div>
</div>
<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="part" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Parts</option>
            <?php foreach ($parts as $p): ?>
            <option value="<?= $p['id'] ?>" <?= $partFilter == $p['id'] ? 'selected' : '' ?>><?= esc($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <select name="stamp" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Stamps</option>
            <?php foreach ($stamps as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $stampFilter == $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>
<div class="table-responsive">
<table class="table table-sm table-bordered table-hover">
<thead class="table-dark"><tr>
    <th>Batch No</th><th>Part</th><th>Stamp</th><th>Pc Weight (g)</th><th>Touch%</th><th>Stock Weight (g)</th><th>Source Order</th><th>Created</th><th>Actions</th>
</tr></thead>
<tbody>
<?php foreach ($items as $row): ?>
<tr>
    <td><strong><?= esc($row['batch_number']) ?></strong></td>
    <td><?= esc($row['part_name']) ?><br><small class="text-muted"><?= esc($row['part_tamil']) ?></small></td>
    <td><?= esc($row['stamp_name'] ?? '-') ?></td>
    <td><?= $row['piece_weight_g'] ? number_format($row['piece_weight_g'],4) : '<span class="text-warning">Not set</span>' ?></td>
    <td><?= $row['touch_pct'] ?>%</td>
    <td><span class="badge <?= $row['weight_in_stock_g'] > 0 ? 'bg-success' : 'bg-secondary' ?>"><?= number_format($row['weight_in_stock_g'],4) ?> g</span><br><small class="text-muted">≈ <?= $row['qty_in_stock'] ?> pcs</small></td>
    <td><?= $row['source_part_order_id'] ? 'PARTORD-'.str_pad($row['source_part_order_id'],3,'0',STR_PAD_LEFT) : '-' ?></td>
    <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
    <td><a href="<?= base_url('part-stock/batch/'.$row['id']) ?>" class="btn btn-sm btn-outline-primary">View</a></td>
</tr>
<?php endforeach; ?>
<?php if (!$items): ?><tr><td colspan="9" class="text-center text-muted">No batches found</td></tr><?php endif; ?>
</tbody>
</table>
</div>
<?= $this->endSection() ?>
