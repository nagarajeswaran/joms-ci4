<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.wizard-step { display: none; }
.wizard-step.active { display: block; }
.step-indicator { display: flex; margin-bottom: 20px; }
.step-indicator .si-item {
    flex: 1; text-align: center; padding: 8px 4px;
    font-size: 12px; font-weight: 600; border-bottom: 3px solid #dee2e6; color: #aaa;
}
.step-indicator .si-item.done  { border-color: #198754; color: #198754; }
.step-indicator .si-item.active{ border-color: #ffc107; color: #856404; }
.step-indicator .si-num {
    width: 26px; height: 26px; border-radius: 50%; display: inline-flex;
    align-items: center; justify-content: center; font-size: 13px; font-weight: 700;
    background: #dee2e6; color: #888; margin-bottom: 3px;
}
.step-indicator .si-item.done  .si-num { background:#198754; color:#fff; }
.step-indicator .si-item.active .si-num { background:#ffc107; color:#212529; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5><i class="bi bi-bell text-warning"></i> Set Minimum Stock</h5>
    <a href="<?= base_url('stock') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show py-2">
    <?= session()->getFlashdata('success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Step indicators -->
<div class="step-indicator" id="stepIndicator">
    <div class="si-item active" id="si1"><div class="si-num">1</div><div>Product</div></div>
    <div class="si-item" id="si2"><div class="si-num">2</div><div>Pattern &amp; Location</div></div>
    <div class="si-item" id="si3"><div class="si-num">3</div><div>Set Minimums</div></div>
</div>

<!-- Step 1 -->
<div class="wizard-step active" id="step1">
    <div class="card">
        <div class="card-header fw-semibold"><i class="bi bi-bag"></i> Step 1 — Select Product</div>
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-md-8">
                    <label class="form-label">Product</label>
                    <select id="sel_product" class="form-select">
                        <option value="">-- Choose product --</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>"
                                    data-img="<?= esc($p['image'] ?? '') ?>"
                                    <?= isset($preProduct) && $preProduct == $p['id'] ? 'selected' : '' ?>>
                                <?= esc(($p['sku'] ? $p['sku'].' — ' : '') . $p['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-center gap-2 pt-3">
                    <div id="prodThumb" style="width:52px;height:52px;border-radius:8px;background:#f4f6f9;display:flex;align-items:center;justify-content:center;font-size:26px;border:1px solid #e0e0e0;">
                        <i class="bi bi-gem text-secondary"></i>
                    </div>
                </div>
            </div>
            <div class="mt-3 text-end">
                <button id="btnStep1Next" class="btn btn-warning" disabled>Next <i class="bi bi-arrow-right"></i></button>
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
                <button id="btnStep2Next" class="btn btn-warning" disabled>Load Grid <i class="bi bi-arrow-right"></i></button>
            </div>
        </div>
    </div>
</div>

<!-- Step 3: Grid -->
<div class="wizard-step" id="step3">
    <form method="post" action="<?= base_url('stock/save-min-stock') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" id="hid_product">
        <input type="hidden" name="pattern_id" id="hid_pattern">
        <input type="hidden" name="location_id" id="hid_location">

        <div class="card mb-3">
            <div class="card-header fw-semibold" id="grid_title"></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Variation</th>
                                <th class="text-center">Size</th>
                                <th class="text-center">Current Stock</th>
                                <th class="text-center" style="width:160px;">Min Qty Alert</th>
                            </tr>
                        </thead>
                        <tbody id="gridBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-between">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="goStep(2)"><i class="bi bi-arrow-left"></i> Back</button>
            <button type="submit" class="btn btn-warning px-4">
                <i class="bi bi-check-circle"></i> Save Minimum Levels
            </button>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
var BASE_URL   = '<?= base_url() ?>';
var CSRF_NAME  = '<?= csrf_token() ?>';
var CSRF_HASH  = '<?= csrf_hash() ?>';
var preProductId  = <?= (int)($preProduct  ?? 0) ?>;
var prePatternId  = <?= (int)($prePattern  ?? 0) ?>;
var preLocationId = <?= (int)($preLocation ?? 0) ?>;

var selProduct  = document.getElementById('sel_product');
var selPattern  = document.getElementById('sel_pattern');
var selLocation = document.getElementById('sel_location');

function goStep(n) {
    document.querySelectorAll('.wizard-step').forEach(function(s,i){ s.classList.toggle('active', i+1===n); });
    ['si1','si2','si3'].forEach(function(id,i){
        var el=document.getElementById(id);
        el.classList.toggle('active', i+1===n);
        el.classList.toggle('done',   i+1<n);
    });
}

selProduct.addEventListener('change', function() {
    var opt = this.options[this.selectedIndex];
    var img = opt ? opt.dataset.img : '';
    var t   = document.getElementById('prodThumb');
    t.innerHTML = img
        ? '<img src="'+BASE_URL+'uploads/products/'+img+'" style="width:52px;height:52px;border-radius:8px;object-fit:cover;">'
        : '<i class="bi bi-gem text-secondary" style="font-size:26px;"></i>';
    document.getElementById('btnStep1Next').disabled = !this.value;
});

document.getElementById('btnStep1Next').addEventListener('click', function(){
    if (!selProduct.value) return;
    loadPatterns(selProduct.value);
    goStep(2);
});

function loadPatterns(pid, cb) {
    selPattern.innerHTML = '<option value="">Loading…</option>';
    document.getElementById('btnStep2Next').disabled = true;
    fetch(BASE_URL+'stock/get-patterns', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({product_id:pid, [CSRF_NAME]:CSRF_HASH})
    }).then(r=>r.json()).then(function(data){
        selPattern.innerHTML = '<option value="">-- Select Pattern --</option>';
        data.forEach(function(p){
            selPattern.innerHTML += '<option value="'+p.id+'">'+(parseInt(p.is_default)===1?'Default':p.name)+'</option>';
        });
        checkStep2();
        if (cb) cb();
    });
}

function checkStep2() {
    document.getElementById('btnStep2Next').disabled = !(selPattern.value && selLocation.value);
}
selPattern.addEventListener('change', checkStep2);
selLocation.addEventListener('change', checkStep2);

document.getElementById('btnStep2Next').addEventListener('click', function(){
    if (!selPattern.value || !selLocation.value) return;
    loadGrid(); goStep(3);
});

function loadGrid() {
    document.getElementById('hid_product').value  = selProduct.value;
    document.getElementById('hid_pattern').value  = selPattern.value;
    document.getElementById('hid_location').value = selLocation.value;

    var patName = selPattern.options[selPattern.selectedIndex].text;
    var locName = selLocation.options[selLocation.selectedIndex].text;
    var prodName = selProduct.options[selProduct.selectedIndex].text;
    document.getElementById('grid_title').textContent = prodName + ' — ' + patName + ' @ ' + locName;

    fetch(BASE_URL+'stock/get-entry-grid', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({product_id:selProduct.value, pattern_id:selPattern.value, location_id:selLocation.value, [CSRF_NAME]:CSRF_HASH})
    }).then(r=>r.json()).then(function(data){
        if (data.error) { alert(data.error); return; }
        var tbody = document.getElementById('gridBody');
        tbody.innerHTML = '';
        data.variations.forEach(function(v){
            var isLow = v.min_qty > 0 && v.current_qty < v.min_qty;
            var badgeCls = v.current_qty > 0 ? (isLow ? 'bg-warning text-dark' : 'bg-success') : 'bg-secondary';
            tbody.innerHTML += '<tr>'+
                '<td>'+escHtml(v.name)+'<input type="hidden" name="variation_id[]" value="'+v.id+'"></td>'+
                '<td class="text-center">'+(v.size?v.size+'"':'-')+'</td>'+
                '<td class="text-center"><span class="badge '+badgeCls+'">'+v.current_qty+'</span></td>'+
                '<td><input type="number" name="min_qty[]" class="form-control form-control-sm text-center" value="'+(v.min_qty||0)+'" min="0" placeholder="0"></td>'+
                '</tr>';
        });
    });
}

function escHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// Auto-load from URL params (e.g. coming from pencil link)
if (preProductId) {
    selProduct.value = preProductId;
    selProduct.dispatchEvent(new Event('change'));
    if (preLocationId) selLocation.value = preLocationId;
    loadPatterns(preProductId, function(){
        if (prePatternId) { selPattern.value = prePatternId; checkStep2(); }
        if (preProductId && prePatternId && preLocationId) { loadGrid(); goStep(3); }
        else if (preProductId) goStep(2);
    });
}
</script>
<?= $this->endSection() ?>
