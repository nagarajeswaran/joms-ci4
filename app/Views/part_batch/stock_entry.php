<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible"><button type="button" class="btn-close" data-bs-dismiss="alert"></button><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible"><button type="button" class="btn-close" data-bs-dismiss="alert"></button><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<h5 class="mb-3">Stock Entry</h5>

<!-- Batch lookup -->
<div class="card mb-3" style="max-width:420px">
<div class="card-header"><strong>Find Batch</strong></div>
<div class="card-body">
<form method="get" action="<?= base_url('part-stock/entry') ?>" class="row g-2 align-items-end">
    <div class="col">
        <label class="form-label small mb-1">Batch Number</label>
        <input type="text" name="batch" id="batchNoInput" class="form-control" placeholder="e.g. A0001" value="<?= esc($batchNo ?? '') ?>" autofocus>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary">Load</button>
    </div>
</form>
</div>
</div>

<?php $newBatch = $newBatch ?? false; ?>
<?php if ($batch || $newBatch): ?>

<?php if ($newBatch): ?>
<div class="alert alert-info mb-3" style="max-width:500px">
    <i class="bi bi-info-circle"></i> Batch <strong><?= esc($batchNo) ?></strong> does not exist yet — it will be created when you save.
</div>
<?php else: ?>
<!-- Batch info -->
<div class="card mb-3" style="max-width:500px">
<div class="card-header d-flex justify-content-between align-items-center">
    <strong><?= esc($batch['batch_number']) ?> — <?= esc($batch['part_name']) ?></strong>
    <span class="badge <?= $batch['weight_in_stock_g'] > 0 ? 'bg-success' : 'bg-secondary' ?> fs-6">
        <?= number_format($batch['weight_in_stock_g'], 4) ?> g
    </span>
</div>
<div class="card-body py-2">
<div class="row row-cols-auto g-3">
    <div class="col"><small class="text-muted d-block">Part</small><strong><?= esc($batch['part_name']) ?></strong></div>
    <div class="col"><small class="text-muted d-block">Pc Weight</small><strong><?= $batch['piece_weight_g'] ? number_format($batch['piece_weight_g'],4).' g' : '<span class="text-warning">Not set</span>' ?></strong></div>
    <div class="col"><small class="text-muted d-block">Stock Weight</small><strong><?= number_format($batch['weight_in_stock_g'],4) ?> g</strong></div>
    <div class="col"><small class="text-muted d-block">≈ Pcs</small><strong><?= $batch['qty_in_stock'] ?> pcs</strong></div>
    <div class="col"><small class="text-muted d-block">Touch%</small><strong><?= $batch['touch_pct'] ?>%</strong></div>
    <div class="col"><small class="text-muted d-block">Stamp</small><strong><?= esc($batch['stamp_name'] ?? '-') ?></strong></div>
</div>
</div>
</div>
<?php endif; ?>

<!-- Entry form -->
<?php if (!empty($logEntry)): ?>
<div class="alert alert-info py-2 px-3 mb-2" style="max-width:600px">
    <i class="bi bi-pencil-square"></i> Editing entry from <strong><?= date('d/m/Y H:i', strtotime($logEntry['created_at'])) ?></strong>
    <a href="<?= base_url('part-stock/batch/' . $batch['id']) ?>" class="float-end text-muted small">Cancel</a>
</div>
<?php endif; ?>
<div class="card" style="max-width:600px">
<div class="card-header"><strong><?= !empty($logEntry) ? 'Edit Entry' : 'New Entry' ?></strong></div>
<div class="card-body">
<?php
if (!empty($logEntry)) {
    $formAction = base_url('part-stock/stock-log/' . $logEntry['id'] . '/update');
} else {
    $formAction = base_url('part-stock/entry/save');
}
?>
<form method="post" action="<?= $formAction ?>">
<?= csrf_field() ?>
<?php if (empty($logEntry)): ?>
<input type="hidden" name="batch_number" value="<?= esc($batchNo) ?>">
<?php endif; ?>

<div class="row g-2 mb-3">
    <div class="col-auto">
        <label class="form-label small mb-1">Direction</label><br>
        <div class="btn-group" role="group">
            <input type="radio" class="btn-check" name="entry_type" id="typeIn" value="in" <?= empty($logEntry) || $logEntry['entry_type'] === 'in' ? 'checked' : '' ?> onchange="onDirectionChange()">
            <label class="btn btn-outline-success" for="typeIn"><i class="bi bi-plus-circle"></i> IN</label>
            <input type="radio" class="btn-check" name="entry_type" id="typeOut" value="out" <?= !empty($logEntry) && $logEntry['entry_type'] === 'out' ? 'checked' : '' ?> onchange="onDirectionChange()">
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
    <div class="col-auto">
        <label class="form-label small mb-1">Part
            <?php if (!empty($batch['part_id'])): ?>
            <small class="text-muted">(change only if label was wrong)</small>
            <?php endif; ?>
        </label>
        <select name="part_id" class="form-select form-select-sm" style="width:200px">
            <option value="">-- No Part --</option>
            <?php foreach ($parts as $p): ?>
            <option value="<?= $p['id'] ?>" <?= (!empty($batch) && $p['id'] == $batch['part_id']) ? 'selected' : '' ?>><?= esc($p['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="row g-3 mb-3 align-items-end">
    <div class="col-auto">
        <label class="form-label mb-1">Weight (g) <span class="badge bg-primary">PRIMARY</span></label>
        <input type="number" step="0.0001" name="weight_g" id="weightG" class="form-control" placeholder="0.0000" style="width:140px" value="<?= !empty($logEntry) ? $logEntry['weight_g'] : '' ?>" oninput="weightChanged()">
    </div>
    <div class="col-auto">
        <label class="form-label mb-1">Pc Weight (g)</label>
        <input type="number" step="0.0001" name="piece_weight_g" id="pcWtG" class="form-control" placeholder="0.0000" style="width:130px" value="<?= !empty($logEntry) ? ($logEntry['piece_weight_g'] ?? ($batch['piece_weight_g'] ?? '')) : ($batch['piece_weight_g'] ?? '') ?>" oninput="pcWtChanged()">
    </div>
    <div class="col-auto">
        <label class="form-label mb-1">Pcs <small class="text-muted">(auto or enter)</small></label>
        <input type="number" name="pcs" id="pcsField" class="form-control" placeholder="0" style="width:100px" oninput="pcsChanged()">
    </div>
</div>

<?php if (!$newBatch && !empty($batch)): ?>
<div id="outInfo" class="alert alert-warning py-2 px-3 mb-3" style="display:none">
    <small>Current stock: <strong><?= number_format($batch['weight_in_stock_g'],4) ?> g (<?= $batch['qty_in_stock'] ?> pcs)</strong></small><br>
    <small>After removal: <strong id="afterRemoval">—</strong></small>
</div>
<?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-auto">
        <label class="form-label mb-1">Touch%</label>
        <input type="number" step="0.0001" name="touch_pct" class="form-control" style="width:110px" value="<?= !empty($logEntry) ? $logEntry['touch_pct'] : (!empty($batch) ? ($batch['touch_pct'] ?? 0) : 0) ?>">
    </div>
    <div class="col-auto">
        <label class="form-label mb-1">Stamp</label>
        <?php if (!empty($batch['stamp_id'])): ?>
        <div class="form-control-plaintext fw-bold"><?= esc($batch['stamp_name'] ?? '-') ?> <small class="text-muted">(locked)</small></div>
        <input type="hidden" name="stamp_id" value="<?= $batch['stamp_id'] ?>">
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

<div class="mb-3">
    <label class="form-label mb-1">Notes (optional)</label>
    <input type="text" name="notes" class="form-control" placeholder="Notes" value="<?= esc($logEntry['notes'] ?? '') ?>">
</div>

<button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> <?= !empty($logEntry) ? 'Update Entry' : 'Save Entry' ?></button>
<a href="<?= !empty($logEntry) ? base_url('part-stock/batch/' . $batch['id']) : base_url('part-stock/entry') ?>" class="btn btn-outline-secondary ms-2"><?= !empty($logEntry) ? 'Cancel' : 'Clear' ?></a>
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
var currentStock = <?= (!empty($batch) && !($newBatch ?? false)) ? (float)$batch['weight_in_stock_g'] : 0 ?>;
var pcWt = <?= !empty($batch) ? (float)($batch['piece_weight_g'] ?? 0) : 0 ?>;

function weightChanged() {
    var w = parseFloat(document.getElementById('weightG').value) || 0;
    var p = parseFloat(document.getElementById('pcWtG').value) || pcWt;
    if (p > 0 && w > 0) {
        document.getElementById('pcsField').value = Math.round(w / p);
    } else {
        document.getElementById('pcsField').value = '';
    }
    updateOutInfo();
}
function pcsChanged() {
    var pcs = parseInt(document.getElementById('pcsField').value) || 0;
    var p   = parseFloat(document.getElementById('pcWtG').value) || pcWt;
    if (p > 0 && pcs > 0) {
        document.getElementById('weightG').value = (pcs * p).toFixed(4);
    }
    updateOutInfo();
}
function pcWtChanged() {
    var w = parseFloat(document.getElementById('weightG').value) || 0;
    var p = parseFloat(document.getElementById('pcWtG').value) || 0;
    if (p > 0 && w > 0) {
        document.getElementById('pcsField').value = Math.round(w / p);
    }
}
function updateOutInfo() {
    var outInfoEl = document.getElementById('outInfo');
    if (!outInfoEl) return;
    var isOut = document.getElementById('typeOut').checked;
    if (!isOut) return;
    var w = parseFloat(document.getElementById('weightG').value) || 0;
    var remaining = currentStock - w;
    document.getElementById('afterRemoval').textContent = remaining.toFixed(4) + ' g' + (remaining < 0 ? ' (INSUFFICIENT)' : '');
    document.getElementById('afterRemoval').style.color = remaining < 0 ? 'red' : 'inherit';
}
function onDirectionChange() {
    var outInfoEl = document.getElementById('outInfo');
    var isOut = document.getElementById('typeOut').checked;
    if (outInfoEl) outInfoEl.style.display = isOut ? '' : 'none';
    var sel = document.getElementById('reasonSelect');
    sel.innerHTML = isOut
        ? '<option value="used_in_prod">Used in Production</option><option value="damaged">Damaged / Loss</option><option value="sale">Sale / Dispatch</option><option value="adjustment_out">Adjustment (remove)</option><option value="other_out">Other</option>'
        : '<option value="purchase">Purchase</option><option value="return">Return</option><option value="adjustment_in">Adjustment (add)</option><option value="other_in">Other</option>';
    updateOutInfo();
}
</script>
<?= $this->endSection() ?>
