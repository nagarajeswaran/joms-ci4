<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-gem"></i> Kacha — Bullion List</h5>
    <a href="<?= base_url('kacha/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Add Lots</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show py-2"><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show py-2"><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- Filters -->
<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <input type="text" name="q" class="form-control form-control-sm" placeholder="Search lot / party..." value="<?= esc($q) ?>" style="width:200px;">
    </div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm">
            <option value="">All Status</option>
            <option value="available" <?= $status === 'available' ? 'selected' : '' ?>>Available</option>
            <option value="used"      <?= $status === 'used'      ? 'selected' : '' ?>>Used</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-secondary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
        <a href="<?= base_url('kacha') ?>" class="btn btn-outline-secondary btn-sm">Clear</a>
    </div>
</form>

<div class="table-responsive">
<table class="table table-sm table-bordered table-hover" id="kachaTable">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Lot Number</th>
            <th>Receipt Date</th>
            <th class="text-end">Weight (g)</th>
            <th class="text-end">Touch %</th>
            <th class="text-end">Fine (g)</th>
            <th>Party</th>
            <th>Source</th>
            <th class="text-end">Test Touch</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($lots): ?>
        <?php foreach ($lots as $i => $lot): ?>
        <tr class="<?= $lot['status'] === 'used' ? 'text-muted' : '' ?>">
            <td><?= $i + 1 ?></td>
            <td>
                <a href="<?= base_url('kacha/view/' . $lot['id']) ?>" class="fw-semibold text-decoration-none">
                    <?= esc($lot['lot_number']) ?>
                </a>
            </td>
            <td><?= $lot['receipt_date'] ? date('d/m/Y', strtotime($lot['receipt_date'])) : '—' ?></td>
            <td class="text-end"><?= number_format($lot['weight'], 3) ?></td>
            <td class="text-end"><?= number_format($lot['touch_pct'], 2) ?>%</td>
            <td class="text-end fw-semibold"><?= number_format($lot['fine'], 4) ?></td>
            <td><?= esc($lot['party'] ?? '—') ?></td>
            <td>
                <?php $srcMap = ['purchase'=>'Purchase','internal'=>'Internal','part_order'=>'Part Order','melt_job'=>'Melt Job']; ?>
                <span class="badge bg-secondary"><?= $srcMap[$lot['source_type']] ?? $lot['source_type'] ?></span>
            </td>
            <td class="text-end"><?= $lot['test_touch'] ? number_format($lot['test_touch'], 2) . '%' : '—' ?></td>
            <td>
                <?php if ($lot['status'] === 'available'): ?>
                    <span class="badge bg-success">Available</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Used</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="<?= base_url('kacha/view/' . $lot['id']) ?>" class="btn btn-xs btn-outline-secondary py-0 px-1" title="View"><i class="bi bi-eye"></i></a>
                <?php if ($lot['status'] === 'available'): ?>
                    <a href="<?= base_url('kacha/edit/' . $lot['id']) ?>" class="btn btn-xs btn-outline-primary py-0 px-1" title="Edit"><i class="bi bi-pencil"></i></a>
                    <a href="<?= base_url('kacha/delete/' . $lot['id']) ?>" class="btn btn-xs btn-outline-danger py-0 px-1" title="Delete" onclick="return confirm('Delete lot <?= esc($lot['lot_number']) ?>?')"><i class="bi bi-trash"></i></a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="11" class="text-center text-muted py-3">No kacha lots found.</td></tr>
    <?php endif; ?>
    </tbody>
    <?php if ($lots): ?>
    <tfoot class="table-light fw-semibold">
        <tr>
            <td colspan="3" class="text-end">Totals (<?= count($lots) ?> lots):</td>
            <td class="text-end"><?= number_format($totalWeight, 3) ?></td>
            <td class="text-end"><?= number_format($avgTouch, 2) ?>%</td>
            <td class="text-end"><?= number_format($totalFine, 4) ?></td>
            <td colspan="5"></td>
        </tr>
    </tfoot>
    <?php endif; ?>
</table>
</div>
<?= $this->endSection() ?>
