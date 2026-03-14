<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5>Stock Entry</h5>
    <a href="<?= base_url('stock') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Product</label>
                <select id="sel_product" class="form-select">
                    <option value="">-- Select Product --</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= esc($p['sku'] ? $p['sku'].' - ' : '') ?><?= esc($p['name']) ?></option>
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
                    <?php foreach ($locations as $l): ?>
                        <option value="<?= $l['id'] ?>"><?= esc($l['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="button" id="btnLoad" class="btn btn-primary w-100" disabled>Load Grid</button>
            </div>
        </div>
    </div>
</div>

<div id="entryGrid" style="display:none;">
    <form method="post" action="<?= base_url('stock/save-entry') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" id="hid_product">
        <input type="hidden" name="pattern_id" id="hid_pattern">
        <input type="hidden" name="location_id" id="hid_location">

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span id="grid_title" class="fw-semibold"></span>
                <div class="d-flex gap-3 align-items-center">
                    <div>
                        <label class="me-1">Entry Type:</label>
                        <div class="btn-group btn-group-sm">
                            <input type="radio" class="btn-check" name="entry_type" id="et_add" value="add" checked>
                            <label class="btn btn-outline-success" for="et_add"><i class="bi bi-plus"></i> Add to stock</label>
                            <input type="radio" class="btn-check" name="entry_type" id="et_set" value="set">
                            <label class="btn btn-outline-warning" for="et_set"><i class="bi bi-pencil"></i> Set exact qty</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-bordered mb-0" id="varGrid">
                    <thead class="table-light">
                        <tr>
                            <th>Variation</th>
                            <th>Size</th>
                            <th class="text-center">Current Stock</th>
                            <th class="text-center" style="width:130px;">Qty to Enter</th>
                        </tr>
                    </thead>
                    <tbody id="varGridBody"></tbody>
                </table>
            </div>
        </div>

        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Note (optional)</label>
                <input type="text" name="note" class="form-control form-control-sm" placeholder="e.g. Stock from manufacturing batch #12">
            </div>
            <div class="col-md-3">
                <a href="<?= base_url('stock/labels') ?>/0" id="lblLink" class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-qr-code"></i> Print QR Labels</a>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-success w-100"><i class="bi bi-check-circle"></i> Save Stock</button>
            </div>
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

function checkLoad() {
    btnLoad.disabled = !(selProduct.value && selPattern.value && selLocation.value);
}

selProduct.addEventListener('change', function() {
    selPattern.innerHTML = '<option value="">Loading...</option>';
    selPattern.disabled = true;
    if (!this.value) { selPattern.innerHTML = '<option value="">-- Select Pattern --</option>'; checkLoad(); return; }
    fetch('<?= base_url('stock/get-patterns') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({product_id: this.value, '<?= csrf_token() ?>': '<?= csrf_hash() ?>'})
    }).then(r => r.json()).then(function(data) {
        selPattern.innerHTML = '<option value="">-- Select Pattern --</option>';
        data.forEach(function(p) {
            selPattern.innerHTML += '<option value="'+p.id+'">'+(parseInt(p.is_default) === 1 ? 'Default' : p.name)+'</option>';
        });
        selPattern.disabled = false;
        checkLoad();
    });
});
selPattern.addEventListener('change', checkLoad);
selLocation.addEventListener('change', checkLoad);

btnLoad.addEventListener('click', function() {
    document.getElementById('hid_product').value  = selProduct.value;
    document.getElementById('hid_pattern').value  = selPattern.value;
    document.getElementById('hid_location').value = selLocation.value;

    fetch('<?= base_url('stock/get-entry-grid') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({product_id: selProduct.value, pattern_id: selPattern.value, location_id: selLocation.value, '<?= csrf_token() ?>': '<?= csrf_hash() ?>'})
    }).then(r => r.json()).then(function(data) {
        if (data.error) { alert(data.error); return; }
        var tbody = document.getElementById('varGridBody');
        tbody.innerHTML = '';
        data.variations.forEach(function(v, i) {
            tbody.innerHTML += '<tr>'
                + '<td>'+v.name+'<input type="hidden" name="variation_id[]" value="'+v.id+'"></td>'
                + '<td>'+(v.size ? v.size+' inch' : '-')+'</td>'
                + '<td class="text-center"><span class="badge '+(v.current_qty > 0 ? 'bg-success' : 'bg-secondary')+'">'+v.current_qty+'</span></td>'
                + '<td><input type="number" name="qty[]" class="form-control form-control-sm text-center" value="0" min="0"></td>'
                + '</tr>';
        });
        var title = (data.product.name || '') + (data.pattern && !data.pattern.is_default ? ' - '+data.pattern.name : '');
        document.getElementById('grid_title').textContent = title;
        document.getElementById('lblLink').href = '<?= base_url('stock/labels') ?>/'+selProduct.value;
        document.getElementById('entryGrid').style.display = '';
    });
});
</script>
<?= $this->endSection() ?>
