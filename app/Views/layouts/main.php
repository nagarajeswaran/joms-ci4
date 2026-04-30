<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'JOMS - Job Order Management System' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <meta name="turbo-cache-control" content="no-cache">
    <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.12/dist/turbo.es2017-umd.js" defer></script>
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', sans-serif; }

        /* Sidebar */
        .sidebar {
            position: fixed; top: 0; left: 0; width: 220px; height: 100vh;
            background: #2c3e50; color: white; padding-top: 0;
            overflow-y: auto; z-index: 1000;
            transition: transform 0.25s ease;
            display: flex; flex-direction: column;
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
        .sidebar-backdrop {
            position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45);
            z-index: 998; display: none;
        }
        body.sidebar-open-mobile .sidebar-backdrop { display: block; }
        @media (max-width: 991.98px) {
            .sidebar { transform: translateX(-220px); box-shadow: 0 8px 24px rgba(0,0,0,0.2); }
            .main-content { margin-left: 0; }
            .sidebar-toggle { display: block; }
            body.sidebar-open-mobile .sidebar { transform: translateX(0); }
            body.sidebar-collapsed .sidebar-toggle { display: block; }
        }

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
    <?php
        use Config\RoleAccess;

        $currentUri = uri_string();
        $currentUser = session()->get('user') ?? [];
        $normalizedRole = RoleAccess::normalizeRole((string) ($currentUser['role'] ?? 'user'));
        $userModules    = (array) ($currentUser['modules'] ?? []);
        $canViewModule = static function (array $module, string $role, array $modules): bool {
            return RoleAccess::canViewSidebarModule($role, $module['title'] ?? '', $modules);
        };
        $sidebarModules = [
            [
                'title' => 'Dashboard',
                'items' => [
                    [
                        'label' => 'Admin Dashboard',
                        'url' => base_url('/'),
                        'icon' => 'bi-speedometer2',
                        'match' => static fn(string $uri): bool => $uri === '',
                    ],
                ],
            ],
            [
                'title' => 'Administration',
                'items' => [
                    [
                        'label' => 'Users',
                        'url' => base_url('users'),
                        'icon' => 'bi-people',
                        'match' => static fn(string $uri): bool => $uri === 'users',
                    ],
                    [
                        'label' => 'Create User',
                        'url' => base_url('users/create'),
                        'icon' => 'bi-person-plus',
                        'match' => static fn(string $uri): bool => $uri === 'users/create',
                        'indent' => true,
                    ],
                ],
            ],
            [
                'title' => 'Masters',
                'items' => [
                    ['label' => 'Product Types', 'url' => base_url('product-types'), 'icon' => 'bi-grid', 'match' => static fn(string $uri): bool => $uri === 'product-types'],
                    ['label' => 'Bodies', 'url' => base_url('bodies'), 'icon' => 'bi-box', 'match' => static fn(string $uri): bool => $uri === 'bodies'],
                    ['label' => 'Variations', 'url' => base_url('variations'), 'icon' => 'bi-arrows-angle-expand', 'match' => static fn(string $uri): bool => $uri === 'variations'],
                    ['label' => 'Departments', 'url' => base_url('departments'), 'icon' => 'bi-building', 'match' => static fn(string $uri): bool => $uri === 'departments'],
                    ['label' => 'Parts', 'url' => base_url('parts'), 'icon' => 'bi-puzzle', 'match' => static fn(string $uri): bool => $uri === 'parts'],
                    ['label' => 'Podi', 'url' => base_url('podies'), 'icon' => 'bi-circle', 'match' => static fn(string $uri): bool => $uri === 'podies'],
                    ['label' => 'Clients', 'url' => base_url('clients'), 'icon' => 'bi-people', 'match' => static fn(string $uri): bool => $uri === 'clients'],
                    ['label' => 'Stamps', 'url' => base_url('stamps'), 'icon' => 'bi-bookmark', 'match' => static fn(string $uri): bool => $uri === 'stamps'],
                    ['label' => 'Pattern Names', 'url' => base_url('pattern-names'), 'icon' => 'bi-tag', 'match' => static fn(string $uri): bool => str_starts_with($uri, 'pattern-names')],
                    ['label' => 'BOM Templates', 'url' => base_url('templates'), 'icon' => 'bi-file-earmark-text', 'match' => static fn(string $uri): bool => str_starts_with($uri, 'templates')],
                ],
            ],
            [
                'title' => 'Products',
                'items' => [
                    ['label' => 'Products', 'url' => base_url('products'), 'icon' => 'bi-bag', 'match' => static fn(string $uri): bool => str_starts_with($uri, 'products'), 'indent' => false],
                    ['label' => 'Image Gallery', 'url' => base_url('products/imageGallery'), 'icon' => 'bi-images', 'match' => static fn(string $uri): bool => $uri === 'products/imageGallery', 'indent' => true],
                    ['label' => 'Bulk Update', 'url' => base_url('products/bulkEdit'), 'icon' => 'bi-file-earmark-spreadsheet', 'match' => static fn(string $uri): bool => str_starts_with($uri, 'products/bulk'), 'indent' => true],
                ],
            ],
            [
                'title' => 'Product Inventory',
                'items' => [
                    ['label' => 'Stock', 'url' => base_url('stock'), 'icon' => 'bi-boxes', 'match' => static fn(string $uri): bool => str_starts_with($uri, 'stock')],
                    ['label' => 'Add Stock', 'url' => base_url('stock/entry'), 'icon' => 'bi-plus-circle', 'match' => static fn(string $uri): bool => $uri === 'stock/entry'],
                    ['label' => 'Scan & Sell', 'url' => base_url('stock/scan'), 'icon' => 'bi-qr-code-scan', 'match' => static fn(string $uri): bool => $uri === 'stock/scan'],
                    ['label' => 'Transfer', 'url' => base_url('stock/transfer'), 'icon' => 'bi-arrow-left-right', 'match' => static fn(string $uri): bool => $uri === 'stock/transfer'],
                    ['label' => 'Low Stock', 'url' => base_url('stock/low-stock'), 'icon' => 'bi-exclamation-triangle', 'match' => static fn(string $uri): bool => $uri === 'stock/low-stock'],
                    ['label' => 'Audit Log', 'url' => base_url('stock/audit-log'), 'icon' => 'bi-journal-text', 'match' => static fn(string $uri): bool => $uri === 'stock/audit-log'],
                    ['label' => 'Generate Labels', 'url' => base_url('stock/label-generate'), 'icon' => 'bi-printer', 'match' => static fn(string $uri): bool => $uri === 'stock/label-generate'],
                    ['label' => 'QR Registry', 'url' => base_url('stock/qr-registry'), 'icon' => 'bi-upc-scan', 'match' => static fn(string $uri): bool => $uri === 'stock/qr-registry'],
                ],
            ],
            [
                'title' => 'Manufacturing',
                'items' => [
                    ['label' => 'Orders', 'url' => base_url('orders'), 'icon' => 'bi-clipboard-check', 'match' => static fn(string $uri): bool => str_starts_with($uri, 'orders')],
                    ['label' => 'Karigar', 'url' => base_url('karigar'), 'icon' => 'bi-person-badge', 'match' => static fn(string $uri): bool => str_starts_with($uri, 'karigar') && !str_starts_with($uri, 'karigar-ledger')],
                    ['label' => 'Melt Jobs', 'url' => base_url('melt-jobs'), 'icon' => 'bi-fire', 'match' => static fn(string $uri): bool => str_starts_with($uri, 'melt-jobs')],
                    ['label' => 'Touch Ledger', 'url' => base_url('touch-shops'), 'icon' => 'bi-flask', 'match' => static fn(string $uri): bool => str_starts_with($uri, 'touch-shops')],
                    ['label' => 'Part Orders', 'url' => base_url('part-orders'), 'icon' => 'bi-clipboard2-check', 'match' => static fn(string $uri): bool => str_starts_with($uri, 'part-orders')],
                    ['label' => 'Pending Receive Entry', 'url' => base_url('pending-receive-entry'), 'icon' => 'bi-inboxes', 'match' => static fn(string $uri): bool => str_starts_with($uri, 'pending-receive-entry')],
                    ['label' => 'Assembly Work', 'url' => base_url('assembly-work'), 'icon' => 'bi-tools', 'match' => static fn(string $uri): bool => str_starts_with($uri, 'assembly-work')],
                    ['label' => 'Karigar Ledger', 'url' => base_url('karigar-ledger'), 'icon' => 'bi-journal-bookmark', 'match' => static fn(string $uri): bool => str_starts_with($uri, 'karigar-ledger')],
                ],
            ],
            [
                'title' => 'Raw Material Stock',
                'items' => [
                    ['label' => 'Kacha', 'url' => base_url('kacha'), 'icon' => 'bi-droplet', 'match' => static fn(string $uri): bool => str_starts_with($uri, 'kacha')],
                    ['label' => 'Part Batches', 'url' => base_url('part-stock'), 'icon' => 'bi-boxes', 'match' => static fn(string $uri): bool => str_starts_with($uri, 'part-stock')],
                    ['label' => 'Gatti Stock', 'url' => base_url('gatti-stock'), 'icon' => 'bi-bar-chart-steps', 'match' => static fn(string $uri): bool => $uri === 'gatti-stock'],
                    ['label' => 'RM Batches', 'url' => base_url('raw-material-batches'), 'icon' => 'bi-stack', 'match' => static fn(string $uri): bool => str_starts_with($uri, 'raw-material-batches')],
                    ['label' => 'Raw Materials', 'url' => base_url('raw-materials'), 'icon' => 'bi-collection', 'match' => static fn(string $uri): bool => str_starts_with($uri, 'raw-materials')],
                    ['label' => 'Byproducts', 'url' => base_url('byproducts'), 'icon' => 'bi-recycle', 'match' => static fn(string $uri): bool => $uri === 'byproducts'],
                ],
            ],
            [
                'title' => 'Mfg Masters',
                'items' => [
                    ['label' => 'Raw Material Types', 'url' => base_url('raw-material-types'), 'icon' => 'bi-list-ul', 'match' => static fn(string $uri): bool => str_starts_with($uri, 'raw-material-types')],
                    ['label' => 'Byproduct Types', 'url' => base_url('byproduct-types'), 'icon' => 'bi-list-ul', 'match' => static fn(string $uri): bool => str_starts_with($uri, 'byproduct-types')],
                    ['label' => 'Finished Goods', 'url' => base_url('finished-goods'), 'icon' => 'bi-gem', 'match' => static fn(string $uri): bool => str_starts_with($uri, 'finished-goods')],
                ],
            ],
        ];
    ?>
    <!-- Toggle button shown when sidebar is hidden -->
    <button class="sidebar-toggle" id="sidebarToggle" data-turbo-permanent title="Open menu">
        <i class="bi bi-list"></i>
    </button>
    <div class="sidebar-backdrop" id="sidebarBackdrop" data-turbo-permanent></div>

    <div class="sidebar" id="sidebar" data-turbo-permanent>
        <div class="sidebar-header">
            <h4><i class="bi bi-gem"></i> JOMS</h4>
            <button class="sidebar-close" id="sidebarClose" title="Hide sidebar">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div style="flex:1; overflow-y:auto;">
        <?php foreach ($sidebarModules as $module): ?>
            <?php if (!$canViewModule($module, $normalizedRole, $userModules)): ?>
                <?php continue; ?>
            <?php endif; ?>
            <div class="nav-section"><?= esc($module['title']) ?></div>
            <?php foreach ($module['items'] as $item): ?>
                <?php
                    $isActive = $item['match']($currentUri);
                    $itemClasses = $isActive ? 'active' : '';
                    $itemStyles = !empty($item['indent']) ? 'padding-left:28px;font-size:12px;' : '';
                ?>
                <a href="<?= $item['url'] ?>" class="<?= $itemClasses ?>"<?= $itemStyles !== '' ? ' style="' . $itemStyles . '"' : '' ?>>
                    <i class="bi <?= esc($item['icon']) ?>"></i><?= esc($item['label']) ?>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </div>
        <div style="border-top:1px solid #34495e; padding:12px 16px; margin-top:auto;">
            <div style="font-size:12px; color:#7f8c8d; margin-bottom:8px; padding:0 4px;">
                <i class="bi bi-person-circle"></i>
                <?= esc($currentUser['name'] ?? $currentUser['username'] ?? 'User') ?>
                <span class="badge ms-1" style="background:#34495e;font-size:10px;">
                    <?= esc($currentUser['role'] ?? 'user') ?>
                </span>
            </div>
            <a href="<?= base_url('logout') ?>" class="d-block text-center" data-turbo="false"
               style="color:#e74c3c;font-size:13px;padding:7px;border-radius:5px;background:#2c3e50;text-decoration:none;"
               onmouseover="this.style.background='#e74c3c';this.style.color='white';"
               onmouseout="this.style.background='#2c3e50';this.style.color='#e74c3c';">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
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
        if (window.__jomsSidebarBound) return;
        window.__jomsSidebarBound = true;

        var STORAGE_KEY = 'joms_sidebar';
        var body = document.body;
        var sidebar = document.getElementById('sidebar');
        var closeBtn = document.getElementById('sidebarClose');
        var toggleBtn = document.getElementById('sidebarToggle');
        var backdrop = document.getElementById('sidebarBackdrop');
        var mobileQuery = window.matchMedia('(max-width: 991.98px)');
        var navLinks = sidebar.querySelectorAll('a');

        function isMobile() {
            return mobileQuery.matches;
        }

        function collapse() {
            if (isMobile()) {
                body.classList.remove('sidebar-open-mobile');
                body.classList.add('sidebar-collapsed');
                return;
            }

            sidebar.classList.add('collapsed');
            body.classList.add('sidebar-collapsed');
            localStorage.setItem(STORAGE_KEY, 'collapsed');
        }
        function expand() {
            if (isMobile()) {
                body.classList.add('sidebar-open-mobile');
                body.classList.remove('sidebar-collapsed');
                return;
            }

            sidebar.classList.remove('collapsed');
            body.classList.remove('sidebar-collapsed');
            localStorage.setItem(STORAGE_KEY, 'open');
        }
        function syncSidebarMode() {
            if (isMobile()) {
                sidebar.classList.remove('collapsed');
                body.classList.add('sidebar-collapsed');
                body.classList.remove('sidebar-open-mobile');
                return;
            }

            body.classList.remove('sidebar-open-mobile');
            if (localStorage.getItem(STORAGE_KEY) === 'collapsed') {
                sidebar.classList.add('collapsed');
                body.classList.add('sidebar-collapsed');
                return;
            }

            sidebar.classList.remove('collapsed');
            body.classList.remove('sidebar-collapsed');
        }

        syncSidebarMode();

        closeBtn.addEventListener('click', collapse);
        toggleBtn.addEventListener('click', expand);
        backdrop.addEventListener('click', collapse);
        mobileQuery.addEventListener('change', syncSidebarMode);
        navLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                if (isMobile()) collapse();
            });
        });
    })();
    </script>
    <script>
    document.addEventListener('turbo:load', function() {
        var links = document.querySelectorAll('#sidebar a[href]');
        var path = window.location.pathname.replace(/\/$/, '');
        var basePath = '<?= rtrim(base_url(), '/') ?>';
        links.forEach(function(link) {
            var href = link.getAttribute('href').replace(/\/$/, '');
            var linkPath = href.replace(basePath, '');
            var isActive = (linkPath && linkPath !== '' && linkPath !== '/')
                ? path.indexOf(basePath + linkPath) === 0
                : (path === basePath || path === basePath + '/' || path === '');
            if (isActive) { link.classList.add('active'); } else { link.classList.remove('active'); }
        });
        if (window.matchMedia('(max-width: 991.98px)').matches) {
            document.body.classList.remove('sidebar-open-mobile');
        }
    });
    </script>
    <?= $this->renderSection('scripts') ?>
</body>
</html>

