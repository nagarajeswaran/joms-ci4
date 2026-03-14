<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h5 class="mb-3">Convert: <?= esc($karigar['name']) ?></h5>
<div class="row mb-3">
    <div class="col-auto"><div class="alert alert-warning py-2 mb-0">Fine Balance: <strong><?= number_format($fineBalance,4) ?>g</strong></div></div>
    <div class="col-auto"><div class="alert alert-success py-2 mb-0">Cash Balance: <strong>Rs <?= number_format($cashBalance,2) ?></strong></div></div>
</div>
<div class="card" style="max-width:500px">
<div class="card-header"><strong>Fine &harr; Cash Conversion</strong></div>
<div class="card-body">
<form method="post" action="<?= base_url('karigar-ledger/'.$karigar['id'].'/store-convert') ?>">
<?= csrf_field() ?>
<div class="mb-3">
    <label class="form-label">From Account</label>
    <select name="from_account" class="form-select" onchange="updateLabel(this)">
        <option value="fine">Fine (grams) to Cash (Rs)</option>
        <option value="cash">Cash (Rs) to Fine (grams)</option>
    </select>
</div>
<div class="mb-3">
    <label class="form-label" id="fromLabel">Amount (fine grams)</label>
    <input type="number" step="0.0001" name="from_amount" class="form-control" required>
</div>
<div class="mb-3">
    <label class="form-label">Silver Rate (Rs/kg)</label>
    <input type="number" step="0.01" name="rate_per_kg" class="form-control" required placeholder="e.g. 90000">
</div>
<div class="mb-3"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control"></div>
<button type="submit" class="btn btn-primary">Post Conversion</button>
<a href="<?= base_url('karigar-ledger/'.$karigar['id']) ?>" class="btn btn-secondary ms-2">Cancel</a>
</form>
</div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function updateLabel(sel) {
    document.getElementById('fromLabel').textContent = sel.value === 'fine' ? 'Amount (fine grams)' : 'Amount (Rs)';
}
</script>
<?= $this->endSection() ?>
