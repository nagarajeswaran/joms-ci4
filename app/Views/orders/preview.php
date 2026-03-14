<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="card mb-3">
    <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
        <div>
            <strong><?= esc($order['title']) ?></strong>
            <?php if ($order['client_name']): ?>&nbsp;<span class="text-muted"><?= esc($order['client_name']) ?></span><?php endif; ?>
        </div>
        <div>
            <a href="<?= base_url('orders/view/' . $order['id']) ?>" class="btn btn-outline-secondary btn-sm me-1"><i class="bi bi-arrow-left"></i> Back</a>
            <?php $anyMissing = array_filter($items, fn($i) => !$i['has_qty']); ?>
            <?php if ($anyMissing): ?>
            <div class="alert alert-warning py-1 px-2 d-inline-block mb-0" style="font-size:12px;">
                <i class="bi bi-exclamation-triangle"></i> <?= count($anyMissing) ?> item(s) have no quantities entered.
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header py-2"><strong>Order Summary</strong></div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0" style="font-size:13px;">
            <thead>
                <tr><th>#</th><th>Product</th><th>Pattern</th><th>Stamp</th><th>Variations & Quantities</th><th>Total Pcs</th></tr>
            </thead>
            <tbody>
                <?php foreach ($items as $idx => $item): ?>
                <?php $total = array_sum($item['qty_map']); ?>
                <tr class="<?= !$item['has_qty'] ? 'table-warning' : '' ?>">
                    <td><?= $idx + 1 ?></td>
                    <td><strong><?= esc($item['product_name']) ?></strong></td>
                    <td><?= esc($item['pattern_name'] ?? '—') ?></td>
                    <td><?= esc($item['stamp_name'] ?? '—') ?></td>
                    <td>
                        <?php if (!$item['has_qty']): ?>
                        <span class="text-warning"><i class="bi bi-exclamation-triangle"></i> No quantities entered</span>
                        <?php else: ?>
                        <?php foreach ($item['variations'] as $v): ?>
                            <?php if (!empty($item['qty_map'][$v['id']])): ?>
                            <span class="badge bg-light text-dark border me-1" style="font-size:11px;"><?= esc($v['name']) ?>: <?= $item['qty_map'][$v['id']] ?></span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= $total > 0 ? $total : '—' ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">No items in order</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($items) && !$anyMissing): ?>
<div class="text-center">
    <form action="<?= base_url('orders/confirm/' . $order['id']) ?>" method="post" class="d-inline">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-success btn-lg"><i class="bi bi-check-circle"></i> Confirm Order & Generate Reports</button>
    </form>
</div>
<?php elseif (!empty($items)): ?>
<div class="text-center">
    <p class="text-warning">Please enter quantities for all items before confirming.</p>
</div>
<?php endif; ?>
<?php $this->endSection() ?>
