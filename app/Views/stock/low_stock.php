<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5><i class="bi bi-exclamation-triangle text-danger"></i> Low Stock Alert</h5>
    <div class="d-flex gap-2">
        <form method="post" action="<?= base_url('orders/fromLowStock') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-clipboard-plus"></i> Create Order from Low Stock</button>
        </form>
        <a href="<?= base_url('stock') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Stock</a>
    </div>
</div>

<?php if (empty($rows)): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> All stock levels are above minimum.</div>
<?php else: ?>
    <div class="alert alert-warning"><?= count($rows) ?> item(s) are at or below minimum stock level.</div>
    <div class="table-responsive">
        <table class="table table-sm table-hover table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>SKU</th>
                    <th>Product</th>
                    <th>Pattern</th>
                    <th>Variation</th>
                    <th>Location</th>
                    <th class="text-center">Current Qty</th>
                    <th class="text-center">Min Qty</th>
                    <th class="text-center">Shortage</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><small class="text-muted"><?= esc($r['sku'] ?? '') ?></small></td>
                    <td><?= esc($r['product_name']) ?></td>
                    <td><?= $r['pat_is_default'] ? '<span class="text-muted fst-italic">Default</span>' : esc($r['pattern_name']) ?></td>
                    <td><?= esc($r['variation_name']) ?> <?= $r['variation_size'] ? '('.$r['variation_size'].' in)' : '' ?></td>
                    <td><span class="badge bg-secondary"><?= esc($r['location_name']) ?></span></td>
                    <td class="text-center fw-bold text-danger"><?= $r['qty'] ?></td>
                    <td class="text-center"><?= $r['min_qty'] ?></td>
                    <td class="text-center text-danger fw-bold"><?= max(0, $r['min_qty'] - $r['qty']) ?></td>
                    <td>
                        <a href="<?= base_url("stock/entry?product_id={$r['product_id']}&pattern_id={$r['pattern_id']}") ?>" class="btn btn-success btn-sm py-0"><i class="bi bi-plus"></i> Add Stock</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
<?= $this->endSection() ?>
