<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-collection"></i> Batch: <?= esc($batch['batch_number']) ?></h5>
    <div>
        <a href="<?= base_url('raw-material-batches/edit/'.$batch['id']) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil"></i> Edit</a>
        <a href="<?= base_url('raw-material-batches/delete/'.$batch['id']) ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete batch <?= esc($batch['batch_number']) ?>? This will also delete its transaction logs.')"><i class="bi bi-trash"></i> Delete</a>
        <a href="<?= base_url('raw-material-batches') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr><th style="width:40%">Batch Number</th><td><strong><?= esc($batch['batch_number']) ?></strong></td></tr>
                    <tr><th>Material Type</th><td><?= esc($batch['type_name']) ?></td></tr>
                    <tr><th>Group</th><td><span class="badge bg-<?= $batch['material_group'] === 'silver' ? 'secondary' : ($batch['material_group'] === 'gold' ? 'warning text-dark' : 'info') ?>"><?= esc($batch['material_group'] ?? 'other') ?></span></td></tr>
                    <tr><th>Current Stock</th><td class="fw-bold text-primary"><?= number_format($batch['weight_in_stock_g'], 4) ?> g</td></tr>
                    <tr><th>Touch %</th><td><?= $batch['touch_pct'] ?>%</td></tr>
                    <tr><th>Fine</th><td><?= number_format((float)$batch['weight_in_stock_g'] * (float)$batch['touch_pct'] / 100, 4) ?> g</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><strong>Add Stock</strong></div>
            <div class="card-body">
                <form method="post" action="<?= base_url('raw-material-batches/add-stock/'.$batch['id']) ?>">
                    <?= csrf_field() ?>
                    <div class="row g-2">
                        <div class="col">
                            <label class="form-label">Weight (g)</label>
                            <input type="number" step="0.0001" name="weight_g" class="form-control form-control-sm" required>
                        </div>
                        <div class="col">
                            <label class="form-label">Touch %</label>
                            <input type="number" step="0.0001" name="touch_pct" class="form-control form-control-sm" value="<?= $batch['touch_pct'] ?>" required>
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label">Notes</label>
                        <input type="text" name="notes" class="form-control form-control-sm">
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm mt-2">Add Stock</button>
                </form>
            </div>
        </div>
    </div>
</div>

<h6>Transaction Log</h6>
<div class="table-responsive">
<table class="table table-sm table-bordered">
<thead class="table-dark">
<tr><th>Date</th><th>Type</th><th class="text-end">Weight (g)</th><th>Touch %</th><th>Reason</th><th>Ref</th><th>Notes</th></tr>
</thead>
<tbody>
<?php foreach ($logs as $log): ?>
<tr>
    <td><?= $log['created_at'] ?></td>
    <td><span class="badge bg-<?= $log['entry_type'] === 'in' ? 'success' : 'danger' ?>"><?= strtoupper($log['entry_type']) ?></span></td>
    <td class="text-end"><?= number_format($log['weight_g'], 4) ?></td>
    <td><?= $log['touch_pct'] ?>%</td>
    <td><?= esc($log['reason'] ?? '') ?></td>
    <td><?= $log['ref_type'] ? esc($log['ref_type']) . '#' . $log['ref_id'] : '-' ?></td>
    <td><?= esc($log['notes'] ?? '') ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$logs): ?><tr><td colspan="7" class="text-center text-muted">No log entries</td></tr><?php endif; ?>
</tbody>
</table>
</div>
<?= $this->endSection() ?>