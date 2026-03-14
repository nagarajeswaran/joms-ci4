<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="<?= base_url('orders/view/' . $order['id']) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Order</a>
    <a href="<?= base_url('orders/partRequirements/' . $order['id']) ?>" class="btn btn-success btn-sm"><i class="bi bi-list-check"></i> Part Requirements</a>
    <button onclick="window.print()" class="btn btn-outline-dark btn-sm"><i class="bi bi-printer"></i> Print</button>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong><i class="bi bi-droplet"></i> Touch Analysis — <?= esc($order['title']) ?></strong>
        <small class="text-muted"><?= esc($order['client_name'] ?? '') ?> &nbsp; <?= date('d M Y', strtotime($order['created_at'])) ?></small>
    </div>

    <?php if (empty($groupData)): ?>
    <div class="card-body text-center text-muted py-4">
        No department group data found. Make sure parts have departments with groups assigned.
    </div>
    <?php else: ?>
    <form action="<?= base_url('orders/saveTouchAnalysis/' . $order['id']) ?>" method="post" id="touchForm">
        <?= csrf_field() ?>
        <div class="card-body p-0">
            <table class="table table-bordered mb-0" style="font-size:13px;">
                <thead style="background:#f8f9fa;">
                    <tr>
                        <th>#</th>
                        <th>Department Group</th>
                        <th class="text-end">Total Weight (g)</th>
                        <th class="text-center" style="width:160px;">Melting Touch (%)</th>
                        <th class="text-end">Total Pure (g)</th>
                    </tr>
                </thead>
                <tbody id="touchBody">
                    <?php $i = 1; $totalWt = 0; $totalPure = 0; ?>
                    <?php foreach ($groupData as $gn => $wt): ?>
                    <?php $tv = $savedTouch[$gn] ?? 0; $pure = round($wt * $tv / 100, 4); $totalWt += $wt; $totalPure += $pure; ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>
                            <strong><?= esc($gn) ?></strong>
                            <input type="hidden" name="group_name[]" value="<?= esc($gn) ?>">
                        </td>
                        <td class="text-end row-wt"><?= number_format($wt, 4) ?></td>
                        <td class="text-center">
                            <input type="number"
                                   name="touch_value[]"
                                   value="<?= $tv ?>"
                                   min="0" max="100" step="0.01"
                                   class="form-control form-control-sm text-center touch-input"
                                   data-wt="<?= $wt ?>"
                                   oninput="recalcRow(this)">
                        </td>
                        <td class="text-end row-pure"><?= number_format($pure, 4) ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <?php
                    $totalPodiWt = array_sum($podiGroupData ?? []);
                    if ($totalPodiWt > 0):
                        $podiTv   = $savedTouch['Podi'] ?? 0;
                        $podiPure = round($totalPodiWt * $podiTv / 100, 4);
                        $totalWt    += $totalPodiWt;
                        $totalPure  += $podiPure;
                    ?>
                    <tr class="table-info">
                        <td>—</td>
                        <td>
                            <strong><i class="bi bi-droplet text-info"></i> Total Podi Weight</strong>
                            <input type="hidden" name="group_name[]" value="Podi">
                        </td>
                        <td class="text-end row-wt"><?= number_format($totalPodiWt, 4) ?></td>
                        <td class="text-center">
                            <input type="number"
                                   name="touch_value[]"
                                   value="<?= $podiTv ?>"
                                   min="0" max="100" step="0.01"
                                   class="form-control form-control-sm text-center touch-input"
                                   data-wt="<?= $totalPodiWt ?>"
                                   oninput="recalcRow(this)">
                        </td>
                        <td class="text-end row-pure"><?= number_format($podiPure, 4) ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-dark fw-bold">
                        <td colspan="2" class="text-end">TOTAL</td>
                        <td class="text-end" id="footerWt"><?= number_format($totalWt, 4) ?></td>
                        <td class="text-center">
                            <span class="text-warning" id="footerTouch"><?= $totalWt > 0 ? number_format($totalPure / $totalWt * 100, 2) : '0.00' ?></span>
                            <small class="text-muted d-block" style="font-size:10px;">Req. Touch %</small>
                        </td>
                        <td class="text-end" id="footerPure"><?= number_format($totalPure, 4) ?></td>
                    </tr>
                    <tr class="table-secondary" style="font-size:12px;">
                        <td colspan="2" class="text-end text-muted">Required Touch = (Total Pure / Total Weight) × 100</td>
                        <td colspan="3" class="text-muted">Includes all department groups + podi weight.</td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="p-3 border-top d-flex gap-3 align-items-center">
            <button type="submit" class="btn btn-warning btn-sm"><i class="bi bi-save"></i> Save Touch Values</button>
            <small class="text-muted">Touch values are saved per order. Edit and save anytime.</small>
        </div>
    </form>
    <?php endif; ?>
</div>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
function recalcRow(input) {
    var row   = input.closest('tr');
    var wt    = parseFloat(input.dataset.wt) || 0;
    var touch = parseFloat(input.value) || 0;
    row.querySelector('.row-pure').textContent = (wt * touch / 100).toFixed(4);
    recalcFooter();
}

function recalcFooter() {
    var totalWt   = 0;
    var totalPure = 0;
    document.querySelectorAll('#touchBody tr').forEach(function(row) {
        var wtCell   = row.querySelector('.row-wt');
        var pureCell = row.querySelector('.row-pure');
        if (wtCell)   totalWt   += parseFloat(wtCell.textContent.replace(/,/g, ''))   || 0;
        if (pureCell) totalPure += parseFloat(pureCell.textContent.replace(/,/g, '')) || 0;
    });
    document.getElementById('footerWt').textContent    = totalWt.toFixed(4);
    document.getElementById('footerPure').textContent  = totalPure.toFixed(4);
    document.getElementById('footerTouch').textContent = totalWt > 0 ? (totalPure / totalWt * 100).toFixed(2) : '0.00';
}
</script>
<?php $this->endSection() ?>

<?php $this->section('styles') ?>
<style>
@media print {
    .sidebar, .sidebar-toggle, .top-bar, .btn, form .border-top { display:none!important; }
    .main-content { margin-left:0!important; padding:0!important; }
    .card { border:1px solid #ddd!important; box-shadow:none!important; }
    input.form-control { border:none!important; background:transparent!important; padding:0!important; width:auto!important; text-align:center; }
}
</style>
<?php $this->endSection() ?>
