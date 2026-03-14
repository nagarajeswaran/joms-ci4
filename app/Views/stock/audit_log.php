<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5><i class="bi bi-journal-text"></i> Stock Audit Log</h5>
    <a href="<?= base_url('stock') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<form class="row g-2 mb-3" method="get">
    <div class="col-md-3">
        <input type="text" name="q" class="form-control form-control-sm" placeholder="Product / SKU..." value="<?= esc($q) ?>">
    </div>
    <div class="col-md-2">
        <select name="loc" class="form-select form-select-sm">
            <option value="">All Locations</option>
            <?php foreach ($locations as $l): ?>
                <option value="<?= $l['id'] ?>" <?= $locId == $l['id'] ? 'selected' : '' ?>><?= esc($l['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select name="type" class="form-select form-select-sm">
            <option value="">All Types</option>
            <option value="in" <?= $type=='in'?'selected':'' ?>>Stock In</option>
            <option value="out" <?= $type=='out'?'selected':'' ?>>Stock Out (Sale)</option>
            <option value="adjustment" <?= $type=='adjustment'?'selected':'' ?>>Adjustment</option>
            <option value="transfer_in" <?= $type=='transfer_in'?'selected':'' ?>>Transfer In</option>
            <option value="transfer_out" <?= $type=='transfer_out'?'selected':'' ?>>Transfer Out</option>
        </select>
    </div>
    <div class="col-md-2">
        <input type="date" name="from" class="form-control form-control-sm" value="<?= esc($from) ?>" placeholder="From date">
    </div>
    <div class="col-md-2">
        <input type="date" name="to" class="form-control form-control-sm" value="<?= esc($to) ?>" placeholder="To date">
    </div>
    <div class="col-md-1">
        <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
    </div>
</form>

<?php if (empty($transactions)): ?>
    <div class="alert alert-info">No transactions found.</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-sm table-hover table-bordered align-middle">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Date/Time</th>
                <th>Type</th>
                <th>Product</th>
                <th>Pattern</th>
                <th>Variation</th>
                <th>Location</th>
                <th class="text-center">Qty</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($transactions as $t): ?>
            <?php
            $typeClasses = [
                'in'           => 'bg-success',
                'out'          => 'bg-danger',
                'adjustment'   => 'bg-warning text-dark',
                'transfer_in'  => 'bg-info',
                'transfer_out' => 'bg-secondary',
            ];
            $typeLabels = [
                'in'           => 'Stock In',
                'out'          => 'Sale',
                'adjustment'   => 'Adjustment',
                'transfer_in'  => 'Transfer In',
                'transfer_out' => 'Transfer Out',
            ];
            $cls = $typeClasses[$t['type']] ?? 'bg-secondary';
            $lbl = $typeLabels[$t['type']] ?? $t['type'];
            ?>
            <tr>
                <td><?= $t['id'] ?></td>
                <td><small><?= $t['created_at'] ?></small></td>
                <td><span class="badge <?= $cls ?>"><?= $lbl ?></span></td>
                <td><?= esc($t['product_name']) ?><?= $t['sku'] ? ' <small class="text-muted">('.$t['sku'].')</small>' : '' ?></td>
                <td><?= $t['pat_is_default'] ? '<span class="text-muted fst-italic">Default</span>' : esc($t['pattern_name']) ?></td>
                <td><?= esc($t['variation_name']) ?><?= $t['variation_size'] ? ' '.$t['variation_size'].'"' : '' ?></td>
                <td><span class="badge bg-secondary"><?= esc($t['location_name']) ?></span></td>
                <td class="text-center fw-bold <?= in_array($t['type'], ['out','transfer_out']) ? 'text-danger' : 'text-success' ?>">
                    <?= in_array($t['type'], ['out','transfer_out']) ? '-' : '+' ?><?= $t['qty'] ?>
                </td>
                <td><small class="text-muted"><?= esc($t['note'] ?? '') ?><?= $t['ref_transfer_id'] ? ' [Tr#'.$t['ref_transfer_id'].']' : '' ?></small></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <small class="text-muted">Showing last 500 transactions</small>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
