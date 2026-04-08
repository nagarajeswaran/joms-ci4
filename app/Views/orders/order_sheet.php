<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="mb-3 d-flex gap-2 flex-wrap align-items-center">
    <a href="<?= base_url('orders/view/' . $order['id']) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    <a href="<?= base_url('orders/partRequirements/' . $order['id']) ?>" class="btn btn-success btn-sm"><i class="bi bi-list-check"></i> Part Requirements</a>
    <button onclick="window.print()" class="btn btn-outline-dark btn-sm"><i class="bi bi-printer"></i> Print</button>
    <div class="btn-group btn-group-sm">
        <a href="<?= base_url('orders/orderSheetPdf/' . $order['id']) ?>" class="btn btn-danger" target="_blank">
            <i class="bi bi-file-pdf"></i> Order Sheet PDF
        </a>
        <button type="button" class="btn btn-danger dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="visually-hidden">More PDF formats</span>
        </button>
        <ul class="dropdown-menu">
            <li>
                <a class="dropdown-item" href="<?= base_url('orders/orderSheetPdf/' . $order['id']) ?>" target="_blank">
                    <i class="bi bi-file-pdf text-danger me-1"></i> Order Sheet (Full)
                </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li><span class="dropdown-item text-muted" style="font-size:12px;"><i class="bi bi-clock me-1"></i> More formats coming soon</span></li>
        </ul>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <strong>Order Sheet &mdash; <?= esc($order['title']) ?></strong>
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

        $activeGroups = [];
        foreach ($varsByGroup as $gName => $vars) {
            $active = array_values(array_filter($vars, fn($v) => ($item['qty_map'][$v['id']] ?? 0) > 0));
            if (!empty($active)) {
                $groupLabel  = !empty($active[0]['group_tamil_name']) ? $active[0]['group_tamil_name'] : $gName;
                $groupTotal  = array_sum(array_map(fn($v) => (int)($item['qty_map'][$v['id']] ?? 0), $active));
                $minSize     = min(array_map(fn($v) => (float)($v['size'] ?? 0), $active));
                $activeGroups[] = ['label' => $groupLabel, 'vars' => $active, 'total' => $groupTotal, 'min_size' => $minSize];
            }
        }
        usort($activeGroups, fn($a, $b) => $a['min_size'] <=> $b['min_size']);
        $productTotal = array_sum(array_column($activeGroups, 'total'));

        $displayName = !empty($item['pattern_tamil_name'])
            ? $item['pattern_tamil_name']
            : (!empty($item['pattern_name']) ? $item['pattern_name'] : $item['product_name']);
        ?>
        <div class="product-block p-3 <?= $idx > 0 ? 'border-top' : '' ?>">
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="fw-bold"><?= $idx + 1 ?>. <?= esc($displayName) ?></span>
                <?php if (!empty($item['pattern_code'])): ?><span class="badge bg-secondary" style="font-size:10px;"><?= esc($item['pattern_code']) ?></span><?php endif; ?>
                <?php if ($item['sku']): ?><small class="text-muted">(<?= esc($item['sku']) ?>)</small><?php endif; ?>
                <?php if ($item['stamp_name']): ?><span class="badge bg-warning text-dark"><i class="bi bi-bookmark"></i> <?= esc($item['stamp_name']) ?></span><?php endif; ?>
                <?php if ($productTotal > 0): ?><small class="text-muted">— <?= $productTotal ?> pcs</small><?php endif; ?>
            </div>

            <?php if (!empty($activeGroups)): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-2" style="font-size:12px; min-width:max-content;">
                    <thead>
                        <tr>
                            <?php foreach ($activeGroups as $gIdx => $g): ?>
                            <?php $cls = ($gIdx % 2 === 0) ? 'g-even' : 'g-odd'; ?>
                            <th colspan="<?= count($g['vars']) ?>" class="text-center <?= $cls ?>"><?= esc($g['label']) ?> (<?= $g['total'] ?> pcs)</th>
                            <?php endforeach; ?>
                        </tr>
                        <tr>
                            <?php foreach ($activeGroups as $gIdx => $g): ?>
                            <?php $cls = ($gIdx % 2 === 0) ? 'g-even' : 'g-odd'; ?>
                            <?php foreach ($g['vars'] as $v): ?>
                            <th class="text-center <?= $cls ?>" style="min-width:55px;"><?= esc($v['name']) ?></th>
                            <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php foreach ($activeGroups as $g): ?>
                            <?php foreach ($g['vars'] as $v): ?>
                            <td class="text-center fw-bold"><?= (int)($item['qty_map'][$v['id']] ?? 0) ?></td>
                            <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
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
    .sidebar, .sidebar-toggle, .top-bar, .btn, .btn-group, .alert { display:none!important; }
    .main-content { margin-left:0!important; padding:0!important; }
    .card { border:1px solid #ddd!important; box-shadow:none!important; }
    .card-header { background:#f8f9fa!important; }
    .product-block { page-break-inside: avoid; break-inside: avoid; }
    table { page-break-inside: avoid; break-inside: avoid; }
    .g-even { background: #fde8c8; }
    .g-odd  { background: #d4edda; }
}
</style>
<?php $this->endSection() ?>
