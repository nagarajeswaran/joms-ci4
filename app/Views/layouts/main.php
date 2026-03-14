<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'JOMS - Job Order Management System' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }

        /* Sidebar */
        .sidebar {
            position: fixed; top: 0; left: 0; width: 220px; height: 100vh;
            background: #2c3e50; color: white; padding-top: 0;
            overflow-y: auto; z-index: 1000;
            transition: transform 0.25s ease;
        }
        .sidebar.collapsed { transform: translateX(-220px); }
        .sidebar-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 16px 14px 20px;
            border-bottom: 1px solid #34495e;
        }
        .sidebar-header h4 { margin: 0; font-size: 16px; }
        .sidebar-close {
            background: none; border: none; color: #bdc3c7;
            font-size: 18px; cursor: pointer; line-height: 1; padding: 0;
        }
        .sidebar-close:hover { color: white; }
        .sidebar a { display: block; color: #bdc3c7; padding: 10px 20px; text-decoration: none; font-size: 14px; border-left: 3px solid transparent; }
        .sidebar a:hover, .sidebar a.active { background: #34495e; color: white; border-left-color: #3498db; }
        .sidebar a i { margin-right: 8px; width: 20px; text-align: center; }
        .sidebar .nav-section { color: #7f8c8d; font-size: 11px; text-transform: uppercase; padding: 15px 20px 5px; letter-spacing: 1px; }

        /* Toggle button (visible when sidebar is collapsed) */
        .sidebar-toggle {
            position: fixed; top: 14px; left: 14px; z-index: 999;
            background: #2c3e50; color: white; border: none;
            border-radius: 6px; padding: 7px 11px; font-size: 16px;
            cursor: pointer; display: none; box-shadow: 0 2px 6px rgba(0,0,0,0.25);
            transition: left 0.25s ease;
        }
        .sidebar-toggle:hover { background: #34495e; }
        body.sidebar-collapsed .sidebar-toggle { display: block; }

        /* Main content */
        .main-content { margin-left: 220px; padding: 20px; transition: margin-left 0.25s ease; }
        body.sidebar-collapsed .main-content { margin-left: 0; }

        .top-bar {
            background: white; padding: 12px 20px; margin: -20px -20px 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex; justify-content: space-between; align-items: center;
        }
        .top-bar h5 { margin: 0; }

        .card-stat { border: none; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
        .card-stat .card-body { padding: 20px; }
        .table th { font-size: 13px; font-weight: 600; }
        thead:not(.table-dark) th { background: #f8f9fa; }
        .btn-sm { font-size: 12px; }
        .form-label { font-weight: 600; font-size: 13px; }
    </style>
    <?= $this->renderSection('styles') ?>
</head>
<body>
    <!-- Toggle button shown when sidebar is hidden -->
    <button class="sidebar-toggle" id="sidebarToggle" title="Open menu">
        <i class="bi bi-list"></i>
    </button>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4><i class="bi bi-gem"></i> JOMS</h4>
            <button class="sidebar-close" id="sidebarClose" title="Hide sidebar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="nav-section">Master Data</div>
        <a href="<?= base_url('product-types') ?>" class="<?= uri_string() == 'product-types' ? 'active' : '' ?>"><i class="bi bi-grid"></i> Product Types</a>
        <a href="<?= base_url('bodies') ?>" class="<?= uri_string() == 'bodies' ? 'active' : '' ?>"><i class="bi bi-box"></i> Bodies</a>
        <a href="<?= base_url('variations') ?>" class="<?= uri_string() == 'variations' ? 'active' : '' ?>"><i class="bi bi-arrows-angle-expand"></i> Variations</a>
        <a href="<?= base_url('departments') ?>" class="<?= uri_string() == 'departments' ? 'active' : '' ?>"><i class="bi bi-building"></i> Departments</a>
        <a href="<?= base_url('parts') ?>" class="<?= uri_string() == 'parts' ? 'active' : '' ?>"><i class="bi bi-puzzle"></i> Parts</a>
        <a href="<?= base_url('podies') ?>" class="<?= uri_string() == 'podies' ? 'active' : '' ?>"><i class="bi bi-circle"></i> Podi</a>
        <a href="<?= base_url('clients') ?>" class="<?= uri_string() == 'clients' ? 'active' : '' ?>"><i class="bi bi-people"></i> Clients</a>
        <a href="<?= base_url('stamps') ?>" class="<?= uri_string() == 'stamps' ? 'active' : '' ?>"><i class="bi bi-bookmark"></i> Stamps</a>
        <a href="<?= base_url('pattern-names') ?>" class="<?= str_starts_with(uri_string(), 'pattern-names') ? 'active' : '' ?>"><i class="bi bi-tag"></i> Pattern Names</a>
        <a href="<?= base_url('templates') ?>" class="<?= str_starts_with(uri_string(), 'templates') ? 'active' : '' ?>"><i class="bi bi-file-earmark-text"></i> BOM Templates</a>
        <div class="nav-section">Production</div>
        <a href="<?= base_url('products') ?>" class="<?= str_starts_with(uri_string(), 'products') ? 'active' : '' ?>"><i class="bi bi-bag"></i> Products</a>
        <a href="<?= base_url('orders') ?>" class="<?= str_starts_with(uri_string(), 'orders') ? 'active' : '' ?>"><i class="bi bi-clipboard-check"></i> Orders</a>
        <div class="nav-section">Inventory</div>
        <a href="<?= base_url('stock') ?>" class="<?= str_starts_with(uri_string(), 'stock') ? 'active' : '' ?>"><i class="bi bi-boxes"></i> Stock</a>
        <a href="<?= base_url('stock/entry') ?>" class="<?= uri_string() == 'stock/entry' ? 'active' : '' ?>"><i class="bi bi-plus-circle"></i> Add Stock</a>
        <a href="<?= base_url('stock/scan') ?>" class="<?= uri_string() == 'stock/scan' ? 'active' : '' ?>"><i class="bi bi-qr-code-scan"></i> Scan & Sell</a>
        <a href="<?= base_url('stock/transfer') ?>" class="<?= uri_string() == 'stock/transfer' ? 'active' : '' ?>"><i class="bi bi-arrow-left-right"></i> Transfer</a>
        <a href="<?= base_url('stock/low-stock') ?>" class="<?= uri_string() == 'stock/low-stock' ? 'active' : '' ?>"><i class="bi bi-exclamation-triangle"></i> Low Stock</a>
        <a href="<?= base_url('stock/audit-log') ?>" class="<?= uri_string() == 'stock/audit-log' ? 'active' : '' ?>"><i class="bi bi-journal-text"></i> Audit Log</a>
        <a href="<?= base_url('stock/label-generate') ?>" class="<?= uri_string() == 'stock/label-generate' ? 'active' : '' ?>"><i class="bi bi-printer"></i> Generate Labels</a>
        <div class="nav-section">Manufacturing</div>
        <a href="<?= base_url('karigar') ?>" class="<?= str_starts_with(uri_string(), 'karigar') && !str_starts_with(uri_string(), 'karigar-ledger') ? 'active' : '' ?>"><i class="bi bi-person-badge"></i> Karigar</a>
        <a href="<?= base_url('melt-jobs') ?>" class="<?= str_starts_with(uri_string(), 'melt-jobs') ? 'active' : '' ?>"><i class="bi bi-fire"></i> Melt Jobs</a>
        <a href="<?= base_url('part-orders') ?>" class="<?= str_starts_with(uri_string(), 'part-orders') ? 'active' : '' ?>"><i class="bi bi-clipboard2-check"></i> Part Orders</a>
        <a href="<?= base_url('karigar-ledger') ?>" class="<?= str_starts_with(uri_string(), 'karigar-ledger') ? 'active' : '' ?>"><i class="bi bi-journal-bookmark"></i> Karigar Ledger</a>
        <div class="nav-section">Mfg Stock</div>
        <a href="<?= base_url('part-stock') ?>" class="<?= str_starts_with(uri_string(), 'part-stock') ? 'active' : '' ?>"><i class="bi bi-boxes"></i> Part Batches</a>
        <a href="<?= base_url('gatti-stock') ?>" class="<?= uri_string() == 'gatti-stock' ? 'active' : '' ?>"><i class="bi bi-bar-chart-steps"></i> Gatti Stock</a>
        <a href="<?= base_url('raw-materials') ?>" class="<?= str_starts_with(uri_string(), 'raw-materials') ? 'active' : '' ?>"><i class="bi bi-collection"></i> Raw Materials</a>
        <a href="<?= base_url('byproducts') ?>" class="<?= uri_string() == 'byproducts' ? 'active' : '' ?>"><i class="bi bi-recycle"></i> Byproducts</a>
        <div class="nav-section">Mfg Masters</div>
        <a href="<?= base_url('raw-material-types') ?>" class="<?= str_starts_with(uri_string(), 'raw-material-types') ? 'active' : '' ?>"><i class="bi bi-list-ul"></i> Raw Material Types</a>
        <a href="<?= base_url('byproduct-types') ?>" class="<?= str_starts_with(uri_string(), 'byproduct-types') ? 'active' : '' ?>"><i class="bi bi-list-ul"></i> Byproduct Types</a>
        <a href="<?= base_url('kacha') ?>" class="<?= str_starts_with(uri_string(), 'kacha') ? 'active' : '' ?>"><i class="bi bi-droplet"></i> Kacha</a>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h5><?= $title ?? 'Dashboard' ?></h5>
            <span class="text-muted" style="font-size:13px;">JOMS v2.0 | CI4</span>
        </div>
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?= $this->renderSection('content') ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script><script>var BASE_URL = '<?= base_url() ?>';</script>
    <script>
    (function() {
        var STORAGE_KEY = 'joms_sidebar';
        var body = document.body;
        var sidebar = document.getElementById('sidebar');
        var closeBtn = document.getElementById('sidebarClose');
        var toggleBtn = document.getElementById('sidebarToggle');

        function collapse() {
            sidebar.classList.add('collapsed');
            body.classList.add('sidebar-collapsed');
            localStorage.setItem(STORAGE_KEY, 'collapsed');
        }
        function expand() {
            sidebar.classList.remove('collapsed');
            body.classList.remove('sidebar-collapsed');
            localStorage.setItem(STORAGE_KEY, 'open');
        }

        // Restore state on page load
        if (localStorage.getItem(STORAGE_KEY) === 'collapsed') collapse();

        closeBtn.addEventListener('click', collapse);
        toggleBtn.addEventListener('click', expand);
    })();
    </script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>

