<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<?php
$orderLabels  = array_map(fn($o) => $o['order_number'] ?: ('#'.$o['id']), $orders);
$orderStr     = implode(' · ', $orderLabels);
$clients      = array_unique(array_filter(array_column($orders, 'client_name')));
$clientStr    = implode(', ', $clients);
?>
<div class="mb-3 d-flex gap-2 flex-wrap no-print">
    <a href="<?= base_url('orders') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Orders</a>
    <button onclick="window.print()" class="btn btn-outline-dark btn-sm"><i class="bi bi-printer"></i> Print</button>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-start flex-wrap">
        <div>
            <strong>Combined Part Requirements</strong>
            <div class="text-muted small mt-1">Orders: <?= esc($orderStr) ?></div>
            <div class="text-muted small">Clients: <?= esc($clientStr ?: '—') ?></div>
        </div>
        <div class="text-end small text-muted">
            Generated: <?= date('d/m/Y') ?><br>
            Total products: <?= $totalProducts ?>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" style="font-size:13px;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Part Name</th>
                    <th>Dept</th>
                    <th class="text-end">Total Pcs</th>
                    <th class="text-end">Weight/pc (g)</th>
                    <th class="text-end">Est. Weight (g)</th>
                    <th class="text-end">Gatti Req (g)</th>
                    <th>Podi</th>
                    <th class="text-end">Podi Wt/pc</th>
                    <th class="text-end">Podi Qty</th>
                </tr>
            </thead>
            <tbody>
                <?php $totalPcs = 0; $totalWt = 0; $totalGatti = 0; $i = 1; $currentDept = null; ?>
                <?php foreach ($combined as $partId => $data): ?>
                <?php
                $part     = $parts[$partId] ?? null;
                $pName    = $part ? $part['name'] : ('Part #'.$partId);
                $deptName = $part['dept_name'] ?? '—';
                $isMain   = !empty($part['is_main_part']);
                $gattiPkg = (float)($part['gatti'] ?? 0);

                if ($isMain && isset($mainSetup[$partId])) {
                    $wpp = (float)$mainSetup[$partId]['weight_per_kanni'];
                } else {
                    $wpp = (float)($part['weight'] ?? 0);
                }

                $pcs      = round($data['part_pcs'], 2);
                $wt       = round($pcs * $wpp, 4);
                $gattiReq = $gattiPkg > 0 ? round($wt * $gattiPkg / 1000, 4) : 0;
                $podiId   = $data['podi_id'] ?? null;
                $podi     = $podiId ? ($podies[$podiId] ?? null) : null;
                $podiQty  = round($data['podi_pcs'] ?? 0, 2);
                $totalPcs   += $pcs;
                $totalWt    += $wt;
                $totalGatti += $gattiReq;
                ?>
                <?php if ($deptName !== $currentDept): $currentDept = $deptName; ?>
                <tr style="background:#e8f0fe;">
                    <td colspan="10" class="py-1 px-3">
                        <small class="fw-bold text-primary"><i class="bi bi-building"></i> <?= esc($deptName) ?></small>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>
                        <strong><?= esc($pName) ?></strong>
                        <?php if ($isMain): ?><span class="badge bg-success ms-1" style="font-size:10px;">Main</span><?php endif; ?>
                    </td>
                    <td class="text-muted small"><?= esc($deptName) ?></td>
                    <td class="text-end"><?= $pcs > 0 ? number_format($pcs, 2) : '—' ?></td>
                    <td class="text-end"><?= $wpp > 0 ? number_format($wpp, 4) : '—' ?></td>
                    <td class="text-end"><?= $wt > 0 ? number_format($wt, 4) : '—' ?></td>
                    <td class="text-end"><?= $gattiReq > 0 ? number_format($gattiReq, 4) : '—' ?></td>
                    <td><?= $podi ? esc($podi['name']) : '—' ?></td>
                    <td class="text-end"><?= ($podi && $podi['weight'] > 0) ? number_format($podi['weight'], 4) : '—' ?></td>
                    <td class="text-end"><?= $podiQty > 0 ? number_format($podiQty, 2) : '—' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="fw-bold" style="background:#f8f9fa;">
                    <td colspan="3">Total</td>
                    <td class="text-end"><?= number_format($totalPcs, 2) ?></td>
                    <td></td>
                    <td class="text-end"><?= number_format($totalWt, 4) ?></td>
                    <td class="text-end"><?= number_format($totalGatti, 4) ?></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<style>
@media print {
    .no-print { display: none !important; }
    .card { border: none; }
    .card-header { background: #fff !important; color: #000 !important; }
}
</style>
<?= $this->endSection() ?>
