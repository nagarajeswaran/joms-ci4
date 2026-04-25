<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= esc($title ?? 'Staff App') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background:#f3f5f7; font-family:'Segoe UI',sans-serif; padding-bottom:88px; }
        .staff-shell { max-width:760px; margin:0 auto; }
        .staff-topbar { position:sticky; top:0; z-index:1020; background:#0f172a; color:#fff; padding:14px 16px; box-shadow:0 2px 10px rgba(0,0,0,.15); }
        .staff-topbar h1 { font-size:18px; margin:0; font-weight:700; }
        .staff-topbar .meta { font-size:12px; opacity:.8; }
        .staff-content { padding:16px; }
        .staff-card { border:none; border-radius:18px; box-shadow:0 8px 24px rgba(15,23,42,.08); }
        .quick-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:12px; }
        .quick-link { display:flex; flex-direction:column; gap:6px; text-decoration:none; color:#0f172a; padding:16px; background:#fff; border-radius:18px; box-shadow:0 8px 20px rgba(15,23,42,.06); min-height:120px; }
        .quick-link i { font-size:24px; }
        .quick-link .label { font-size:17px; font-weight:700; }
        .quick-link .hint { font-size:13px; color:#64748b; }
        .form-control, .form-select, .btn { min-height:48px; border-radius:14px; }
        .btn { font-weight:600; }
        .mobile-list { display:grid; gap:12px; }
        .mobile-item { background:#fff; border-radius:16px; padding:14px; box-shadow:0 6px 18px rgba(15,23,42,.06); }
        .mobile-item .title { font-weight:700; font-size:16px; }
        .mobile-item .meta { font-size:13px; color:#64748b; }
        .stat-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:12px; }
        .stat-box { background:#fff; border-radius:18px; padding:14px; box-shadow:0 8px 20px rgba(15,23,42,.06); }
        .stat-box .value { font-size:24px; font-weight:800; }
        .bottom-nav { position:fixed; left:0; right:0; bottom:0; background:#fff; border-top:1px solid #e5e7eb; padding:10px 12px calc(10px + env(safe-area-inset-bottom)); z-index:1030; }
        .bottom-nav-inner { max-width:760px; margin:0 auto; display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:10px; }
        .bottom-nav a { text-decoration:none; color:#475569; font-size:12px; display:flex; flex-direction:column; align-items:center; gap:4px; padding:6px; }
        .bottom-nav a.active { color:#2563eb; font-weight:700; }
        .search-box { position:relative; }
        .search-box i { position:absolute; left:16px; top:50%; transform:translateY(-50%); color:#64748b; }
        .search-box input { padding-left:44px; }
        .badge-soft { background:#eff6ff; color:#1d4ed8; border-radius:999px; padding:6px 10px; font-size:12px; font-weight:700; }
        <?= $this->renderSection('styles') ?>
    </style>
</head>
<body>
    <div class="staff-shell">
        <div class="staff-topbar">
            <div class="d-flex justify-content-between align-items-center gap-3">
                <div>
                    <h1><?= esc($title ?? 'Staff') ?></h1>
                    <div class="meta"><?= esc(session()->get('staff_user')['name'] ?? 'Staff') ?></div>
                </div>
                <a href="<?= base_url('staff/logout') ?>" class="btn btn-sm btn-light">Logout</a>
            </div>
        </div>
        <div class="staff-content">
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
            <?php endif; ?>
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
            <?php endif; ?>
            <?= $this->renderSection('content') ?>
        </div>
    </div>
    <div class="bottom-nav">
        <div class="bottom-nav-inner">
            <a href="<?= base_url('staff') ?>" class="<?= uri_string() === 'staff' ? 'active' : '' ?>">
                <i class="bi bi-house-door"></i>
                <span>Home</span>
            </a>
            <a href="<?= base_url('staff/users') ?>" class="<?= str_starts_with(uri_string(), 'staff/users') ? 'active' : '' ?>">
                <i class="bi bi-people"></i>
                <span>Users</span>
            </a>
            <a href="<?= base_url('staff/touch-booking') ?>" class="<?= str_starts_with(uri_string(), 'staff/touch-booking') ? 'active' : '' ?>">
                <i class="bi bi-flask"></i>
                <span>Touch</span>
            </a>
            <a href="<?= base_url('staff/stock-lookup') ?>" class="<?= str_starts_with(uri_string(), 'staff/stock-lookup') ? 'active' : '' ?>">
                <i class="bi bi-search"></i>
                <span>Stock</span>
            </a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>