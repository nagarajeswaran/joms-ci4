<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0" id="productCount"><?= count($items) ?> Products</h6>
    <a href="<?= base_url('products/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Add Product</a>
</div>

<!-- Filter bar -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" id="filterQ" class="form-control form-control-sm"
                       placeholder="Search name, SKU, Tamil name...">
            </div>
            <div class="col-md-2">
                <select id="filterType" class="form-select form-select-sm">
                    <option value="">All Product Types</option>
                    <?php foreach ($productTypes as $pt): ?>
                    <option value="<?= $pt['id'] ?>"><?= esc($pt['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select id="filterBody" class="form-select form-select-sm">
                    <option value="">All Bodies</option>
                    <?php foreach ($bodies as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= esc($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select id="filterPidi" class="form-select form-select-sm">
                    <option value="">All Pidi</option>
                    <?php foreach ($pidiValues as $pv): ?>
                    <option value="<?= strtolower(esc($pv)) ?>"><?= esc($pv) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select id="filterMain" class="form-select form-select-sm">
                    <option value="">All Main Parts</option>
                    <?php foreach ($mainParts as $mp): ?>
                    <option value="<?= $mp['id'] ?>"><?= esc($mp['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-auto">
                <button id="btnReset" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-circle"></i> Reset
                </button>
            </div>
        </div>
        <div id="activeBadges" class="mt-2 d-flex gap-1 flex-wrap"></div>
    </div>
</div>

<?php
$sort    = $sortBy  ?? 'name';
$dir     = $sortDir ?? 'asc';
$nextDir = $dir === 'asc' ? 'desc' : 'asc';

function sortUrl($col, $sort, $nextDir, $dir) {
    $d = ($sort === $col) ? $nextDir : 'asc';
    return "javascript:sortProducts('" . $col . "','" . $d . "')";
}
function sortIcon($col, $sort, $dir) {
    if ($sort !== $col) return '<i class="bi bi-arrow-down-up text-muted ms-1" style="font-size:10px;"></i>';
    return $dir === 'asc'
        ? '<i class="bi bi-arrow-up ms-1" style="font-size:10px;"></i>'
        : '<i class="bi bi-arrow-down ms-1" style="font-size:10px;"></i>';
}
?>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" style="font-size:13px;" id="productsTable">
            <thead>
                <tr>
                    <th class="seq-col">#</th>
                    <th style="width:40px;"></th>
                    <th><a href="<?= sortUrl('sku', $sort, $nextDir, $dir) ?>" class="text-decoration-none text-dark">SKU<?= sortIcon('sku', $sort, $dir) ?></a></th>
                    <th><a href="<?= sortUrl('name', $sort, $nextDir, $dir) ?>" class="text-decoration-none text-dark">Name<?= sortIcon('name', $sort, $dir) ?></a></th>
                    <th>Tamil Name</th>
                    <th><a href="<?= sortUrl('product_type_name', $sort, $nextDir, $dir) ?>" class="text-decoration-none text-dark">Product Type<?= sortIcon('product_type_name', $sort, $dir) ?></a></th>
                    <th>Body</th>
                    <th>Main Part</th>
                    <th>Pidi</th>
                    <th><a href="<?= sortUrl('pattern_count', $sort, $nextDir, $dir) ?>" class="text-decoration-none text-dark">Patterns<?= sortIcon('pattern_count', $sort, $dir) ?></a></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr
                    data-name="<?= strtolower(esc($item['name'])) ?>"
                    data-sku="<?= strtolower(esc($item['sku'] ?? '')) ?>"
                    data-tamil="<?= strtolower(esc($item['tamil_name'] ?? '')) ?>"
                    data-type="<?= $item['product_type_id'] ?? '' ?>"
                    data-body="<?= $item['body_id'] ?? '' ?>"
                    data-pidi="<?= strtolower(esc($item['pidi'] ?? '')) ?>"
                    data-main="<?= $item['main_part_id'] ?? '' ?>"
                >
                    <td class="seq-col"><?= $i + 1 ?></td>
                    <td class="text-center p-1">
                        <?php if (!empty($item['image'])): ?>
                        <img src="<?= base_url('uploads/products/' . $item['image']) ?>"
                             style="height:32px;width:32px;object-fit:cover;border-radius:3px;border:1px solid #ddd;cursor:pointer;"
                             data-img="<?= base_url('uploads/products/' . $item['image']) ?>"
                             data-name="<?= esc($item['name']) ?>"
                             class="product-thumb-preview">
                        <?php else: ?><span class="text-muted" style="font-size:11px;">-</span><?php endif; ?>
                    </td>
                    <td><small class="text-muted"><?= esc($item['sku'] ?? '') ?></small></td>
                    <td><strong><?= esc($item['name']) ?></strong></td>
                    <td><?= esc($item['tamil_name'] ?? '') ?></td>
                    <td><?= esc($item['product_type_name'] ?? '') ?></td>
                    <td><?= esc($item['body_name'] ?? '') ?></td>
                    <td><?= esc($item['main_part_name'] ?? '') ?></td>
                    <td><?= esc($item['pidi'] ?? '') ?></td>
                    <td><span class="badge bg-info"><?= $item['pattern_count'] ?? 0 ?></span></td>
                    <td>
                        <a href="<?= base_url('products/view/' . $item['id']) ?>" class="btn btn-info btn-sm"><i class="bi bi-eye"></i></a>
                        <a href="<?= base_url('products/edit/' . $item['id']) ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                        <a href="<?= base_url('products/duplicate/' . $item['id']) ?>" class="btn btn-secondary btn-sm" onclick="return confirm('Duplicate this product?')" title="Duplicate"><i class="bi bi-copy"></i></a>
                        <a href="<?= base_url('products/delete/' . $item['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product and all its BOM data?')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr id="noResultsRow" style="display:none;">
                    <td colspan="11" class="text-center text-muted py-4">No products match the current filters.</td>
                </tr>
                <?php if (empty($items)): ?>
                <tr><td colspan="11" class="text-center text-muted py-4">No products found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Image preview modal -->
<div id="thumbModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:8px;padding:12px;max-width:340px;width:90%;text-align:center;position:relative;">
    <button id="thumbModalClose" style="position:absolute;top:6px;right:10px;background:none;border:none;font-size:18px;cursor:pointer;">&times;</button>
    <p id="thumbModalName" style="font-weight:600;margin-bottom:8px;"></p>
    <img id="thumbModalImg" src="" style="max-width:100%;max-height:300px;object-fit:contain;border-radius:4px;">
  </div>
</div>

<script>
(function() {
    var typeLabels = {}, bodyLabels = {}, mainLabels = {};
    <?php foreach ($productTypes as $pt): ?>
    typeLabels['<?= $pt['id'] ?>'] = '<?= addslashes($pt['name']) ?>';
    <?php endforeach; ?>
    <?php foreach ($bodies as $b): ?>
    bodyLabels['<?= $b['id'] ?>'] = '<?= addslashes($b['name']) ?>';
    <?php endforeach; ?>
    <?php foreach ($mainParts as $mp): ?>
    mainLabels['<?= $mp['id'] ?>'] = '<?= addslashes($mp['name']) ?>';
    <?php endforeach; ?>

    var rows      = Array.from(document.querySelectorAll('#productsTable tbody tr[data-name]'));
    var countEl   = document.getElementById('productCount');
    var badgesEl  = document.getElementById('activeBadges');
    var noResults = document.getElementById('noResultsRow');

    function applyFilter() {
        var q    = document.getElementById('filterQ').value.toLowerCase().trim();
        var type = document.getElementById('filterType').value;
        var body = document.getElementById('filterBody').value;
        var pidi = document.getElementById('filterPidi').value;
        var main = document.getElementById('filterMain').value;

        var visible = 0;
        rows.forEach(function(row) {
            var match =
                (!q    || row.dataset.name.includes(q) || row.dataset.sku.includes(q) || row.dataset.tamil.includes(q)) &&
                (!type || row.dataset.type === type) &&
                (!body || row.dataset.body === body) &&
                (!pidi || row.dataset.pidi === pidi) &&
                (!main || row.dataset.main === main);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });

        // renumber
        var seq = 1;
        rows.forEach(function(row) {
            if (row.style.display !== 'none') {
                var c = row.querySelector('.seq-col');
                if (c) c.textContent = seq++;
            }
        });

        countEl.textContent = visible + ' Products';
        if (noResults) noResults.style.display = (visible === 0 && rows.length > 0) ? '' : 'none';
        updateBadges(q, type, body, pidi, main);
        updateUrl(q, type, body, pidi, main);
    }

    function updateBadges(q, type, body, pidi, main) {
        var defs = [
            { id:'filterQ',    val:q,    label:'Search: ' + q },
            { id:'filterType', val:type, label:'Type: '   + (typeLabels[type] || type) },
            { id:'filterBody', val:body, label:'Body: '   + (bodyLabels[body] || body) },
            { id:'filterPidi', val:pidi, label:'Pidi: '   + pidi },
            { id:'filterMain', val:main, label:'Part: '   + (mainLabels[main] || main) },
        ];
        var html = '';
        defs.forEach(function(d) {
            if (!d.val) return;
            html += '<span class="badge bg-primary d-inline-flex align-items-center gap-1" style="font-size:12px;font-weight:500;">' +
                escHtml(d.label) +
                '<button type="button" class="btn-close btn-close-white ms-1" style="font-size:9px;" data-filter-id="' + d.id + '" aria-label="Clear"></button>' +
                '</span>';
        });
        badgesEl.innerHTML = html;
        badgesEl.querySelectorAll('[data-filter-id]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var el = document.getElementById(this.dataset.filterId);
                if (el) { el.value = ''; applyFilter(); }
            });
        });
    }

    function updateUrl(q, type, body, pidi, main) {
        var params = new URLSearchParams();
        if (q)    params.set('q',    q);
        if (type) params.set('type', type);
        if (body) params.set('body', body);
        if (pidi) params.set('pidi', pidi);
        if (main) params.set('main', main);
        var qs = params.toString();
        history.replaceState(null, '', qs ? '?' + qs : location.pathname);
    }

    var debounceTimer;
    document.getElementById('filterQ').addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(applyFilter, 300);
    });
    ['filterType','filterBody','filterPidi','filterMain'].forEach(function(id) {
        document.getElementById(id).addEventListener('change', applyFilter);
    });

    document.getElementById('btnReset').addEventListener('click', function() {
        ['filterQ','filterType','filterBody','filterPidi','filterMain'].forEach(function(id) {
            document.getElementById(id).value = '';
        });
        applyFilter();
    });

    // restore URL params on load
    (function() {
        var p    = new URLSearchParams(location.search);
        var q    = p.get('q')    || '';
        var type = p.get('type') || '';
        var body = p.get('body') || '';
        var pidi = p.get('pidi') || '';
        var main = p.get('main') || '';
        if (q)    document.getElementById('filterQ').value    = q;
        if (type) document.getElementById('filterType').value = type;
        if (body) document.getElementById('filterBody').value = body;
        if (pidi) document.getElementById('filterPidi').value = pidi;
        if (main) document.getElementById('filterMain').value = main;
        if (q || type || body || pidi || main) applyFilter();
    })();

    // sort — preserves active filters
    window.sortProducts = function(col, dir) {
        var params = new URLSearchParams(location.search);
        params.set('sort', col);
        params.set('dir',  dir);
        location.href = '?' + params.toString();
    };

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // thumbnail modal
    document.querySelectorAll('.product-thumb-preview').forEach(function(img) {
        img.addEventListener('click', function() {
            document.getElementById('thumbModalImg').src  = this.dataset.img;
            document.getElementById('thumbModalName').textContent = this.dataset.name;
            document.getElementById('thumbModal').style.display = 'flex';
        });
    });
    document.getElementById('thumbModalClose').addEventListener('click', function() {
        document.getElementById('thumbModal').style.display = 'none';
    });
    document.getElementById('thumbModal').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
})();
</script>
<?php $this->endSection() ?>
