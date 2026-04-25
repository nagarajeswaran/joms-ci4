<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0"><i class="bi bi-flask"></i> <?= esc($shop['name']) ?></h5>
        <?php if ($shop['notes']): ?><small class="text-muted"><?= esc($shop['notes']) ?></small><?php endif; ?>
    </div>
    <a href="<?= base_url('touch-shops') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success py-2"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger py-2"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<!-- Summary -->
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="text-muted small">Total Issued</div>
                <div class="fs-5 fw-bold"><?= number_format($totalIssued, 4) ?> g</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="text-muted small">Total Received Back</div>
                <div class="fs-5 fw-bold text-success"><?= number_format($totalReceived, 4) ?> g</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-0 shadow-sm <?= $pending > 0 ? 'border-warning' : '' ?>">
            <div class="card-body py-3">
                <div class="text-muted small">Pending with Shop (Loss)</div>
                <div class="fs-5 fw-bold <?= $pending > 0 ? 'text-warning' : 'text-success' ?>"><?= number_format($pending, 4) ?> g</div>
            </div>
        </div>
    </div>
</div>

<!-- Issues Ledger -->
<div class="card">
<div class="card-header fw-semibold"><i class="bi bi-arrow-up-circle"></i> Issues Sent to Shop</div>
<div class="card-body p-0">
<table class="table table-sm table-bordered mb-0">
<thead class="table-dark">
<tr>
    <th>Date</th>
    <th>Melt Job</th>
    <th class="text-end">Issued (g)</th>
    <th class="text-end">Touch%</th>
    <th class="text-end">Received Back (g)</th>
    <th class="text-end">Pending (g)</th>
    <th>Notes</th>
    <th></th>
</tr>
</thead>
<tbody>
<?php foreach ($transactions as $tx):
    $recvBack = (float)($tx['received_back'] ?? 0);
    $txPending = $tx['weight_g'] - $recvBack;
?>
<tr>
    <td style="white-space:nowrap"><?= date('d M Y', strtotime($tx['created_at'])) ?></td>
    <td>
        <?php if ($tx['job_number']): ?>
        <a href="<?= base_url('melt-jobs/view/'.$tx['melt_job_id']) ?>"><?= esc($tx['job_number']) ?></a>
        <?php else: ?>
        <span class="text-muted">—</span>
        <?php endif; ?>
    </td>
    <td class="text-end"><?= number_format($tx['weight_g'], 4) ?></td>
    <td class="text-end"><?= number_format($tx['touch_pct'], 2) ?>%</td>
    <td class="text-end text-success"><?= $recvBack > 0 ? number_format($recvBack, 4) : '—' ?></td>
    <td class="text-end <?= $txPending > 0 ? 'text-warning fw-semibold' : 'text-muted' ?>"><?= number_format($txPending, 4) ?></td>
    <td><small><?= esc($tx['notes']) ?></small></td>
    <td class="text-center" style="white-space:nowrap">
        <?php if ($txPending > 0): ?>
        <button class="btn btn-xs btn-outline-success py-0 px-1" style="font-size:11px"
            onclick="openReceiveModal(<?= $tx['id'] ?>, <?= $tx['weight_g'] ?>, <?= $txPending ?>)">
            <i class="bi bi-arrow-down-circle"></i> Receive Back
        </button>
        <?php else: ?>
        <span class="badge bg-success">Settled</span>
        <?php endif; ?>
        <?php if ($recvBack == 0): ?>
        <a href="<?= base_url('touch-shops/delete-tx/'.$tx['id']) ?>" class="btn btn-xs btn-outline-danger py-0 px-1" style="font-size:11px"
            onclick="return confirm('Delete this issue record?')"><i class="bi bi-x"></i></a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$transactions): ?>
<tr><td colspan="8" class="text-center text-muted py-3">No issues recorded yet.</td></tr>
<?php endif; ?>
</tbody>
<tfoot class="table-light">
<tr>
    <td colspan="2" class="text-end fw-semibold">Total</td>
    <td class="text-end fw-semibold"><?= number_format($totalIssued, 4) ?></td>
    <td></td>
    <td class="text-end fw-semibold text-success"><?= number_format($totalReceived, 4) ?></td>
    <td class="text-end fw-semibold <?= $pending > 0 ? 'text-warning' : '' ?>"><?= number_format($pending, 4) ?></td>
    <td colspan="2"></td>
</tr>
</tfoot>
</table>
</div>
</div>

<!-- Receive Back Modal -->
<div class="modal fade" id="receiveBackModal" tabindex="-1">
<div class="modal-dialog modal-sm">
<div class="modal-content">
    <div class="modal-header py-2">
        <h6 class="modal-title"><i class="bi bi-arrow-down-circle"></i> Receive Back from Shop</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <form method="post" id="receiveBackForm" action="">
    <?= csrf_field() ?>
    <div class="modal-body">
        <div class="mb-2">
            <label class="form-label form-label-sm">Issued (g)</label>
            <input type="text" id="rbIssued" class="form-control form-control-sm" readonly>
        </div>
        <div class="mb-2">
            <label class="form-label form-label-sm">Pending (g)</label>
            <input type="text" id="rbPending" class="form-control form-control-sm" readonly>
        </div>
        <div class="mb-2">
            <label class="form-label form-label-sm">Received Back (g) <span class="text-danger">*</span></label>
            <input type="number" step="0.0001" min="0.0001" name="weight_g" id="rbWeight" class="form-control form-control-sm" required>
        </div>
        <div class="mb-2">
            <label class="form-label form-label-sm">Touch% (optional)</label>
            <input type="number" step="0.01" min="0" max="100" name="touch_pct" class="form-control form-control-sm" value="0">
        </div>
        <div class="mb-2">
            <label class="form-label form-label-sm">Notes</label>
            <input type="text" name="notes" class="form-control form-control-sm">
        </div>
    </div>
    <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success btn-sm">Save Receive Back</button>
    </div>
    </form>
</div></div></div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
var rbModal;
document.addEventListener('DOMContentLoaded', function() {
    rbModal = new bootstrap.Modal(document.getElementById('receiveBackModal'));
});
function openReceiveModal(issueId, issued, pending) {
    document.getElementById('rbIssued').value  = parseFloat(issued).toFixed(4);
    document.getElementById('rbPending').value = parseFloat(pending).toFixed(4);
    document.getElementById('rbWeight').value  = parseFloat(pending).toFixed(4);
    document.getElementById('rbWeight').max    = parseFloat(issued).toFixed(4);
    document.getElementById('receiveBackForm').action = '<?= base_url('touch-shops/receive-back/') ?>' + issueId;
    rbModal.show();
}
</script>
<?= $this->endSection() ?>
