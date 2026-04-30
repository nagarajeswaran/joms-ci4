<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible"><button type="button" class="btn-close" data-bs-dismiss="alert"></button><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible"><button type="button" class="btn-close" data-bs-dismiss="alert"></button><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Gatti Stock Entry</h5>
    <a href="<?= base_url('gatti-stock') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<!-- Batch lookup -->
<div class="card mb-3" style="max-width:420px">
<div class="card-header"><strong>Find Batch</strong></div>
<div class="card-body">
<form method="get" action="<?= base_url('gatti-stock/entry') ?>" class="row g-2 align-items-end">
    <div class="col">
        <label class="form-label small mb-1">Batch Number</label>
        <input type="text" name="batch" id="batchNoInput" class="form-control" placeholder="e.g. GA001" value="<?= esc($batchNo ?? '') ?>" autofocus>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary">Load</button>
    </div>
</form>
</div>
</div>

<?php $newBatch = $newBatch ?? false; ?>
<?php if ($row || $newBatch): ?>

<?php if ($newBatch): ?>
<div class="alert alert-info mb-3" style="max-width:500px">
    <i class="bi bi-info-circle"></i> Batch <strong><?= esc($batchNo) ?></strong> does not exist yet — it will be created when you save.
</div>
<?php else: ?>
<!-- Batch summary -->
<?php $balance = (float)$row['weight_g'] - (float)$row['qty_issued_g']; ?>
<div class="card mb-3" style="max-width:500px">
<div class="card-header d-flex justify-content-between align-items-center">
    <strong><?= esc($row['batch_number']) ?><?= $row['job_number'] ? ' — Job ' . esc($row['job_number']) : '' ?></strong>
    <span class="badge <?= $row['weight_g'] > 0 ? 'bg-success' : 'bg-secondary' ?> fs-6"><?= number_format($row['weight_g'],4) ?> g</span>
</div>
<div class="card-body py-2">
<div class="row row-cols-auto g-3">
    <div class="col"><small class="text-muted d-block">Weight (g)</small><strong><?= number_format($row['weight_g'],4) ?></strong></div>
    <div class="col"><small class="text-muted d-block">Touch%</small><strong><?= $row['touch_pct'] ?>%</strong></div>
    <div class="col"><small class="text-muted d-block">Stamp</small><strong><?= esc($row['stamp_name'] ?? '-') ?></strong></div>
    <div class="col"><small class="text-muted d-block">Issued (g)</small><strong><?= number_format($row['qty_issued_g'],4) ?></strong></div>
    <div class="col"><small class="text-muted d-block">Balance (g)</small><span class="badge <?= $balance > 0 ? 'bg-success' : 'bg-secondary' ?>"><?= number_format($balance,4) ?></span></div>
</div>
</div>
</div>
<?php endif; ?>

<!-- Entry form -->
<div class="card" style="max-width:560px">
<div class="card-header"><strong>New Entry</strong></div>
<div class="card-body">
<form method="post" action="<?= base_url('gatti-stock/entry/save') ?>">
<?= csrf_field() ?>
<input type="hidden" name="batch_number" value="<?= esc($batchNo) ?>">

<div class="row g-2 mb-3">
    <div class="col-auto">
        <label class="form-label small mb-1">Direction</label><br>
        <div class="btn-group" role="group">
            <input type="radio" class="btn-check" name="entry_type" id="typeIn" value="in" checked onchange="onDirectionChange()">
            <label class="btn btn-outline-success" for="typeIn"><i class="bi bi-plus-circle"></i> IN</label>
            <input type="radio" class="btn-check" name="entry_type" id="typeOut" value="out" onchange="onDirectionChange()">
            <label class="btn btn-outline-danger" for="typeOut"><i class="bi bi-dash-circle"></i> OUT</label>
        </div>
    </div>
    <div class="col-auto">
        <label class="form-label small mb-1">Reason</label>
        <select name="reason" id="reasonSelect" class="form-select">
            <option value="purchase">Purchase</option>
            <option value="return">Return</option>
            <option value="adjustment_in">Adjustment (add)</option>
            <option value="other_in">Other</option>
        </select>
    </div>
</div>

<div class="row g-3 mb-3 align-items-end">
    <div class="col-auto">
        <label class="form-label mb-1">Weight (g) <span class="badge bg-primary">PRIMARY</span></label>
        <input type="number" step="0.0001" name="weight_g" id="weightG" class="form-control" placeholder="0.0000" style="width:150px" oninput="updateOutInfo()" required>
    </div>
    <div class="col-auto">
        <label class="form-label mb-1">Touch%</label>
        <input type="number" step="0.0001" name="touch_pct" class="form-control" style="width:110px" value="<?= !empty($row) ? $row['touch_pct'] : 0 ?>">
    </div>
    <div class="col-auto">
        <label class="form-label mb-1">Stamp</label>
        <?php if (!empty($row['stamp_id'])): ?>
        <div class="form-control-plaintext fw-bold"><?= esc($row['stamp_name'] ?? '-') ?></div>
        <input type="hidden" name="stamp_id" value="<?= $row['stamp_id'] ?>">
        <?php else: ?>
        <select name="stamp_id" class="form-select" style="width:160px">
            <option value="">-- Stamp --</option>
            <?php foreach ($stamps as $s): ?>
            <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
    </div>
</div>

<?php if (!$newBatch && !empty($row)): ?>
<div id="outInfo" class="alert alert-warning py-2 px-3 mb-3" style="display:none">
    <small>Current stock: <strong><?= number_format($row['weight_g'],4) ?> g</strong></small><br>
    <small>After removal: <strong id="afterRemoval">—</strong></small>
</div>
<?php endif; ?>

<div class="mb-3">
    <label class="form-label mb-1">Notes (optional)</label>
    <input type="text" name="notes" class="form-control" placeholder="Notes">
</div>

<button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Entry</button>
<a href="<?= base_url('gatti-stock/entry') ?>" class="btn btn-outline-secondary ms-2">Clear</a>
<?php if (!$newBatch && !empty($row)): ?>
<a href="<?= base_url('gatti-stock/view/'.$row['id']) ?>" class="btn btn-outline-primary ms-2">View Batch</a>
<?php endif; ?>
</form>
</div>
</div>

<?php else: ?>
<?php if ($batchNo): ?>
<div class="alert alert-warning">No batch number loaded. Type a batch number above and click Load.</div>
<?php endif; ?>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
var currentStock = <?= (!empty($row) && !($newBatch ?? false)) ? (float)$row['weight_g'] : 0 ?>;

function updateOutInfo() {
    var outInfoEl = document.getElementById('outInfo');
    if (!outInfoEl) return;
    var isOut = document.getElementById('typeOut').checked;
    if (!isOut) { outInfoEl.style.display = 'none'; return; }
    outInfoEl.style.display = '';
    var w = parseFloat(document.getElementById('weightG').value) || 0;
    var remaining = currentStock - w;
    document.getElementById('afterRemoval').textContent = remaining.toFixed(4) + ' g' + (remaining < 0 ? ' (INSUFFICIENT)' : '');
    document.getElementById('afterRemoval').style.color = remaining < 0 ? 'red' : 'inherit';
}
function onDirectionChange() {
    var isOut = document.getElementById('typeOut').checked;
    var outInfoEl = document.getElementById('outInfo');
    if (outInfoEl) outInfoEl.style.display = isOut ? '' : 'none';
    var sel = document.getElementById('reasonSelect');
    sel.innerHTML = isOut
        ? '<option value="used_in_prod">Used in Production</option><option value="damaged">Damaged / Loss</option><option value="adjustment_out">Adjustment (remove)</option><option value="other_out">Other</option>'
        : '<option value="purchase">Purchase</option><option value="return">Return</option><option value="adjustment_in">Adjustment (add)</option><option value="other_in">Other</option>';
    updateOutInfo();
}
</script>
<?= $this->endSection() ?>
