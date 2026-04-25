<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-flask"></i> Touch Ledger</h5>
    <a href="<?= base_url('touch-shops/create') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg"></i> New Entry
        <span class="badge bg-light text-dark ms-1"><?= esc($nextSerial) ?></span>
    </a>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible py-2 mb-3">
    <?= session()->getFlashdata('success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible py-2 mb-3">
    <?= session()->getFlashdata('error') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row g-2 mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="text-muted small">Total Entries</div>
            <div class="fs-5 fw-bold"><?= count($entries) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center py-2">
            <div class="text-muted small">Total Issued (g)</div>
            <div class="fs-5 fw-bold"><?= number_format($totalIssued, 4) ?></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center py-2 <?= $pendingCount > 0 ? 'border-warning' : '' ?>">
            <div class="text-muted small">Pending</div>
            <div class="fs-5 fw-bold <?= $pendingCount > 0 ? 'text-warning' : 'text-success' ?>"><?= $pendingCount ?></div>
        </div>
    </div>
</div>

<!-- Filters -->
<form method="get" action="" class="card card-body py-2 mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-auto">
            <label class="form-label form-label-sm mb-1">Touch Shop</label>
            <select name="shop" class="form-select form-select-sm">
                <option value="">All Shops</option>
                <?php foreach ($shopNames as $sn): ?>
                <option value="<?= esc($sn['touch_shop_name']) ?>" <?= $qShop === $sn['touch_shop_name'] ? 'selected' : '' ?>>
                    <?= esc($sn['touch_shop_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label form-label-sm mb-1">Karigar</label>
            <select name="karigar" class="form-select form-select-sm">
                <option value="">All Karigars</option>
                <?php foreach ($karigars as $k): ?>
                <option value="<?= $k['id'] ?>" <?= $qKarigar == $k['id'] ? 'selected' : '' ?>><?= esc($k['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label form-label-sm mb-1">Stamp</label>
            <select name="stamp" class="form-select form-select-sm">
                <option value="">All Stamps</option>
                <?php foreach ($stamps as $s): ?>
                <option value="<?= $s['id'] ?>" <?= $qStamp == $s['id'] ? 'selected' : '' ?>><?= esc($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label form-label-sm mb-1">Status</label>
            <select name="status" class="form-select form-select-sm">
                <option value="" <?= $qStatus === '' ? 'selected' : '' ?>>All</option>
                <option value="pending"   <?= $qStatus === 'pending'   ? 'selected' : '' ?>>Pending</option>
                <option value="completed" <?= $qStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
            <a href="<?= base_url('touch-shops') ?>" class="btn btn-outline-secondary btn-sm">Clear</a>
        </div>
    </div>
</form>

<!-- Ledger Table -->
<div class="card">
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm table-bordered table-hover mb-0">
<thead class="table-dark" style="font-size:12px">
<tr>
    <th>Serial#</th>
    <th>Touch Shop</th>
    <th>Date &amp; Time</th>
    <th>Karigar</th>
    <th>Stamp</th>
    <th>Gatti Batch</th>
    <th class="text-end">Issue (g)</th>
    <th class="text-end">Receive (g)</th>
    <th class="text-end">Touch%</th>
    <th>Photo</th>
    <th>Notes</th>
    <th>Status</th>
    <th></th>
</tr>
</thead>
<tbody>
<?php foreach ($entries as $e):
    $isPending = ($e['received_at'] === null);
    $loss      = $isPending ? null : round($e['issue_weight_g'] - $e['receive_weight_g'], 4);
?>
<tr class="<?= $isPending ? '' : 'table-success' ?>">
    <td><strong><?= esc($e['serial_number']) ?></strong></td>
    <td>
        <?php if ($e['touch_shop_name']): ?>
        <a href="?shop=<?= urlencode($e['touch_shop_name']) ?>&status=<?= esc($qStatus) ?>" class="text-decoration-none text-dark">
            <span class="badge bg-secondary"><?= esc($e['touch_shop_name']) ?></span>
        </a>
        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
    </td>
    <td style="white-space:nowrap; font-size:11px"><?= date('d M Y H:i', strtotime($e['created_at'])) ?></td>
    <td><?= $e['karigar_name'] ? esc($e['karigar_name']) : '<span class="text-muted">—</span>' ?></td>
    <td><?= $e['stamp_name']   ? esc($e['stamp_name'])   : '<span class="text-muted">—</span>' ?></td>
    <td>
        <?php if ($e['gatti_batch']): ?>
        <a href="<?= base_url('gatti-stock/entry?batch='.urlencode($e['gatti_batch'])) ?>" class="text-decoration-none" title="Touch: <?= $e['gatti_touch'] ?>%">
            <?= esc($e['gatti_batch']) ?>
        </a>
        <?php elseif ($e['job_number']): ?>
        <a href="<?= base_url('melt-jobs/view/'.(/* we don't have id, use job ref */0)) ?>" class="text-muted text-decoration-none">Job <?= esc($e['job_number']) ?></a>
        <?php else: ?>
        <span class="text-muted">—</span>
        <?php endif; ?>
    </td>
    <td class="text-end"><?= number_format($e['issue_weight_g'], 4) ?></td>
    <td class="text-end">
        <?php if (!$isPending): ?>
        <?= number_format($e['receive_weight_g'], 4) ?>
        <?php if ($loss > 0): ?><br><small class="text-danger">-<?= number_format($loss, 4) ?>g</small><?php endif; ?>
        <?php else: ?><span class="text-muted">—</span><?php endif; ?>
    </td>
    <td class="text-end">
        <?= !$isPending && $e['touch_result_pct'] ? '<strong>'.number_format($e['touch_result_pct'], 2).'%</strong>' : '<span class="text-muted">—</span>' ?>
    </td>
    <td class="text-center">
        <?php if ($e['sample_image']): ?>
        <a href="<?= base_url($e['sample_image']) ?>" target="_blank" title="Sample Image">
            <img src="<?= base_url($e['sample_image']) ?>" alt="sample" style="width:30px;height:30px;object-fit:cover;border-radius:3px;border:1px solid #ffc107;" title="Sample">
        </a>
        <?php endif; ?>
        <?php if ($e['touch_photo']): ?>
        <a href="<?= base_url($e['touch_photo']) ?>" target="_blank" title="Touch Form">
            <img src="<?= base_url($e['touch_photo']) ?>" alt="touch form" style="width:30px;height:30px;object-fit:cover;border-radius:3px;border:1px solid #0d6efd;" title="Touch Form">
        </a>
        <?php endif; ?>
        <?php if (!$e['sample_image'] && !$e['touch_photo']): ?>
        <span class="text-muted" style="font-size:11px">—</span>
        <?php endif; ?>
    </td>
    <td style="font-size:11px;max-width:120px"><?= esc($e['notes'] ?? '') ?></td>
    <td>
        <?php if ($isPending): ?>
        <span class="badge bg-warning text-dark">Pending</span>
        <?php else: ?>
        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Done</span>
        <?php endif; ?>
    </td>
    <td style="white-space:nowrap">
        <?php if ($isPending): ?>
        <button class="btn btn-xs btn-outline-success py-0 px-1" style="font-size:11px"
            onclick="openRbModal(<?= $e['id'] ?>, '<?= esc($e['serial_number']) ?>', <?= (float)$e['issue_weight_g'] ?>)">
            <i class="bi bi-arrow-down-circle"></i> Receive
        </button>
        <a href="<?= base_url('touch-shops/edit/'.$e['id']) ?>" class="btn btn-xs btn-outline-secondary py-0 px-1" style="font-size:11px" title="Edit">
            <i class="bi bi-pencil"></i>
        </a>
        <a href="<?= base_url('touch-shops/delete/'.$e['id']) ?>" class="btn btn-xs btn-outline-danger py-0 px-1" style="font-size:11px"
            onclick="return confirm('Delete <?= esc($e['serial_number']) ?>?')"><i class="bi bi-x"></i></a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$entries): ?>
<tr><td colspan="13" class="text-center text-muted py-4">No touch entries found. <a href="<?= base_url('touch-shops/create') ?>">Create first entry</a>.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>

<!-- Receive Back Modal -->
<div class="modal fade" id="rbModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
    <div class="modal-header py-2">
        <h6 class="modal-title"><i class="bi bi-arrow-down-circle text-success"></i> Receive Back — <span id="rbSerial"></span></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <form method="post" id="rbForm" action="" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="modal-body">
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label form-label-sm">Issued (g)</label>
                <input type="text" id="rbIssued" class="form-control form-control-sm" readonly>
            </div>
            <div class="col-6">
                <label class="form-label form-label-sm">Receive Weight (g) <span class="text-danger">*</span></label>
                <input type="number" step="0.0001" min="0.0001" name="receive_weight_g" id="rbRecvWt" class="form-control form-control-sm" required>
            </div>
            <div class="col-6">
                <label class="form-label form-label-sm">Touch Result % <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="0" max="100" name="touch_result_pct" class="form-control form-control-sm" required>
            </div>
            <div class="col-6">
                <label class="form-label form-label-sm">Touch Form <small class="text-muted">(jpg/png/webp)</small></label>
                <input type="file" name="touch_photo" accept="image/*" class="form-control form-control-sm">
            </div>
            <div class="col-12">
                <label class="form-label form-label-sm">Notes</label>
                <input type="text" name="notes" class="form-control form-control-sm">
            </div>
        </div>
    </div>
    <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-check-circle"></i> Complete Entry</button>
    </div>
    </form>
</div></div></div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
var rbModal;
document.addEventListener('DOMContentLoaded', function() {
    rbModal = new bootstrap.Modal(document.getElementById('rbModal'));
});
function openRbModal(id, serial, issued) {
    document.getElementById('rbSerial').textContent = serial;
    document.getElementById('rbIssued').value       = parseFloat(issued).toFixed(4);
    document.getElementById('rbRecvWt').value       = parseFloat(issued).toFixed(4);
    document.getElementById('rbRecvWt').max         = parseFloat(issued).toFixed(4);
    document.getElementById('rbForm').action        = '<?= base_url('touch-shops/receive-back/') ?>' + id;
    rbModal.show();
    setTimeout(function() { document.getElementById('rbRecvWt').focus(); document.getElementById('rbRecvWt').select(); }, 400);
}
</script>
<?= $this->endSection() ?>
