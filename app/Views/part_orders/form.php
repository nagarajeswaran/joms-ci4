<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h5 class="mb-3">Create Part Order — <?= esc($nextNum) ?></h5>
<div class="card" style="max-width:600px">
<div class="card-body">
<form method="post" action="<?= base_url('part-orders/store') ?>">
<?= csrf_field() ?>
<div class="mb-3">
    <label class="form-label">Department <small class="text-muted">(filter karigar)</small></label>
    <select id="deptFilter" class="form-select" onchange="filterKarigars(this.value)">
        <option value="">-- All Departments --</option>
        <?php foreach ($departments as $d): ?>
        <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="mb-3">
    <label class="form-label">Karigar *</label>
    <select name="karigar_id" id="karigarSel" class="form-select" required>
        <option value="">-- Select --</option>
        <?php foreach ($karigars as $k): ?>
        <option value="<?= $k['id'] ?>" data-dept="<?= $k['department_id'] ?>"><?= esc($k['name']) ?><?= $k['dept_name'] ? ' ('.$k['dept_name'].')' : '' ?></option>
        <?php endforeach; ?>
    </select>
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
function filterKarigars(deptId) {
    var sel  = document.getElementById('karigarSel');
    sel.value = '';
    Array.from(sel.options).forEach(function(opt) {
        if (!opt.value) { opt.style.display = ''; return; }
        opt.style.display = (!deptId || opt.dataset.dept == deptId) ? '' : 'none';
    });
}
</script>
<?= $this->endSection() ?>
