<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<!-- v2024-build5 -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-file-earmark-spreadsheet text-success"></i> Bulk Update Products</h5>
    <a href="<?= base_url('products') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Products</a>
</div>

<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('info')): ?>
<div class="alert alert-info"><?= session()->getFlashdata('info') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>

<div class="row g-3">
    <!-- Step 1 -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <strong><i class="bi bi-download"></i> Step 1 — Download Template</strong>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3" style="font-size:13px;">
                    Optionally filter by product type, body, pidi or main part before downloading.
                    Open the CSV in <strong>Excel</strong> or <strong>Google Sheets</strong>, edit what you need, save as <code>.csv</code>, then upload in Step 2.
                </p>

                <!-- Filter bar -->
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <select id="bulkType" class="form-select form-select-sm">
                            <option value="">All Product Types</option>
                            <?php foreach ($productTypes as $pt): ?>
                            <option value="<?= $pt['id'] ?>"><?= esc($pt['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <select id="bulkBody" class="form-select form-select-sm">
                            <option value="">All Bodies</option>
                            <?php foreach ($bodies as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= esc($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <select id="bulkPidi" class="form-select form-select-sm">
                            <option value="">All Pidi</option>
                            <?php foreach ($pidiValues as $pv): ?>
                            <option value="<?= esc($pv) ?>"><?= esc($pv) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <select id="bulkMain" class="form-select form-select-sm">
                            <option value="">All Main Parts</option>
                            <?php foreach ($mainParts as $mp): ?>
                            <option value="<?= $mp['id'] ?>"><?= esc($mp['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Active filter badges -->
                <div id="bulkBadges" class="mb-3 d-flex gap-1 flex-wrap"></div>

                <a id="downloadLink" href="<?= base_url('products/bulkExportCsv') ?>" class="btn btn-success mb-3">
                    <i class="bi bi-download"></i> <span id="downloadLabel">Download CSV (all products)</span>
                </a>

                <hr class="my-3">

                <!-- Column reference -->
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0" style="font-size:12px;">
                        <thead class="table-light">
                            <tr><th>Column</th><th>Description</th><th>Editable?</th></tr>
                        </thead>
                        <tbody>
                            <tr><td><code>product_id</code></td><td>Product ID — reference key</td><td><span class="badge bg-danger">Lock</span></td></tr>
                            <tr><td><code>product_sku</code></td><td>SKU code</td><td><span class="badge bg-success">Yes</span></td></tr>
                            <tr><td><code>product_name</code></td><td>Name (English)</td><td><span class="badge bg-success">Yes</span></td></tr>
                            <tr><td><code>product_tamil_name</code></td><td>Tamil name</td><td><span class="badge bg-success">Yes</span></td></tr>
                            <tr><td><code>product_short_name</code></td><td>Short / display name</td><td><span class="badge bg-success">Yes</span></td></tr>
                            <tr><td><code>pattern_code</code></td><td>Pattern Code — reference key</td><td><span class="badge bg-danger">Lock</span></td></tr>
                            <tr><td><code>pattern_name</code></td><td>Pattern name</td><td><span class="badge bg-success">Yes</span></td></tr>
                            <tr><td><code>pattern_tamil_name</code></td><td>Pattern Tamil name</td><td><span class="badge bg-success">Yes</span></td></tr>
                            <tr><td><code>pattern_short_name</code></td><td>Pattern short name</td><td><span class="badge bg-success">Yes</span></td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-warning py-2 mt-2 mb-0" style="font-size:12px;">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Do not change</strong> <code>product_id</code> or <code>pattern_code</code> — they identify each record.
                    Unchanged rows are automatically ignored on upload.
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2 -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header bg-info text-white">
                <strong><i class="bi bi-upload"></i> Step 2 — Upload Filled CSV</strong>
            </div>
            <div class="card-body d-flex flex-column">
                <p class="text-muted mb-3" style="font-size:13px;">
                    Upload your edited CSV. A <strong>preview of changes</strong> will appear before anything is saved.
                    Rows with no edits are skipped automatically.
                </p>
                <form action="<?= base_url('products/bulkPreview') ?>" method="post" enctype="multipart/form-data" class="flex-grow-1 d-flex flex-column justify-content-between">
                    <?= csrf_field() ?>
                    <div>
                        <label class="form-label fw-bold">Select CSV File</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <div class="form-text">Max 5 MB. Save as <code>.csv</code> (UTF-8). Column order must match the template.</div>
                    </div>
                    <button type="submit" class="btn btn-info text-white mt-4">
                        <i class="bi bi-eye"></i> Preview Changes
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var BASE_URL = '<?= base_url() ?>';

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

    function updateDownloadLink() {
        var type = document.getElementById('bulkType').value;
        var body = document.getElementById('bulkBody').value;
        var pidi = document.getElementById('bulkPidi').value;
        var main = document.getElementById('bulkMain').value;

        var params = new URLSearchParams();
        if (type) params.set('type', type);
        if (body) params.set('body', body);
        if (pidi) params.set('pidi', pidi);
        if (main) params.set('main', main);

        var qs = params.toString();
        document.getElementById('downloadLink').href = BASE_URL + 'products/bulkExportCsv' + (qs ? '?' + qs : '');

        var hasFilter = type || body || pidi || main;
        document.getElementById('downloadLabel').textContent = hasFilter
            ? 'Download Filtered CSV'
            : 'Download CSV (all products)';

        updateBadges(type, body, pidi, main);
    }

    function updateBadges(type, body, pidi, main) {
        var defs = [
            { id: 'bulkType', val: type, label: 'Type: '  + (typeLabels[type] || type) },
            { id: 'bulkBody', val: body, label: 'Body: '  + (bodyLabels[body] || body) },
            { id: 'bulkPidi', val: pidi, label: 'Pidi: '  + pidi },
            { id: 'bulkMain', val: main, label: 'Part: '  + (mainLabels[main] || main) },
        ];
        var html = '';
        defs.forEach(function (d) {
            if (!d.val) return;
            html += '<span class="badge bg-primary d-inline-flex align-items-center gap-1" style="font-size:12px;">'
                + esc(d.label)
                + '<button type="button" class="btn-close btn-close-white ms-1" style="font-size:9px;" data-clear="' + d.id + '"></button>'
                + '</span>';
        });
        var el = document.getElementById('bulkBadges');
        el.innerHTML = html;
        el.querySelectorAll('[data-clear]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById(this.dataset.clear).value = '';
                updateDownloadLink();
            });
        });
    }

    ['bulkType', 'bulkBody', 'bulkPidi', 'bulkMain'].forEach(function (id) {
        document.getElementById(id).addEventListener('change', updateDownloadLink);
    });

    updateDownloadLink();

    function esc(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
})();
</script>
<?php $this->endSection() ?>
