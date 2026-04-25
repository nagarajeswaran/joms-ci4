<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-search"></i> Raw Material Batch Lookup</h5>
    <a href="<?= base_url('raw-material-batches') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to List</a>
</div>

<form method="get" class="row g-2 mb-4 align-items-end" style="max-width:400px">
    <div class="col">
        <label class="form-label">Batch Number</label>
        <input type="text" name="batch" class="form-control" value="<?= esc($batchCode) ?>" placeholder="e.g. ZN1" autofocus>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
    </div>
</form>

<?php if ($batchCode && !$batch): ?>
    <div class="alert alert-warning">Batch <strong><?= esc($batchCode) ?></strong> not found.
        <a href="<?= base_url('raw-material-batches/create') ?>" class="alert-link">Create new batch?</a>
    </div>
<?php elseif ($batch): ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><small class="text-muted">Batch</small><div class="fw-bold fs-5"><?= esc($batch['batch_number']) ?></div></div>
                <div class="col-md-3"><small class="text-muted">Type</small><div><?= esc($batch['type_name']) ?> <span class="badge bg-<?= $batch['material_group'] === 'silver' ? 'secondary' : ($batch['material_group'] === 'gold' ? 'warning text-dark' : 'info') ?>"><?= esc($batch['material_group'] ?? 'other') ?></span></div></div>
                <div class="col-md-2"><small class="text-muted">Stock</small><div class="fw-bold text-primary"><?= number_format($batch['weight_in_stock_g'], 4) ?> g</div></div>
                <div class="col-md-2"><small class="text-muted">Touch</small><div><?= $batch['touch_pct'] ?>%</div></div>
                <div class="col-md-2"><small class="text-muted">Fine</small><div><?= number_format((float)$batch['weight_in_stock_g'] * (float)$batch['touch_pct'] / 100, 4) ?> g</div></div>
            </div>
        </div>
    </div>

    <div class="card mb-3" style="max-width:500px">
        <div class="card-header"><strong>Add Stock to <?= esc($batch['batch_number']) ?></strong></div>
        <div class="card-body">
            <form method="post" action="<?= base_url('raw-material-batches/add-stock/'.$batch['id']) ?>">
                <?= csrf_field() ?>
                <div class="row g-2">
                    <div class="col"><label class="form-label">Weight (g)</label><input type="number" step="0.0001" name="weight_g" class="form-control form-control-sm" required></div>
                    <div class="col"><label class="form-label">Touch %</label><input type="number" step="0.0001" name="touch_pct" class="form-control form-control-sm" value="<?= $batch['touch_pct'] ?>" required></div>
                </div>
                <div class="mt-2"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control form-control-sm"></div>
                <button type="submit" class="btn btn-primary btn-sm mt-2">Add Stock</button>
            </form>
        </div>
    </div>

    <?php if ($logs): ?>
    <h6>Transaction Log</h6>
    <div class="table-responsive">
    <table class="table table-sm table-bordered">
    <thead class="table-dark"><tr><th>Date</th><th>Type</th><th class="text-end">Weight (g)</th><th>Touch %</th><th>Reason</th><th>Notes</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $log): ?>
    <tr>
        <td><?= $log['created_at'] ?></td>
        <td><span class="badge bg-<?= $log['entry_type'] === 'in' ? 'success' : 'danger' ?>"><?= strtoupper($log['entry_type']) ?></span></td>
        <td class="text-end"><?= number_format($log['weight_g'], 4) ?></td>
        <td><?= $log['touch_pct'] ?>%</td>
        <td><?= esc($log['reason'] ?? '') ?></td>
        <td><?= esc($log['notes'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
    <?php endif; ?>
<?php endif; ?>
<?= $this->endSection() ?>