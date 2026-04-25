<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<div class="stat-grid mb-3">
    <div class="stat-box">
        <div class="text-muted small">Touch Pending</div>
        <div class="value"><?= (int) ($touchSummary['pending_entries'] ?? 0) ?></div>
    </div>
    <div class="stat-box">
        <div class="text-muted small">Touch Issued (g)</div>
        <div class="value"><?= number_format((float) ($touchSummary['total_issue_weight'] ?? 0), 2) ?></div>
    </div>
    <div class="stat-box">
        <div class="text-muted small">Products in Stock</div>
        <div class="value"><?= (int) ($stockSummary['product_count'] ?? 0) ?></div>
    </div>
    <div class="stat-box">
        <div class="text-muted small">Total Stock Qty</div>
        <div class="value"><?= number_format((float) ($stockSummary['total_qty'] ?? 0), 0) ?></div>
    </div>
    <div class="stat-box">
        <div class="text-muted small">Staff Logins</div>
        <div class="value"><?= (int) ($loginSummary['total_logins'] ?? 0) ?></div>
    </div>
    <div class="stat-box">
        <div class="text-muted small">Active Sessions</div>
        <div class="value"><?= (int) ($loginSummary['active_sessions'] ?? 0) ?></div>
    </div>
</div>

<div class="quick-grid">
    <a href="<?= base_url('staff/users') ?>" class="quick-link">
        <i class="bi bi-person-plus text-dark"></i>
        <span class="label">Staff Users</span>
        <span class="hint">Create staff username and password</span>
    </a>
    <a href="<?= base_url('staff/touch-booking') ?>" class="quick-link">
        <i class="bi bi-flask text-primary"></i>
        <span class="label">Touch Booking</span>
        <span class="hint">Create and view recent touch entries</span>
    </a>
    <a href="<?= base_url('staff/stock-lookup') ?>" class="quick-link">
        <i class="bi bi-box-seam text-success"></i>
        <span class="label">Stock Lookup</span>
        <span class="hint">Search stock by product, SKU, or pattern</span>
    </a>
</div>

<?php if (!empty($recentLogins)): ?>
    <div class="card staff-card mt-3">
        <div class="card-body p-3">
            <div class="fw-bold mb-2">Recent Login Activity</div>
            <div class="mobile-list">
                <?php foreach ($recentLogins as $log): ?>
                    <div class="mobile-item">
                        <div class="title"><?= esc($log['username']) ?></div>
                        <div class="meta">Login: <?= esc($log['login_at']) ?></div>
                        <div class="meta">Logout: <?= esc($log['logout_at'] ?: 'Active') ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>