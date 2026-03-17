<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="mb-3 d-flex gap-2">
    <a href="<?= base_url('orders/view/' . $order['id']) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    <a href="<?= base_url('orders/partRequirements/' . $order['id']) ?>" class="btn btn-success btn-sm"><i class="bi bi-list-check"></i> Part Requirements</a>
    <button onclick="window.print()" class="btn btn-outline-dark btn-sm"><i class="bi bi-printer"></i> Print</button>
    <a href="<?= base_url('orders/orderSheetPdf/' . $order['id']) ?>" class="btn btn-danger btn-sm" target="_blank"><i class="bi bi-file-pdf"></i> PDF</a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <strong>Order Sheet — <?= esc($order['title']) ?></strong>
        <span>
            <?php if ($order['client_name']): ?><span class="text-muted me-2"><?= esc($order['client_name']) ?></span><?php endif; ?>
            <span class="badge bg-secondary"><?= ucfirst($order['status']) ?></span>
            <small class="text-muted ms-2"><?= date('d M Y', strtotime($order['created_at'])) ?></small>
        </span>
    </div>
    <div class="card-body p-0">
        <?php foreach ($items as $idx => $item): ?>
        <?php
        $varsByGroup = [];
        foreach ($item['variations'] as $v) $varsByGroup[$v['group_name']][] = $v;
        $hasQty = count($item['qty_map']) > 0;
        ?>
        <div class="p-3 <?= $idx > 0 ? 'border-top' : '' ?>">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="fw-bold"><?= $idx + 1 ?>. <?= esc($item['product_name']) ?></span>
                <?php if ($item['sku']): ?><small class="text-muted">(<?= esc($item['sku']) ?>)</small><?php endif; ?>
                <?php if ($item['pattern_name']): ?><span class="badge bg-info"><?= esc($item['pattern_name']) ?></span><?php endif; ?>
                <?php if ($item['stamp_name']): ?><span class="badge bg-warning text-dark"><i class="bi bi-bookmark"></i> <?= esc($item['stamp_name']) ?></span><?php endif; ?>
            </div>

            <?php if ($hasQty): ?>
            <div class="table-responsive">
            <?php foreach ($varsByGroup as $groupName => $vars): ?>
                <table class="table table-bordered table-sm mb-2" style="font-size:12px; min-width:max-content;">
                    <thead style="background:#f0f4f8;">
                        <tr>
                            <th style="min-width:100px;"><?= esc($groupName) ?></th>
                            <?php foreach ($vars as $v): ?>
                            <?php if (($item['qty_map'][$v['id']] ?? 0) > 0): ?>
                            <th class="text-center" style="min-width:70px;"><?= esc($v['name']) ?><br><small><?= $v['size'] ?>"</small></th>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-muted" style="font-size:11px;">Qty (pcs)</td>
                            <?php foreach ($vars as $v): ?>
                            <?php $qty = $item['qty_map'][$v['id']] ?? 0; ?>
                            <?php if ($qty > 0): ?>
                            <td class="text-center fw-bold"><?= (int)$qty ?></td>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-muted" style="font-size:12px;"><i class="bi bi-exclamation-triangle"></i> No quantities entered</div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php if (empty($items)): ?>
        <div class="text-center text-muted py-4">No items in this order.</div>
        <?php endif; ?>
    </div>
</div>
<?php $this->endSection() ?>
<?php $this->section('styles') ?>
<style>
@media print {
    .sidebar, .sidebar-toggle, .top-bar, .btn, .alert { display:none!important; }
    .main-content { margin-left:0!important; padding:0!important; }
    .card { border:1px solid #ddd!important; box-shadow:none!important; }
    .card-header { background:#f8f9fa!important; }
}
</style>
<?php $this->endSection() ?>
