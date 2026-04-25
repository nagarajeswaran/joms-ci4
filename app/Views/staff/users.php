<?= $this->extend(session()->has('user') ? 'layouts/main' : 'layouts/staff') ?>
<?= $this->section('content') ?>

<div class="d-flex gap-2 mb-3">
    <a href="<?= base_url('staff/users/create') ?>" class="btn btn-primary flex-fill">
        <i class="bi bi-person-plus"></i> Create Staff User
    </a>
</div>

<div class="card staff-card mb-3">
    <div class="card-body p-3">
        <div class="fw-bold mb-3">Staff Users</div>
        <div class="mobile-list">
            <?php foreach ($staffUsers as $user): ?>
                <div class="mobile-item">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="title"><?= esc($user['username']) ?></div>
                            <div class="meta"><?= esc($user['name'] ?: '-') ?></div>
                        </div>
                        <span class="badge-soft"><?= esc($user['role'] ?: '-') ?></span>
                    </div>
                    <div class="mt-2 small text-muted">
                        <div>Status: <?= esc((string) ($user['status'] ?? '-')) ?></div>
                        <div>Email: <?= esc($user['email'] ?: '-') ?></div>
                        <div>Created: <?= esc($user['created_at'] ?: '-') ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (empty($staffUsers)): ?>
            <div class="alert alert-info mb-0">No staff users created yet.</div>
        <?php endif; ?>
    </div>
</div>

<div class="card staff-card">
    <div class="card-body p-3">
        <div class="fw-bold mb-3">Login Tracking</div>
        <div class="mobile-list">
            <?php foreach ($loginLogs as $log): ?>
                <div class="mobile-item">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="title"><?= esc($log['username']) ?></div>
                            <div class="meta">IP: <?= esc($log['ip_address'] ?: '-') ?></div>
                        </div>
                        <span class="badge-soft"><?= esc($log['login_status']) ?></span>
                    </div>
                    <div class="mt-2 small text-muted">
                        <div>Login: <?= esc($log['login_at']) ?></div>
                        <div>Logout: <?= esc($log['logout_at'] ?: 'Active') ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (empty($loginLogs)): ?>
            <div class="alert alert-info mb-0">No login history yet.</div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>