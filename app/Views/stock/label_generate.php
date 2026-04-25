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
                    <tr><th style="width:40px;"></th><th>SKU</th><th>Product</th><th>Type</th><th style="width:190px;">Pattern</th><th style="width:70px;"></th></tr>
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
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
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
            var thumbHtml = p.image
                ? '<img src="<?= upload_url('products/') ?>'+p.image+'" style="width:32px;height:32px;border-radius:5px;object-fit:cover;">'
                : '<div style="width:32px;height:32px;border-radius:5px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;font-size:14px;"><i class=\'bi bi-gem text-secondary\'></i></div>';
            tr.innerHTML = '<td>'+thumbHtml+'</td>'
                + '<td><small class="text-muted">' + (p.sku || '') + '</small></td>'
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
        var fsName = Math.max(8, Math.round(lh * 0.26));
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
                card.style.cssText = 'width:' + previewW + 'px;height:' + previewH + 'px;box-sizing:border-box;display:flex;align-items:center;padding:2px;overflow:hidden;';
                var patLine = lbl.pattern_code ? lbl.pattern_code : '';
                var sizeTxt = lbl.size ? lbl.size : lbl.variation_name.toUpperCase();
                var sideW = Math.round(previewW * 0.18);
                card.innerHTML =
                    '<div style="flex:0 0 ' + sideW + 'px;display:flex;align-items:center;justify-content:center;font-size:' + fsNum + 'px;font-weight:900;font-family:monospace;text-align:center;padding-left:3px;writing-mode:vertical-rl;transform:rotate(180deg);">#' + lbl.qr_number + '</div>'
                    + '<div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:0;overflow:hidden;">'
                    + '<div style="font-size:' + fsName + 'px;font-weight:bold;text-transform:uppercase;text-align:center;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:100%;">' + lbl.pattern_label + '</div>'
                    + '<div style="display:flex;align-items:center;justify-content:center;"><svg class="bc" data-val="' + lbl.qr_number + '" style="display:block;"></svg></div>'
                    + (patLine ? '<div style="font-size:' + fsName + 'px;font-weight:bold;text-align:center;margin-top:3px;">' + patLine + '</div>' : '')
                    + '</div>'
                    + '<div style="flex:0 0 ' + sideW + 'px;display:flex;align-items:center;justify-content:center;padding-right:4px;">'
                    + '<div style="width:' + (sideW-4) + 'px;height:' + (sideW-4) + 'px;border-radius:50%;border:2px solid #1a1a1a;display:flex;align-items:center;justify-content:center;font-size:' + fsSz + 'px;font-weight:900;line-height:1;text-align:center;word-break:break-word;text-transform:uppercase;">' + sizeTxt + '</div>'
                    + '</div>';

                card.dataset.prod = lbl.pattern_label || '';
                card.dataset.sku  = lbl.sku || '';
                card.dataset.pat  = lbl.pattern_code || '';
                card.dataset.num  = lbl.qr_number || '';
                card.dataset.sz   = sizeTxt;
                sheet.appendChild(card);
            }
        });

        var previewSideW = Math.round(previewW * 0.18);
        var previewBcW = Math.round((previewW - previewSideW * 2) * 0.90);
        var previewBcH = Math.max(14, Math.round(previewH * 0.48));
        sheet.querySelectorAll('svg.bc').forEach(function(svg) { JsBarcode(svg, svg.getAttribute('data-val'), { format: 'CODE128', width: 1.5, height: previewBcH, displayValue: false, margin: 0 }); svg.style.width = previewBcW + 'px'; svg.style.height = 'auto'; });
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
    var qrSz  = Math.min(lw, lh) * 0.60;  // kept for compat
    var fsProd = Math.max(3, Math.round(lh * 0.14));
    var fsPat  = Math.max(2, Math.round(lh * 0.11));
    var fsSz   = Math.max(5, Math.round(lh * 0.30));
    var fsNum  = Math.max(5, Math.round(lh * 0.30));
    var fsName = Math.max(6, Math.round(lh * 0.26));
    // barcode: fill 90% of center column (lw minus two 18% sides), height ~48% of label height
    var sideWmm  = lw * 0.18;
    var bcWmm    = (lw - sideWmm * 2) * 0.90;
    var bcHpx    = Math.max(14, Math.round(lh * 3.78 * 0.48));
    var css = '@page{size:' + paper + ' portrait;margin:0}'
        + 'body{margin:0;padding:0}'
        + '.label-page{display:grid;grid-template-columns:repeat(' + cols + ',calc(' + phys.w + '/' + cols + '));grid-template-rows:repeat(' + rows + ',calc(' + phys.h + '/' + rows + '));width:' + phys.w + ';height:' + phys.h + ';overflow:hidden;gap:0;page-break-after:always;break-after:page}'
        + '.label-page:last-child{page-break-after:avoid;break-after:avoid}'
        + '.label-card{box-sizing:border-box;overflow:hidden;display:flex;align-items:center;padding:0.3mm;border-right:0.4px solid #aaa;border-bottom:0.4px solid #aaa}'
        + '.lc-side{flex:0 0 auto;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:0 1.5mm 0 1.5mm}'
        + '.lc-num{font-size:' + fsNum + 'pt;font-weight:900;font-family:monospace;line-height:1;text-align:center;writing-mode:vertical-rl;transform:rotate(180deg)}'
        + '.lc-sz{font-size:' + fsSz + 'pt;font-weight:900;line-height:1;text-align:center;word-break:break-word;text-transform:uppercase;width:calc(' + lw*0.18 + 'mm - 3mm);height:calc(' + lw*0.18 + 'mm - 3mm);border-radius:50%;border:1.5pt solid #1a1a1a;display:flex;align-items:center;justify-content:center;}'
        + '.lc-center{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:0;overflow:hidden}'
        + '.lc-name{font-size:' + fsName + 'pt;font-weight:bold;text-align:center;text-transform:uppercase;line-height:1.1;overflow:hidden;max-width:100%}'
        + '.lc-pat{font-size:' + fsName + 'pt;font-weight:bold;text-align:center;line-height:1;overflow:hidden;max-width:100%;margin-top:0.8mm}'
        + '.lc-qr{width:100%}';
    css += '.lc-qr svg{display:block;width:' + bcWmm.toFixed(1) + 'mm;height:auto;max-width:100%}';

    var cards = Array.from(sheet.querySelectorAll('.label-card'));
    var perPage = cols * rows;
    var pages = [];
    for (var i = 0; i < cards.length; i += perPage) {
        var pageCards = cards.slice(i, i + perPage).map(function(card) {
            var dd = card.dataset;
            return '<div class="label-card">'
                + '<div class="lc-side"><div class="lc-num">#' + (dd.num||'') + '</div></div>'
                + '<div class="lc-center">'
                + '<div class="lc-name">' + (dd.prod||'') + '</div>'
                + '<div class="lc-qr"><svg class="bc" data-val="' + (dd.num||'') + '"></svg></div>'
                + (dd.pat ? '<div class="lc-pat">' + dd.pat + '</div>' : '')
                + '</div>'
                + '<div class="lc-side"><div class="lc-sz">' + (dd.sz||'') + '</div></div>'
                + '</div>';
        }).join('');
        pages.push('<div class="label-page">' + pageCards + '</div>');
    }
    var _bcHpx = bcHpx; var _bcWmm = bcWmm;
    var w = window.open('', '_blank', 'width=900,height=700');
    w.document.write('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Labels</title>'
        + '<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"><\/script>'
        + '<style>' + css + '</style></head><body>' + pages.join('') + '</body></html>');
    w.document.close();
    w.focus();
    setTimeout(function() {
        w.document.querySelectorAll('svg.bc').forEach(function(svg) {
            w.JsBarcode(svg, svg.getAttribute('data-val'), { format: 'CODE128', width: 1.5, height: _bcHpx, displayValue: false, margin: 0 });
        });
        setTimeout(function() { w.print(); }, 300);
    }, 800);
}
</script>
<?= $this->endSection() ?>
