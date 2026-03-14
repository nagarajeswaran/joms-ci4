<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<style>
.label-card { border: 1px solid #ccc; border-radius: 6px; padding: 10px; text-align: center; width: 150px; display: inline-block; margin: 6px; vertical-align: top; background: #fff; }
.label-card img { width: 120px; height: 120px; display: block; margin: 0 auto 6px; }
.label-prod  { font-size: 11px; font-weight: bold; word-break: break-word; text-transform: uppercase; }
.label-pat   { font-size: 10px; color: #888; font-style: italic; }
.label-size  { font-size: 15px; font-weight: 900; letter-spacing: 1px; color: #1a1a1a; margin: 4px 0; }
.label-num   { font-size: 11px; color: #555; font-family: monospace; border-top: 1px solid #eee; padding-top: 4px; margin-top: 4px; }
#searchResults .product-row { transition: background 0.15s; }
#searchResults .product-row:hover { background: #f0f7ff; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
    <h5><i class="bi bi-qr-code"></i> Generate QR Labels</h5>
    <a href="<?= base_url('stock') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="d-print-none">
    <!-- Label Settings -->
    <div class="card mb-3">
        <div class="card-header fw-semibold py-2">Label Settings</div>
        <div class="card-body py-2">
            <div class="row g-3 align-items-end">
                <div class="col-auto">
                    <label class="form-label mb-1">Paper Size</label>
                    <select id="paperSize" class="form-select form-select-sm" style="width:120px;">
                        <option value="A4" selected>A4</option>
                        <option value="A5">A5</option>
                        <option value="A6">A6</option>
                        <option value="Letter">Letter</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label mb-1">Columns</label>
                    <input type="number" id="labelCols" class="form-control form-control-sm" style="width:80px;" value="4" min="1" max="10">
                </div>
                <div class="col-auto">
                    <label class="form-label mb-1">Rows per page</label>
                    <input type="number" id="labelRows" class="form-control form-control-sm" style="width:80px;" value="6" min="1" max="15">
                </div>
                <div class="col-auto">
                    <small class="text-muted">Label size auto-calculates from paper ÷ columns/rows</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Search bar -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-5">
                    <input type="text" id="searchQ" class="form-control" placeholder="Search product name or SKU...">
                </div>
                <div class="col-md-4">
                    <select id="filterType" class="form-select">
                        <option value="">All Product Types</option>
                        <?php foreach ($productTypes as $pt): ?>
                            <option value="<?= $pt['id'] ?>"><?= esc($pt['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" id="btnSearch" class="btn btn-primary w-100"><i class="bi bi-search"></i> Search</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Search results — includes inline pattern dropdown -->
    <div id="searchResults" style="display:none;" class="card mb-3">
        <div class="card-header fw-semibold">Search Results</div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr><th>SKU</th><th>Product</th><th>Type</th><th style="width:190px;">Pattern</th><th style="width:70px;"></th></tr>
                </thead>
                <tbody id="resultsBody"></tbody>
            </table>
        </div>
    </div>

    <!-- Selected items — one row per product+pattern -->
    <div class="card mb-3">
        <div class="card-header fw-semibold">Selected Items</div>
        <div class="card-body p-0">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:36px;">#</th>
                        <th>Product</th>
                        <th style="width:160px;">Pattern</th>
                        <th>Variation</th>
                        <th style="width:90px;">Qty (copies)</th>
                        <th style="width:50px;"></th>
                    </tr>
                </thead>
                <tbody id="selectedItems">
                    <tr id="emptyRow"><td colspan="6" class="text-center text-muted py-3">No items selected. Search and click a product above.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mb-3">
        <button type="button" id="btnGenerate" class="btn btn-success px-4" disabled>
            <i class="bi bi-printer"></i> Generate & Print Labels
        </button>
    </div>
</div>

<div id="printControls" style="display:none;" class="d-flex justify-content-between align-items-center mb-3 d-print-none">
    <span class="text-success fw-semibold"><i class="bi bi-check-circle"></i> <span id="labelCount"></span> label(s) ready to print</span>
    <div class="d-flex gap-2">
        <button type="button" onclick="printLabels()" class="btn btn-primary">
            <i class="bi bi-printer"></i> Print Labels
        </button>
        <button type="button" id="btnClear" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x"></i> Clear
        </button>
    </div>
</div>

<div id="printSheet" style="display:none;" class="mb-4"></div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
var CSRF_NAME  = '<?= csrf_token() ?>';
var CSRF_TOKEN = '<?= csrf_hash() ?>';
var selectedItems = [];
var rowSeq = 0;

// ── SEARCH ────────────────────────────────────────────────────────────────
function doSearch() {
    var q      = document.getElementById('searchQ').value.trim();
    var typeId = document.getElementById('filterType').value;
    if (!q && !typeId) return;

    var body = new URLSearchParams({ q: q, product_type_id: typeId });
    body.append(CSRF_NAME, CSRF_TOKEN);

    fetch('<?= base_url('orders/searchProducts') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    }).then(r => r.json()).then(function(d) {
        var tbody = document.getElementById('resultsBody');
        tbody.innerHTML = '';
        if (!d.products || d.products.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No products found</td></tr>';
            document.getElementById('searchResults').style.display = '';
            return;
        }

        d.products.forEach(function(p) {
            var tr = document.createElement('tr');
            tr.className = 'product-row';
            tr.innerHTML = '<td><small class="text-muted">' + (p.sku || '') + '</small></td>'
                + '<td>' + p.name + '</td>'
                + '<td><small>' + (p.type_name || '') + '</small></td>'
                + '<td><select class="form-select form-select-sm" id="pat_' + p.id + '"><option value="">Loading...</option></select></td>'
                + '<td><button class="btn btn-sm btn-outline-primary py-0" onclick="addSelected(' + p.id + ', \'' + p.name.replace(/'/g, "\\'") + '\', \'' + (p.sku || '').replace(/'/g, "\\'") + '\')"><i class="bi bi-plus"></i> Add</button></td>';
            tbody.appendChild(tr);

            // Fetch patterns for this product and populate dropdown
            fetch('<?= base_url('stock/get-patterns') ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({ product_id: p.id, [CSRF_NAME]: CSRF_TOKEN })
            }).then(r => r.json()).then(function(patterns) {
                var sel = document.getElementById('pat_' + p.id);
                if (!sel) return;
                sel.innerHTML = '';
                if (!patterns || patterns.length === 0) {
                    sel.innerHTML = '<option value="">No patterns</option>';
                    return;
                }
                patterns.forEach(function(pt) {
                    var o = document.createElement('option');
                    o.value = pt.id;
                    o.textContent = parseInt(pt.is_default) === 1 ? 'Default' : pt.name;
                    sel.appendChild(o);
                });
            });
        });

        document.getElementById('searchResults').style.display = '';
    });
}

document.getElementById('btnSearch').addEventListener('click', doSearch);
document.getElementById('searchQ').addEventListener('keydown', function(e) { if (e.key === 'Enter') doSearch(); });

// ── ADD SELECTED ───────────────────────────────────────────────────────────
function addSelected(productId, productName, sku) {
    var sel = document.getElementById('pat_' + productId);
    var patternId   = sel ? sel.value : '';
    var patternName = sel ? (sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].text : '') : '';

    if (!patternId) { alert('Please wait for patterns to load, then try again.'); return; }

    fetch('<?= base_url('stock/get-entry-grid') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({ product_id: productId, pattern_id: patternId, location_id: 1, [CSRF_NAME]: CSRF_TOKEN })
    }).then(r => r.json()).then(function(data) {
        var vars = data.variations || [];
        if (vars.length === 0) { alert('No variations found for this product.'); return; }
        vars.forEach(function(v) {
            var rowId = 'sel_' + productId + '_' + patternId + '_' + v.id;
            if (document.getElementById(rowId)) return;
            rowSeq++;
            var item = { rowId: rowId, seq: rowSeq, productId: productId, productName: productName, sku: sku, patternId: patternId, patternName: patternName, variationId: v.id, variationName: v.name, size: v.size };
            selectedItems.push(item);
            renderSelectedRow(item);
        });
        updateEmptyRow();
        updateGenerateBtn();
        renumberRows();
    });
}

function renderSelectedRow(item) {
    var tbody = document.getElementById('selectedItems');
    var tr = document.createElement('tr');
    tr.id = item.rowId;
    tr.dataset.productId   = item.productId;
    tr.dataset.patternId   = item.patternId;
    tr.dataset.variationId = item.variationId;
    var sizeTxt = item.size ? '<strong>' + item.size + ' inch</strong>' : item.variationName;
    tr.innerHTML = '<td class="row-num text-center text-muted">' + item.seq + '</td>'
        + '<td>' + (item.sku ? '<small class="text-muted me-1">' + item.sku + '</small>' : '') + item.productName + '</td>'
        + '<td><small>' + item.patternName + '</small></td>'
        + '<td>' + sizeTxt + '</td>'
        + '<td><input type="number" class="form-control form-control-sm qty-inp text-center" value="1" min="1" max="999"></td>'
        + '<td class="text-center"><button class="btn btn-outline-danger btn-sm py-0" onclick="removeSelected(\'' + item.rowId + '\')"><i class="bi bi-x"></i></button></td>';
    tbody.appendChild(tr);
}

function removeSelected(rowId) {
    var el = document.getElementById(rowId);
    if (el) el.remove();
    selectedItems = selectedItems.filter(function(r) { return r.rowId !== rowId; });
    updateEmptyRow();
    updateGenerateBtn();
    renumberRows();
}

function renumberRows() {
    var rows = document.querySelectorAll('#selectedItems tr:not(#emptyRow)');
    rows.forEach(function(tr, i) {
        var numCell = tr.querySelector('.row-num');
        if (numCell) numCell.textContent = i + 1;
    });
}

function updateEmptyRow() {
    var emptyRow = document.getElementById('emptyRow');
    var tbody = document.getElementById('selectedItems');
    var hasRows = tbody.querySelectorAll('tr:not(#emptyRow)').length > 0;
    if (emptyRow) emptyRow.style.display = hasRows ? 'none' : '';
}

function updateGenerateBtn() {
    var count = document.getElementById('selectedItems').querySelectorAll('tr:not(#emptyRow)').length;
    document.getElementById('btnGenerate').disabled = count === 0;
}

// ── GENERATE ───────────────────────────────────────────────────────────────
document.getElementById('btnGenerate').addEventListener('click', function() {
    var rows = document.getElementById('selectedItems').querySelectorAll('tr:not(#emptyRow)');
    if (rows.length === 0) return;

    var items = [];
    rows.forEach(function(tr) {
        var productId   = tr.dataset.productId;
        var patternId   = tr.dataset.patternId;
        var variationId = tr.dataset.variationId;
        var qty = parseInt(tr.querySelector('.qty-inp').value) || 1;
        if (!productId || !patternId || !variationId) return;
        items.push({ product_id: productId, pattern_id: patternId, variation_id: variationId, qty: qty });
    });

    if (items.length === 0) { alert('No items selected.'); return; }

    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating...';

    fetch('<?= base_url('stock/generate-labels') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN},
        body: JSON.stringify({ items: items, '<?= csrf_token() ?>': CSRF_TOKEN })
    }).then(r => r.json()).then(function(d) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-printer"></i> Generate & Print Labels';

        if (!d.labels || d.labels.length === 0) { alert('No labels generated.'); return; }

        var paperDims = { 'A4': {w:200,h:287}, 'A5': {w:138,h:200}, 'A6': {w:95,h:138}, 'Letter': {w:206,h:269} };
        var paper = document.getElementById('paperSize').value;
        var cols  = Math.max(1, parseInt(document.getElementById('labelCols').value) || 4);
        var rows  = Math.max(1, parseInt(document.getElementById('labelRows').value) || 6);
        var dim   = paperDims[paper] || paperDims['A4'];
        var lw    = dim.w / cols;
        var lh    = dim.h / rows;
        var qrSz  = Math.min(lw, lh) * 0.60;
        var fsProd = Math.max(4, Math.round(lh * 0.14));
        var fsPat  = Math.max(3, Math.round(lh * 0.11));
        var fsSz   = Math.max(7, Math.round(lh * 0.30));
        var fsNum  = Math.max(7, Math.round(lh * 0.30));
        var previewW  = Math.round(lw * 3.7795);
        var previewH  = Math.round(lh * 3.7795);
        var previewQR = Math.round(qrSz * 3.7795);

        var sheet = document.getElementById('printSheet');
        sheet.innerHTML = '';
        sheet.style.cssText = 'display:grid;grid-template-columns:repeat(' + cols + ',' + previewW + 'px);gap:2px;';

        d.labels.forEach(function(lbl) {
            var times = parseInt(lbl.qty) || 1;
            for (var c = 0; c < times; c++) {
                var card = document.createElement('div');
                card.className = 'label-card';
                card.style.cssText = 'width:' + previewW + 'px;height:' + previewH + 'px;padding:2px;box-sizing:border-box;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;';
                                var patLine = (!lbl.pat_is_default && lbl.pattern_label) ? ' \u2022 ' + lbl.pattern_label : '';
                var sizeTxt = lbl.size ? lbl.size + ' INCH' : lbl.variation_name.toUpperCase();
                var topH = Math.round(previewH * 0.22);
                card.innerHTML =
                    '<div style="overflow:hidden;text-align:center;line-height:1.2;padding-bottom:1px;max-width:100%;">'
                    + '<span style="font-size:' + (fsProd+2) + 'px;font-weight:bold;text-transform:uppercase;">' + lbl.product_label + (lbl.sku ? ' (' + lbl.sku + ')' : '') + '</span>'
                    + (patLine ? '<br><span style="font-size:' + fsPat + 'px;color:#555;font-style:italic;">' + patLine + '</span>' : '')
                    + '</div>'
                    + '<div style="display:flex;align-items:center;justify-content:center;min-height:0;">'
                    + '<div style="flex:0 0 auto;padding:0 4px;text-align:center;font-size:' + fsNum + 'px;font-weight:900;font-family:monospace;line-height:1;">#' + lbl.qr_number + '</div>'
                    + '<div style="flex:0 0 auto;display:flex;align-items:center;justify-content:center;"><img src="data:image/png;base64,' + lbl.qr_image_base64 + '" style="width:' + previewQR + 'px;height:' + previewQR + 'px;display:block;"></div>'
                    + '<div style="flex:0 0 auto;padding:0 4px;text-align:center;font-size:' + fsSz + 'px;font-weight:900;line-height:1;word-break:break-word;">' + sizeTxt + '</div>'
                    + '</div>';

                card.dataset.prod = lbl.product_label || '';
                card.dataset.sku  = lbl.sku || '';
                card.dataset.pat  = (!lbl.pat_is_default && lbl.pattern_label) ? lbl.pattern_label : '';
                card.dataset.num  = lbl.qr_number || '';
                card.dataset.sz   = sizeTxt;
                sheet.appendChild(card);
            }
        });

        var totalCards = d.labels.reduce(function(s, l) { return s + (parseInt(l.qty) || 1); }, 0);
        document.getElementById('labelCount').textContent = totalCards;
        document.getElementById('printControls').style.display = '';
        sheet.scrollIntoView({ behavior: 'smooth' });
    }).catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-printer"></i> Generate & Print Labels';
        alert('Error generating labels. Please try again.');
    });
});

document.getElementById('btnClear').addEventListener('click', function() {
    var sheet = document.getElementById('printSheet');
    sheet.style.display = 'none';
    sheet.innerHTML = '';
    document.getElementById('printControls').style.display = 'none';
});

function printLabels() {
    var sheet = document.getElementById('printSheet');
    if (!sheet || !sheet.innerHTML.trim()) { alert('No labels to print.'); return; }

    var paperDims     = { 'A4': {w:210,h:297}, 'A5': {w:148,h:210}, 'A6': {w:105,h:148}, 'Letter': {w:216,h:279} };
    var paperPhysical = { 'A4': {w:'210mm',h:'297mm'}, 'A5': {w:'148mm',h:'210mm'}, 'A6': {w:'105mm',h:'148mm'}, 'Letter': {w:'216mm',h:'279mm'} };
    var paper = document.getElementById('paperSize').value;
    var cols  = Math.max(1, parseInt(document.getElementById('labelCols').value) || 4);
    var rows  = Math.max(1, parseInt(document.getElementById('labelRows').value) || 6);
    var dim   = paperDims[paper] || paperDims['A4'];
    var phys  = paperPhysical[paper] || paperPhysical['A4'];
    var lw    = dim.w / cols;
    var lh    = dim.h / rows;
    var qrSz  = Math.min(lw, lh) * 0.60;
    var fsProd = Math.max(3, Math.round(lh * 0.14));
    var fsPat  = Math.max(2, Math.round(lh * 0.11));
    var fsSz   = Math.max(5, Math.round(lh * 0.30));
    var fsNum  = Math.max(5, Math.round(lh * 0.30));

    var css = '@page{size:' + paper + ' portrait;margin:0}'
        + 'body{margin:0;padding:0}'
        + '.label-page{display:grid;grid-template-columns:repeat(' + cols + ',calc(' + phys.w + '/' + cols + '));grid-template-rows:repeat(' + rows + ',calc(' + phys.h + '/' + rows + '));width:' + phys.w + ';height:' + phys.h + ';overflow:hidden;gap:0;border:0.5px solid #555;page-break-after:always;break-after:page}'
        + '.label-page:last-child{page-break-after:avoid;break-after:avoid}'
        + '.label-card{box-sizing:border-box;overflow:hidden;display:flex;flex-direction:column;padding:0.3mm;border-right:0.4px solid #aaa;border-bottom:0.4px solid #aaa}'
        + '.lc-top{overflow:hidden;text-align:center;line-height:1.2;font-size:' + (fsProd+1) + 'pt;padding-bottom:0.2mm;max-width:100%}'
        + '.lc-top b{font-weight:bold;text-transform:uppercase}'
        + '.lc-top i{color:#555;font-style:italic;font-size:' + fsPat + 'pt}'
        + '.lc-mid{display:flex;flex:1;align-items:center;justify-content:center;min-height:0}'
        + '.lc-num{flex:0 0 auto;padding:0 0.8mm;text-align:center;font-size:' + fsNum + 'pt;font-weight:900;font-family:monospace;line-height:1}'
        + '.lc-qr{flex:0 0 auto;display:flex;align-items:center;justify-content:center}'
        + '.lc-qr img{width:' + qrSz.toFixed(2) + 'mm;height:' + qrSz.toFixed(2) + 'mm;display:block}'
        + '.lc-sz{flex:0 0 auto;padding:0 0.8mm;text-align:center;font-size:' + fsSz + 'pt;font-weight:900;line-height:1;word-break:break-word;text-transform:uppercase}';

    // Group cards into pages of cols*rows
    var cards = Array.from(sheet.querySelectorAll('.label-card'));
    var perPage = cols * rows;
    var pages = [];
    for (var i = 0; i < cards.length; i += perPage) {
        var pageCards = cards.slice(i, i + perPage).map(function(card) {
            var dd = card.dataset;
            var patLine = (dd.pat && dd.pat !== '') ? ' &bull; <i>' + dd.pat + '</i>' : '';
            var img = card.querySelector('img');
            var imgSrc = img ? img.src : '';
            return '<div class="label-card">'
                + '<div class="lc-top"><b>' + (dd.prod||'') + (dd.sku ? ' ('+dd.sku+')' : '') + '</b>' + patLine + '</div>'
                + '<div class="lc-mid">'
                + '<div class="lc-num">#' + (dd.num||'') + '</div>'
                + '<div class="lc-qr"><img src="' + imgSrc + '"></div>'
                + '<div class="lc-sz">' + (dd.sz||'') + '</div>'
                + '</div></div>';
        }).join('');
        pages.push('<div class="label-page">' + pageCards + '</div>');
    }

    var w = window.open('', '_blank', 'width=900,height=700');
    w.document.write('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>QR Labels</title><style>' + css + '</style></head><body>' + pages.join('') + '</body></html>');
    w.document.close();
    w.focus();
    setTimeout(function() { w.print(); }, 700);
}
</script>
<?= $this->endSection() ?>