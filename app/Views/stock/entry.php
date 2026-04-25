<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
/* ── Mode toggle ── */
.mode-toggle { display:flex; gap:8px; margin-bottom:18px; }
.mode-btn {
    flex:1; text-align:center; padding:9px 0; border-radius:10px; cursor:pointer;
    font-size:13px; font-weight:700; border:1.5px solid #dee2e6;
    background:#f8f9fa; color:#6c757d; transition:all .15s;
}
.mode-btn.active { background:#0d6efd; color:#fff; border-color:#0d6efd; }

/* ── Wizard ── */
.wizard-step { display:none; }
.wizard-step.active { display:block; }
.step-indicator { display:flex; gap:0; margin-bottom:20px; }
.step-indicator .si-item {
    flex:1; text-align:center; padding:8px 4px;
    font-size:12px; font-weight:600; border-bottom:3px solid #dee2e6; color:#aaa; transition:all .2s;
}
.step-indicator .si-item.done  { border-color:#198754; color:#198754; }
.step-indicator .si-item.active{ border-color:#0d6efd; color:#0d6efd; }
.step-indicator .si-num {
    width:26px; height:26px; border-radius:50%; display:inline-flex;
    align-items:center; justify-content:center; font-size:13px; font-weight:700;
    background:#dee2e6; color:#888; margin-bottom:3px;
}
.step-indicator .si-item.done  .si-num { background:#198754; color:#fff; }
.step-indicator .si-item.active .si-num { background:#0d6efd; color:#fff; }
.product-thumb-preview {
    width:52px; height:52px; border-radius:8px; object-fit:cover;
    border:1px solid #e0e0e0; background:#f4f6f9;
    display:flex; align-items:center; justify-content:center;
}
.entry-type-toggle { display:flex; gap:8px; }
.entry-type-toggle label {
    flex:1; text-align:center; padding:6px 0; border-radius:8px;
    border:1.5px solid #dee2e6; cursor:pointer; font-size:13px; font-weight:600; transition:all .15s;
}
.entry-type-toggle input:checked + label { border-color:#0d6efd; background:#e8f0fe; color:#0d6efd; }

/* ── Scan panel ── */
#scanPanel { display:none; }
.scan-mode-toggle { display:flex; gap:6px; }
.scan-mode-btn {
    flex:1; text-align:center; padding:6px 0; border-radius:8px; cursor:pointer;
    font-size:12px; font-weight:700; border:1.5px solid #dee2e6;
    background:#f8f9fa; color:#6c757d; transition:all .15s;
}
.scan-mode-btn.active { background:#198754; color:#fff; border-color:#198754; }
.scan-result-card { border-left:4px solid #0d6efd; }
.scan-result-card .prod-header { display:flex; gap:12px; align-items:center; padding:10px 14px; background:#f8f9fa; }
.scan-result-card .prod-header img,
.scan-result-card .prod-header .no-img {
    width:44px; height:44px; border-radius:8px; object-fit:cover;
    background:#eee; display:flex; align-items:center; justify-content:center;
    font-size:20px; color:#bbb; flex-shrink:0;
}
.scanned-row { border-left:3px solid #198754; background:#f0fff4; }
.scan-entry-toggle { display:flex; gap:6px; }
.scan-entry-toggle label {
    flex:1; text-align:center; padding:5px 0; border-radius:8px;
    border:1.5px solid #dee2e6; cursor:pointer; font-size:12px; font-weight:600; transition:all .15s;
}
.scan-entry-toggle input:checked + label { border-color:#0d6efd; background:#e8f0fe; color:#0d6efd; }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5><i class="bi bi-plus-circle text-success"></i> Stock Entry</h5>
    <a href="<?= base_url('stock') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show py-2">
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ── Mode toggle ── -->
<div class="mode-toggle">
    <div class="mode-btn" id="modeManual" onclick="setMode('manual')">
        <i class="bi bi-pencil-square"></i> Manual Entry
    </div>
    <div class="mode-btn" id="modeScan" onclick="setMode('scan')">
        <i class="bi bi-barcode-scan"></i> Scan to Add
    </div>
</div>

<!-- ════════════════════════════════════════
     MANUAL WIZARD
════════════════════════════════════════ -->
<div id="manualPanel">
    <div class="step-indicator" id="stepIndicator">
        <div class="si-item active" id="si1"><div class="si-num">1</div><div>Product</div></div>
        <div class="si-item" id="si2"><div class="si-num">2</div><div>Pattern &amp; Location</div></div>
        <div class="si-item" id="si3"><div class="si-num">3</div><div>Enter Quantities</div></div>
    </div>

    <!-- Step 1 -->
    <div class="wizard-step active" id="step1">
        <div class="card">
            <div class="card-header fw-semibold"><i class="bi bi-bag"></i> Step 1 — Select Product</div>
            <div class="card-body">
                <div class="row g-3 align-items-start">
                    <div class="col-md-8">
                        <label class="form-label">Product</label>
                        <select id="sel_product" class="form-select">
                            <option value="">-- Choose a product --</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>"
                                        data-img="<?= esc($p['image'] ?? '') ?>"
                                        data-sku="<?= esc($p['sku'] ?? '') ?>"
                                        <?= $preProduct == $p['id'] ? 'selected' : '' ?>>
                                    <?= esc(($p['sku'] ? $p['sku'].' — ' : '') . $p['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-center gap-3 pt-3">
                        <div class="product-thumb-preview" id="prodThumb"><i class="bi bi-gem text-secondary fs-4"></i></div>
                        <div id="prodMeta" class="small text-muted"></div>
                    </div>
                </div>
                <div class="mt-3 text-end">
                    <button id="btnStep1Next" class="btn btn-primary" disabled>Next <i class="bi bi-arrow-right"></i></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2 -->
    <div class="wizard-step" id="step2">
        <div class="card">
            <div class="card-header fw-semibold"><i class="bi bi-diagram-3"></i> Step 2 — Pattern &amp; Location</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Pattern</label>
                        <select id="sel_pattern" class="form-select"><option value="">Loading…</option></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Location</label>
                        <select id="sel_location" class="form-select">
                            <option value="">-- Select Location --</option>
                            <?php foreach ($locations as $l): ?>
                                <option value="<?= $l['id'] ?>"><?= esc($l['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2 justify-content-between">
                    <button class="btn btn-outline-secondary btn-sm" onclick="goStep(1)"><i class="bi bi-arrow-left"></i> Back</button>
                    <button id="btnStep2Next" class="btn btn-primary" disabled>Load Grid <i class="bi bi-arrow-right"></i></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3 -->
    <div class="wizard-step" id="step3">
        <form method="post" action="<?= base_url('stock/save-entry') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" id="hid_product">
            <input type="hidden" name="pattern_id" id="hid_pattern">
            <input type="hidden" name="location_id" id="hid_location">
            <div class="card mb-3">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span id="grid_title" class="fw-semibold"></span>
                    <div class="entry-type-toggle">
                        <input type="radio" class="btn-check" name="entry_type" id="et_add" value="add" checked>
                        <label for="et_add"><i class="bi bi-plus-circle text-success"></i> Add to stock</label>
                        <input type="radio" class="btn-check" name="entry_type" id="et_set" value="set">
                        <label for="et_set"><i class="bi bi-pencil text-warning"></i> Set exact qty</label>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Variation</th>
                                    <th class="text-center">Size</th>
                                    <th class="text-center">Current Stock</th>
                                    <th class="text-center" style="width:140px;">Qty to Enter</th>
                                </tr>
                            </thead>
                            <tbody id="varGridBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="row g-2 align-items-end">
                <div class="col-md-7">
                    <label class="form-label">Note <span class="text-muted">(optional)</span></label>
                    <input type="text" name="note" class="form-control form-control-sm" placeholder="e.g. Batch #12 received">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm w-100" onclick="goStep(2)"><i class="bi bi-arrow-left"></i></button>
                </div>
                <div class="col-md-2">
                    <a href="#" id="lblLink" target="_blank" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-barcode"></i> Labels
                    </a>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-check-circle"></i> Save</button>
                </div>
            </div>
        </form>
    </div>
</div><!-- /manualPanel -->

<!-- ════════════════════════════════════════
     SCAN PANEL
════════════════════════════════════════ -->
<div id="scanPanel">

    <!-- Location + scan input row -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-sm-4 col-md-3">
                    <label class="form-label fw-semibold">Location <span class="text-danger">*</span></label>
                    <select id="scan_location" class="form-select">
                        <option value="">-- Select Location --</option>
                        <?php foreach ($locations as $l): ?>
                            <option value="<?= $l['id'] ?>"><?= esc($l['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6 col-md-5">
                    <label class="form-label fw-semibold">Barcode / Number</label>
                    <div class="alert alert-success py-1 px-2 mb-1 d-flex align-items-center gap-2" style="font-size:12px;">
                        <i class="bi bi-usb-symbol"></i>
                        <span><strong>HID Scanner Ready</strong> — just scan any barcode</span>
                        <span id="hidPulse" class="ms-auto" style="display:none;">
                            <span class="spinner-grow spinner-grow-sm text-success"></span>
                        </span>
                    </div>
                    <div class="input-group">
                        <input type="text" id="scanInput" class="form-control"
                               placeholder="Scan or type barcode…" autocomplete="off" inputmode="numeric">
                        <button class="btn btn-primary" id="btnDoScan" type="button">
                            <i class="bi bi-search"></i>
                        </button>
                        <button class="btn btn-outline-secondary" id="btnCamera" type="button" title="Camera">
                            <i class="bi bi-camera"></i>
                        </button>
                    </div>
                    <div id="scanMsg" class="mt-1"></div>
                </div>
                <div class="col-sm-2 col-md-4">
                    <label class="form-label fw-semibold d-block">Show mode</label>
                    <div class="scan-mode-toggle">
                        <div class="scan-mode-btn" id="smSingle" onclick="setScanMode('single')">
                            <i class="bi bi-dash-circle"></i> Scanned Only
                        </div>
                        <div class="scan-mode-btn" id="smAll" onclick="setScanMode('all')">
                            <i class="bi bi-list-ul"></i> All Variations
                        </div>
                    </div>
                </div>
            </div>
            <!-- Camera box -->
            <div id="cameraBox" class="mt-2" style="display:none;">
                <video id="scanVideo" autoplay playsinline muted
                       style="width:260px;height:190px;border-radius:8px;border:1px solid #ccc;background:#111;"></video>
                <button class="btn btn-sm btn-secondary ms-2 align-top" id="btnStopCam">Stop</button>
            </div>
        </div>
    </div>

    <!-- Result card (shown after scan) -->
    <div id="scanResult" style="display:none;">
        <form method="post" action="<?= base_url('stock/save-entry') ?>" id="scanForm">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id"  id="sc_product">
            <input type="hidden" name="pattern_id"  id="sc_pattern">
            <input type="hidden" name="location_id" id="sc_location">

            <div class="card scan-result-card mb-3">
                <!-- Product header -->
                <div class="prod-header" id="sc_header"></div>

                <div class="card-body pt-2 pb-2 px-3">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <div class="scan-entry-toggle">
                            <input type="radio" class="btn-check" name="entry_type" id="sc_et_add" value="add" checked>
                            <label for="sc_et_add"><i class="bi bi-plus-circle text-success"></i> Add to stock</label>
                            <input type="radio" class="btn-check" name="entry_type" id="sc_et_set" value="set">
                            <label for="sc_et_set"><i class="bi bi-pencil text-warning"></i> Set exact qty</label>
                        </div>
                        <div>
                            <label class="form-label small mb-0 me-1">Note:</label>
                            <input type="text" name="note" class="form-control form-control-sm d-inline-block"
                                   style="width:180px;" placeholder="Optional…">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Variation</th>
                                    <th class="text-center">Size</th>
                                    <th class="text-center">Current Stock</th>
                                    <th class="text-center" style="width:130px;">Qty to Add</th>
                                </tr>
                            </thead>
                            <tbody id="sc_gridBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearScanResult()">
                    <i class="bi bi-x-circle"></i> Clear
                </button>
                <button type="submit" class="btn btn-success px-4">
                    <i class="bi bi-check-circle"></i> Save Stock
                </button>
            </div>
        </form>
    </div>

</div><!-- /scanPanel -->

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/@zxing/browser@0.1.4/umd/index.min.js"></script>
<script>
var BASE_URL  = '<?= base_url() ?>';
var CSRF_NAME = '<?= csrf_token() ?>';
var CSRF_HASH = '<?= csrf_hash() ?>';
var PRE_PRODUCT = <?= (int)($preProduct ?? 0) ?>;

// ── Persist mode in localStorage
var inputMode = localStorage.getItem('stockEntryInputMode') || 'manual';
var scanMode  = localStorage.getItem('stockEntryScanMode')  || 'single';

function setMode(m) {
    inputMode = m;
    localStorage.setItem('stockEntryInputMode', m);
    document.getElementById('manualPanel').style.display = m === 'manual' ? 'block' : 'none';
    document.getElementById('scanPanel').style.display   = m === 'scan'   ? 'block' : 'none';
    document.getElementById('modeManual').classList.toggle('active', m === 'manual');
    document.getElementById('modeScan').classList.toggle('active', m === 'scan');
    if (m === 'scan') {
        setScanMode(scanMode);
        setTimeout(function(){ document.getElementById('scanInput').focus(); }, 100);
    }
}

function setScanMode(m) {
    scanMode = m;
    localStorage.setItem('stockEntryScanMode', m);
    document.getElementById('smSingle').classList.toggle('active', m === 'single');
    document.getElementById('smAll').classList.toggle('active', m === 'all');
    // Re-render grid if a scan result is already showing
    if (window._lastScanData) renderScanResult(window._lastScanData);
}

// ── Init on load
setMode(inputMode);

// ══════════════════════════════
// MANUAL WIZARD
// ══════════════════════════════
var selProduct  = document.getElementById('sel_product');
var selPattern  = document.getElementById('sel_pattern');
var selLocation = document.getElementById('sel_location');

function goStep(n) {
    document.querySelectorAll('.wizard-step').forEach(function(s,i){ s.classList.toggle('active', i+1===n); });
    ['si1','si2','si3'].forEach(function(id,i){
        var el = document.getElementById(id);
        el.classList.toggle('active', i+1===n);
        el.classList.toggle('done',   i+1<n);
    });
}

selProduct.addEventListener('change', function() {
    var opt = this.options[this.selectedIndex];
    var img = opt ? opt.dataset.img : '';
    var sku = opt ? opt.dataset.sku : '';
    var t = document.getElementById('prodThumb');
    t.innerHTML = img
        ? '<img src="'+BASE_URL+'uploads/products/'+img+'" style="width:52px;height:52px;border-radius:8px;object-fit:cover;">'
        : '<i class="bi bi-gem text-secondary fs-4"></i>';
    document.getElementById('prodMeta').textContent = sku ? ('SKU: '+sku) : '';
    document.getElementById('btnStep1Next').disabled = !this.value;
});

document.getElementById('btnStep1Next').addEventListener('click', function() {
    if (!selProduct.value) return;
    loadPatterns(selProduct.value);
    goStep(2);
});

function loadPatterns(pid) {
    selPattern.innerHTML = '<option value="">Loading…</option>';
    document.getElementById('btnStep2Next').disabled = true;
    post('stock/get-patterns', {product_id: pid}).then(function(data) {
        selPattern.innerHTML = '<option value="">-- Select Pattern --</option>';
        data.forEach(function(p) {
            var label = p.pattern_code || (parseInt(p.is_default)===1 ? 'Default' : p.name);
            selPattern.innerHTML += '<option value="'+p.id+'">'+escHtml(label)+'</option>';
        });
        checkStep2();
    });
}

function checkStep2() {
    document.getElementById('btnStep2Next').disabled = !(selPattern.value && selLocation.value);
}
selPattern.addEventListener('change', checkStep2);
selLocation.addEventListener('change', checkStep2);

document.getElementById('btnStep2Next').addEventListener('click', function() {
    if (!selPattern.value || !selLocation.value) return;
    loadManualGrid(); goStep(3);
});

function loadManualGrid() {
    document.getElementById('hid_product').value  = selProduct.value;
    document.getElementById('hid_pattern').value  = selPattern.value;
    document.getElementById('hid_location').value = selLocation.value;
    document.getElementById('lblLink').href = BASE_URL+'stock/label-generate?product_id='+selProduct.value;

    var prodName = selProduct.options[selProduct.selectedIndex].text;
    var patName  = selPattern.options[selPattern.selectedIndex].text;
    var locName  = selLocation.options[selLocation.selectedIndex].text;
    document.getElementById('grid_title').textContent = prodName + ' — ' + patName + ' @ ' + locName;

    post('stock/get-entry-grid', {
        product_id: selProduct.value,
        pattern_id: selPattern.value,
        location_id: selLocation.value,
    }).then(function(data) {
        if (data.error) { alert(data.error); return; }
        var tbody = document.getElementById('varGridBody');
        tbody.innerHTML = '';
        data.variations.forEach(function(v) {
            var isLow = v.min_qty > 0 && v.current_qty < v.min_qty;
            var badgeCls = v.current_qty > 0 ? (isLow ? 'bg-warning text-dark' : 'bg-success') : 'bg-secondary';
            tbody.innerHTML +=
                '<tr>' +
                '<td>'+escHtml(v.name)+'<input type="hidden" name="variation_id[]" value="'+v.id+'"></td>' +
                '<td class="text-center">'+(v.size ? v.size+'"' : '-')+'</td>' +
                '<td class="text-center"><span class="badge '+badgeCls+'">'+v.current_qty+'</span></td>' +
                '<td><input type="number" name="qty[]" class="form-control form-control-sm text-center" value="0" min="0"></td>' +
                '</tr>';
        });
    });
}

if (PRE_PRODUCT) {
    selProduct.value = PRE_PRODUCT;
    selProduct.dispatchEvent(new Event('change'));
}

// ══════════════════════════════
// SCAN PANEL
// ══════════════════════════════
var scanInput = document.getElementById('scanInput');

function post(url, data) {
    var p = new URLSearchParams(data);
    p.append(CSRF_NAME, CSRF_HASH);
    return fetch(BASE_URL + url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: p
    }).then(function(r){ return r.json(); });
}

function doScan() {
    var val = scanInput.value.trim();
    var loc = document.getElementById('scan_location').value;
    if (!val) { setMsg('Enter a barcode number.', 'warning'); return; }
    if (!loc) { setMsg('Select a location first.', 'warning'); return; }
    setMsg('<span class="spinner-border spinner-border-sm"></span> Looking up…', '');
    post('stock/get-stock-info', {qr_data: val}).then(function(d) {
        if (d.error) { setMsg(d.error, 'danger'); return; }
        // Attach location id to result for grid fetching
        d._locationId = loc;
        window._lastScanData = d;
        renderScanResult(d);
        scanInput.value = '';
        scanInput.focus();
        setMsg('', '');
    }).catch(function(){ setMsg('Network error.', 'danger'); });
}

function setMsg(html, type) {
    var el = document.getElementById('scanMsg');
    el.innerHTML = html ? '<div class="alert alert-'+type+' py-1 mb-0 small">'+html+'</div>' : '';
}

scanInput.addEventListener('keydown', function(e){
    if (e.key === 'Enter') { e.preventDefault(); doScan(); }
});
document.getElementById('btnDoScan').addEventListener('click', doScan);

// ── Global HID barcode reader listener ──────────────────────────────────────
(function() {
    var hidBuf = '', hidTimer = null;
    document.addEventListener('keydown', function(e) {
        var tag = document.activeElement ? document.activeElement.tagName : '';
        if ((tag === 'INPUT' || tag === 'TEXTAREA') && document.activeElement.id !== 'scanInput') return;
        if (e.key === 'Enter') {
            if (hidBuf.length >= 3) {
                var pulse = document.getElementById('hidPulse');
                pulse.style.display = '';
                setTimeout(function(){ pulse.style.display = 'none'; }, 600);
                scanInput.value = hidBuf;
                doScan();
            }
            hidBuf = '';
            if (hidTimer) { clearTimeout(hidTimer); hidTimer = null; }
            return;
        }
        if (e.key.length === 1) {
            hidBuf += e.key;
            if (document.activeElement.id !== 'scanInput') {
                scanInput.value = hidBuf;
            }
            if (hidTimer) clearTimeout(hidTimer);
            hidTimer = setTimeout(function() { hidBuf = ''; }, 500);
        }
    });
})();

function clearScanResult() {
    document.getElementById('scanResult').style.display = 'none';
    window._lastScanData = null;
    scanInput.focus();
}

// ── Render scan result grid
function renderScanResult(d) {
    var loc = document.getElementById('scan_location').value || d._locationId;
    document.getElementById('sc_product').value  = d.product.id;
    document.getElementById('sc_pattern').value  = d.pattern.id;
    document.getElementById('sc_location').value = loc;

    // Header
    var imgHtml = d.product.image
        ? '<img src="'+BASE_URL+'uploads/products/'+escHtml(d.product.image)+'" alt="">'
        : '<div class="no-img"><i class="bi bi-gem"></i></div>';
    var patLabel = d.pattern.pattern_code || d.pattern.name;
    document.getElementById('sc_header').innerHTML =
        imgHtml +
        '<div class="ms-1">'+
        '<div class="fw-bold">'+escHtml(d.product.name)+'</div>'+
        (d.product.sku ? '<div class="small text-muted">'+escHtml(d.product.sku)+'</div>' : '')+
        '<div class="small"><span class="badge bg-primary">'+escHtml(patLabel)+'</span></div>'+
        '</div>';

    var tbody = document.getElementById('sc_gridBody');

    if (scanMode === 'single') {
        // Show only the scanned variation, qty default 1
        var curQty = 0;
        if (d.stocks && d.stocks.length) {
            var locStock = d.stocks.find(function(s){ return String(s.loc_id) === String(loc); });
            curQty = locStock ? locStock.qty : 0;
        }
        var badgeCls = curQty > 0 ? 'bg-success' : 'bg-secondary';
        tbody.innerHTML =
            '<tr class="scanned-row">'+
            '<td>'+escHtml(d.variation.name)+
                '<input type="hidden" name="variation_id[]" value="'+d.variation.id+'"></td>'+
            '<td class="text-center">'+(d.variation.size ? d.variation.size+'"' : '-')+'</td>'+
            '<td class="text-center"><span class="badge '+badgeCls+'">'+curQty+'</span></td>'+
            '<td><input type="number" name="qty[]" class="form-control form-control-sm text-center" value="1" min="0"></td>'+
            '</tr>';
        document.getElementById('scanResult').style.display = '';
    } else {
        // All variations of this product-pattern
        post('stock/get-entry-grid', {
            product_id:  d.product.id,
            pattern_id:  d.pattern.id,
            location_id: loc,
        }).then(function(grid) {
            if (grid.error) { setMsg(grid.error, 'danger'); return; }
            tbody.innerHTML = '';
            grid.variations.forEach(function(v) {
                var isScanned = String(v.id) === String(d.variation.id);
                var isLow = v.min_qty > 0 && v.current_qty < v.min_qty;
                var badgeCls = v.current_qty > 0 ? (isLow ? 'bg-warning text-dark' : 'bg-success') : 'bg-secondary';
                var rowCls = isScanned ? 'scanned-row' : '';
                tbody.innerHTML +=
                    '<tr class="'+rowCls+'">'+
                    '<td>'+escHtml(v.name)+(isScanned ? ' <i class="bi bi-barcode text-success small"></i>' : '')+
                        '<input type="hidden" name="variation_id[]" value="'+v.id+'"></td>'+
                    '<td class="text-center">'+(v.size ? v.size+'"' : '-')+'</td>'+
                    '<td class="text-center"><span class="badge '+badgeCls+'">'+v.current_qty+'</span></td>'+
                    '<td><input type="number" name="qty[]" class="form-control form-control-sm text-center" value="'+(isScanned?1:0)+'" min="0"></td>'+
                    '</tr>';
            });
            document.getElementById('scanResult').style.display = '';
        });
    }
}

// ── After save redirect: restore scan mode
<?php if (session()->getFlashdata('success')): ?>
    localStorage.setItem('stockEntryInputMode', localStorage.getItem('stockEntryInputMode') || 'scan');
<?php endif; ?>

// ── Camera (BarcodeDetector + ZXing fallback)
var camStream = null, zxingControls = null;

function stopCam() {
    if (zxingControls) { try { zxingControls.stop(); } catch(e){} zxingControls = null; }
    if (camStream) { camStream.getTracks().forEach(function(t){t.stop();}); camStream=null; }
    var video = document.getElementById('scanVideo');
    video.srcObject = null;
    document.getElementById('cameraBox').style.display = 'none';
}
document.getElementById('btnStopCam').addEventListener('click', stopCam);

document.getElementById('btnCamera').addEventListener('click', function() {
    document.getElementById('cameraBox').style.display = '';
    var video = document.getElementById('scanVideo');

    if ('BarcodeDetector' in window) {
        // Native BarcodeDetector path
        navigator.mediaDevices.getUserMedia({video:{facingMode:'environment'}}).then(function(stream){
            camStream = stream; video.srcObject = stream; video.play();
            var det = new BarcodeDetector({formats:['code_128','qr_code','ean_13','code_39']});
            var lastVal = '', lastTime = 0;
            function tick(){
                if (!camStream) return;
                det.detect(video).then(function(codes){
                    if (codes.length) {
                        var now=Date.now(), val=codes[0].rawValue;
                        if (val!==lastVal||(now-lastTime)>2000){
                            lastVal=val; lastTime=now;
                            scanInput.value=val; doScan(); stopCam();
                        }
                    }
                }).catch(function(){});
                requestAnimationFrame(tick);
            }
            tick();
        }).catch(function(){ alert('Camera not available.'); stopCam(); });
    } else if (typeof ZXingBrowser !== 'undefined') {
        // ZXing fallback for Chrome Android without BarcodeDetector
        var lastVal = '', lastTime = 0;
        var reader = new ZXingBrowser.BrowserMultiFormatReader();
        reader.decodeFromVideoDevice(null, video, function(result, err) {
            if (result) {
                var now = Date.now(), val = result.getText();
                if (val !== lastVal || (now - lastTime) > 2000) {
                    lastVal = val; lastTime = now;
                    scanInput.value = val; doScan(); stopCam();
                }
            }
        }).then(function(controls) {
            zxingControls = controls;
            camStream = video.srcObject;
        }).catch(function(){ alert('Camera not available.'); stopCam(); });
    } else {
        // Last resort: plain getUserMedia, user reads and types
        navigator.mediaDevices.getUserMedia({video:{facingMode:'environment'}}).then(function(stream){
            camStream = stream; video.srcObject = stream; video.play();
        }).catch(function(){ alert('Camera not available.'); stopCam(); });
    }
});

function escHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
</script>
<?= $this->endSection() ?>
