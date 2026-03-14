<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-gem"></i> Kacha Lot — <?= esc($lot['lot_number']) ?></h5>
    <div>
        <?php if ($lot['status'] === 'available'): ?>
            <a href="<?= base_url('kacha/edit/' . $lot['id']) ?>" class="btn btn-primary btn-sm me-1"><i class="bi bi-pencil"></i> Edit</a>
        <?php endif; ?>
        <a href="<?= base_url('kacha') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<?php if ($lot['status'] === 'used'): ?>
<div class="alert alert-secondary">
    <i class="bi bi-check-circle-fill text-success"></i> This lot has been <strong>fully consumed</strong>.
    <?php if ($lot['used_in_melt_job_id']): ?>
        Used in <a href="<?= base_url('melt-jobs/view/' . $lot['used_in_melt_job_id']) ?>">Melt Job #<?= $lot['used_in_melt_job_id'] ?></a>.
    <?php endif; ?>
</div>
<?php else: ?>
<div class="alert alert-success py-2"><i class="bi bi-circle-fill text-success"></i> Available</div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Lot Number</dt>
            <dd class="col-sm-9 fw-bold"><?= esc($lot['lot_number']) ?></dd>

            <dt class="col-sm-3">Receipt Date</dt>
            <dd class="col-sm-9"><?= $lot['receipt_date'] ? date('d/m/Y', strtotime($lot['receipt_date'])) : '—' ?></dd>

            <dt class="col-sm-3">Weight (g)</dt>
            <dd class="col-sm-9"><?= number_format($lot['weight'], 3) ?></dd>

            <dt class="col-sm-3">Touch %</dt>
            <dd class="col-sm-9"><?= number_format($lot['touch_pct'], 4) ?>%</dd>

            <dt class="col-sm-3">Fine (g)</dt>
            <dd class="col-sm-9 fw-semibold text-success"><?= number_format($lot['fine'], 4) ?></dd>

            <dt class="col-sm-3">Source</dt>
            <dd class="col-sm-9">
                <?php $srcMap = ['purchase'=>'Purchase','internal'=>'Internal','part_order'=>'Part Order','melt_job'=>'Melt Job']; ?>
                <span class="badge bg-secondary"><?= $srcMap[$lot['source_type']] ?? $lot['source_type'] ?></span>
            </dd>

            <dt class="col-sm-3">Party</dt>
            <dd class="col-sm-9"><?= esc($lot['party'] ?? '—') ?></dd>

            <dt class="col-sm-3">Test Touch %</dt>
            <dd class="col-sm-9"><?= $lot['test_touch'] ? number_format($lot['test_touch'], 4) . '%' : '—' ?></dd>

            <dt class="col-sm-3">Test Number</dt>
            <dd class="col-sm-9"><?= esc($lot['test_number'] ?? '—') ?></dd>

            <dt class="col-sm-3">Notes</dt>
            <dd class="col-sm-9"><?= nl2br(esc($lot['notes'] ?? '—')) ?></dd>

            <dt class="col-sm-3">Created</dt>
            <dd class="col-sm-9"><?= $lot['created_at'] ? date('d/m/Y H:i', strtotime($lot['created_at'])) : '—' ?></dd>
        </dl>
    </div>
</div>
<?= $this->endSection() ?>
