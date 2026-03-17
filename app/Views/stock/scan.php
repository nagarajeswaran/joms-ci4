<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5><i class="bi bi-qr-code-scan"></i> QR Scan — Sale / Deduct Stock</h5>
    <a href="<?= base_url('stock') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="position-fixed top-0 end-0 p-3" style="z-index:9999;">
    <div id="dupToast" class="toast align-items-center text-bg-warning border-0" role="alert" aria-live="assertive" data-bs-delay="2500">
        <div class="d-flex">
            <div class="toast-body fw-semibold"><i class="bi bi-exclamation-triangle"></i> <span id="dupToastMsg">Already scanned — added again</span></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <div class="row align-items-center g-2">
            <div class="col-auto fw-semibold">Location:</div>
            <div class="col-auto">
                <select id="sessionLocation" class="form-select form-select-sm">
                    <?php foreach ($locations as $l): ?>
                        <option value="<?= $l['id'] ?>"><?= esc($l['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col text-muted small">All scans in this session will be deducted from this location.</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header fw-semibold"><i class="bi bi-camera"></i> Scan QR Code</div>
            <div class="card-body">
                <div id="qr-reader" style="width:100%;"></div>
                <div class="mt-2 text-center">
                    <button id="btnStartScan" class="btn btn-primary"><i class="bi bi-camera"></i> Start Camera</button>
                    <button id="btnStopScan" class="btn btn-secondary" style="display:none;"><i class="bi bi-stop-circle"></i> Stop Camera</button>
                </div>
                <hr>
                <div class="text-center text-muted small mb-2">Or enter QR number manually:</div>
                <div class="input-group">
                    <input type="text" id="manualQr" class="form-control" placeholder="QR number (e.g. 10001)" inputmode="numeric">
                    <button class="btn btn-outline-primary" id="btnManual"><i class="bi bi-search"></i> Add</button>
                </div>
                <div id="scanMsg" class="mt-2"></div>
            </div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card">
            <div class="card-header fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-check"></i> Scanned Items <span id="scanCount" class="badge bg-primary ms-1">0</span></span>
                <button class="btn btn-outline-danger btn-sm" id="btnClearAll" style="display:none;" onclick="clearAll()"><i class="bi bi-trash"></i> Clear All</button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th style="width:32px;">#</th>
                                <th>Product</th>
                                <th>Pattern</th>
                                <th>Variation</th>
                                <th class="text-center" style="width:42px;">Qty</th>
                                <th style="width:40px;"></th>
                            </tr>
                        </thead>
                        <tbody id="scanListBody">
                            <tr><td colspan="6" class="text-center text-muted py-3">No items scanned yet</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer text-end">
                <button class="btn btn-success" id="btnConfirmSale" disabled onclick="openSaleModal()">
                    <i class="bi bi-check-circle"></i> Confirm Sale (<span id="totalQty">0</span> items)
                </button>
            </div>
        </div>
    </div>
</div>

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
                    <label class="form-label fw-semibold">Notes <span class="text-muted small">(optional)</span></label>
                    <textarea id="saleNotes" class="form-control" rows="2" placeholder="Any additional notes..."></textarea>
                </div>
                <div class="alert alert-info mb-0" id="saleModalSummary"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="btnSubmitSale"><i class="bi bi-check-circle"></i> Confirm &amp; Deduct</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
var html5QrCode = null;
var scanList = [];
var lastScannedText = '';
var lastScannedTime = 0;
var saleModal = null;

function lookupQR(text) {
    text = text.trim();
    if (!text) return;
    document.getElementById('scanMsg').innerHTML = '<div class="text-muted small"><i class="bi bi-hourglass-split"></i> Looking up...</div>';
    fetch('<?= base_url('stock/get-stock-info') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({qr_data: text, '<?= csrf_token() ?>': '<?= csrf_hash() ?>'})
    }).then(r => r.json()).then(function(d) {
        document.getElementById('scanMsg').innerHTML = '';
        if (d.error) {
            document.getElementById('scanMsg').innerHTML = '<div class="alert alert-danger py-2">'+escHtml(d.error)+'</div>';
            return;
        }
        addToScanList(d);
        document.getElementById('manualQr').value = '';
    }).catch(function() {
        document.getElementById('scanMsg').innerHTML = '<div class="alert alert-danger py-2">Network error. Try again.</div>';
    });
}

function addToScanList(d) {
    var isDup = scanList.some(function(item) {
        return item.product_id == d.product.id && item.pattern_id == d.pattern.id && item.variation_id == d.variation.id;
    });
    if (isDup) {
        document.getElementById('dupToastMsg').textContent = 'Already scanned: ' + d.product.name + ' \u2014 added again';
        new bootstrap.Toast(document.getElementById('dupToast')).show();
    } else {
        document.getElementById('scanMsg').innerHTML = '<div class="alert alert-success py-2"><i class="bi bi-check-circle"></i> Added: ' + escHtml(d.product.name) + '</div>';
        setTimeout(function(){ document.getElementById('scanMsg').innerHTML = ''; }, 2000);
    }
    scanList.push({
        product_id:     d.product.id,
        pattern_id:     d.pattern.id,
        variation_id:   d.variation.id,
        product_name:   d.product.name,
        pattern_name:   d.pattern.is_default ? 'Default' : d.pattern.name,
        variation_name: d.variation.name + (d.variation.size ? ' ' + d.variation.size + '"' : ''),
        qty: 1
    });
    renderScanList();
}

function renderScanList() {
    var tbody = document.getElementById('scanListBody');
    var count = scanList.length;
    document.getElementById('scanCount').textContent = count;
    document.getElementById('totalQty').textContent = count;
    document.getElementById('btnConfirmSale').disabled = count === 0;
    document.getElementById('btnClearAll').style.display = count > 0 ? '' : 'none';
    if (count === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No items scanned yet</td></tr>';
        return;
    }
    var html = '';
    scanList.forEach(function(item, i) {
        html += '<tr>' +
            '<td class="text-center">' + (i+1) + '</td>' +
            '<td>' + escHtml(item.product_name) + '</td>' +
            '<td>' + escHtml(item.pattern_name) + '</td>' +
            '<td>' + escHtml(item.variation_name) + '</td>' +
            '<td class="text-center fw-bold">1</td>' +
            '<td class="text-center"><button class="btn btn-outline-danger btn-sm py-0 px-1" onclick="removeItem(' + i + ')"><i class="bi bi-x"></i></button></td>' +
            '</tr>';
    });
    tbody.innerHTML = html;
}

function removeItem(i) { scanList.splice(i, 1); renderScanList(); }

function clearAll() {
    if (confirm('Clear all scanned items?')) { scanList = []; renderScanList(); }
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function openSaleModal() {
    if (scanList.length === 0) return;
    var locText = document.getElementById('sessionLocation').options[document.getElementById('sessionLocation').selectedIndex].text;
    document.getElementById('saleModalSummary').innerHTML =
        '<strong>' + scanList.length + ' item(s)</strong> will be deducted from <strong>' + escHtml(locText) + '</strong>.';
    if (!saleModal) saleModal = new bootstrap.Modal(document.getElementById('saleModal'));
    saleModal.show();
}

document.getElementById('btnSubmitSale').addEventListener('click', function() {
    var btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
    var body = new URLSearchParams();
    body.append('location_id', document.getElementById('sessionLocation').value);
    body.append('party_name',  document.getElementById('partyName').value.trim());
    body.append('notes',       document.getElementById('saleNotes').value.trim());
    body.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
    scanList.forEach(function(item, i) {
        body.append('items['+i+'][product_id]',   item.product_id);
        body.append('items['+i+'][pattern_id]',   item.pattern_id);
        body.append('items['+i+'][variation_id]', item.variation_id);
        body.append('items['+i+'][qty]',           item.qty);
    });
    fetch('<?= base_url('stock/bulk-deduct') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    }).then(r => r.json()).then(function(d) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Confirm & Deduct';
        if (d.error) { alert('Error: ' + d.error); return; }
        saleModal.hide();
        scanList = [];
        renderScanList();
        document.getElementById('partyName').value = '';
        document.getElementById('saleNotes').value = '';
        document.getElementById('scanMsg').innerHTML =
            '<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Sale confirmed! <strong>' + d.deducted + '</strong> item(s) deducted.' +
            (d.errors && d.errors.length ? '<br><small class="text-warning">Warnings: ' + escHtml(d.errors.join('; ')) + '</small>' : '') + '</div>';
    }).catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle"></i> Confirm & Deduct';
        alert('Network error. Please try again.');
    });
});

document.getElementById('btnStartScan').addEventListener('click', function() {
    html5QrCode = new Html5Qrcode("qr-reader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 220, height: 220 } },
        function(decodedText) {
            var now = Date.now();
            if (decodedText === lastScannedText && (now - lastScannedTime) < 2000) return;
            lastScannedText = decodedText;
            lastScannedTime = now;
            lookupQR(decodedText);
        }
    );
    this.style.display = 'none';
    document.getElementById('btnStopScan').style.display = '';
});

document.getElementById('btnStopScan').addEventListener('click', function() {
    if (html5QrCode) { html5QrCode.stop(); html5QrCode = null; }
    this.style.display = 'none';
    document.getElementById('btnStartScan').style.display = '';
});

document.getElementById('btnManual').addEventListener('click', function() {
    lookupQR(document.getElementById('manualQr').value);
});
document.getElementById('manualQr').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') lookupQR(this.value);
});
</script>
<?= $this->endSection() ?>
