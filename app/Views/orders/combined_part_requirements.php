<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<?php
$orderLabels = array_map(fn($o) => $o['order_number'] ?: ('#'.$o['id']), $orders);
$orderStr    = implode(' · ', $orderLabels);
$clients     = array_unique(array_filter(array_column($orders, 'client_name')));
$clientStr   = implode(', ', $clients);
?>
<div class="mb-3 d-flex gap-2 flex-wrap no-print">
    <a href="<?= base_url('orders') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Orders</a>
    <button onclick="window.print()" class="btn btn-outline-dark btn-sm"><i class="bi bi-printer"></i> Print</button>
</div>

<div id="printArea">
    <!-- Header -->
    <table style="width:100%;margin-bottom:10px;font-size:13px;">
        <tr>
            <td><strong>Combined Part Requirements</strong></td>
            <td style="text-align:right;color:#666;font-size:12px;">Generated: <?= date('d M Y') ?></td>
        </tr>
        <tr>
            <td style="color:#555;"><strong>Orders:</strong> <?= esc($orderStr) ?></td>
            <td style="text-align:right;color:#666;font-size:12px;">Total products: <?= $totalProducts ?></td>
        </tr>
        <?php if ($clientStr): ?>
        <tr><td colspan="2" style="color:#555;"><strong>Clients:</strong> <?= esc($clientStr) ?></td></tr>
        <?php endif; ?>
    </table>
    <hr style="margin:6px 0 10px 0;">

    <!-- Main parts table -->
    <table class="req-table">
        <thead>
            <tr>
                <th>#</th>
                <th style="text-align:left;">Part Name</th>
                <th>Total Pcs</th>
                <th>Weight/pc (g)</th>
                <th>Est. Weight (g)</th>
                <th>Gatti Req (g)</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $totalWt = 0; $totalGatti = 0; $i = 1; $currentDept = null;
        $podiSummary = [];
        foreach ($combined as $partId => $data):
            $part      = $parts[$partId] ?? null;
            $tamilName = trim($part['tamil_name'] ?? '');
            $pName     = $tamilName !== '' ? $tamilName : ($part ? $part['name'] : 'Part #'.$partId);
            $deptName  = $part['dept_name'] ?? '—';
            $isMain    = !empty($part['is_main_part']);
            $gattiPkg  = (float)($part['gatti'] ?? 0);

            if ($isMain && isset($mainSetup[$partId])) {
                $wpp = (float)$mainSetup[$partId]['weight_per_kanni'];
            } else {
                $wpp = (float)($part['weight'] ?? 0);
            }

            $pcs      = round($data['part_pcs'], 2);
            $wt       = round($pcs * $wpp, 4);
            $gattiReq = $gattiPkg > 0 ? round($wt * $gattiPkg / 1000, 4) : 0;
            $totalWt    += $wt;
            $totalGatti += $gattiReq;

            $podiId  = $data['podi_id'] ?? null;
            if ($podiId && ($data['podi_pcs'] ?? 0) > 0) {
                $podiSummary[$podiId] = ($podiSummary[$podiId] ?? 0) + (float)$data['podi_pcs'];
            }
        ?>
        <?php if ($deptName !== $currentDept): $currentDept = $deptName; ?>
        <tr class="dept-row"><td colspan="6"><?= esc($deptName) ?></td></tr>
        <?php endif; ?>
        <tr>
            <td class="num"><?= $i++ ?></td>
            <td><?= esc($pName) ?><?= $isMain ? ' *' : '' ?></td>
            <td class="num"><?= $pcs ?></td>
            <td class="num"><?= $wpp ?: '—' ?></td>
            <td class="num"><?= $wt ?: '—' ?></td>
            <td class="num"><?= $gattiReq ?: '—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right;font-weight:bold;">TOTAL</td>
                <td class="num"><?= round($totalWt, 4) ?></td>
                <td class="num"><?= round($totalGatti, 4) ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- Podi summary -->
    <?php if (!empty($podiSummary)): ?>
    <div class="section-title">Podi Requirements</div>
    <table class="req-table">
        <thead>
            <tr>
                <th style="text-align:left;">Podi Name</th>
                <th>Total Units</th>
                <th>Weight/unit (g)</th>
                <th>Total Weight (g)</th>
            </tr>
        </thead>
        <tbody>
        <?php $totalPodiWt = 0; ?>
        <?php foreach ($podiSummary as $pid => $totalUnits): ?>
        <?php
        $podi   = $podies[$pid] ?? null;
        if (!$podi) continue;
        $podiWt = round($totalUnits * (float)$podi['weight'], 4);
        $totalPodiWt += $podiWt;
        ?>
        <tr>
            <td><?= esc($podi['name']) ?></td>
            <td class="num"><?= $totalUnits ?></td>
            <td class="num"><?= $podi['weight'] ?></td>
            <td class="num"><?= $podiWt ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align:right;font-weight:bold;">TOTAL</td>
                <td class="num"><?= round($totalPodiWt, 4) ?></td>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<style>
body { font-family: 'Latha', sans-serif; }
#printArea { font-size: 13px; }
.req-table { border-collapse: collapse; width: 100%; font-size: 12px; margin-bottom: 14px; }
.req-table thead th { background: #dce8f5; padding: 5px 8px; text-align: center; border: 1px solid #aaa; font-weight: bold; }
.req-table tbody td { padding: 4px 8px; border: 1px solid #ccc; }
.req-table tfoot td { background: #f0f4f8; font-weight: bold; border: 1px solid #aaa; padding: 4px 8px; }
.dept-row td { background: #e8f0fe; font-weight: bold; font-size: 12px; padding: 3px 8px; }
.num { text-align: right; }
.section-title { font-size: 13px; font-weight: bold; margin: 14px 0 5px 0; border-left: 3px solid #4a90d9; padding-left: 6px; }
@media print {
    .no-print { display: none !important; }
    #printArea { font-size: 11px; }
    .req-table { font-size: 11px; }
}
</style>
<?= $this->endSection() ?>
