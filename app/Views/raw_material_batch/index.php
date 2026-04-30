<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Raw Material Batch Stock</h5>
    <div>
        <a href="<?= base_url('raw-material-batches/entry') ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-search"></i> Batch Lookup</a>
        <a href="<?= base_url('raw-material-batches/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> New Batch</a>
    </div>
</div>

<form method="get" class="row g-2 mb-3 align-items-end">
    <div class="col-auto">
        <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Types</option>
            <?php foreach ($types as $t): ?>
            <option value="<?= $t['id'] ?>" <?= $typeFilter == $t['id'] ? 'selected' : '' ?>><?= esc($t['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <div class="form-check form-check-inline">
            <input type="checkbox" name="all" value="1" class="form-check-input" id="showAll" <?= $showAll ? 'checked' : '' ?> onchange="this.form.submit()">
            <label class="form-check-label" for="showAll" style="font-size:13px;">Show empty batches</label>
        </div>
    </div>
</form>

<div class="row mb-3">
    <div class="col-auto">
        <div class="card card-stat">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Total Weight</small>
                <div class="fw-bold"><?= number_format($totalWeight, 4) ?> g</div>
            </div>
        </div>
    </div>
    <div class="col-auto">
        <div class="card card-stat">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Total Fine</small>
                <div class="fw-bold"><?= number_format($totalFine, 4) ?> g</div>
            </div>
        </div>
    </div>
    <div class="col-auto">
        <div class="card card-stat">
            <div class="card-body py-2 px-3">
                <small class="text-muted">Batches</small>
                <div class="fw-bold"><?= count($batches) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
<table class="table table-sm table-bordered table-hover">
<thead class="table-dark">
<tr>
    <th>#</th>
    <th>Batch No</th>
    <th>Material Type</th>
    <th>Group</th>
    <th class="text-end">Stock (g)</th>
    <th class="text-end">Touch %</th>
    <th class="text-end">Fine (g)</th>
    <th>Actions</th>
</tr>
</thead>
<tbody>
<?php foreach ($batches as $i => $row): ?>
<tr class="<?= (float)$row['weight_in_stock_g'] <= 0 ? 'table-secondary' : '' ?>">
    <td><?= $i+1 ?></td>
    <td><strong><?= esc($row['batch_number']) ?></strong></td>
    <td><?= esc($row['type_name']) ?></td>
    <td><span class="badge bg-<?= $row['material_group'] === 'silver' ? 'secondary' : ($row['material_group'] === 'gold' ? 'warning text-dark' : 'info') ?>"><?= esc($row['material_group'] ?? 'other') ?></span></td>
    <td class="text-end"><?= number_format($row['weight_in_stock_g'], 4) ?></td>
    <td class="text-end"><?= $row['touch_pct'] ?>%</td>
    <td class="text-end"><?= number_format((float)$row['weight_in_stock_g'] * (float)$row['touch_pct'] / 100, 4) ?></td>
    <td>
        <a href="<?= base_url('raw-material-batches/view/'.$row['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
        <a href="<?= base_url('raw-material-batches/edit/'.$row['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
        <a href="<?= base_url('raw-material-batches/delete/'.$row['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete batch <?= esc($row['batch_number']) ?>?')"><i class="bi bi-trash"></i></a>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$batches): ?><tr><td colspan="8" class="text-center text-muted">No batches found</td></tr><?php endif; ?>
</tbody>
</table>
</div>
<?= $this->endSection() ?>