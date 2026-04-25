<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h5 class="mb-3">Create Assembly Work — <?= esc($nextNum) ?></h5>
<div class="card" style="max-width:900px">
    <div class="card-body">
        <form method="post" action="<?= base_url('assembly-work/store') ?>">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Department <small class="text-muted">(filter karigar)</small></label>
                    <select id="deptFilter" class="form-select" onchange="filterKarigars(this.value)">
                        <option value="">-- All Departments --</option>
                        <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= esc($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Assembling Karigar *</label>
                    <select name="karigar_id" id="karigarSel" class="form-select" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($karigars as $k): ?>
                        <option value="<?= $k['id'] ?>" data-dept="<?= $k['department_id'] ?>"><?= esc($k['name']) ?><?= $k['dept_name'] ? ' ('.$k['dept_name'].')' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Link Orders</label>
                    <select name="order_ids[]" class="form-select" multiple size="10">
                        <?php foreach ($orders as $o): ?>
                        <option value="<?= $o['id'] ?>">
                            <?= esc($o['order_number'] ?? ('ORD-'.$o['id'])) ?> — <?= esc($o['title'] ?? '') ?><?= !empty($o['client_name']) ? ' — '.esc($o['client_name']) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">You can link one order or many orders.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Create</button>
                <a href="<?= base_url('assembly-work') ?>" class="btn btn-secondary ms-2">Cancel</a>
            </div>
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