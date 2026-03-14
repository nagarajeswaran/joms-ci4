<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5>Stock Transfer Between Locations</h5>
    <a href="<?= base_url('stock') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<form method="post" action="<?= base_url('stock/save-transfer') ?>">
    <?= csrf_field() ?>

    <!-- Locations + Note -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">From Location <span class="text-danger">*</span></label>
                    <select name="from_location_id" id="fromLocation" class="form-select" required>
                        <option value="">-- Select From --</option>
                        <?php foreach ($locations as $l): ?>
                            <option value="<?= $l['id'] ?>"><?= esc($l['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text text-muted">Select from-location first to see available stock</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">To Location <span class="text-danger">*</span></label>
                    <select name="to_location_id" class="form-select" required>
                        <option value="">-- Select To --</option>
                        <?php foreach ($locations as $l): ?>
                            <option value="<?= $l['id'] ?>"><?= esc($l['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Note</label>
                    <input type="text" name="note" class="form-control" placeholder="Transfer reason...">
                </div>
            </div>
        </div>
    </div>

    <!-- QR Scan Section -->
    <div class="card mb-3">
        <div class="card-header fw-semibold"><i class="bi bi-qr-code-scan"></i> Add Item via QR Scan</div>
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label">QR Number</label>
                    <input type="text" id="qrInput" class="form-control" placeholder="Type or scan QR number..." style="width:200px;">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-outline-primary" id="btnLookupQr"><i class="bi bi-search"></i> Lookup</button>
                    <button type="button" class="btn btn-outline-success" id="btnScanQr"><i class="bi bi-camera"></i> Scan Camera</button>
                </div>
                <div class="col-12">
                    <div id="qrScanMsg" class="text-muted small"></div>
                </div>
            </div>
            <!-- Camera feed (hidden by default) -->
            <div id="cameraBox" class="mt-2" style="display:none;">
                <video id="qrVideo" style="width:300px;height:225px;border:1px solid #ccc;border-radius:4px;" autoplay playsinline muted></video>
                <button type="button" class="btn btn-sm btn-secondary ms-2 align-top" id="btnStopScan">Stop</button>
                <canvas id="qrCanvas" style="display:none;"></canvas>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Items to Transfer</span>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddItem" disabled>
                <i class="bi bi-plus"></i> Add Item Manually
            </button>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Product</th>
                        <th>Pattern</th>
                        <th>Variation</th>
                        <th style="width:120px;">Qty <small class="text-muted">(avail)</small></th>
                        <th style="width:50px;"></th>
                    </tr>
                </thead>
                <tbody id="transferItems">
                    <tr id="emptyRow">
                        <td colspan="5" class="text-center text-muted py-3">
                            <i class="bi bi-info-circle"></i> Select a From Location first, then add items
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-end">
        <button type="submit" class="btn btn-info text-white px-4">
            <i class="bi bi-arrow-left-right"></i> Execute Transfer
        </button>
    </div>
</form>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
var BASE = '<?= base_url() ?>';
var CSRF_NAME = '<?= csrf_token() ?>';
var CSRF_HASH = '<?= csrf_hash() ?>';

function csrfParams(extra) {
    var p = {};
    p[CSRF_NAME] = CSRF_HASH;
    return Object.assign(p, extra);
}

function post(url, data) {
    return fetch(BASE + url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams(csrfParams(data))
    }).then(function(r) { return r.json(); });
}

function getFromLoc() {
    return document.getElementById('fromLocation').value;
}

// ----- Row builder -----
function buildRow(prefill) {
    // prefill = { product_id, product_name, pattern_id, pattern_name, pat_is_default, variation_id, variation_name, available_qty }
    prefill = prefill || {};

    var tr = document.createElement('tr');
    tr.innerHTML =
        '<td>' +
            '<select name="product_id[]" class="form-select form-select-sm trf-product" required>' +
                '<option value="">-- Product --</option>' +
                '<?php foreach ($products as $p): ?>' +
                '<option value="<?= $p['id'] ?>"><?= esc(addslashes(($p['sku'] ? $p['sku'].' - ' : '').$p['name'])) ?></option>' +
                '<?php endforeach; ?>' +
            '</select>' +
        '</td>' +
        '<td><select name="pattern_id[]" class="form-select form-select-sm trf-pattern" disabled required><option value="">-- Pattern --</option></select></td>' +
        '<td><select name="variation_id[]" class="form-select form-select-sm trf-variation" disabled required><option value="">-- Variation --</option></select></td>' +
        '<td>' +
            '<div class="input-group input-group-sm">' +
                '<input type="number" name="qty[]" class="form-control text-center trf-qty" value="1" min="1" required>' +
                '<span class="input-group-text trf-avail" style="font-size:11px;color:#888;min-width:50px;"></span>' +
            '</div>' +
        '</td>' +
        '<td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm py-0 btnRemove"><i class="bi bi-x"></i></button></td>';

    var selProd = tr.querySelector('.trf-product');
    var selPat  = tr.querySelector('.trf-pattern');
    var selVar  = tr.querySelector('.trf-variation');
    var qtyInp  = tr.querySelector('.trf-qty');
    var availSp = tr.querySelector('.trf-avail');

    function loadPatterns(pid, selectPatId) {
        selPat.innerHTML = '<option value="">Loading...</option>';
        selPat.disabled = true;
        selVar.innerHTML = '<option value="">-- Variation --</option>';
        selVar.disabled = true;
        availSp.textContent = '';
        post('stock/get-transfer-patterns', {product_id: pid, from_location_id: getFromLoc()})
            .then(function(data) {
                selPat.innerHTML = '<option value="">-- Pattern --</option>';
                if (!data.length) {
                    selPat.innerHTML = '<option value="">No stock at this location</option>';
                    return;
                }
                data.forEach(function(p) {
                    var opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = p.is_default == 1 ? 'Default' : p.name;
                    if (selectPatId && p.id == selectPatId) opt.selected = true;
                    selPat.appendChild(opt);
                });
                selPat.disabled = false;
                if (selectPatId) selPat.dispatchEvent(new Event('change'));
            });
    }

    function loadVariations(pid, patid, selectVarId) {
        selVar.innerHTML = '<option value="">Loading...</option>';
        selVar.disabled = true;
        availSp.textContent = '';
        post('stock/get-transfer-stock', {product_id: pid, pattern_id: patid, from_location_id: getFromLoc()})
            .then(function(data) {
                selVar.innerHTML = '<option value="">-- Variation --</option>';
                if (!(data.variations || []).length) {
                    selVar.innerHTML = '<option value="">No stock available</option>';
                    return;
                }
                data.variations.forEach(function(v) {
                    var opt = document.createElement('option');
                    opt.value = v.id;
                    opt.dataset.avail = v.available_qty;
                    var label = v.name + (v.size ? ' ' + v.size + ' in' : '') + ' (' + v.available_qty + ' avail)';
                    opt.textContent = label;
                    if (selectVarId && v.id == selectVarId) opt.selected = true;
                    selVar.appendChild(opt);
                });
                selVar.disabled = false;
                if (selectVarId) selVar.dispatchEvent(new Event('change'));
            });
    }

    selProd.addEventListener('change', function() {
        if (!this.value || !getFromLoc()) return;
        loadPatterns(this.value, null);
    });

    selPat.addEventListener('change', function() {
        if (!this.value || !selProd.value || !getFromLoc()) return;
        loadVariations(selProd.value, this.value, null);
    });

    selVar.addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        var avail = opt ? opt.dataset.avail : '';
        availSp.textContent = avail ? '/ ' + avail : '';
        if (avail) qtyInp.max = avail;
    });

    tr.querySelector('.btnRemove').addEventListener('click', function() { tr.remove(); });

    document.getElementById('transferItems').appendChild(tr);
    removeEmptyRow();

    // Prefill
    if (prefill.product_id) {
        selProd.value = prefill.product_id;
        loadPatterns(prefill.product_id, prefill.pattern_id || null);
        // variation will be selected via selectVarId chain
        if (prefill.variation_id) {
            var waitPat = setInterval(function() {
                if (!selPat.disabled && selPat.value == prefill.pattern_id) {
                    clearInterval(waitPat);
                    loadVariations(prefill.product_id, prefill.pattern_id, prefill.variation_id);
                }
            }, 100);
        }
        if (prefill.available_qty) {
            availSp.textContent = '/ ' + prefill.available_qty;
            qtyInp.max = prefill.available_qty;
        }
    }

    return tr;
}

function removeEmptyRow() {
    var e = document.getElementById('emptyRow');
    if (e) e.remove();
}

// From location change → clear items, enable/disable Add button
document.getElementById('fromLocation').addEventListener('change', function() {
    document.getElementById('transferItems').innerHTML =
        this.value ? '' : '<tr id="emptyRow"><td colspan="5" class="text-center text-muted py-3"><i class="bi bi-info-circle"></i> Select a From Location first, then add items</td></tr>';
    document.getElementById('btnAddItem').disabled = !this.value;
    document.getElementById('qrScanMsg').textContent = '';
});

document.getElementById('btnAddItem').addEventListener('click', function() {
    if (!getFromLoc()) { alert('Select From Location first'); return; }
    buildRow();
});

// ----- QR Lookup -----
document.getElementById('btnLookupQr').addEventListener('click', function() {
    lookupQr(document.getElementById('qrInput').value.trim());
});
document.getElementById('qrInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); lookupQr(this.value.trim()); }
});

function lookupQr(val) {
    var msg = document.getElementById('qrScanMsg');
    if (!val) { msg.textContent = 'Enter a QR number first.'; return; }
    if (!getFromLoc()) { msg.textContent = 'Select From Location first.'; return; }
    msg.textContent = 'Looking up...';
    post('stock/get-stock-info', {qr_data: val}).then(function(d) {
        if (d.error) { msg.innerHTML = '<span class="text-danger">' + d.error + '</span>'; return; }
        var pid   = d.product.id;
        var patid = d.pattern.id;
        var vid   = d.variation.id;
        // Check available qty
        post('stock/get-transfer-stock', {product_id: pid, pattern_id: patid, from_location_id: getFromLoc()})
            .then(function(sd) {
                var found = (sd.variations || []).find(function(v) { return v.id == vid; });
                if (!found) {
                    msg.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> No stock of <b>' +
                        d.product.name + ' ' + d.variation.name + '</b> at selected From Location.</span>';
                    return;
                }
                buildRow({
                    product_id: pid, product_name: d.product.name,
                    pattern_id: patid, pattern_name: d.pattern.name, pat_is_default: d.pattern.is_default,
                    variation_id: vid, variation_name: d.variation.name,
                    available_qty: found.available_qty
                });
                msg.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Added: ' +
                    d.product.name + ' — ' + d.variation.name + ' (' + found.available_qty + ' available)</span>';
                document.getElementById('qrInput').value = '';
            });
    });
}

// ----- Camera scan -----
var scanStream = null;
var scanInterval = null;

document.getElementById('btnScanQr').addEventListener('click', function() {
    if (!getFromLoc()) { alert('Select From Location first'); return; }
    document.getElementById('cameraBox').style.display = '';
    var video = document.getElementById('qrVideo');
    navigator.mediaDevices.getUserMedia({video: {facingMode: 'environment'}}).then(function(stream) {
        scanStream = stream;
        video.srcObject = stream;
        video.play();
        scanInterval = setInterval(function() {
            if (video.readyState !== video.HAVE_ENOUGH_DATA) return;
            var canvas = document.getElementById('qrCanvas');
            canvas.width  = video.videoWidth;
            canvas.height = video.videoHeight;
            var ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            var img = ctx.getImageData(0, 0, canvas.width, canvas.height);
            var code = jsQR(img.data, img.width, img.height);
            if (code && code.data) {
                stopScan();
                document.getElementById('qrInput').value = code.data;
                lookupQr(code.data);
            }
        }, 300);
    }).catch(function() {
        alert('Camera not available. Please type the QR number manually.');
        stopScan();
    });
});

function stopScan() {
    clearInterval(scanInterval);
    if (scanStream) { scanStream.getTracks().forEach(function(t) { t.stop(); }); scanStream = null; }
    document.getElementById('cameraBox').style.display = 'none';
}

document.getElementById('btnStopScan').addEventListener('click', stopScan);
</script>
<?= $this->endSection() ?>
