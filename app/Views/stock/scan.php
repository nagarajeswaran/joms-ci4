<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.scan-loc-bar {
    position: sticky; top: 0; z-index: 99;
    background: #2c3e50; color: #fff;
    padding: 10px 16px; margin: -20px -20px 16px;
    display: flex; align-items: center; gap: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.18);
}
.scan-loc-bar select { max-width: 200px; font-weight: 600; }
.scan-loc-bar .loc-label { font-size: 13px; font-weight: 700; letter-spacing: .4px; white-space: nowrap; }
.scan-confirm-bar {
    position: sticky; bottom: 0; z-index: 99;
    background: #fff; border-top: 2px solid #198754;
    padding: 10px 16px; margin: 0 -20px -20px;
    box-shadow: 0 -2px 8px rgba(0,0,0,.08);
    display: flex; align-items: center; justify-content: space-between; gap: 10px;
}
.scan-thumb {
    width: 28px; height: 28px; border-radius: 5px; object-fit: cover;
    border: 1px solid #e0e0e0; flex-shrink: 0;
}
.scan-thumb-placeholder {
    width: 28px; height: 28px; border-radius: 5px;
    background: #f0f0f0; display: flex; align-items: center; justify-content: center;
    font-size: 14px; color: #aaa; flex-shrink: 0;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Sticky location bar -->
<div class="scan-loc-bar">
    <i class="bi bi-qr-code-scan fs-5"></i>
    <span class="loc-label">Location:</span>
    <select id="sessionLocation" class="form-select form-select-sm">
        <?php foreach ($locations as $l): ?>
            <option value="<?= $l['id'] ?>"><?= esc($l['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <span class="text-white-50 small d-none d-md-inline">All scans deducted from selected location</span>
    <a href="<?= base_url('stock') ?>" class="btn btn-outline-light btn-sm ms-auto">
        <i class="bi bi-arrow-left"></i> Back
    </a>
</div>

<!-- Toast for dup -->
<div class="position-fixed top-0 end-0 p-3" style="z-index:9999;">
    <div id="dupToast" class="toast align-items-center text-bg-warning border-0" role="alert" data-bs-delay="2500">
        <div class="d-flex">
            <div class="toast-body fw-semibold"><i class="bi bi-exclamation-triangle"></i> <span id="dupToastMsg"></span></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- Camera / Manual / HID input -->
    <div class="col-12 col-md-5">
        <div class="card h-100">
            <div class="card-header fw-semibold"><i class="bi bi-upc-scan"></i> Scan Barcode / QR</div>
            <div class="card-body">

                <!-- HID scanner status bar -->
                <div id="hidBar" class="alert alert-success py-2 mb-2 d-flex align-items-center gap-2" style="font-size:13px;">
                    <i class="bi bi-usb-symbol fs-5"></i>
                    <div>
                        <strong>HID Scanner Ready</strong><br>
                        <span class="text-muted small">Just scan any barcode — no click needed</span>
                    </div>
                    <span id="hidPulse" class="ms-auto" style="display:none;">
                        <span class="spinner-grow spinner-grow-sm text-success"></span>
                    </span>
                </div>

                <!-- Manual input -->
                <div class="input-group mb-2">
                    <span class="input-group-text"><i class="bi bi-keyboard"></i></span>
                    <input type="text" id="manualQr" class="form-control" placeholder="Type or scan barcode…" inputmode="numeric" autocomplete="off">
                    <button class="btn btn-outline-primary" id="btnManual"><i class="bi bi-search"></i></button>
                </div>

                <hr class="my-2">

                <!-- Camera -->
                <div class="d-flex gap-2 align-items-center mb-2 flex-wrap">
                    <button id="btnStartScan" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-camera"></i> Camera Scan
                    </button>
                    <button id="btnStopScan" class="btn btn-secondary btn-sm" style="display:none;">
                        <i class="bi bi-stop-circle"></i> Stop
                    </button>
                    <select id="cameraSelect" class="form-select form-select-sm" style="display:none;max-width:180px;"></select>
                </div>
                <div id="qr-reader"></div>

                <div id="scanMsg" class="mt-2"></div>
            </div>
        </div>
    </div>

    <!-- Scanned list -->
    <div class="col-12 col-md-7">
        <div class="card">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-check"></i> Scanned Items
                    <span id="scanCount" class="badge bg-primary ms-1">0</span>
                </span>
                <button class="btn btn-outline-danger btn-sm" id="btnClearAll" style="display:none;" onclick="clearAll()">
                    <i class="bi bi-trash"></i> Clear
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:36px;">#</th>
                                <th style="width:36px;"></th>
                                <th>Product</th>
                                <th>Pattern</th>
                                <th>Variation</th>
                                <th class="text-center" style="width:42px;">Qty</th>
                                <th style="width:36px;"></th>
                            </tr>
                        </thead>
                        <tbody id="scanListBody">
                            <tr><td colspan="7" class="text-center text-muted py-3">No items scanned yet</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sale confirm modal -->
<div class="modal fade" id="saleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-bag-check"></i> Confirm Sale</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Party Name <span class="text-muted small">(optional)</span></label>
                    <input type="text" id="partyName" class="form-control" placeholder="Customer / party name">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Notes <span class="text-danger">*</span></label>
                    <textarea id="saleNotes" class="form-control" rows="2" required placeholder="e.g. Customer name, order ref, purpose…"></textarea>
                    <div class="invalid-feedback">Notes are required for a sale/issue.</div>
                </div>
                <div class="alert alert-info mb-0" id="saleModalSummary"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="btnSubmitSale">
                    <i class="bi bi-check-circle"></i> Confirm &amp; Deduct
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Sticky confirm bar -->
<div class="scan-confirm-bar">
    <div class="text-muted small"><span id="totalQty">0</span> item(s) scanned</div>
    <button class="btn btn-success px-4" id="btnConfirmSale" disabled onclick="openSaleModal()">
        <i class="bi bi-check-circle"></i> Confirm Sale
    </button>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/@zxing/browser@0.1.4/umd/index.min.js"></script>
<script>
var BASE_URL   = '<?= base_url() ?>';
var CSRF_NAME  = '<?= csrf_token() ?>';
var CSRF_HASH  = '<?= csrf_hash() ?>';
var zxingControls = null, scanList = [], lastText = '', lastTime = 0, saleModal = null;

function post(url, data) {
    var p = new URLSearchParams(data);
    p.append(CSRF_NAME, CSRF_HASH);
    return fetch(BASE_URL + url, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: p}).then(r=>r.json());
}

function lookupQR(text) {
    text = text.trim(); if (!text) return;
    document.getElementById('scanMsg').innerHTML = '<div class="text-muted small"><i class="bi bi-hourglass-split"></i> Looking up…</div>';
    post('stock/get-stock-info', {qr_data: text}).then(function(d) {
        document.getElementById('scanMsg').innerHTML = '';
        if (d.error) { document.getElementById('scanMsg').innerHTML = '<div class="alert alert-danger py-2">'+escHtml(d.error)+'</div>'; return; }
        addToScanList(d);
        document.getElementById('manualQr').value = '';
    }).catch(function() {
        document.getElementById('scanMsg').innerHTML = '<div class="alert alert-danger py-2">Network error.</div>';
    });
}

function addToScanList(d) {
    var isDup = scanList.some(function(i){ return i.product_id==d.product.id && i.pattern_id==d.pattern.id && i.variation_id==d.variation.id; });
    if (isDup) {
        document.getElementById('dupToastMsg').textContent = 'Already scanned: '+d.product.name+' — added again';
        new bootstrap.Toast(document.getElementById('dupToast')).show();
    } else {
        document.getElementById('scanMsg').innerHTML = '<div class="alert alert-success py-2"><i class="bi bi-check-circle"></i> Added: '+escHtml(d.product.name)+'</div>';
        setTimeout(function(){ document.getElementById('scanMsg').innerHTML=''; }, 2000);
    }
    scanList.push({
        product_id: d.product.id, pattern_id: d.pattern.id, variation_id: d.variation.id,
        product_name: d.product.name, product_image: d.product.image||'',
        pattern_name: parseInt(d.pattern.is_default)===1?'Default':d.pattern.name,
        variation_name: d.variation.name+(d.variation.size?' '+d.variation.size+'"':''),
        qty: 1
    });
    renderScanList();
}

function renderScanList() {
    var tbody = document.getElementById('scanListBody');
    var c = scanList.length;
    document.getElementById('scanCount').textContent = c;
    document.getElementById('totalQty').textContent  = c;
    document.getElementById('btnConfirmSale').disabled = c===0;
    document.getElementById('btnClearAll').style.display = c>0?'':'none';
    if (!c) { tbody.innerHTML='<tr><td colspan="7" class="text-center text-muted py-3">No items scanned yet</td></tr>'; return; }
    var html = '';
    scanList.forEach(function(item, i) {
        var thumbHtml = item.product_image
            ? '<img src="'+BASE_URL+'uploads/products/'+escHtml(item.product_image)+'" class="scan-thumb">'
            : '<div class="scan-thumb-placeholder"><i class="bi bi-gem"></i></div>';
        html += '<tr>' +
            '<td class="text-center text-muted small">'+(i+1)+'</td>'+
            '<td>'+thumbHtml+'</td>'+
            '<td class="small fw-semibold">'+escHtml(item.product_name)+'</td>'+
            '<td class="small">'+escHtml(item.pattern_name)+'</td>'+
            '<td class="small">'+escHtml(item.variation_name)+'</td>'+
            '<td class="text-center fw-bold">1</td>'+
            '<td class="text-center"><button class="btn btn-outline-danger btn-sm py-0 px-1" onclick="removeItem('+i+')"><i class="bi bi-x"></i></button></td>'+
            '</tr>';
    });
    tbody.innerHTML = html;
}

function removeItem(i) { scanList.splice(i,1); renderScanList(); }
function clearAll() { if (confirm('Clear all scanned items?')) { scanList=[]; renderScanList(); } }

function openSaleModal() {
    if (!scanList.length) return;
    var locText = document.getElementById('sessionLocation').options[document.getElementById('sessionLocation').selectedIndex].text;
    document.getElementById('saleModalSummary').innerHTML = '<strong>'+scanList.length+' item(s)</strong> will be deducted from <strong>'+escHtml(locText)+'</strong>.';
    if (!saleModal) saleModal = new bootstrap.Modal(document.getElementById('saleModal'));
    saleModal.show();
}

document.getElementById('btnSubmitSale').addEventListener('click', function() {
    var notesEl = document.getElementById('saleNotes');
    var notes   = notesEl.value.trim();
    if (!notes) {
        notesEl.classList.add('is-invalid');
        notesEl.focus();
        return;
    }
    notesEl.classList.remove('is-invalid');

    var btn = this; btn.disabled=true; btn.innerHTML='<span class="spinner-border spinner-border-sm"></span> Processing…';
    var body = new URLSearchParams();
    body.append('location_id', document.getElementById('sessionLocation').value);
    body.append('party_name',  document.getElementById('partyName').value.trim());
    body.append('notes',       document.getElementById('saleNotes').value.trim());
    body.append(CSRF_NAME, CSRF_HASH);
    scanList.forEach(function(item,i){
        body.append('items['+i+'][product_id]',   item.product_id);
        body.append('items['+i+'][pattern_id]',   item.pattern_id);
        body.append('items['+i+'][variation_id]', item.variation_id);
        body.append('items['+i+'][qty]', item.qty);
    });
    fetch(BASE_URL+'stock/bulk-deduct', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body})
    .then(r=>r.json()).then(function(d) {
        btn.disabled=false; btn.innerHTML='<i class="bi bi-check-circle"></i> Confirm & Deduct';
        if (d.error) { alert('Error: '+d.error); return; }
        saleModal.hide(); scanList=[]; renderScanList();
        document.getElementById('partyName').value=''; document.getElementById('saleNotes').value='';
        document.getElementById('scanMsg').innerHTML=
            '<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Sale confirmed! <strong>'+d.deducted+'</strong> item(s) deducted.'+
            (d.errors&&d.errors.length?'<br><small class="text-warning">'+escHtml(d.errors.join('; '))+'</small>':'')+'</div>';
    }).catch(function(){ btn.disabled=false; btn.innerHTML='<i class="bi bi-check-circle"></i> Confirm & Deduct'; alert('Network error.'); });
});

// Camera
document.getElementById('btnStartScan').addEventListener('click', function() {
    var rd = document.getElementById('qr-reader');
    rd.innerHTML = '<video id="zxingVideo" autoplay playsinline muted style="width:100%;min-height:180px;background:#111;border-radius:6px;"></video><div id="camStatus" class="text-center text-info small mt-1 fw-semibold">Requesting camera…</div>';
    document.getElementById('btnStartScan').style.display='none';
    document.getElementById('btnStopScan').style.display='';

    // Enumerate cameras and show selector
    ZXingBrowser.BrowserMultiFormatReader.listVideoInputDevices().then(function(devices) {
        var sel = document.getElementById('cameraSelect');
        sel.innerHTML = '';
        devices.forEach(function(d) {
            var opt = document.createElement('option');
            opt.value = d.deviceId;
            opt.textContent = d.label || 'Camera ' + (sel.options.length + 1);
            sel.appendChild(opt);
        });
        if (devices.length > 1) {
            sel.style.display = '';
            sel.addEventListener('change', function() { startCamera(this.value); });
        }
        // Prefer rear camera on mobile, first device on desktop
        var preferred = devices.find(function(d){ return /back|rear|environment/i.test(d.label); });
        startCamera(preferred ? preferred.deviceId : (devices[0] ? devices[0].deviceId : null));
    }).catch(function() {
        startCamera(null);
    });
});

function startCamera(deviceId) {
    var constraints = deviceId
        ? {video: {deviceId: {exact: deviceId}}}
        : {video: true};
    navigator.mediaDevices.getUserMedia(constraints).then(function(stream){
        var video = document.getElementById('zxingVideo');
        if (!video) return;
        video.srcObject = stream; zxingControls = stream;
        video.addEventListener('loadedmetadata', function(){
            video.play().then(function(){ startDecoding(video); });
        });
        var st = document.getElementById('camStatus');
        if (st) st.textContent = 'Scanning…';
    }).catch(function(err){
        document.getElementById('scanMsg').innerHTML='<div class="alert alert-danger">Camera error: '+escHtml(err.name+': '+err.message)+'</div>';
        document.getElementById('qr-reader').innerHTML='';
        document.getElementById('btnStartScan').style.display='';
        document.getElementById('btnStopScan').style.display='none';
        document.getElementById('cameraSelect').style.display='none';
    });
}

function startDecoding(video) {
    if ('BarcodeDetector' in window) {
        var detector = new BarcodeDetector({formats:['code_128','qr_code','ean_13','code_39']});
        function tick() {
            if (!zxingControls) return;
            detector.detect(video).then(function(codes){
                if (codes.length) {
                    var now=Date.now(), text=codes[0].rawValue;
                    if (text===lastText&&(now-lastTime)<2000){requestAnimationFrame(tick);return;}
                    lastText=text; lastTime=now; lookupQR(text);
                }
            }).catch(function(){});
            requestAnimationFrame(tick);
        }
        tick();
    } else {
        var reader = new ZXingBrowser.BrowserMultiFormatReader();
        reader.decodeFromStream(video.srcObject, video, function(result,err){
            if(result){
                var now=Date.now(),text=result.getText();
                if(text===lastText&&(now-lastTime)<2000)return;
                lastText=text; lastTime=now; lookupQR(text);
            }
        });
    }
}

document.getElementById('btnStopScan').addEventListener('click', function(){
    if (zxingControls instanceof MediaStream) zxingControls.getTracks().forEach(t=>t.stop());
    else if (zxingControls&&zxingControls.stop) zxingControls.stop();
    zxingControls=null;
    document.getElementById('qr-reader').innerHTML='';
    document.getElementById('cameraSelect').style.display='none';
    this.style.display='none';
    document.getElementById('btnStartScan').style.display='';
});
document.getElementById('btnManual').addEventListener('click', function(){ lookupQR(document.getElementById('manualQr').value); });
document.getElementById('manualQr').addEventListener('keydown', function(e){ if(e.key==='Enter') lookupQR(this.value); });

// ── Global HID barcode reader listener ──────────────────────────────────────
// HID scanners act as keyboards: they fire keypresses very fast then send Enter.
// We capture ALL keystrokes at document level when no modal/input is focused.
(function() {
    var hidBuf = '', hidTimer = null;
    var TIMEOUT = 80; // ms between chars — scanners are faster than humans

    document.addEventListener('keydown', function(e) {
        // Ignore if user is typing in an input/textarea (except manualQr itself)
        var tag = document.activeElement ? document.activeElement.tagName : '';
        var isModal = document.querySelector('.modal.show');
        if (isModal) return;
        if ((tag === 'INPUT' || tag === 'TEXTAREA') && document.activeElement.id !== 'manualQr') return;

        if (e.key === 'Enter') {
            if (hidBuf.length >= 3) {
                // Flash HID pulse
                var pulse = document.getElementById('hidPulse');
                pulse.style.display = '';
                setTimeout(function(){ pulse.style.display = 'none'; }, 600);
                lookupQR(hidBuf);
                document.getElementById('manualQr').value = '';
            }
            hidBuf = '';
            if (hidTimer) { clearTimeout(hidTimer); hidTimer = null; }
            return;
        }

        // Only printable single characters
        if (e.key.length === 1) {
            hidBuf += e.key;
            // Also mirror into the input so user can see what's being scanned
            if (document.activeElement.id !== 'manualQr') {
                document.getElementById('manualQr').value = hidBuf;
            }
            if (hidTimer) clearTimeout(hidTimer);
            hidTimer = setTimeout(function() { hidBuf = ''; }, 500);
        }
    });
})();

function escHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
</script>
<?= $this->endSection() ?>
