<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible"><button type="button" class="btn-close" data-bs-dismiss="alert"></button><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible"><button type="button" class="btn-close" data-bs-dismiss="alert"></button><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Gatti Stock — <?= $row['batch_number'] ? esc($row['batch_number']) : ($row['job_number'] ? 'Job '.esc($row['job_number']) : 'ID #'.$row['id']) ?></h5>
    <div class="d-flex gap-2">
        <?php if ($row['batch_number']): ?>
        <a href="<?= base_url('gatti-stock/entry?batch='.urlencode($row['batch_number'])) ?>" class="btn btn-success btn-sm"><i class="bi bi-plus-circle"></i> Add Entry</a>
        <?php else: ?>
        <a href="<?= base_url('gatti-stock/entry') ?>" class="btn btn-success btn-sm"><i class="bi bi-plus-circle"></i> Add Entry</a>
        <?php endif; ?>
        <a href="<?= base_url('gatti-stock') ?>" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>
</div>

<!-- Row summary -->
<?php $balance = (float)$row['weight_g'] - (float)$row['qty_issued_g']; ?>
<div class="card mb-4" style="max-width:600px">
<div class="card-body py-2">
<div class="row row-cols-auto g-3">
    <?php if ($row['batch_number']): ?>
    <div class="col"><small class="text-muted d-block">Batch No</small><strong><?= esc($row['batch_number']) ?></strong></div>
    <?php endif; ?>
    <div class="col"><small class="text-muted d-block">Melt Job</small><strong><?= $row['job_number'] ? '<a href="'.base_url('melt-jobs/view/'.$row['melt_job_id']).'">'.esc($row['job_number']).'</a>' : '-' ?></strong></div>
    <div class="col"><small class="text-muted d-block">Stamp</small><strong><?= esc($row['stamp_name'] ?? '-') ?></strong></div>
    <div class="col"><small class="text-muted d-block">Weight (g)</small><strong><?= number_format($row['weight_g'],4) ?></strong></div>
    <div class="col"><small class="text-muted d-block">Touch%</small><strong><?= $row['touch_pct'] ?>%</strong></div>
    <div class="col"><small class="text-muted d-block">Fine (g)</small><strong><?= number_format($row['weight_g']*$row['touch_pct']/100,4) ?></strong></div>
    <div class="col"><small class="text-muted d-block">Issued (g)</small><strong><?= number_format($row['qty_issued_g'],4) ?></strong></div>
    <div class="col">
        <small class="text-muted d-block">Balance (g)</small>
        <span class="badge fs-6 <?= $balance > 0 ? 'bg-success' : 'bg-secondary' ?>"><?= number_format($balance,4) ?> g</span>
    </div>
</div>
</div>
</div>

<!-- Log history -->
<h6 class="mb-2">Manual Entry History</h6>
<div class="table-responsive" style="max-width:860px">
<table class="table table-sm table-bordered">
<thead class="table-dark"><tr>
    <th>Date</th><th>Type</th><th>Reason</th><th>Weight (g)</th><th>Touch%</th><th>Notes</th><th>Actions</th>
</tr></thead>
<tbody>
<?php foreach ($logs as $log): ?>
<tr id="log-row-<?= $log['id'] ?>">
    <td><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
    <td><?= $log['entry_type']==='in' ? '<span class="badge bg-success">IN</span>' : '<span class="badge bg-danger">OUT</span>' ?></td>
    <td><?= esc($log['reason']) ?></td>
    <td><?= number_format($log['weight_g'],4) ?></td>
    <td><?= $log['touch_pct'] ?>%</td>
    <td><?= esc($log['notes'] ?? '-') ?></td>
    <td class="text-nowrap">
        <button class="btn btn-sm btn-outline-warning"
            onclick="toggleLogEdit(<?= $log['id'] ?>, '<?= $log['entry_type'] ?>', <?= $log['weight_g'] ?>, <?= $log['touch_pct'] ?>, '<?= esc($log['reason'], 'js') ?>', '<?= esc($log['notes'] ?? '', 'js') ?>')">
            Edit
        </button>
        <a href="<?= base_url('gatti-stock/log/'.$log['id'].'/delete') ?>"
           class="btn btn-sm btn-outline-danger"
           onclick="return confirm('Delete this entry and reverse the stock change?')">Delete</a>
    </td>
</tr>
<tr id="log-edit-<?= $log['id'] ?>" style="display:none" class="table-warning">
    <td colspan="7">
        <form method="post" action="<?= base_url('gatti-stock/log/'.$log['id'].'/update') ?>" class="row g-2 align-items-end py-1">
        <?= csrf_field() ?>
            <div class="col-auto">
                <label class="form-label small mb-0">Direction</label><br>
                <div class="btn-group btn-group-sm">
                    <input type="radio" class="btn-check" name="entry_type" id="log-in-<?= $log['id'] ?>" value="in">
                    <label class="btn btn-outline-success" for="log-in-<?= $log['id'] ?>">IN</label>
                    <input type="radio" class="btn-check" name="entry_type" id="log-out-<?= $log['id'] ?>" value="out">
                    <label class="btn btn-outline-danger" for="log-out-<?= $log['id'] ?>">OUT</label>
                </div>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Reason</label>
                <select name="reason" id="log-reason-<?= $log['id'] ?>" class="form-select form-select-sm" style="width:170px">
                    <option value="purchase">Purchase</option>
                    <option value="return">Return</option>
                    <option value="adjustment_in">Adjustment (add)</option>
                    <option value="other_in">Other IN</option>
                    <option value="used_in_prod">Used in Production</option>
                    <option value="damaged">Damaged / Loss</option>
                    <option value="adjustment_out">Adjustment (remove)</option>
                    <option value="other_out">Other OUT</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Weight (g)</label>
                <input type="number" step="0.0001" name="weight_g" id="log-wt-<?= $log['id'] ?>" class="form-control form-control-sm" style="width:120px" required>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Touch%</label>
                <input type="number" step="0.0001" name="touch_pct" id="log-tp-<?= $log['id'] ?>" class="form-control form-control-sm" style="width:100px">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Notes</label>
                <input type="text" name="notes" id="log-nt-<?= $log['id'] ?>" class="form-control form-control-sm" style="width:160px">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-warning">Update</button>
                <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="toggleLogEdit(<?= $log['id'] ?>)">Cancel</button>
            </div>
        </form>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$logs): ?>
<tr><td colspan="7" class="text-center text-muted">No manual entries yet</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function toggleLogEdit(id, type, wt, touch, reason, notes) {
    var editRow = document.getElementById('log-edit-' + id);
    var mainRow = document.getElementById('log-row-' + id);
    if (editRow.style.display === 'none') {
        // Set direction radio
        var inRadio  = document.getElementById('log-in-'  + id);
        var outRadio = document.getElementById('log-out-' + id);
        if (type === 'in') inRadio.checked = true;
        else outRadio.checked = true;

        // Pre-fill fields
        document.getElementById('log-wt-' + id).value  = wt     || '';
        document.getElementById('log-tp-' + id).value  = touch  || 0;
        document.getElementById('log-nt-' + id).value  = notes  || '';

        // Set reason select
        var sel = document.getElementById('log-reason-' + id);
        for (var i = 0; i < sel.options.length; i++) {
            if (sel.options[i].value === reason) { sel.selectedIndex = i; break; }
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
