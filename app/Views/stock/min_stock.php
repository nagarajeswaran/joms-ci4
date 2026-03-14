<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5><i class="bi bi-bell"></i> Set Minimum Stock</h5>
    <a href="<?= base_url('stock') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show py-2">
    <?= session()->getFlashdata('success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Product</label>
                <select id="sel_product" class="form-select">
                    <option value="">-- Select Product --</option>
                    <?php
                    $preProduct = (int)($this->request->getGet('product_id') ?? 0);
                    foreach ($products as $p):
                    ?>
                        <option value="<?= $p['id'] ?>" <?= $p['id'] == $preProduct ? 'selected' : '' ?>><?= esc($p['sku'] ? $p['sku'].' - ' : '') ?><?= esc($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Pattern</label>
                <select id="sel_pattern" class="form-select" disabled>
                    <option value="">-- Select Pattern --</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Location</label>
                <select id="sel_location" class="form-select">
                    <option value="">-- Select Location --</option>
                    <?php
                    $preLocation = (int)($this->request->getGet('location_id') ?? 0);
                    foreach ($locations as $l):
                    ?>
                        <option value="<?= $l['id'] ?>" <?= $l['id'] == $preLocation ? 'selected' : '' ?>><?= esc($l['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" id="btnLoad" class="btn btn-primary w-100" disabled>Load Grid</button>
            </div>
        </div>
    </div>
</div>

<div id="minGrid" style="display:none;">
    <form method="post" action="<?= base_url('stock/save-min-stock') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" id="hid_product">
        <input type="hidden" name="pattern_id" id="hid_pattern">
        <input type="hidden" name="location_id" id="hid_location">

        <div class="card mb-3">
            <div class="card-header fw-semibold" id="grid_title"></div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Variation</th>
                            <th>Size</th>
                            <th class="text-center" style="width:140px;">Current Stock</th>
                            <th class="text-center" style="width:140px;">Min Qty Alert</th>
                        </tr>
                    </thead>
                    <tbody id="gridBody"></tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-warning px-4"><i class="bi bi-check-circle"></i> Save Minimum Levels</button>
        </div>
    </form>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
var selProduct  = document.getElementById('sel_product');
var selPattern  = document.getElementById('sel_pattern');
var selLocation = document.getElementById('sel_location');
var btnLoad     = document.getElementById('btnLoad');
var CSRF_NAME   = '<?= csrf_token() ?>';
var CSRF_TOKEN  = '<?= csrf_hash() ?>';
var preProductId  = <?= (int)($this->request->getGet('product_id') ?? 0) ?>;
var prePatternId  = <?= (int)($this->request->getGet('pattern_id') ?? 0) ?>;
var preLocationId = <?= (int)($this->request->getGet('location_id') ?? 0) ?>;

function checkLoad() {
    btnLoad.disabled = !(selProduct.value && selPattern.value && selLocation.value);
}

function loadPatterns(productId, afterLoad) {
    selPattern.innerHTML = '<option value="">Loading...</option>';
    selPattern.disabled = true;
    if (!productId) { selPattern.innerHTML = '<option value="">-- Select Pattern --</option>'; checkLoad(); return; }
    fetch('<?= base_url('stock/get-patterns') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({product_id: productId, [CSRF_NAME]: CSRF_TOKEN})
    }).then(r => r.json()).then(function(data) {
        selPattern.innerHTML = '<option value="">-- Select Pattern --</option>';
        data.forEach(function(p) {
            selPattern.innerHTML += '<option value="'+p.id+'">'+(parseInt(p.is_default) === 1 ? 'Default' : p.name)+'</option>';
        });
        selPattern.disabled = false;
        checkLoad();
        if (typeof afterLoad === 'function') afterLoad();
    });
}

function loadGrid() {
    document.getElementById('hid_product').value  = selProduct.value;
    document.getElementById('hid_pattern').value  = selPattern.value;
    document.getElementById('hid_location').value = selLocation.value;

    fetch('<?= base_url('stock/get-entry-grid') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({product_id: selProduct.value, pattern_id: selPattern.value, location_id: selLocation.value, [CSRF_NAME]: CSRF_TOKEN})
    }).then(r => r.json()).then(function(data) {
        if (data.error) { alert(data.error); return; }
        var tbody = document.getElementById('gridBody');
        tbody.innerHTML = '';
        data.variations.forEach(function(v) {
            var stockBadge = '<span class="badge ' + (v.current_qty > 0 ? 'bg-success' : 'bg-secondary') + '">' + v.current_qty + '</span>';
            tbody.innerHTML += '<tr>'
                + '<td>' + v.name + '<input type="hidden" name="variation_id[]" value="' + v.id + '"></td>'
                + '<td>' + (v.size ? v.size + ' inch' : '-') + '</td>'
                + '<td class="text-center">' + stockBadge + '</td>'
                + '<td><input type="number" name="min_qty[]" class="form-control form-control-sm text-center" value="' + (v.min_qty || 0) + '" min="0"></td>'
                + '</tr>';
        });
        var patName = selPattern.options[selPattern.selectedIndex].text;
        var locName = selLocation.options[selLocation.selectedIndex].text;
        document.getElementById('grid_title').textContent = (data.product.name || '') + ' — ' + patName + ' @ ' + locName;
        document.getElementById('minGrid').style.display = '';
    });
}

selProduct.addEventListener('change', function() { loadPatterns(this.value); });
selPattern.addEventListener('change', checkLoad);
selLocation.addEventListener('change', checkLoad);
btnLoad.addEventListener('click', loadGrid);

// Auto-load when opened from pencil link (URL has product_id + pattern_id + location_id)
if (preProductId && prePatternId && preLocationId) {
    if (selLocation.value !== String(preLocationId)) selLocation.value = preLocationId;
    loadPatterns(preProductId, function() {
        selPattern.value = prePatternId;
        checkLoad();
        loadGrid();
    });
}
</script>
<?= $this->endSection() ?>
