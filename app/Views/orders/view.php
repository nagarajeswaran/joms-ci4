<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<?php
$statusColors = ['draft'=>'secondary','confirmed'=>'primary','production'=>'warning','completed'=>'success'];
$sc = $statusColors[$order['status']] ?? 'secondary';
$imgBase = base_url('uploads/products/');
?>

<!-- Order Header -->
<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h5 class="mb-1">
                    <?php if (!empty($order['order_number'])): ?>
                    <span class="badge bg-dark me-2" style="font-size:13px;"><?= esc($order['order_number']) ?></span>
                    <?php endif; ?>
                    <?= esc($order['title']) ?>
                </h5>
                <div class="text-muted" style="font-size:13px;">
                    <?php if ($order['client_name']): ?><i class="bi bi-person"></i> <?= esc($order['client_name']) ?> &nbsp;<?php endif; ?>
                    <i class="bi bi-calendar"></i> <?= date('d M Y', strtotime($order['created_at'])) ?>
                    <?php if ($order['notes']): ?>&nbsp; <i class="bi bi-sticky"></i> <?= esc($order['notes']) ?><?php endif; ?>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap align-items-center">
                <span class="badge bg-<?= $sc ?> fs-6"><?= ucfirst($order['status']) ?></span>
                <?php if ($canEdit): ?>
                    <a href="<?= base_url('orders/edit/' . $order['id']) ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i> Edit</a>
                    <?php if (!empty($items)): ?>
                    <a href="<?= base_url('orders/preview/' . $order['id']) ?>" class="btn btn-primary btn-sm"><i class="bi bi-eye"></i> Preview & Confirm</a>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (in_array($order['status'], ['confirmed','production','completed'])): ?>
                    <a href="<?= base_url('orders/mainPartSetup/' . $order['id']) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-gear"></i> Main Part Setup</a>
                    <a href="<?= base_url('orders/partRequirements/' . $order['id']) ?>" class="btn btn-success btn-sm"><i class="bi bi-list-check"></i> Part Requirements</a>
                    <a href="<?= base_url('orders/orderSheet/' . $order['id']) ?>" class="btn btn-info btn-sm"><i class="bi bi-file-earmark-text"></i> Order Sheet</a>
                    <a href="<?= base_url('orders/touchAnalysis/' . $order['id']) ?>" class="btn btn-outline-warning btn-sm"><i class="bi bi-droplet"></i> Touch Analysis</a>
                    <?php if ($order['status'] === 'confirmed'): ?>
                    <a href="<?= base_url('orders/updateStatus/' . $order['id'] . '/production') ?>" class="btn btn-warning btn-sm" onclick="return confirm('Move to Production?')"><i class="bi bi-arrow-right"></i> Production</a>
                    <?php elseif ($order['status'] === 'production'): ?>
                    <a href="<?= base_url('orders/updateStatus/' . $order['id'] . '/completed') ?>" class="btn btn-success btn-sm" onclick="return confirm('Mark as Completed?')"><i class="bi bi-check-circle"></i> Complete</a>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($order['status'] !== 'closed'): ?>
                <a href="<?= base_url('orders/split/' . $order['id']) ?>" class="btn btn-outline-secondary btn-sm" title="Split this order into two"><i class="bi bi-scissors"></i> Split</a>
                <?php endif; ?>
                <a href="<?= base_url('orders') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
            </div>
        </div>
    </div>
</div>

<!-- Live Est. Weight Bar -->
<?php if (!empty($items)): ?>
<div class="card mb-3" id="estWeightBar" style="border-left:4px solid #198754;">
    <div class="card-body py-2 d-flex align-items-center gap-3 flex-wrap">
        <span style="font-size:13px;color:#555;"><i class="bi bi-calculator text-success"></i> Live Est. Weight:</span>
        <span class="fw-bold" style="font-size:1.25rem;color:#198754;" id="grandEstWeight">0.0000</span>
        <span class="text-muted" style="font-size:12px;">g</span>
        <small class="text-muted ms-2" style="font-size:11px;">(approximate &mdash; based on current BOM &amp; master weights)</small>
    </div>
</div>
<?php endif; ?>

<!-- Existing Items -->
<?php foreach ($items as $idx => $item): ?>
<div class="card mb-3 order-item-card"
     data-item-id="<?= $item['id'] ?>"
     data-item-idx="<?= $idx ?>"
     data-type-id="<?= $item['product_type_id'] ?>"
     data-prev-qty='<?= $item['prev_qty_json'] ?>'
     data-weight-map='<?= htmlspecialchars(json_encode($item['weight_map'] ?? []), ENT_QUOTES, 'UTF-8') ?>'>
    <div class="card-header d-flex justify-content-between align-items-center py-2" style="background:#f8f9fa;">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-secondary">#<?= $idx + 1 ?></span>
            <?php if (!empty($item['product_image'])): ?>
            <img src="<?= $imgBase . esc($item['product_image']) ?>" alt="" style="width:36px;height:36px;object-fit:cover;border-radius:4px;border:1px solid #ddd;">
            <?php else: ?>
            <span style="width:36px;height:36px;background:#eee;border-radius:4px;display:inline-flex;align-items:center;justify-content:center;"><i class="bi bi-image text-muted"></i></span>
            <?php endif; ?>
            <div>
                <strong><?= esc($item['product_name']) ?></strong>
                <?php if ($item['sku']): ?><small class="text-muted ms-1">(<?= esc($item['sku']) ?>)</small><?php endif; ?>
                <?php if ($item['type_name']): ?><span class="badge bg-light text-dark border ms-1"><?= esc($item['type_name']) ?></span><?php endif; ?>
                <?php if ($item['pattern_name']): ?><span class="badge bg-info ms-1"><?= esc($item['pattern_name']) ?></span><?php endif; ?>
                <?php if ($item['stamp_name']): ?><span class="badge bg-warning text-dark ms-1"><i class="bi bi-bookmark"></i> <?= esc($item['stamp_name']) ?></span><?php endif; ?>
            </div>
            <!-- Est weight badge per item -->
            <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size:11px;">
                <i class="bi bi-calculator"></i> <span class="item-wt-value">0.0000</span> g
            </span>
        </div>
        <?php if ($canEdit): ?>
        <a href="<?= base_url('orders/removeItem/' . $item['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Remove this item?')"><i class="bi bi-trash"></i></a>
        <?php endif; ?>
    </div>
    <div class="card-body py-2 px-3">
        <?php if ($canEdit): ?>
        <form action="<?= base_url('orders/saveItemQty/' . $item['id']) ?>" method="post">
            <?= csrf_field() ?>
            <div class="row mb-2 g-2">
                <div class="col-md-4">
                    <label class="form-label mb-0" style="font-size:12px;">Pattern</label>
                    <select class="form-select form-select-sm" name="pattern_id">
                        <option value="">-- No Pattern (Base BOM) --</option>
                        <?php foreach ($item['patterns'] as $pt): ?>
                        <option value="<?= $pt['id'] ?>" <?= $item['pattern_id'] == $pt['id'] ? 'selected' : '' ?>><?= esc($pt['name']) ?><?= $pt['is_default'] ? ' (default)' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-0" style="font-size:12px;">Stamp</label>
                    <select class="form-select form-select-sm" name="stamp_id">
                        <option value="">-- No Stamp --</option>
                        <?php foreach ($stamps as $st): ?>
                        <option value="<?= $st['id'] ?>" <?= $item['stamp_id'] == $st['id'] ? 'selected' : '' ?>><?= esc($st['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($item['same_type_prev']): ?>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="copyFromAbove(this)">
                        <i class="bi bi-arrow-up"></i> Copy qty from above
                    </button>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($item['variation_groups'])): ?>
        <div class="table-responsive">
        <?php foreach ($item['variation_groups'] as $groupName => $vars): ?>
            <div class="mb-1"><small class="text-muted fw-bold"><?= esc($groupName) ?></small></div>
            <table class="table table-bordered table-sm mb-2" style="font-size:12px; min-width:max-content;">
                <thead style="background:#eef2f7;">
                    <tr>
                        <?php foreach ($vars as $v): ?>
                        <th class="text-center" style="min-width:70px;"><?= esc($v['name']) ?><br><small class="text-muted"><?= $v['size'] ?>"</small></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <?php foreach ($vars as $v): ?>
                        <td class="text-center p-1">
                            <?php if ($canEdit): ?>
                            <input type="number" name="qty[<?= $v['id'] ?>]"
                                   value="<?= (int)($item['qty_map'][$v['id']] ?? 0) ?: '' ?>"
                                   min="0" step="1" inputmode="numeric" pattern="[0-9]*"
                                   class="form-control form-control-sm text-center p-0 qty-input"
                                   style="width:65px;margin:auto;"
                                   oninput="this.value=this.value.replace(/[^0-9]/g,'');calcAllWeights();">
                            <?php else: ?>
                            <span class="<?= ($item['qty_map'][$v['id']] ?? 0) > 0 ? 'fw-bold' : 'text-muted' ?>">
                                <?= ($item['qty_map'][$v['id']] ?? 0) > 0 ? (int)$item['qty_map'][$v['id']] : '—' ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-muted" style="font-size:12px;">No variations defined for this product type.</div>
        <?php endif; ?>

        <?php if ($canEdit): ?>
            <button type="submit" class="btn btn-success btn-sm mt-1"><i class="bi bi-check"></i> Save</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>

<?php if (empty($items)): ?>
<div class="text-center text-muted py-4"><i class="bi bi-box" style="font-size:2rem;"></i><br>No products added yet.</div>
<?php endif; ?>

<!-- Add Products Panel (draft + confirmed only) -->
<?php if ($canEdit): ?>
<div class="card mt-3" style="border:2px dashed #cdd5df;">
    <div class="card-body">
        <h6 class="text-muted mb-3"><i class="bi bi-plus-circle"></i> Add Products to Order</h6>

        <div class="row g-2 mb-2">
            <div class="col-md-5">
                <input type="text" id="productSearch" class="form-control form-control-sm" placeholder="Search product name or SKU...">
            </div>
            <div class="col-md-3">
                <select id="productTypeFilter" class="form-select form-select-sm">
                    <option value="">-- All Types --</option>
                    <?php foreach ($productTypes as $pt): ?>
                    <option value="<?= $pt['id'] ?>"><?= esc($pt['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-auto">
                <button type="button" class="btn btn-secondary btn-sm" onclick="doSearch()"><i class="bi bi-search"></i> Search</button>
            </div>
        </div>

        <div id="searchResults" class="mb-3"></div>

        <form action="<?= base_url('orders/addItem/' . $order['id']) ?>" method="post" id="addItemsForm" style="display:none;">
            <?= csrf_field() ?>
            <div id="selectedProductForms"></div>
            <div class="mt-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Add <span id="addCount">0</span> Product(s)</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearSelection()">Cancel</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
var _csrf_name  = '<?= csrf_token() ?>';
var _csrf_hash  = '<?= csrf_hash() ?>';
var _baseUrl    = '<?= base_url() ?>';
var _orderId    = <?= (int)$order['id'] ?>;
var _stamps     = <?= json_encode($stamps) ?>;
var _selectedProducts = {};
var _newFormWeightMaps = {};  // productId -> weight_map for new (unsaved) product forms

function post(url, data) {
    var body = new URLSearchParams(data);
    body.append(_csrf_name, _csrf_hash);
    return fetch(_baseUrl + url, {method:'POST', body:body}).then(function(r){ _csrf_hash = r.headers.get('X-CSRF-TOKEN') || _csrf_hash; return r.json(); });
}
function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// ========== LIVE WEIGHT CALCULATION ==========

function calcAllWeights() {
    var grand = 0;

    // Existing saved items
    document.querySelectorAll('.order-item-card').forEach(function(card) {
        var wmapRaw = card.dataset.weightMap || '{}';
        var wmap = {};
        try { wmap = JSON.parse(wmapRaw); } catch(e) {}
        var itemWt = 0;
        card.querySelectorAll('input.qty-input').forEach(function(inp) {
            var m = inp.name.match(/qty\[(\d+)\]/);
            if (m) {
                var qty = parseFloat(inp.value) || 0;
                var wpp = parseFloat(wmap[m[1]]) || 0;
                itemWt += qty * wpp;
            }
        });
        var badge = card.querySelector('.item-wt-value');
        if (badge) badge.textContent = itemWt.toFixed(4);
        grand += itemWt;
    });

    // New product forms (not yet saved)
    document.querySelectorAll('[id^="pform_"]').forEach(function(div) {
        var pid = div.id.replace('pform_', '');
        var wmap = _newFormWeightMaps[pid] || {};
        var itemWt = 0;
        div.querySelectorAll('input[type="number"][name*="[qty]["]').forEach(function(inp) {
            var m = inp.name.match(/\[qty\]\[(\d+)\]/);
            if (m) {
                var qty = parseFloat(inp.value) || 0;
                var wpp = parseFloat(wmap[m[1]]) || 0;
                itemWt += qty * wpp;
            }
        });
        var badge = div.querySelector('.new-item-wt-value');
        if (badge) badge.textContent = itemWt.toFixed(4);
        grand += itemWt;
    });

    var el = document.getElementById('grandEstWeight');
    if (el) el.textContent = grand.toFixed(4);
}

// Run on page load with existing values
window.addEventListener('DOMContentLoaded', function() { calcAllWeights(); });

// ========== SEARCH ==========

function doSearch() {
    var q  = document.getElementById('productSearch').value.trim();
    var pt = document.getElementById('productTypeFilter').value;
    if (!q && !pt) { document.getElementById('searchResults').innerHTML = '<small class="text-muted">Enter a name or select a type.</small>'; return; }
    post('orders/searchProducts', {q:q, product_type_id:pt}).then(function(d) {
        if (!d.products || !d.products.length) {
            document.getElementById('searchResults').innerHTML = '<small class="text-muted">No products found.</small>';
            return;
        }
        var html = '<div class="border rounded mb-2"><table class="table table-sm table-hover mb-0" style="font-size:12px;">';
        html += '<thead><tr><th style="width:30px;"></th><th>Name</th><th>SKU</th><th>Type</th></tr></thead><tbody>';
        d.products.forEach(function(p) {
            var checked = _selectedProducts[p.id] ? 'checked' : '';
            var img = p.image ? '<img src="' + _baseUrl + 'uploads/products/' + esc(p.image) + '" style="width:24px;height:24px;object-fit:cover;border-radius:3px;" class="me-1">' : '<span style="width:24px;height:24px;background:#eee;display:inline-flex;align-items:center;justify-content:center;border-radius:3px;" class="me-1"><i class="bi bi-image" style="font-size:10px;"></i></span>';
            html += '<tr><td><input type="checkbox" class="product-cb" value="' + p.id + '" ' + checked + ' onchange="toggleProduct(' + JSON.stringify(p).replace(/"/g,'&quot;') + ', this.checked)"></td>';
            html += '<td>' + img + esc(p.name) + '</td><td>' + esc(p.sku||'') + '</td><td>' + esc(p.type_name||'') + '</td></tr>';
        });
        html += '</tbody></table></div>';
        document.getElementById('searchResults').innerHTML = html;
    });
}

// ========== SELECT PRODUCT ==========

function toggleProduct(p, checked) {
    if (checked) {
        _selectedProducts[p.id] = p;
        addProductForm(p);
    } else {
        delete _selectedProducts[p.id];
        delete _newFormWeightMaps[p.id];
        var el = document.getElementById('pform_' + p.id);
        if (el) el.remove();
    }
    updateAddCount();
    calcAllWeights();
}

function addProductForm(p) {
    var container = document.getElementById('selectedProductForms');
    var div = document.createElement('div');
    div.id = 'pform_' + p.id;
    div.className = 'border rounded p-2 mb-2';
    div.innerHTML =
        '<div class="d-flex align-items-center gap-2 mb-2 flex-wrap">' +
        '<strong>' + esc(p.name) + '</strong>' +
        '<small class="text-muted">' + esc(p.sku||'') + '</small>' +
        '<span class="badge bg-light text-dark border">' + esc(p.type_name||'') + '</span>' +
        '<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:11px;"><i class="bi bi-calculator"></i> <span class="new-item-wt-value">0.0000</span> g</span>' +
        '</div>' +
        '<input type="hidden" name="products[' + p.id + '][product_id]" value="' + p.id + '">' +
        '<div class="row g-2 mb-2">' +
        '<div class="col-md-4"><label style="font-size:12px;">Pattern</label>' +
        '<select class="form-select form-select-sm" name="products[' + p.id + '][pattern_id]" id="pat_' + p.id + '" onchange="onPatternChange(' + p.id + ')">' +
        '<option value="">-- Base BOM --</option></select></div>' +
        '<div class="col-md-4"><label style="font-size:12px;">Stamp</label>' +
        '<select class="form-select form-select-sm" name="products[' + p.id + '][stamp_id]">' +
        '<option value="">-- No Stamp --</option>' +
        _stamps.map(function(s){ return '<option value="' + s.id + '">' + esc(s.name) + '</option>'; }).join('') +
        '</select></div></div>' +
        '<div id="vars_' + p.id + '" class="mb-1"><small class="text-muted">Loading variations...</small></div>';
    container.appendChild(div);
    document.getElementById('addItemsForm').style.display = 'block';

    post('orders/getProductPatterns', {product_id: p.id}).then(function(d) {
        var sel = document.getElementById('pat_' + p.id);
        (d.patterns||[]).forEach(function(pt) {
            var o = document.createElement('option');
            o.value = pt.id;
            o.textContent = pt.name + (pt.is_default == 1 ? ' (default)' : '');
            sel.appendChild(o);
        });
    });

    post('orders/getProductVariations', {product_id: p.id}).then(function(d) {
        renderVariationGrid(document.getElementById('vars_' + p.id), d.groups||{}, p.id);
        fetchWeightMap(p.id, null);
    });
}

function onPatternChange(productId) {
    var sel = document.getElementById('pat_' + productId);
    var patternId = sel ? sel.value : null;
    fetchWeightMap(productId, patternId);
}

function fetchWeightMap(productId, patternId) {
    var data = {product_id: productId, order_id: _orderId};
    if (patternId) data.pattern_id = patternId;
    post('orders/getProductWeightData', data).then(function(d) {
        _newFormWeightMaps[productId] = d.weight_map || {};
        calcAllWeights();
    });
}

function renderVariationGrid(container, groups, productId) {
    if (!Object.keys(groups).length) {
        container.innerHTML = '<small class="text-muted">No variations.</small>';
        return;
    }
    var html = '';
    Object.keys(groups).forEach(function(gn) {
        var vars = groups[gn];
        html += '<div class="mb-1"><small class="text-muted fw-bold">' + esc(gn) + '</small></div>';
        html += '<div class="table-responsive"><table class="table table-bordered table-sm mb-2" style="font-size:12px;min-width:max-content;"><thead style="background:#eef2f7;"><tr>';
        vars.forEach(function(v) { html += '<th class="text-center" style="min-width:70px;">' + esc(v.name) + '<br><small class="text-muted">' + v.size + '"</small></th>'; });
        html += '</tr></thead><tbody><tr>';
        vars.forEach(function(v) {
            html += '<td class="text-center p-1"><input type="number" name="products[' + productId + '][qty][' + v.id + ']" min="0" step="1" inputmode="numeric" pattern="[0-9]*" class="form-control form-control-sm text-center p-0" style="width:65px;margin:auto;" placeholder="0" oninput="this.value=this.value.replace(/[^0-9]/g,\'\');calcAllWeights();"></td>';
        });
        html += '</tr></tbody></table></div>';
    });
    container.innerHTML = html;
}

function updateAddCount() {
    var n = Object.keys(_selectedProducts).length;
    document.getElementById('addCount').textContent = n;
    document.getElementById('addItemsForm').style.display = n > 0 ? 'block' : 'none';
}

function clearSelection() {
    _selectedProducts = {};
    _newFormWeightMaps = {};
    document.getElementById('selectedProductForms').innerHTML = '';
    document.getElementById('addItemsForm').style.display = 'none';
    document.getElementById('searchResults').innerHTML = '';
    document.querySelectorAll('.product-cb').forEach(function(cb){ cb.checked = false; });
    updateAddCount();
    calcAllWeights();
}

// ---- COPY FROM ABOVE ----
function copyFromAbove(btn) {
    var card    = btn.closest('.card');
    var prevQty = JSON.parse(card.dataset.prevQty || '{}');
    if (!Object.keys(prevQty).length) { alert('No previous item quantities to copy.'); return; }
    var inputs = card.querySelectorAll('input.qty-input[name^="qty["]');
    inputs.forEach(function(inp) {
        var m = inp.name.match(/qty\[(\d+)\]/);
        if (m && prevQty[m[1]] !== undefined) {
            inp.value = parseInt(prevQty[m[1]]) || '';
        }
    });
    calcAllWeights();
}

document.getElementById('productSearch').addEventListener('keydown', function(e){ if(e.key==='Enter'){e.preventDefault();doSearch();} });
</script>
<?php $this->endSection() ?>
