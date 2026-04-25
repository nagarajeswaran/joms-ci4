<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="d-flex gap-2 mb-3">
    <a href="<?= base_url('staff/touch-booking/create') ?>" class="btn btn-primary flex-fill">
        <i class="bi bi-plus-lg"></i> New Booking
    </a>
</div>

<div class="d-flex gap-2 mb-3">
    <a href="<?= base_url('staff/touch-booking?status=pending') ?>" class="btn <?= $status === 'pending' ? 'btn-dark' : 'btn-outline-dark' ?> flex-fill">Pending</a>
    <a href="<?= base_url('staff/touch-booking?status=completed') ?>" class="btn <?= $status === 'completed' ? 'btn-dark' : 'btn-outline-dark' ?> flex-fill">Completed</a>
</div>

<div class="mobile-list">
    <?php foreach ($entries as $entry): ?>
        <div class="mobile-item">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="title"><?= esc($entry['serial_number']) ?></div>
                    <div class="meta"><?= esc($entry['touch_shop_name'] ?: 'No touch shop') ?></div>
                </div>
                <span class="badge-soft"><?= $entry['received_at'] ? 'Done' : 'Pending' ?></span>
            </div>
            <div class="mt-2 small text-muted">
                <div>Karigar: <?= esc($entry['karigar_name'] ?: '-') ?></div>
                <div>Stamp: <?= esc($entry['stamp_name'] ?: '-') ?></div>
                <div>Issue: <?= number_format((float) $entry['issue_weight_g'], 4) ?> g</div>
                <?php if (!empty($entry['created_at'])): ?>
                    <div>Created: <?= esc(date('d M Y H:i', strtotime($entry['created_at']))) ?></div>
                <?php endif; ?>
            </div>
            <?php if (!empty($entry['notes'])): ?>
                <div class="mt-2"><?= esc($entry['notes']) ?></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php if (!$entries): ?>
    <div class="alert alert-info mt-3 mb-0">No touch entries found.</div>
<?php endif; ?>

<?= $this->endSection() ?>