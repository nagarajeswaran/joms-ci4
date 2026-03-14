<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h5 class="mb-3">Create Part Order — <?= esc($nextNum) ?></h5>
<div class="card" style="max-width:600px">
<div class="card-body">
<form method="post" action="<?= base_url('part-orders/store') ?>">
<?= csrf_field() ?>
<div class="mb-3">
    <label class="form-label">Karigar *</label>
    <select name="karigar_id" class="form-select" required onchange="fillRates(this)">
        <option value="">-- Select --</option>
        <?php foreach ($karigars as $k): ?>
        <option value="<?= $k['id'] ?>" data-cash="<?= $k['default_cash_rate'] ?>" data-fine="<?= $k['default_fine_pct'] ?>"><?= esc($k['name']) ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="row">
    <div class="col mb-3"><label class="form-label">Cash Rate (Rs/kg)</label><input type="number" step="0.01" name="cash_rate_per_kg" id="cashRate" class="form-control" value="0"></div>
    <div class="col mb-3"><label class="form-label">Fine %</label><input type="number" step="0.0001" name="fine_pct" id="finePct" class="form-control" value="0"></div>
</div>
<div class="mb-3">
    <label class="form-label">Link to Client Order (optional)</label>
    <select name="client_order_id" class="form-select">
        <option value="">-- None --</option>
        <?php foreach ($orders as $o): ?>
        <option value="<?= $o['id'] ?>"><?= esc($o['order_number'] ?? 'ORD-'.$o['id']) ?> — <?= esc($o['title'] ?? '') ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="mb-3"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
<button type="submit" class="btn btn-primary">Create</button>
<a href="<?= base_url('part-orders') ?>" class="btn btn-secondary ms-2">Cancel</a>
</form>
</div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function fillRates(sel) {
    var opt = sel.options[sel.selectedIndex];
    document.getElementById('cashRate').value = opt.dataset.cash || 0;
    document.getElementById('finePct').value  = opt.dataset.fine || 0;
}
</script>
<?= $this->endSection() ?>
