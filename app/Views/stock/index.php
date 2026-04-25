<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
/* ── Sticky toolbar ── */
.stock-toolbar {
    position: sticky; top: 0; z-index: 100;
    background: #fff; padding: 10px 0 10px;
    border-bottom: 1px solid #e0e0e0;
    margin: -20px -20px 18px; padding: 10px 20px;
    box-shadow: 0 2px 6px rgba(0,0,0,.06);
}
/* ── Product card ── */
.product-card {
    border-radius: 14px; border: 1.5px solid #e8e8e8;
    overflow: hidden; cursor: pointer; transition: box-shadow .18s, transform .15s;
    background: #fff; position: relative;
}
.product-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.13); transform: translateY(-2px); }
.product-card .card-img-wrap {
    height: 130px; background: #f4f6f9;
    display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.product-card .card-img-wrap img { width:100%; height:100%; object-fit: cover; }
.product-card .card-img-wrap .no-img { font-size: 44px; color: #c5ccd8; }
.product-card .qty-bubble {
    position: absolute; top: 8px; right: 8px;
    min-width: 28px; height: 28px; border-radius: 50px;
    font-size: 12px; font-weight: 700; line-height: 28px;
    text-align: center; padding: 0 7px;
    background: #198754; color: #fff; box-shadow: 0 2px 6px rgba(0,0,0,.18);
}
.product-card .qty-bubble.low { background: #dc3545; }
.product-card .qty-bubble.zero { background: #6c757d; }
.product-card .card-body { padding: 8px 10px 10px; }
.product-card .prod-name { font-size: 13px; font-weight: 700; line-height: 1.3; margin-bottom: 2px; }
.product-card .prod-sku  { font-size: 11px; color: #888; }
.product-card .pat-badges { margin-top: 6px; display: flex; flex-wrap: wrap; gap: 4px; }
.pat-badge {
    font-size: 10px; font-weight: 600; border-radius: 6px;
    padding: 2px 7px; background: #e8f0fe; color: #2563eb; border: 1px solid #bfcffc;
}
/* ── Offcanvas detail ── */
#stockOffcanvas { width: 480px; max-width: 98vw; }
.offcanvas-product-header { display: flex; gap: 14px; align-items: center; }
.offcanvas-product-header img, .offcanvas-product-header .oc-no-img {
    width: 60px; height: 60px; border-radius: 10px; object-fit: cover;
    background: #f4f6f9; display: flex; align-items: center; justify-content: center;
    font-size: 26px; color: #b0bec5; flex-shrink: 0;
}
.var-row-low  { background: #fff8e1; }
.var-row-empty{ background: #f5f5f5; }
/* ── Stat pills ── */
.stat-pill {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 14px; border-radius: 50px; font-size: 13px; font-weight: 600;
    border: 1px solid #e0e0e0; background: #f8f9fa;
}
.stat-pill .stat-num { font-size: 16px; font-weight: 800; }
/* hide cards via JS filter */
.product-col.hidden { display: none !important; }
/* ── Size summary pills ── */
.size-summary { display:flex; flex-wrap:wrap; gap:3px; margin-top:4px; min-height:16px; }
.ss-item {
    font-size:10px; font-weight:600; color:#444;
    background:#f0f0f0; border-radius:4px; padding:1px 4px;
}
/* ── Pattern switcher on card ── */
.pat-switcher { display:flex; flex-wrap:wrap; gap:2px; margin-top:4px; }
.ps-btn {
    font-size:10px; font-weight:700; border-radius:5px; padding:1px 5px; cursor:pointer;
    background:#e8f0fe; color:#2563eb; border:1px solid #bfcffc;
    transition: background .12s, color .12s; user-select:none; white-space:nowrap;
}
.ps-btn.active { background:#2563eb; color:#fff; border-color:#2563eb; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- ── Sticky Toolbar ── -->
<div class="stock-toolbar">
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <!-- Search -->
        <div class="flex-grow-1" style="min-width:180px;max-width:280px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" id="stockSearch" class="form-control" placeholder="Search product, SKU…" autocomplete="off">
            </div>
        </div>
        <!-- Location filter -->
        <select id="locationFilter" class="form-select form-select-sm" style="max-width:180px;">
            <option value="">All Locations</option>
            <?php foreach ($locations as $loc): ?>
                <option value="<?= $loc['id'] ?>"><?= esc($loc['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <!-- Low stock toggle -->
        <button id="btnLowOnly" class="btn btn-outline-danger btn-sm" title="Show low stock only">
            <i class="bi bi-exclamation-triangle"></i> Low
            <?php if ($lowCount > 0): ?>
                <span class="badge bg-danger ms-1"><?= $lowCount ?></span>
            <?php endif; ?>
        </button>
        <!-- Action buttons -->
        <a href="<?= base_url('stock/entry') ?>" class="btn btn-success btn-sm"><i class="bi bi-plus-circle"></i> Add Stock</a>
        <a href="<?= base_url('stock/scan') ?>" class="btn btn-warning btn-sm"><i class="bi bi-qr-code-scan"></i> Scan</a>
        <a href="<?= base_url('stock/transfer') ?>" class="btn btn-info btn-sm text-white"><i class="bi bi-arrow-left-right"></i> Transfer</a>
        <a href="<?= base_url('stock/audit-log') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-journal-text"></i> Log</a>
    </div>

    <!-- Stat pills row -->
    <div class="d-flex flex-wrap gap-2 mt-2">
        <span class="stat-pill">
            <i class="bi bi-boxes text-primary"></i>
            <span class="stat-num" id="statProducts"><?= count($products) ?></span>
            <span class="text-muted">Products</span>
        </span>
        <span class="stat-pill">
            <i class="bi bi-stack text-success"></i>
            <span class="stat-num" id="statQty"><?= number_format($totalQty) ?></span>
            <span class="text-muted">Total Qty</span>
        </span>
        <?php if ($lowCount > 0): ?>
        <span class="stat-pill border-danger" style="background:#fff5f5;">
            <i class="bi bi-exclamation-triangle text-danger"></i>
            <span class="stat-num text-danger"><?= $lowCount ?></span>
            <span class="text-muted">Low Stock</span>
        </span>
        <?php endif; ?>
        <span class="stat-pill text-muted" id="statVisible" style="display:none;">
            <i class="bi bi-funnel"></i>
            <span id="statVisibleNum">0</span> shown
        </span>
    </div>
</div>

<!-- ── Product Card Grid ── -->
<?php if (empty($products)): ?>
    <div class="alert alert-info mt-3">No stock records found. <a href="<?= base_url('stock/entry') ?>">Add stock</a> to get started.</div>
<?php else: ?>
<div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3" id="productGrid">
<?php foreach ($products as $pid => $pdata):
    $prod     = $pdata['product'];
    $totalQty = $pdata['total_qty'];
    $lowCnt   = $pdata['low_count'];
    $bubbleCls = $lowCnt > 0 ? 'low' : ($totalQty == 0 ? 'zero' : '');

    // Collect pattern codes / labels for badges
    $patLabels = [];
    foreach ($pdata['patterns'] as $ptid => $ptdata) {
        $pt = $ptdata['pattern'];
        $patLabels[] = $pt['pattern_code'] ?: ($pt['is_default'] ? 'Default' : $pt['name']);
    }

    // data attrs for JS filtering — search text built from product name + sku
    $searchText = strtolower($prod['name'] . ' ' . ($prod['sku'] ?? ''));
    $hasLow     = $lowCnt > 0 ? '1' : '0';
    $locIds = [];
    foreach ($pdata['patterns'] as $ptdata) {
        foreach ($ptdata['variations'] as $v) {
            $locIds[] = $v['location_id'];
        }
    }
    $locIds = array_unique($locIds);
?>
<div class="col product-col"
     data-search="<?= esc($searchText) ?>"
     data-low="<?= $hasLow ?>"
     data-locs="<?= implode(',', $locIds) ?>"
     data-pid="<?= $pid ?>">
    <div class="product-card h-100"
         onclick="openStock(<?= $pid ?>)"
         title="<?= esc($prod['name']) ?>">

        <div class="qty-bubble <?= $bubbleCls ?>"><?= $totalQty ?></div>

        <div class="card-img-wrap">
            <?php if (!empty($prod['image'])): ?>
                <img src="<?= upload_url('products/' . $prod['image']) ?>"
                     alt="<?= esc($prod['name']) ?>"
                     loading="lazy">
            <?php else: ?>
                <i class="bi bi-gem no-img"></i>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <div class="prod-name"><?= esc($prod['name']) ?></div>
            <?php if (!empty($prod['sku'])): ?>
                <div class="prod-sku"><?= esc($prod['sku']) ?></div>
            <?php endif; ?>
            <!-- Pattern switcher: JS will populate -->
            <div class="pat-switcher" id="patsw-<?= $pid ?>"></div>
            <!-- Size summary: JS will populate -->
            <div class="size-summary" id="ss-<?= $pid ?>"></div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<div id="noResults" class="text-center text-muted py-5" style="display:none;">
    <i class="bi bi-search fs-2"></i><br>No products match your filter.
</div>
<?php endif; ?>

<!-- ── Offcanvas Detail Panel ── -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="stockOffcanvas">
    <div class="offcanvas-header border-bottom pb-3">
        <div class="offcanvas-product-header" id="ocHeader"></div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <!-- Location filter inside offcanvas -->
        <div class="px-3 py-2 bg-light border-bottom d-flex align-items-center gap-2">
            <label class="fw-semibold small mb-0">Location:</label>
            <select id="ocLocation" class="form-select form-select-sm" style="max-width:200px;">
                <option value="">All Locations</option>
                <?php foreach ($locations as $loc): ?>
                    <option value="<?= $loc['id'] ?>"><?= esc($loc['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Pattern tabs -->
        <div class="px-3 pt-3">
            <ul class="nav nav-tabs" id="patternTabs" role="tablist"></ul>
        </div>
        <div class="tab-content px-3 pb-3" id="patternTabContent"></div>
    </div>
    <div class="offcanvas-footer border-top px-3 py-2 d-flex gap-2">
        <a id="ocAddStock" href="#" class="btn btn-success btn-sm flex-fill">
            <i class="bi bi-plus-circle"></i> Add Stock
        </a>
        <a id="ocLabels" href="#" class="btn btn-outline-secondary btn-sm flex-fill">
            <i class="bi bi-barcode"></i> Labels
        </a>
        <a id="ocMinStock" href="#" class="btn btn-outline-warning btn-sm flex-fill">
            <i class="bi bi-bell"></i> Min Stock
        </a>
    </div>
</div>

<!-- Embed product data for JS -->
<script>
var STOCK_DATA = <?= json_encode(array_values($products), JSON_UNESCAPED_UNICODE) ?>;
var BASE_URL   = '<?= base_url() ?>';
var LOCATIONS  = <?= json_encode(array_values($locations), JSON_UNESCAPED_UNICODE) ?>;
</script>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// ── Build index map pid → data
var stockMap = {};
STOCK_DATA.forEach(function(p) { stockMap[p.product.id] = p; });

// ── Helper: compute size summary for one pattern's variations
function buildSizeSummary(variations) {
    var summary = {};
    variations.forEach(function(v) {
        var key = v.size
            ? String(v.size).toUpperCase().charAt(0)
            : v.name.toUpperCase().charAt(0);
        summary[key] = (summary[key] || 0) + v.qty;
    });
    return summary;
}

// ── Render size pills into a container el
function renderSizePills(el, summary) {
    var keys = Object.keys(summary).sort();
    if (!keys.length) { el.innerHTML = ''; return; }
    el.innerHTML = keys.map(function(k) {
        return '<span class="ss-item">'+k+':<strong>'+summary[k]+'</strong></span>';
    }).join('');
}

// ── Initialise card: pattern switcher buttons + size pills
function initCard(pdata) {
    var pid      = pdata.product.id;
    var swEl     = document.getElementById('patsw-'+pid);
    var ssEl     = document.getElementById('ss-'+pid);
    if (!swEl || !ssEl) return;

    var patterns = pdata.patterns;           // object: ptid → {pattern, variations}
    var ptIds    = Object.keys(patterns);
    if (!ptIds.length) return;

    swEl.innerHTML = '';

    ptIds.forEach(function(ptid, idx) {
        var pt    = patterns[ptid];
        var label = pt.pattern.pattern_code || pt.pattern.name; // code only on card

        var btn = document.createElement('span');
        btn.className  = 'ps-btn' + (idx === 0 ? ' active' : '');
        btn.textContent = label;
        btn.dataset.ptid = ptid;

        btn.addEventListener('click', function(e) {
            e.stopPropagation();   // don't open offcanvas
            // deactivate siblings
            swEl.querySelectorAll('.ps-btn').forEach(function(b){ b.classList.remove('active'); });
            btn.classList.add('active');
            // update size pills for this pattern
            renderSizePills(ssEl, buildSizeSummary(pt.variations));
        });

        swEl.appendChild(btn);
    });

    // Render first pattern's size pills by default
    var firstPt = patterns[ptIds[0]];
    renderSizePills(ssEl, buildSizeSummary(firstPt.variations));
}

// Initialise all cards on load
STOCK_DATA.forEach(function(p) { initCard(p); });

// ── Filter logic
var searchEl   = document.getElementById('stockSearch');
var locFilter  = document.getElementById('locationFilter');
var btnLow     = document.getElementById('btnLowOnly');
var lowOnly    = false;

btnLow.addEventListener('click', function() {
    lowOnly = !lowOnly;
    btnLow.classList.toggle('active', lowOnly);
    btnLow.classList.toggle('btn-danger', lowOnly);
    btnLow.classList.toggle('btn-outline-danger', !lowOnly);
    applyFilter();
});

searchEl.addEventListener('input', applyFilter);
locFilter.addEventListener('change', applyFilter);

function applyFilter() {
    var q   = searchEl.value.toLowerCase().trim();
    var loc = locFilter.value;
    var cols = document.querySelectorAll('.product-col');
    var shown = 0;
    cols.forEach(function(col) {
        var txt    = col.dataset.search || '';
        var locs   = (col.dataset.locs || '').split(',');
        var isLow  = col.dataset.low === '1';
        var matchQ = !q   || txt.indexOf(q) >= 0;
        var matchL = !loc || locs.indexOf(loc) >= 0;
        var matchLow = !lowOnly || isLow;
        if (matchQ && matchL && matchLow) { col.classList.remove('hidden'); shown++; }
        else                              { col.classList.add('hidden'); }
    });
    document.getElementById('statVisible').style.display = (q || loc || lowOnly) ? '' : 'none';
    document.getElementById('statVisibleNum').textContent = shown;
    document.getElementById('noResults').style.display = shown === 0 ? '' : 'none';
}

// ── Open offcanvas
var ocBS = null;
function openStock(pid) {
    var pdata = stockMap[pid];
    if (!pdata) return;
    renderOffcanvas(pdata);
    if (!ocBS) ocBS = new bootstrap.Offcanvas(document.getElementById('stockOffcanvas'));
    ocBS.show();
}

function renderOffcanvas(pdata) {
    var prod = pdata.product;
    var imgHtml = prod.image
        ? '<img src="'+BASE_URL+'uploads/products/'+prod.image+'" alt="">'
        : '<div class="oc-no-img d-flex align-items-center justify-content-center" style="width:60px;height:60px;border-radius:10px;background:#f4f6f9;font-size:28px;"><i class="bi bi-gem text-secondary"></i></div>';
    document.getElementById('ocHeader').innerHTML =
        imgHtml +
        '<div><div class="fw-bold fs-6">'+escHtml(prod.name)+'</div>'+
        (prod.sku ? '<div class="text-muted small">'+escHtml(prod.sku)+'</div>' : '')+
        '<div class="text-muted small">'+pdata.total_qty+' pcs total'+(pdata.low_count?'  <span class="badge bg-danger">'+pdata.low_count+' low</span>':'')+'</div></div>';

    document.getElementById('ocAddStock').href = BASE_URL + 'stock/entry?product_id=' + prod.id;
    document.getElementById('ocLabels').href   = BASE_URL + 'stock/label-generate?product_id=' + prod.id;
    document.getElementById('ocMinStock').href = BASE_URL + 'stock/min-stock?product_id=' + prod.id;

    document.getElementById('ocLocation').value = locFilter.value;

    renderPatternTabs(pdata);

    document.getElementById('ocLocation').onchange = function() {
        renderPatternTabs(pdata);
    };
}

function renderPatternTabs(pdata) {
    var selLoc  = document.getElementById('ocLocation').value;
    var tabsEl  = document.getElementById('patternTabs');
    var contEl  = document.getElementById('patternTabContent');
    tabsEl.innerHTML = ''; contEl.innerHTML = '';

    var patterns = pdata.patterns;
    var ptIds    = Object.keys(patterns);
    if (!ptIds.length) {
        contEl.innerHTML = '<div class="text-muted py-3">No stock records.</div>';
        return;
    }

    ptIds.forEach(function(ptid, idx) {
        var pt    = patterns[ptid];
        var code  = pt.pattern.pattern_code || pt.pattern.name;
        var tamil = pt.pattern.tamil_name || '';
        var label = tamil ? code + ' / ' + tamil : code;
        var tabId = 'ptab-' + ptid;

        // Build tab button using a real <button> so Bootstrap Tab works correctly
        var li  = document.createElement('li');
        li.className = 'nav-item';
        var btn = document.createElement('button');
        btn.className   = 'nav-link' + (idx === 0 ? ' active' : '');
        btn.type        = 'button';
        btn.dataset.bsToggle = 'tab';
        btn.dataset.bsTarget = '#' + tabId;
        btn.textContent = label;
        li.appendChild(btn);
        tabsEl.appendChild(li);

        // Build tab pane
        var pane = document.createElement('div');
        pane.className = 'tab-pane fade' + (idx === 0 ? ' show active' : '');
        pane.id = tabId;

        var vars = pt.variations.filter(function(v) {
            return !selLoc || String(v.location_id) === String(selLoc);
        });

        var rows = '';
        if (!vars.length) {
            rows = '<tr><td colspan="6" class="text-center text-muted py-2">No stock at this location</td></tr>';
        } else {
            vars.forEach(function(v) {
                var statusBadge = v.is_low
                    ? '<span class="badge bg-danger">Low</span>'
                    : (v.qty === 0 ? '<span class="badge bg-secondary">Empty</span>' : '<span class="badge bg-success">OK</span>');
                var rowCls = v.is_low ? 'var-row-low' : (v.qty === 0 ? 'var-row-empty' : '');
                rows += '<tr class="'+rowCls+'">' +
                    '<td>'+escHtml(v.name)+'</td>' +
                    '<td class="text-center">'+(v.size ? v.size+'"' : '-')+'</td>' +
                    '<td class="text-center fw-bold '+(v.is_low?'text-danger':'')+'">'+v.qty+'</td>' +
                    '<td class="text-center text-muted small">'+v.min_qty+'</td>' +
                    '<td class="text-center">'+statusBadge+'</td>' +
                    '<td class="text-muted small">'+escHtml(v.location_name)+'</td>' +
                    '</tr>';
            });
        }

        pane.innerHTML =
            '<div class="table-responsive mt-2">' +
            '<table class="table table-sm table-bordered align-middle mb-0">' +
            '<thead class="table-light"><tr>' +
            '<th>Variation</th><th class="text-center">Size</th>' +
            '<th class="text-center">Qty</th><th class="text-center">Min</th>' +
            '<th class="text-center">Status</th><th>Location</th>' +
            '</tr></thead><tbody>'+rows+'</tbody></table></div>';

        contEl.appendChild(pane);

        // Initialise Bootstrap Tab on the button after it's in the DOM
        new bootstrap.Tab(btn);
    });
}

function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
<?= $this->endSection() ?>
