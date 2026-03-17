<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="row justify-content-center">
<div class="col-md-6">
<div class="d-flex justify-content-between align-items-center mb-3">
<h5 class="mb-0">Batch Serial Settings</h5>
<a href="<?= base_url('part-stock/labels') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Labels</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="card">
<div class="card-body">
<form method="post" action="<?= base_url('part-stock/save-serial-settings') ?>">
<?= csrf_field() ?>

<div class="mb-3">
    <label class="form-label fw-bold">Current Prefix <span class="text-muted fw-normal">(A–Z only)</span></label>
    <input type="text" name="prefix" id="fPrefix" class="form-control" maxlength="1"
        value="<?= esc($config['prefix'] ?? 'A') ?>" style="width:80px; text-transform:uppercase"
        oninput="updatePreview()">
</div>

<div class="mb-3">
    <label class="form-label fw-bold">Last Number Used <span class="text-muted fw-normal">(next batch = this + 1)</span></label>
    <input type="number" name="last_number" id="fLast" class="form-control" style="width:140px"
        min="0" max="9998" value="<?= esc($config['last_number'] ?? 0) ?>"
        oninput="updatePreview()">
</div>

<div class="mb-3">
    <label class="form-label fw-bold">Max Number / Prefix <span class="text-muted fw-normal">(1–9999)</span></label>
    <input type="number" name="max_number" id="fMax" class="form-control" style="width:140px"
        min="1" max="9999" value="<?= esc($config['max_number'] ?? 9999) ?>"
        oninput="updatePreview()">
</div>

<hr>
<p class="mb-3">Next batch number: <strong id="previewNum" class="fs-5 text-primary"><?= esc($next) ?></strong></p>
<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Settings</button>
    <button type="button" class="btn btn-outline-danger" onclick="resetSerial()"><i class="bi bi-arrow-counterclockwise"></i> Reset to A / 0</button>
</div>
</form>
</div>
</div>

<div class="card mt-3">
<div class="card-body text-muted small">
<strong>How it works:</strong> All parts share one global counter. Batches are numbered A0001, A0002 &hellip; up to max per prefix, then rolls to B0001, B0002 &hellip; Stops at Z9999 — use Reset or manually set a new prefix to continue.
</div>
</div>
</div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function updatePreview() {
    var prefix = (document.getElementById('fPrefix').value || 'A').toUpperCase().replace(/[^A-Z]/g, '') || 'A';
    var last = parseInt(document.getElementById('fLast').value) || 0;
    var next = last + 1;
    var padded = String(next).padStart(4, '0');
    document.getElementById('previewNum').textContent = prefix + padded;
    document.getElementById('fPrefix').value = prefix;
}

function resetSerial() {
    if (!confirm('Reset serial to A0001? This will set prefix=A and last_number=0.')) return;
    document.getElementById('fPrefix').value = 'A';
    document.getElementById('fLast').value = '0';
    updatePreview();
    document.querySelector('form').submit();
}
</script>
<?= $this->endSection() ?>
