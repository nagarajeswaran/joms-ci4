<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0"><?= esc($po['order_number']) ?></h5>
        <small class="text-muted">Karigar: <strong><?= esc($po['karigar_name']) ?></strong> (<?= esc($po['dept_name'] ?? '') ?>) | Cash: Rs<?= number_format($po['cash_rate_per_kg'],2) ?>/kg | Fine: <?= $po['fine_pct'] ?>%</small>
    </div>
    <span class="badge <?= $po['status']==='posted'?'bg-success':'bg-warning text-dark' ?> fs-6"><?= ucfirst($po['status']) ?></span>
</div>

<!-- ISSUED -->
<div class="card mb-3">
<div class="card-header d-flex justify-content-between">
    <strong>Gatti Issued</strong>
    <?php if ($po['status']==='draft'): ?>
    <button class="btn btn-outline-secondary btn-sm" onclick="toggleForm('issueForm')">+ Add Issue</button>
    <?php endif; ?>
</div>
<?php if ($po['status']==='draft'): ?>
<div id="issueForm" style="display:none" class="card-body border-bottom bg-light">
<form method="post" action="<?= base_url('part-orders/add-issue/'.$po['id']) ?>">
<?= csrf_field() ?>
<div class="row g-2">
    <div class="col">
        <select name="gatti_stock_id" class="form-select form-select-sm" required onchange="fillGattiTouch(this)">
            <option value="">-- Select Gatti --</option>
            <?php foreach ($gattiStock as $g): ?>
            <option value="<?= $g['id'] ?>" data-touch="<?= $g['touch_pct'] ?>">
                <?= esc($g['job_number'] ?? 'Manual') ?> | <?= number_format($g['weight_g']-$g['qty_issued_g'],2) ?>g avail | <?= $g['touch_pct'] ?>%
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto"><input type="number" step="0.0001" name="weight_g" class="form-control form-control-sm" placeholder="Weight (g)" required></div>
    <div class="col-auto">
        <select name="stamp_id" class="form-select form-select-sm">
            <option value="">Stamp (opt)</option>
            <?php foreach ($stamps as $s): ?><option value="<?= $s['id'] ?>"><?= esc($s['name']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Add</button></div>
</div>
</form>
</div>
<?php endif; ?>
<div class="card-body p-0">
<table class="table table-sm table-bordered mb-0">
<thead class="table-dark"><tr><th>Melt Job</th><th>Weight (g)</th><th>Touch%</th><th>Fine (g)</th><th>Stamp</th><th>Issued At</th><?php if ($po['status']==='draft'): ?><th></th><?php endif; ?></tr></thead>
<tbody>
<?php foreach ($issues as $row): ?>
<tr>
    <td><?= esc($row['job_number'] ?? '-') ?></td>
    <td><?= number_format($row['weight_g'],4) ?></td>
    <td><?= $row['touch_pct'] ?>%</td>
    <td><?= number_format($row['weight_g']*$row['touch_pct']/100,4) ?></td>
    <td><?= esc($row['stamp_name'] ?? '-') ?></td>
    <td><?= date('d/m/Y H:i', strtotime($row['issued_at'])) ?></td>
    <?php if ($po['status']==='draft'): ?>
    <td><a href="<?= base_url('part-orders/delete-issue/'.$row['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')">Del</a></td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
<?php if (!$issues): ?><tr><td colspan="7" class="text-center text-muted">No issues yet</td></tr><?php endif; ?>
</tbody>
<tfoot class="table-light"><tr><td colspan="3"><strong>Total Issued Fine</strong></td><td><strong><?= number_format($totalIssuedFine,4) ?>g</strong></td><td colspan="<?= $po['status']==='draft'?3:2 ?>"></td></tr></tfoot>
</table>
</div>
</div>

<!-- RECEIVED -->
<div class="card mb-3">
<div class="card-header d-flex justify-content-between">
    <strong>Received (Parts + Byproducts)</strong>
    <?php if ($po['status']==='draft'): ?>
    <button class="btn btn-outline-secondary btn-sm" onclick="toggleForm('recvForm')">+ Add Receive</button>
    <?php endif; ?>
</div>
<?php if ($po['status']==='draft'): ?>
<div id="recvForm" style="display:none" class="card-body border-bottom bg-light">
<form method="post" action="<?= base_url('part-orders/add-receive/'.$po['id']) ?>">
<?= csrf_field() ?>
<div class="row g-2 mb-2">
    <div class="col-auto"><select name="receive_type" class="form-select form-select-sm" onchange="toggleRecvType(this)">
        <option value="part">Part</option><option value="byproduct">Byproduct</option>
    </select></div>
    <div class="col-auto" id="partDiv">
        <select name="part_id" class="form-select form-select-sm"><option value="">-- Part --</option>
        <?php foreach ($parts as $p): ?><option value="<?= $p['id'] ?>"><?= esc($p['name']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto" id="batchNoDiv"><input type="text" name="batch_number" class="form-control form-control-sm" placeholder="Batch No (from label)"></div>
    <div class="col-auto" id="byprodDiv" style="display:none">
        <select name="byproduct_type_id" class="form-select form-select-sm"><option value="">-- Byproduct --</option>
        <?php foreach ($byprods as $b): ?><option value="<?= $b['id'] ?>"><?= esc($b['name']) ?></option><?php endforeach; ?>
        </select>
    </div>
</div>
<div class="row g-2">
    <div class="col-auto"><input type="number" step="0.0001" name="weight_g" id="recvWeight" class="form-control form-control-sm" placeholder="Weight (g)" required oninput="calcPcs()"></div>
    <div class="col-auto" id="pcWtDiv"><input type="number" step="0.0001" name="piece_weight_g" id="pcWt" class="form-control form-control-sm" placeholder="Pc Weight (g)" oninput="calcPcs()"></div>
    <div class="col-auto"><span class="form-control-plaintext form-control-sm" id="pcsCalc" style="min-width:80px">Pcs: -</span></div>
    <div class="col-auto"><input type="number" step="0.0001" name="touch_pct" class="form-control form-control-sm" placeholder="Touch%" value="0"></div>
    <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Add</button></div>
</div>
</form>
</div>
<?php endif; ?>
<div class="card-body p-0">
<table class="table table-sm table-bordered mb-0">
<thead class="table-dark"><tr><th>Type</th><th>Part / Byproduct</th><th>Batch No</th><th>Weight (g)</th><th>Pc Wt (g)</th><th>Pcs</th><th>Touch%</th><th>Fine (g)</th><?php if ($po['status']==='draft'): ?><th></th><?php endif; ?></tr></thead>
<tbody>
<?php foreach ($receives as $row): ?>
<tr>
    <td><?= ucfirst($row['receive_type']) ?></td>
    <td><?= $row['receive_type']==='part' ? esc($row['part_name']) : esc($row['byprod_name']) ?></td>
    <td><?= esc($row['batch_number'] ?? '-') ?></td>
    <td><?= number_format($row['weight_g'],4) ?></td>
    <td><?= $row['piece_weight_g'] ? number_format($row['piece_weight_g'],4) : '-' ?></td>
    <td><?= $row['qty'] ?></td>
    <td><?= $row['touch_pct'] ?>%</td>
    <td><?= number_format($row['weight_g']*$row['touch_pct']/100,4) ?></td>
    <?php if ($po['status']==='draft'): ?>
    <td><a href="<?= base_url('part-orders/delete-receive/'.$row['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')">Del</a></td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
<?php if (!$receives): ?><tr><td colspan="9" class="text-center text-muted">No receives yet</td></tr><?php endif; ?>
</tbody>
<tfoot class="table-light"><tr><td colspan="3"><strong>Total Recv Fine</strong></td><td></td><td></td><td></td><td></td><td><strong><?= number_format($totalRecvFine,4) ?>g</strong></td><?php if ($po['status']==='draft'): ?><td></td><?php endif; ?></tr></tfoot>
</table>
</div>
</div>

<!-- SUMMARY -->
<div class="card mb-3" style="max-width:500px">
<div class="card-header"><strong>Making Charge Summary</strong></div>
<table class="table table-sm table-borderless mb-0">
<tr><td>Total Issued Fine (g)</td><td class="text-end"><strong><?= number_format($totalIssuedFine,4) ?></strong></td></tr>
<tr><td>Total Received Fine (g)</td><td class="text-end"><?= number_format($totalRecvFine,4) ?></td></tr>
<tr class="table-warning"><td>Fine Difference (loss)</td><td class="text-end"><strong><?= number_format($fineDiff,4) ?></strong></td></tr>
<tr><td>Making Charge Fine (<?= $po['fine_pct'] ?>% of <?= number_format($totalPartsWeight,4) ?>g parts)</td><td class="text-end"><?= number_format($mcFine,4) ?></td></tr>
<tr class="table-danger"><td><strong>Net Fine Karigar Owes (g)</strong></td><td class="text-end"><strong><?= number_format($netFine,4) ?></strong></td></tr>
<tr class="table-success"><td><strong>Cash Making Charge (Rs)</strong></td><td class="text-end"><strong><?= number_format($mcCash,2) ?></strong></td></tr>
</table>
</div>

<?php if ($po['status']==='draft'): ?>
<form method="post" action="<?= base_url('part-orders/post/'.$po['id']) ?>" onsubmit="return confirm('Post to karigar ledger? This cannot be undone.')">
<?= csrf_field() ?>
<button type="submit" class="btn btn-danger"><i class="bi bi-check-circle"></i> Post to Ledger</button>
</form>
<?php endif; ?>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function toggleForm(id) { var el=document.getElementById(id); el.style.display=el.style.display===''?'none':''; }
function fillGattiTouch(sel) {}
function toggleRecvType(sel) {
    var isPart = sel.value === 'part';
    document.getElementById('partDiv').style.display    = isPart ? '' : 'none';
    document.getElementById('batchNoDiv').style.display = isPart ? '' : 'none';
    document.getElementById('byprodDiv').style.display  = isPart ? 'none' : '';
    document.getElementById('pcWtDiv').style.display    = isPart ? '' : 'none';
}
function calcPcs() {
    var w = parseFloat(document.getElementById('recvWeight').value)||0;
    var p = parseFloat(document.getElementById('pcWt').value)||0;
    document.getElementById('pcsCalc').textContent = p > 0 ? 'Pcs: '+Math.round(w/p) : 'Pcs: -';
}
</script>
<?= $this->endSection() ?>
