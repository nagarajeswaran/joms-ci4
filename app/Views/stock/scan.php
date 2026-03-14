<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5>QR Scan - Deduct Stock (Sale)</h5>
    <a href="<?= base_url('stock') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header fw-semibold"><i class="bi bi-qr-code-scan"></i> Scan QR Code</div>
            <div class="card-body">
                <div id="qr-reader" style="width:100%;"></div>
                <div class="mt-2 text-center">
                    <button id="btnStartScan" class="btn btn-primary"><i class="bi bi-camera"></i> Start Camera Scan</button>
                    <button id="btnStopScan" class="btn btn-secondary" style="display:none;"><i class="bi bi-stop-circle"></i> Stop</button>
                </div>
                <hr>
                <div class="text-center text-muted small mb-2">Or enter QR code manually:</div>
                <div class="input-group">
                    <input type="text" id="manualQr" class="form-control" placeholder="Enter QR number (e.g. 10001)" inputmode="numeric">
                    <button class="btn btn-outline-primary" id="btnManual">Look up</button>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div id="stockInfo" style="display:none;">
            <div class="card mb-3 border-success">
                <div class="card-header bg-success text-white fw-semibold">Item Found</div>
                <div class="card-body">
                    <p class="mb-1"><strong>Product:</strong> <span id="inf_product"></span></p>
                    <p class="mb-1"><strong>Pattern:</strong> <span id="inf_pattern"></span></p>
                    <p class="mb-3"><strong>Variation:</strong> <span id="inf_variation"></span></p>
                    <table class="table table-sm table-bordered mb-3" id="stockTable">
                        <thead class="table-light"><tr><th>Location</th><th class="text-center">Available Qty</th></tr></thead>
                        <tbody id="stockTableBody"></tbody>
                    </table>
                    <form id="deductForm">
                        <input type="hidden" id="d_product_id" name="product_id">
                        <input type="hidden" id="d_pattern_id" name="pattern_id">
                        <input type="hidden" id="d_variation_id" name="variation_id">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">Location</label>
                                <select name="location_id" class="form-select form-select-sm" id="deductLoc">
                                    <?php foreach ($locations as $l): ?>
                                        <option value="<?= $l['id'] ?>"><?= esc($l['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-3">
                                <label class="form-label">Qty</label>
                                <input type="number" name="qty" class="form-control form-control-sm text-center" value="1" min="1" id="deductQty">
                            </div>
                            <div class="col-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-danger w-100 btn-sm"><i class="bi bi-dash-circle"></i> Deduct</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div id="scanMsg"></div>
    </div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
var html5QrCode = null;

function lookupQR(text) {
    fetch('<?= base_url('stock/get-stock-info') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({qr_data: text, '<?= csrf_token() ?>': '<?= csrf_hash() ?>'})
    }).then(r => r.json()).then(function(d) {
        if (d.error) {
            document.getElementById('scanMsg').innerHTML = '<div class="alert alert-danger">'+d.error+'</div>';
            document.getElementById('stockInfo').style.display = 'none';
            return;
        }
        document.getElementById('inf_product').textContent = d.product.name + (d.product.sku ? ' ('+d.product.sku+')' : '');
        document.getElementById('inf_pattern').textContent  = d.pattern.is_default ? 'Default' : d.pattern.name;
        document.getElementById('inf_variation').textContent = d.variation.name + (d.variation.size ? ' '+d.variation.size+' inch' : '');
        document.getElementById('d_product_id').value   = d.product.id;
        document.getElementById('d_pattern_id').value   = d.pattern.id;
        document.getElementById('d_variation_id').value = d.variation.id;
        var tbody = document.getElementById('stockTableBody');
        tbody.innerHTML = '';
        if (d.stocks.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">No stock records</td></tr>';
        } else {
            d.stocks.forEach(function(s) {
                tbody.innerHTML += '<tr><td>'+s.loc_name+'</td><td class="text-center fw-bold '+(s.qty > 0 ? 'text-success' : 'text-danger')+'">'+s.qty+'</td></tr>';
            });
            // pre-select location with highest stock
            var best = d.stocks.reduce((a,b) => a.qty > b.qty ? a : b);
            document.getElementById('deductLoc').value = best.loc_id;
        }
        document.getElementById('stockInfo').style.display = '';
        document.getElementById('scanMsg').innerHTML = '';
    });
}

document.getElementById('btnStartScan').addEventListener('click', function() {
    html5QrCode = new Html5Qrcode("qr-reader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 250, height: 250 } },
        function(decodedText) {
            html5QrCode.stop();
            document.getElementById('btnStartScan').style.display = '';
            document.getElementById('btnStopScan').style.display = 'none';
            lookupQR(decodedText);
        }
    );
    this.style.display = 'none';
    document.getElementById('btnStopScan').style.display = '';
});

document.getElementById('btnStopScan').addEventListener('click', function() {
    if (html5QrCode) html5QrCode.stop();
    this.style.display = 'none';
    document.getElementById('btnStartScan').style.display = '';
});

document.getElementById('btnManual').addEventListener('click', function() {
    lookupQR(document.getElementById('manualQr').value.trim());
});

document.getElementById('deductForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var formData = new URLSearchParams(new FormData(this));
    formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
    fetch('<?= base_url('stock/deduct') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: formData
    }).then(r => r.json()).then(function(d) {
        if (d.error) {
            document.getElementById('scanMsg').innerHTML = '<div class="alert alert-danger">'+d.error+'</div>';
        } else {
            document.getElementById('scanMsg').innerHTML = '<div class="alert alert-success">Deducted! New qty: <strong>'+d.new_qty+'</strong></div>';
            document.getElementById('stockInfo').style.display = 'none';
        }
    });
});
</script>
<?= $this->endSection() ?>
