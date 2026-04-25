<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Pending Receive Entry</h5>
    <a href="<?= base_url('part-orders') ?>" class="btn btn-outline-secondary btn-sm">Part Orders</a>
</div>

<?php if (!empty($tableMissing)): ?>
<div class="alert alert-warning">
    `pending_receive_entry` table is missing. Import `pending_receive_entry.sql` into the database first.
</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header"><strong>Add Pending Receive</strong></div>
    <div class="card-body bg-light">
        <form method="post" action="<?= base_url('pending-receive-entry/store') ?>">
            <?= csrf_field() ?>
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1">Part</label>
                    <select name="part_id" class="form-select form-select-sm" required>
                        <option value="">-- Part --</option>
                        <?php foreach ($parts as $part): ?>
                        <option value="<?= $part['id'] ?>"><?= esc($part['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Batch Barcode</label>
                    <input type="text" name="batch_number" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Weight (g)</label>
                    <input type="number" step="0.0001" name="weight_g" class="form-control form-control-sm" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">One Pc Weight (g)</label>
                    <input type="number" step="0.0001" name="piece_weight_g" class="form-control form-control-sm">
                </div>
                <div class="col-md-1">
                    <label class="form-label form-label-sm mb-1">Touch%</label>
                    <input type="number" step="0.0001" name="touch_pct" class="form-control form-control-sm" value="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Stamp</label>
                    <select name="stamp_id" class="form-select form-select-sm">
                        <option value="">-- Stamp --</option>
                        <?php foreach ($stamps as $stamp): ?>
                        <option value="<?= $stamp['id'] ?>"><?= esc($stamp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-10">
                    <label class="form-label form-label-sm mb-1">Note</label>
                    <input type="text" name="note" class="form-control form-control-sm" placeholder="Optional note">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100" <?= !empty($tableMissing) ? 'disabled' : '' ?>>Save Pending Row</button>
                </div>
            </div>
        </form>
    </div>
</div>

<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="used" <?= $statusFilter === 'used' ? 'selected' : '' ?>>Used</option>
            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
    </div>
    <div class="col-auto">
        <select name="part_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="0">All Parts</option>
            <?php foreach ($parts as $part): ?>
            <option value="<?= $part['id'] ?>" <?= (int)$partFilter === (int)$part['id'] ? 'selected' : '' ?>><?= esc($part['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <input type="text" name="batch" value="<?= esc($batchFilter) ?>" class="form-control form-control-sm" placeholder="Search batch">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-outline-secondary btn-sm">Filter</button>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-sm table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Created</th>
                <th>Part</th>
                <th>Batch</th>
                <th>Weight (g)</th>
                <th>Pc Wt (g)</th>
                <th>Pcs</th>
                <th>Touch%</th>
                <th>Stamp</th>
                <th>Note</th>
                <th>Created By</th>
                <th>Status</th>
                <th>Linked Order</th>
                <th style="width:260px">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $row): ?>
            <tr id="pending-row-<?= $row['id'] ?>">
                <td><?= $row['id'] ?></td>
                <td><?= !empty($row['created_at']) ? date('d/m/Y H:i', strtotime($row['created_at'])) : '-' ?></td>
                <td><?= esc($row['part_name'] ?? '-') ?></td>
                <td><?= esc($row['batch_number']) ?></td>
                <td><?= number_format($row['weight_g'], 4) ?></td>
                <td><?= $row['piece_weight_g'] ? number_format($row['piece_weight_g'], 4) : '-' ?></td>
                <td><?= (int)$row['qty'] ?></td>
                <td><?= number_format((float)$row['touch_pct'], 4) ?></td>
                <td><?= esc($row['stamp_name'] ?? '-') ?></td>
                <td><?= esc($row['note'] ?? '-') ?></td>
                <td><?= esc($row['created_by'] ?? '-') ?></td>
                <td>
                    <?php
                        $badge = 'bg-warning text-dark';
                        if ($row['status'] === 'used') $badge = 'bg-success';
                        if ($row['status'] === 'cancelled') $badge = 'bg-secondary';
                    ?>
                    <span class="badge <?= $badge ?>"><?= ucfirst($row['status']) ?></span>
                </td>
                <td>
                    <?php if (!empty($row['linked_part_order_id'])): ?>
                        <a href="<?= base_url('part-orders/view/'.$row['linked_part_order_id']) ?>"><?= esc($row['linked_order_number'] ?? ('#'.$row['linked_part_order_id'])) ?></a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td class="text-nowrap">
                    <?php if ($row['status'] === 'pending'): ?>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="togglePendingEdit(<?= $row['id'] ?>)">Edit</button>
                    <a href="<?= base_url('pending-receive-entry/cancel/'.$row['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this pending row?')">Cancel</a>
                    <?php else: ?>
                    <span class="text-muted">Locked</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ($row['status'] === 'pending'): ?>
            <tr id="pending-edit-<?= $row['id'] ?>" class="table-warning" style="display:none">
                <td colspan="14">
                    <form method="post" action="<?= base_url('pending-receive-entry/update/'.$row['id']) ?>" class="row g-2 align-items-end">
                        <?= csrf_field() ?>
                        <div class="col-md-2">
                            <select name="part_id" class="form-select form-select-sm" required>
                                <?php foreach ($parts as $part): ?>
                                <option value="<?= $part['id'] ?>" <?= (int)$row['part_id'] === (int)$part['id'] ? 'selected' : '' ?>><?= esc($part['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2"><input type="text" name="batch_number" value="<?= esc($row['batch_number']) ?>" class="form-control form-control-sm" required></div>
                        <div class="col-md-1"><input type="number" step="0.0001" name="weight_g" value="<?= number_format((float)$row['weight_g'], 4, '.', '') ?>" class="form-control form-control-sm" required></div>
                        <div class="col-md-1"><input type="number" step="0.0001" name="piece_weight_g" value="<?= $row['piece_weight_g'] !== null ? number_format((float)$row['piece_weight_g'], 4, '.', '') : '' ?>" class="form-control form-control-sm"></div>
                        <div class="col-md-1"><input type="number" step="0.0001" name="touch_pct" value="<?= number_format((float)$row['touch_pct'], 4, '.', '') ?>" class="form-control form-control-sm"></div>
                        <div class="col-md-2">
                            <select name="stamp_id" class="form-select form-select-sm">
                                <option value="">-- Stamp --</option>
                                <?php foreach ($stamps as $stamp): ?>
                                <option value="<?= $stamp['id'] ?>" <?= (int)$row['stamp_id'] === (int)$stamp['id'] ? 'selected' : '' ?>><?= esc($stamp['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2"><input type="text" name="note" value="<?= esc($row['note'] ?? '') ?>" class="form-control form-control-sm"></div>
                        <div class="col-md-1 text-nowrap">
                            <button type="submit" class="btn btn-sm btn-warning">Update</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="togglePendingEdit(<?= $row['id'] ?>)">Close</button>
                        </div>
                    </form>
                </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
            <?php if (!$items): ?>
            <tr><td colspan="14" class="text-center text-muted">No pending receive entries found</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function togglePendingEdit(id) {
    const row = document.getElementById('pending-edit-' + id);
    if (!row) return;
    row.style.display = row.style.display === 'none' ? '' : 'none';
}
</script>
<?= $this->endSection() ?>