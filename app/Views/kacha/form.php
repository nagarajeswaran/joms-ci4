<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-pencil"></i> Edit Kacha Lot — <?= esc($lot['lot_number']) ?></h5>
    <a href="<?= base_url('kacha') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<?php if ($lot['status'] === 'used'): ?>
<div class="alert alert-warning">
    <i class="bi bi-lock-fill"></i> This lot is <strong>Used</strong> and cannot be edited.
    <?php if ($lot['used_in_melt_job_id']): ?>
        Consumed in Melt Job #<?= $lot['used_in_melt_job_id'] ?>.
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<form method="post" action="<?= base_url('kacha/update/' . $lot['id']) ?>">
    <?= csrf_field() ?>
    <?php $ro = $lot['status'] === 'used' ? 'readonly disabled' : ''; ?>
    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Lot Number *</label>
                    <input type="text" name="lot_number" class="form-control" value="<?= esc($lot['lot_number']) ?>" required <?= $ro ?>>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Receipt Date</label>
                    <input type="date" name="receipt_date" class="form-control" value="<?= esc($lot['receipt_date'] ?? '') ?>" <?= $ro ?>>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Weight (g) *</label>
                    <input type="number" name="weight" class="form-control text-end" step="0.001" min="0.001" required value="<?= esc($lot['weight']) ?>" id="inpWeight" <?= $ro ?>>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Touch % *</label>
                    <input type="number" name="touch_pct" class="form-control text-end" step="0.0001" min="0" max="100" required value="<?= esc($lot['touch_pct']) ?>" id="inpTouch" <?= $ro ?>>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fine (g)</label>
                    <input type="text" class="form-control text-end bg-light" readonly id="inpFine" value="<?= number_format($lot['fine'], 4) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Source</label>
                    <select name="source_type" class="form-select" <?= $ro ?>>
                        <?php foreach (['purchase'=>'Purchase','internal'=>'Internal','part_order'=>'Part Order','melt_job'=>'Melt Job'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= $lot['source_type']===$v?'selected':'' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Party</label>
                    <input type="text" name="party" class="form-control" value="<?= esc($lot['party'] ?? '') ?>" <?= $ro ?>>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Test Touch %</label>
                    <input type="number" name="test_touch" class="form-control text-end" step="0.0001" min="0" max="100" value="<?= esc($lot['test_touch'] ?? '') ?>" <?= $ro ?>>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Test Number</label>
                    <input type="text" name="test_number" class="form-control" value="<?= esc($lot['test_number'] ?? '') ?>" <?= $ro ?>>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" <?= $ro ?>><?= esc($lot['notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
    </div>
    <?php if ($lot['status'] === 'available'): ?>
    <div class="text-end mt-3">
        <a href="<?= base_url('kacha') ?>" class="btn btn-secondary me-2">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
    </div>
    <?php endif; ?>
</form>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
var w = document.getElementById('inpWeight');
var t = document.getElementById('inpTouch');
var f = document.getElementById('inpFine');
function calc() { f.value = ((parseFloat(w.value)||0)*(parseFloat(t.value)||0)/100).toFixed(4); }
if (w && !w.readOnly) { w.addEventListener('input', calc); t.addEventListener('input', calc); }
</script>
<?= $this->endSection() ?>
