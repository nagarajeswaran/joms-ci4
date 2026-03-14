<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<?php
$orderLabels = array_map(fn($o) => $o['order_number'] ?: ('#'.$o['id']), $orders);
$orderStr    = implode(' · ', $orderLabels);
$clients     = array_unique(array_filter(array_column($orders, 'client_name')));
?>
<div class="mb-3">
    <a href="<?= base_url('orders') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Orders</a>
</div>
<div class="card">
    <div class="card-header">
        <strong>Combined Main Part Setup</strong>
        <div class="text-muted small mt-1">Combining: <?= esc($orderStr) ?><?= $clients ? ' &nbsp;|&nbsp; ' . esc(implode(', ', $clients)) : '' ?></div>
    </div>
    <div class="card-body">
        <?php if (empty($setupRows)): ?>
        <div class="alert alert-warning">No main parts found across selected orders. Please confirm the orders and generate Part Requirements first.</div>
        <a href="<?= base_url('orders') ?>" class="btn btn-secondary">Back</a>
        <?php else: ?>
        <form method="post" action="<?= base_url('orders/combined-part-requirements') ?>">
            <?= csrf_field() ?>
            <?php foreach ($orderIds as $oid): ?>
            <input type="hidden" name="order_ids[]" value="<?= $oid ?>">
            <?php endforeach; ?>

            <table class="table table-sm mb-3" style="font-size:13px;">
                <thead>
                    <tr>
                        <th>Part Name</th>
                        <th style="width:160px;">Kanni / Inch</th>
                        <th style="width:190px;">Weight / Kanni (g)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $lastOrderId = null; ?>
                    <?php foreach ($setupRows as $row): ?>
                    <?php if ($row['order_id'] !== $lastOrderId): $lastOrderId = $row['order_id']; ?>
                    <tr style="background:#e8f0fe;">
                        <td colspan="3" class="py-1 px-3">
                            <strong class="text-primary"><i class="bi bi-receipt"></i> <?= esc($row['order_label']) ?></strong>
                            <?php if ($row['no_setup'] ?? false): ?>
                            <span class="badge bg-warning text-dark ms-2" style="font-size:10px;">No saved setup — using defaults</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <input type="hidden" name="row_order_id[]" value="<?= $row['order_id'] ?>">
                        <input type="hidden" name="row_part_id[]"  value="<?= $row['part_id'] ?>">
                        <td class="ps-4"><?= esc($row['part_name']) ?></td>
                        <td><input type="number" class="form-control form-control-sm" name="row_kanni[]"  value="<?= $row['kanni_per_inch'] ?>"   step="0.0001"   min="0" required></td>
                        <td><input type="number" class="form-control form-control-sm" name="row_weight[]" value="<?= $row['weight_per_kanni'] ?>" step="0.000001" min="0" required></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="save_to_orders" id="saveBack" value="1">
                <label class="form-check-label" for="saveBack">
                    Save these values back to each order's Main Part Setup
                </label>
            </div>
            <button type="submit" class="btn btn-success"><i class="bi bi-list-check"></i> Generate Combined Part Requirements</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php $this->endSection() ?>
