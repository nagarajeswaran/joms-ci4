<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.partord-scan-panel {
    display: none;
    border: 1px solid #d7e3f3;
    background: #f8fbff;
}
.partord-scan-panel.active {
    display: block;
}
.partord-scan-status {
    min-height: 24px;
}
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Part Orders (PARTORD)</h5>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-info btn-sm" id="partOrderScanToggle"><i class="bi bi-upc-scan"></i> Scan</button>
        <a href="<?= base_url('part-orders/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> New Part Order</a>
    </div>
</div>
<div class="card mb-3 partord-scan-panel" id="partOrderScanPanel">
    <div class="card-body">
        <div class="row g-2 align-items-center mb-2">
            <div class="col-lg-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-keyboard"></i></span>
                    <input type="text" id="partOrderScanInput" class="form-control" placeholder="Scan or type ORDER NO">
                    <button type="button" class="btn btn-info" id="partOrderScanBtn">Open</button>
                </div>
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="partOrderCameraStart"><i class="bi bi-camera"></i> Camera</button>
                <button type="button" class="btn btn-secondary btn-sm" id="partOrderCameraStop" style="display:none"><i class="bi bi-stop-circle"></i> Stop</button>
            </div>
            <div class="col">
                <div class="partord-scan-status small" id="partOrderScanStatus"></div>
            </div>
        </div>
        <div id="partOrderCameraWrap" style="display:none">
            <div id="partOrderCameraReader" style="max-width:420px"></div>
        </div>
    </div>
</div>
<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="karigar" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Karigar</option>
            <?php foreach ($karigars as $k): ?>
            <option value="<?= $k['id'] ?>" <?= $karigarFilter == $k['id'] ? 'selected' : '' ?>><?= esc($k['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="draft" <?= $statusFilter==='draft'?'selected':'' ?>>Draft</option>
            <option value="posted" <?= $statusFilter==='posted'?'selected':'' ?>>Posted</option>
        </select>
    </div>
</form>
<div class="table-responsive">
<table class="table table-sm table-bordered table-hover">
<thead class="table-dark"><tr><th>Order No</th><th>Karigar</th><th>Linked Order</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($items as $row): ?>
<tr>
    <td><strong><?= esc($row['order_number']) ?></strong></td>
    <td><?= esc($row['karigar_name']) ?></td>
    <td><?= $row['client_order_id'] ? 'ORD-'.str_pad($row['client_order_id'],3,'0',STR_PAD_LEFT) : '-' ?></td>
    <td><span class="badge <?= $row['status']==='posted'?'bg-success':'bg-warning text-dark' ?>"><?= ucfirst($row['status']) ?></span></td>
    <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
    <td><a href="<?= base_url('part-orders/view/'.$row['id']) ?>" class="btn btn-sm btn-outline-primary">View</a></td>
</tr>
<?php endforeach; ?>
<?php if (!$items): ?><tr><td colspan="6" class="text-center text-muted">No part orders found</td></tr><?php endif; ?>
</tbody>
</table>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/@zxing/browser@0.1.4/umd/index.min.js"></script>
<script>
(function () {
    var baseUrl = '<?= base_url() ?>';
    var panel = document.getElementById('partOrderScanPanel');
    var toggleBtn = document.getElementById('partOrderScanToggle');
    var input = document.getElementById('partOrderScanInput');
    var openBtn = document.getElementById('partOrderScanBtn');
    var statusEl = document.getElementById('partOrderScanStatus');
    var startBtn = document.getElementById('partOrderCameraStart');
    var stopBtn = document.getElementById('partOrderCameraStop');
    var cameraWrap = document.getElementById('partOrderCameraWrap');
    var lastScan = '';
    var lastScanAt = 0;
    var hidBuffer = '';
    var hidTimer = null;
    var stream = null;

    function setStatus(html) {
        statusEl.innerHTML = html;
    }

    function normalize(text) {
        return String(text || '').trim();
    }

    function openOrder(code) {
        code = normalize(code);
        if (!code) {
            setStatus('<span class="text-danger">Enter order number</span>');
            return;
        }
        setStatus('<span class="text-muted">Looking up...</span>');
        fetch(baseUrl + 'part-orders/lookup-order?q=' + encodeURIComponent(code))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) {
                    setStatus('<span class="text-danger">' + escapeHtml(data.error) + '</span>');
                    return;
                }
                setStatus('<span class="text-success">Opening ' + escapeHtml(data.order_number) + '...</span>');
                window.location.href = data.url;
            })
            .catch(function () {
                setStatus('<span class="text-danger">Network error</span>');
            });
    }

    function handleScanText(text) {
        var now = Date.now();
        if (text === lastScan && (now - lastScanAt) < 1500) return;
        lastScan = text;
        lastScanAt = now;
        openOrder(text);
    }

    toggleBtn.addEventListener('click', function () {
        panel.classList.toggle('active');
        if (panel.classList.contains('active')) {
            input.focus();
        }
    });

    openBtn.addEventListener('click', function () {
        openOrder(input.value);
        input.value = '';
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            openOrder(input.value);
            input.value = '';
        }
    });

    document.addEventListener('keydown', function (e) {
        var tag = document.activeElement ? document.activeElement.tagName : '';
        if (tag === 'INPUT' && document.activeElement !== input) return;

        if (e.key === 'Enter') {
            if (hidBuffer.length >= 3) {
                panel.classList.add('active');
                handleScanText(hidBuffer);
                hidBuffer = '';
                input.value = '';
            }
            if (hidTimer) {
                clearTimeout(hidTimer);
                hidTimer = null;
            }
            return;
        }

        if (e.key.length === 1) {
            hidBuffer += e.key;
            input.value = hidBuffer;
            if (hidTimer) clearTimeout(hidTimer);
            hidTimer = setTimeout(function () {
                hidBuffer = '';
            }, 500);
        }
    });

    startBtn.addEventListener('click', function () {
        cameraWrap.style.display = '';
        startBtn.style.display = 'none';
        stopBtn.style.display = '';
        setStatus('<span class="text-muted">Starting camera...</span>');

        var reader = new ZXingBrowser.BrowserMultiFormatReader();
        reader.decodeFromVideoDevice(null, 'partOrderCameraReader', function (result, err) {
            if (!result) return;
            var text = result.getText();
            if (!text) return;
            if (reader && reader.reset) reader.reset();
            setStatus('<span class="text-success">Scanned: ' + escapeHtml(text) + '</span>');
            handleScanText(text);
        }).then(function (controls) {
            stream = controls;
            setStatus('<span class="text-muted">Camera ready</span>');
        }).catch(function (error) {
            setStatus('<span class="text-danger">Camera error: ' + escapeHtml(error.message || error) + '</span>');
            cameraWrap.style.display = 'none';
            startBtn.style.display = '';
            stopBtn.style.display = 'none';
        });
    });

    stopBtn.addEventListener('click', function () {
        if (stream && stream.stop) stream.stop();
        stream = null;
        cameraWrap.style.display = 'none';
        document.getElementById('partOrderCameraReader').innerHTML = '';
        startBtn.style.display = '';
        stopBtn.style.display = 'none';
        setStatus('');
    });

    function escapeHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
})();
</script>
<?= $this->endSection() ?>
