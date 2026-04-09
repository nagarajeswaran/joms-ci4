<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible"><button type="button" class="btn-close" data-bs-dismiss="alert"></button><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible"><button type="button" class="btn-close" data-bs-dismiss="alert"></button><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Gatti Stock</h5>
    <a href="<?= base_url('gatti-stock/entry') ?>" class="btn btn-success btn-sm"><i class="bi bi-plus-circle"></i> Stock Entry</a>
</div>

<div class="table-responsive">
<table class="table table-sm table-bordered table-hover">
<thead class="table-dark"><tr>
    <th>#</th><th>Batch No</th><th>Melt Job</th><th>Stamp</th><th>Weight (g)</th><th>Touch%</th><th>Fine (g)</th><th>Issued (g)</th><th>Balance (g)</th><th>Created</th><th>Actions</th>
</tr></thead>
<tbody>
<?php foreach ($items as $i => $row): ?>
<?php $balance = (float)$row['weight_g'] - (float)$row['qty_issued_g']; ?>
<?php $canRename = ((float)$row['qty_issued_g'] == 0); ?>
<tr id="gs-row-<?= $row['id'] ?>">
    <td><?= $i+1 ?></td>
    <td><strong><?= $row['batch_number'] ? esc($row['batch_number']) : '<span class="text-muted">—</span>' ?></strong></td>
    <td><?= $row['job_number'] ? '<a href="'.base_url('melt-jobs/view/'.$row['melt_job_id']).'">'.esc($row['job_number']).'</a>' : '-' ?></td>
    <td><?= esc($row['stamp_name'] ?? '-') ?></td>
    <td><?= number_format($row['weight_g'],4) ?></td>
    <td><?= $row['touch_pct'] ?>%</td>
    <td><?= number_format($row['weight_g']*$row['touch_pct']/100,4) ?></td>
    <td><?= number_format($row['qty_issued_g'],4) ?></td>
    <td><span class="badge <?= $balance > 0 ? 'bg-success' : 'bg-secondary' ?>"><?= number_format($balance,4) ?> g</span></td>
    <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
    <td class="text-nowrap">
        <a href="<?= base_url('gatti-stock/view/'.$row['id']) ?>" class="btn btn-sm btn-outline-primary">View</a>
        <?php if ($row['batch_number']): ?>
        <a href="<?= base_url('gatti-stock/entry?batch='.urlencode($row['batch_number'])) ?>" class="btn btn-sm btn-outline-success">Entry</a>
        <?php else: ?>
        <a href="<?= base_url('gatti-stock/entry') ?>" class="btn btn-sm btn-outline-secondary">Entry</a>
        <?php endif; ?>
        <button class="btn btn-sm btn-outline-warning"
            onclick="toggleGsEdit(<?= $row['id'] ?>, '<?= esc($row['batch_number'] ?? '', 'js') ?>', <?= $row['touch_pct'] ?>, <?= (int)($row['stamp_id'] ?? 0) ?>, '<?= esc($row['notes'] ?? '', 'js') ?>', <?= $canRename ? 'true' : 'false' ?>)">
            Edit
        </button>
    </td>
</tr>
<tr id="gs-edit-<?= $row['id'] ?>" style="display:none" class="table-warning">
    <td colspan="11">
        <form method="post" action="<?= base_url('gatti-stock/'.$row['id'].'/update') ?>" class="row g-2 align-items-center py-1">
        <?= csrf_field() ?>
            <div class="col-auto">
                <label class="form-label small mb-0">Batch No</label>
                <input type="text" name="batch_number" id="gs-edit-bn-<?= $row['id'] ?>" class="form-control form-control-sm" style="width:120px"
                    <?= !$canRename ? 'readonly title="Cannot rename — has issued stock"' : '' ?>>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Touch%</label>
                <input type="number" step="0.0001" name="touch_pct" id="gs-edit-tp-<?= $row['id'] ?>" class="form-control form-control-sm" style="width:100px">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Stamp</label>
                <select name="stamp_id" id="gs-edit-st-<?= $row['id'] ?>" class="form-select form-select-sm" style="width:150px">
                    <option value="">-- None --</option>
                    <?php foreach ($stamps as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Notes</label>
                <input type="text" name="notes" id="gs-edit-nt-<?= $row['id'] ?>" class="form-control form-control-sm" style="width:160px">
            </div>
            <div class="col-auto mt-3">
                <button type="submit" class="btn btn-sm btn-warning">Update</button>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="toggleGsEdit(<?= $row['id'] ?>)">Cancel</button>
            </div>
        </form>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$items): ?><tr><td colspan="11" class="text-center text-muted">No gatti stock</td></tr><?php endif; ?>
</tbody>
</table>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function toggleGsEdit(id, batchNo, touch, stampId, notes, canRename) {
    var editRow = document.getElementById('gs-edit-' + id);
    var mainRow = document.getElementById('gs-row-' + id);
    if (editRow.style.display === 'none') {
        // Pre-fill fields
        var bnInput = document.getElementById('gs-edit-bn-' + id);
        var tpInput = document.getElementById('gs-edit-tp-' + id);
        var stSel   = document.getElementById('gs-edit-st-' + id);
        var ntInput = document.getElementById('gs-edit-nt-' + id);
        if (bnInput) bnInput.value  = batchNo || '';
        if (tpInput) tpInput.value  = touch   || 0;
        if (ntInput) ntInput.value  = notes   || '';
        if (stSel && stampId) {
            for (var i = 0; i < stSel.options.length; i++) {
                if (parseInt(stSel.options[i].value) === stampId) { stSel.selectedIndex = i; break; }
            }
        }
        editRow.style.display = '';
        mainRow.classList.add('table-active');
    } else {
        editRow.style.display = 'none';
        mainRow.classList.remove('table-active');
    }
}
</script>
<?= $this->endSection() ?>
