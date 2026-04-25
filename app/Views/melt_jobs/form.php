<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h5 class="mb-3"><i class="bi bi-fire"></i> Create Melt Job — <?= esc($nextNum) ?></h5>
<form method="post" action="<?= base_url('melt-jobs/store') ?>">
<?= csrf_field() ?>
<div class="row g-3 justify-content-center">

<!-- Job Details -->
<div class="col-md-6">
<div class="card">
<div class="card-header fw-semibold">Job Details</div>
<div class="card-body">
    <div class="mb-2">
        <label class="form-label">Karigar *</label>
        <select name="karigar_id" id="karigarSel" class="form-select" required>
            <option value="">-- Select --</option>
            <?php foreach ($karigars as $k): ?>
            <option value="<?= $k['id'] ?>"><?= esc($k['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-2"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
    <div class="row g-2">
        <div class="col"><label class="form-label">Required Touch %</label><input type="number" step="0.0001" name="required_touch_pct" class="form-control" placeholder="Target touch %"></div>
        <div class="col"><label class="form-label">Required Weight (g)</label><input type="number" step="0.001" name="required_weight_g" class="form-control" placeholder="Target gatti weight"></div>
    </div>
</div>
</div>
</div>

</div><!-- /row -->

<div class="mt-3">
    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save & Continue</button>
    <a href="<?= base_url('melt-jobs') ?>" class="btn btn-secondary ms-2">Cancel</a>
</div>
</form>
<?= $this->endSection() ?>
