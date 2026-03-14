<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0"><i class="bi bi-layers"></i> Merge Orders</h5>
        <small class="text-muted">Merging: <?= implode(' · ', array_column($orders, 'order_number')) ?></small>
    </div>
    <a href="<?= base_url('orders') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Cancel</a>
</div>

<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<form method="post" action="<?= base_url('orders/do-merge') ?>">
<?= csrf_field() ?>
<?php foreach ($orderIds as $oid): ?>
<input type="hidden" name="order_ids[]" value="<?= (int)$oid ?>">
<?php endforeach; ?>

<div class="row g-3">

<!-- Left: New order details -->
<div class="col-md-4">
    <div class="card">
        <div class="card-header fw-semibold">New Merged Order</div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Title *</label>
                <input type="text" name="new_title" class="form-control" value="Merged — <?= date('d/m/Y') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Client</label>
                <select name="new_client_id" class="form-select">
                    <option value="">-- No Client --</option>
                    <?php foreach ($clients as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="alert alert-warning py-2 small mb-0">
                <i class="bi bi-exclamation-triangle"></i>
                Source orders will be marked <strong>Closed</strong>. Part Requirements must be regenerated on the new order.
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn-primary w-100 mt-3">
        <i class="bi bi-layers"></i> Merge → Create New Order
    </button>
</div>

<!-- Right: Products summary -->
<div class="col-md-8">
    <?php $grandTotal = 0; ?>
    <?php foreach ($orders as $order): ?>
    <div class="card mb-2">
        <div class="card-header d-flex justify-content-between py-2">
            <span class="fw-semibold"><?= esc($order['order_number']) ?> — <?= esc($order['title']) ?></span>
            <span class="text-muted small">
                <?= $order['client_name'] ? esc($order['client_name']) . ' · ' : '' ?>
                <span class="badge <?= $order['status']==='draft'?'bg-warning text-dark':'bg-primary' ?>"><?= ucfirst($order['status']) ?></span>
            </span>
        </div>
        <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>SKU</th><th>Product</th><th>Pattern</th><th class="text-end">Qty</th></tr>
            </thead>
            <tbody>
            <?php foreach ($order['items'] as $j => $item): $grandTotal++; ?>
            <tr>
                <td><?= $j + 1 ?></td>
                <td><span class="badge bg-secondary"><?= esc($item['sku'] ?? '—') ?></span></td>
                <td class="fw-semibold"><?= esc($item['product_name']) ?></td>
                <td><?= esc($item['pattern_name']) ?></td>
                <td class="text-end"><?= (int)($item['total_qty'] ?? 0) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <div class="card-footer py-1 text-muted small text-end"><?= count($order['items']) ?> products</div>
    </div>
    <?php endforeach; ?>
    <div class="card">
        <div class="card-body py-2 d-flex justify-content-between align-items-center">
            <strong>Total products in merged order</strong>
            <span class="fs-5 fw-bold text-primary"><?= $grandTotal ?></span>
        </div>
    </div>
</div>

</div>
</form>

<?= $this->endSection() ?>
