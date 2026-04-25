<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.filter-bar {
    background: #fff; border: 1px solid #e0e0e0; border-radius: 10px;
    padding: 12px 16px; margin-bottom: 16px;
}
.type-badge-in           { background:#198754;color:#fff; }
.type-badge-out          { background:#dc3545;color:#fff; }
.type-badge-adjustment   { background:#ffc107;color:#212529; }
.type-badge-transfer_in  { background:#0dcaf0;color:#212529; }
.type-badge-transfer_out { background:#6c757d;color:#fff; }
.qty-positive { color:#198754; font-weight:700; }
.qty-negative { color:#dc3545; font-weight:700; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5><i class="bi bi-journal-text"></i> Stock Audit Log</h5>
    <a href="<?= base_url('stock') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<!-- Filter bar -->
<form class="filter-bar" method="get">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-sm-6 col-md-3">
            <label class="form-label small mb-1">Search</label>
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control" placeholder="Product / SKU…" value="<?= esc($q) ?>">
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md-2">
            <label class="form-label small mb-1">Location</label>
            <select name="loc" class="form-select form-select-sm">
                <option value="">All Locations</option>
                <?php foreach ($locations as $l): ?>
                    <option value="<?= $l['id'] ?>" <?= $locId==$l['id']?'selected':'' ?>><?= esc($l['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-sm-4 col-md-2">
            <label class="form-label small mb-1">Type</label>
            <select name="type" class="form-select form-select-sm">
                <option value="">All Types</option>
                <option value="in"           <?= $type=='in'?'selected':''?>>Stock In</option>
                <option value="out"          <?= $type=='out'?'selected':''?>>Sale / Out</option>
                <option value="adjustment"   <?= $type=='adjustment'?'selected':''?>>Adjustment</option>
                <option value="transfer_in"  <?= $type=='transfer_in'?'selected':''?>>Transfer In</option>
                <option value="transfer_out" <?= $type=='transfer_out'?'selected':''?>>Transfer Out</option>
            </select>
        </div>
        <div class="col-6 col-sm-4 col-md-2">
            <label class="form-label small mb-1">From Date</label>
            <input type="date" name="from" class="form-control form-control-sm" value="<?= esc($from) ?>">
        </div>
        <div class="col-6 col-sm-4 col-md-2">
            <label class="form-label small mb-1">To Date</label>
            <input type="date" name="to" class="form-control form-control-sm" value="<?= esc($to) ?>">
        </div>
        <div class="col-12 col-md-1 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-fill">Filter</button>
            <a href="<?= base_url('stock/audit-log') ?>" class="btn btn-outline-secondary btn-sm" title="Reset">
                <i class="bi bi-x"></i>
            </a>
        </div>
    </div>
</form>

<?php if (empty($transactions)): ?>
    <div class="alert alert-info">No transactions found for the selected filters.</div>
<?php else: ?>

<?php
$typeClasses = [
    'in'           => 'type-badge-in',
    'out'          => 'type-badge-out',
    'adjustment'   => 'type-badge-adjustment',
    'transfer_in'  => 'type-badge-transfer_in',
    'transfer_out' => 'type-badge-transfer_out',
];
$typeLabels = [
    'in'           => '<i class="bi bi-arrow-down-circle"></i> Stock In',
    'out'          => '<i class="bi bi-arrow-up-circle"></i> Sale',
    'adjustment'   => '<i class="bi bi-pencil"></i> Adjustment',
    'transfer_in'  => '<i class="bi bi-box-arrow-in-right"></i> Transfer In',
    'transfer_out' => '<i class="bi bi-box-arrow-right"></i> Transfer Out',
];
?>

<div class="d-flex justify-content-between align-items-center mb-2">
    <small class="text-muted">Showing <?= count($transactions) ?> transaction(s)</small>
</div>

<div class="table-responsive">
    <table class="table table-sm table-hover table-bordered align-middle" style="font-size:13px;">
        <thead class="table-dark">
            <tr>
                <th style="width:48px;">#</th>
                <th style="width:130px;">Date / Time</th>
                <th style="width:120px;">Type</th>
                <th>Product</th>
                <th>Pattern</th>
                <th>Variation</th>
                <th>Location</th>
                <th class="text-center" style="width:60px;">Qty</th>
                <th>Note</th>
                <th style="width:36px;"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($transactions as $t):
            $cls = $typeClasses[$t['type']] ?? 'bg-secondary';
            $lbl = $typeLabels[$t['type']] ?? $t['type'];
            $isOut = in_array($t['type'], ['out','transfer_out']);
        ?>
        <tr>
            <td class="text-muted small"><?= $t['id'] ?></td>
            <td class="small text-muted"><?= date('d/m/y H:i', strtotime($t['created_at'])) ?></td>
            <td><span class="badge <?= $cls ?>" style="font-size:11px;"><?= $lbl ?></span></td>
            <td class="fw-semibold">
                <?= esc($t['product_name']) ?>
                <?php if ($t['sku']): ?><br><small class="text-muted"><?= esc($t['sku']) ?></small><?php endif; ?>
            </td>
            <td class="small"><?= $t['pat_is_default'] ? '<span class="text-muted fst-italic">Default</span>' : esc($t['pattern_name']) ?></td>
            <td class="small"><?= esc($t['variation_name']) ?><?= $t['variation_size'] ? ' '.$t['variation_size'].'"' : '' ?></td>
            <td><span class="badge bg-secondary" style="font-size:10px;"><?= esc($t['location_name']) ?></span></td>
            <td class="text-center <?= $isOut ? 'qty-negative' : 'qty-positive' ?>">
                <?= $isOut ? '−' : '+' ?><?= $t['qty'] ?>
            </td>
            <td class="small text-muted" id="note-cell-<?= $t['id'] ?>">
                <?= esc($t['note'] ?? '') ?>
                <?php if ($t['ref_transfer_id']): ?><br><span class="badge bg-light text-dark border">Tr#<?= $t['ref_transfer_id'] ?></span><?php endif; ?>
            </td>
            <td class="text-center">
                <button class="btn btn-outline-secondary py-0 px-1" style="font-size:11px;"
                    onclick="openEditNote(<?= $t['id'] ?>, <?= json_encode($t['note'] ?? '') ?>)"
                    title="Edit note">
                    <i class="bi bi-pencil"></i>
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<small class="text-muted">Showing last 500 transactions. Use date filters to narrow results.</small>
<?php endif; ?>

<!-- Edit Note Modal -->
<div class="modal fade" id="editNoteModal" tabindex="-1">
<div class="modal-dialog modal-sm">
<div class="modal-content">
    <div class="modal-header py-2">
        <h6 class="modal-title"><i class="bi bi-pencil"></i> Edit Note</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <form method="post" id="editNoteForm" action="">
    <?= csrf_field() ?>
    <!-- preserve current filters so redirect goes back to same view -->
    <input type="hidden" name="_loc"  value="<?= esc($locId) ?>">
    <input type="hidden" name="_type" value="<?= esc($type) ?>">
    <input type="hidden" name="_from" value="<?= esc($from) ?>">
    <input type="hidden" name="_to"   value="<?= esc($to) ?>">
    <input type="hidden" name="_q"    value="<?= esc($q) ?>">
    <div class="modal-body">
        <label class="form-label form-label-sm">Note</label>
        <textarea name="note" id="editNoteText" class="form-control form-control-sm" rows="3"></textarea>
    </div>
    <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check"></i> Save</button>
    </div>
    </form>
</div></div></div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
var _enModal;
document.addEventListener('DOMContentLoaded', function() {
    _enModal = new bootstrap.Modal(document.getElementById('editNoteModal'));
});
function openEditNote(id, note) {
    document.getElementById('editNoteText').value = note || '';
    document.getElementById('editNoteForm').action = '<?= base_url('stock/edit-note/') ?>' + id;
    _enModal.show();
    setTimeout(function() { document.getElementById('editNoteText').focus(); }, 350);
}
</script>
<?= $this->endSection() ?>
