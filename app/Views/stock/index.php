<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Stock Overview</h5>
    <div class="d-flex gap-2">
        <a href="<?= base_url('stock/entry') ?>" class="btn btn-success btn-sm"><i class="bi bi-plus-circle"></i> Add Stock</a>
        <a href="<?= base_url('stock/scan') ?>" class="btn btn-warning btn-sm"><i class="bi bi-qr-code-scan"></i> Scan & Deduct</a>
        <a href="<?= base_url('stock/transfer') ?>" class="btn btn-info btn-sm text-white"><i class="bi bi-arrow-left-right"></i> Transfer</a>
        <a href="<?= base_url('stock/low-stock') ?>" class="btn btn-danger btn-sm position-relative">
            <i class="bi bi-exclamation-triangle"></i> Low Stock
            <?php if ($lowCount > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= $lowCount ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= base_url('stock/min-stock') ?>" class="btn btn-outline-warning btn-sm"><i class="bi bi-bell"></i> Min Stock</a>
        <a href="<?= base_url('stock/audit-log') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-journal-text"></i> Audit Log</a>
    </div>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-md-4">
        <input type="text" name="q" class="form-control form-control-sm" placeholder="Search product, SKU..." value="<?= esc($q) ?>">
    </div>
    <div class="col-md-3">
        <select name="loc" class="form-select form-select-sm">
            <option value="">All Locations</option>
            <?php foreach ($locations as $loc): ?>
                <option value="<?= $loc['id'] ?>" <?= $locId == $loc['id'] ? 'selected' : '' ?>><?= esc($loc['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <div class="form-check mt-1">
            <input class="form-check-input" type="checkbox" name="low" value="1" id="chkLow" <?= $showLow ? 'checked' : '' ?>>
            <label class="form-check-label" for="chkLow">Low stock only</label>
        </div>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
    </div>
</form>

<?php if (empty($rows)): ?>
    <div class="alert alert-info">No stock records found. <a href="<?= base_url('stock/entry') ?>">Add stock</a> to get started.</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-sm table-hover table-bordered align-middle">
        <thead class="table-dark">
            <tr>
                <th>SKU</th>
                <th>Product</th>
                <th>Pattern</th>
                <th>Variation</th>
                <th>Location</th>
                <th class="text-center">Qty</th>
                <th class="text-center">Min Qty</th>
                <th class="text-center">Status</th>
                <th>QR Label</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <?php $isLow = $r['min_qty'] > 0 && $r['qty'] < $r['min_qty']; ?>
            <tr class="<?= $isLow ? 'table-warning' : '' ?>">
                <td><small class="text-muted"><?= esc($r['sku'] ?? '') ?></small></td>
                <td><?= esc($r['product_name']) ?></td>
                <td><?= $r['pat_is_default'] ? '<span class="text-muted fst-italic">Default</span>' : esc($r['pattern_name']) ?></td>
                <td><?= esc($r['variation_name']) ?> <?= $r['variation_size'] ? '('.$r['variation_size'].' in)' : '' ?></td>
                <td><span class="badge bg-secondary"><?= esc($r['location_name']) ?></span></td>
                <td class="text-center fw-bold <?= $isLow ? 'text-danger' : '' ?>"><?= $r['qty'] ?></td>
                <td class="text-center">
                    <?= $r['min_qty'] ?>
                    <a href="<?= base_url('stock/min-stock') ?>?product_id=<?= $r['product_id'] ?>&pattern_id=<?= $r['pattern_id'] ?>&location_id=<?= $r['location_id'] ?>" class="text-muted ms-1" title="Edit min stock" style="font-size:10px;"><i class="bi bi-pencil"></i></a>
                </td>
                <td class="text-center">
                    <?php if ($isLow): ?>
                        <span class="badge bg-danger">Low</span>
                    <?php elseif ($r['qty'] == 0): ?>
                        <span class="badge bg-secondary">Empty</span>
                    <?php else: ?>
                        <span class="badge bg-success">OK</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?= base_url("stock/qr-image/{$r['product_id']}/{$r['pattern_id']}/{$r['variation_id']}") ?>" target="_blank" class="btn btn-outline-secondary btn-sm py-0">
                        <i class="bi bi-qr-code"></i>
                    </a>
                </td>
                <td><small class="text-muted"><?= $r['updated_at'] ? date('d/m H:i', strtotime($r['updated_at'])) : '-' ?></small></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?= $this->endSection() ?>

