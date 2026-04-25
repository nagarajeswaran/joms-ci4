<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<?php
$statusColors = ['draft'=>'secondary','confirmed'=>'primary','production'=>'warning','completed'=>'success'];
$sc = $statusColors[$order['status']] ?? 'secondary';
$imgBase = upload_url('products/');
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
                <?php if ($order['status'] === 'draft'): ?>
                    <a href="<?= base_url('orders/edit/' . $order['id']) ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i> Edit Header</a>
                    <?php if (!empty($items)): ?>
                    <a href="<?= base_url('orders/preview/' . $order['id']) ?>" class="btn btn-primary btn-sm"><i class="bi bi-eye"></i> Preview & Confirm</a>
                    <?php endif; ?>
                <?php elseif ($canEdit): ?>
                    <span class="text-muted small"><i class="bi bi-info-circle"></i> Edit products &amp; quantities below</span>
                <?php endif; ?>
                <?php if (in_array($order['status'], ['confirmed','production','completed'])): ?>
                    <a href="<?= base_url('orders/mainPartSetup/' . $order['id']) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-gear"></i> Main Part Setup</a>
                    <a href="<?= base_url('orders/partRequirements/' . $order['id']) ?>" class="btn btn-success btn-sm"><i class="bi bi-list-check"></i> Part Requirements</a>
                    <a href="<?= base_url('orders/orderSheet/' . $order['id']) ?>" class="btn btn-info btn-sm"><i class="bi bi-file-earmark-text"></i> Order Sheet</a>
                    <a href="<?= base_url('orders/touchAnalysis/' . $order['id']) ?>" class="btn btn-outline-warning btn-sm"><i class="bi bi-droplet"></i> Touch Analysis</a>
                    <button type="button" id="touchToggleBtn" class="btn btn-outline-warning btn-sm" onclick="toggleTouchMode()"><i class="bi bi-droplet"></i> Show Touch %</button>
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

<?php if (!empty($items)): ?>
<?php
// Collect all unique group names across all items
$_allGroups = [];
foreach ($items as $_itm) {
    foreach (array_keys($_itm['variation_groups'] ?? []) as $_gn) {
        if (!in_array($_gn, $_allGroups)) $_allGroups[] = $_gn;
    }
}
?>
<div class="card mb-3" id="pcsBar" style="border-left:4px solid #0d6efd;">
    <div class="card-body py-2 d-flex align-items-center gap-3 flex-wrap">
        <span style="font-size:13px;color:#555;"><i class="bi bi-boxes text-primary"></i> Total PCS:</span>
        <span class="fw-bold" style="font-size:1.15rem;color:#0d6efd;" id="summary-total-pcs">0</span>
        <?php foreach ($_allGroups as $_gn): ?>
        <span class="text-muted" style="font-size:13px;">|</span>
        <span style="font-size:13px;color:#555;"><?= esc($_gn) ?>:</span>
        <span class="fw-semibold" style="color:#0d6efd;" id="summary-grp-<?= esc(strtolower(preg_replace('/\s+/','_',$_gn))) ?>">0</span>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if ($canEdit): ?>
<div class="mb-2">
<button type="button" id="saveAllBtn" class="btn btn-success btn-sm d-none" onclick="saveAllItems()">
    <i class="bi bi-floppy"></i> Save All Changes (<span id="dirtyCount">0</span>)
</button>
</div>
<?php endif; ?>

<!-- Existing Items -->
<?php foreach ($items as $idx => $item): ?>
<div class="card mb-3 order-item-card"
     data-item-id="<?= $item['id'] ?>"
     data-item-idx="<?= $idx ?>"
     data-product-id="<?= $item['product_id'] ?>"
     data-type-id="<?= $item['product_type_id'] ?>"
     data-prev-qty='<?= $item['prev_qty_json'] ?>'
     data-weight-map='<?= htmlspecialchars(json_encode($item['weight_map'] ?? []), ENT_QUOTES, 'UTF-8') ?>'>
    <div class="card-header d-flex justify-content-between align-items-center py-2" style="background:#f8f9fa;">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-secondary">#<?= $idx + 1 ?></span>
            <?php if (!empty($item['product_image'])): ?>
            <img src="<?= $imgBase . esc($item['product_image']) ?>"
                 alt="" style="width:36px;height:36px;object-fit:cover;border-radius:4px;border:1px solid #ddd;cursor:pointer;"
                 data-img="<?= $imgBase . esc($item['product_image']) ?>"
                 data-name="<?= esc($item['product_name'] ?? '') ?>"
                 class="order-thumb-preview">
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
            <?php if (in_array($order['status'], ['confirmed','production','completed'])): ?>
            <a href="<?= base_url('orders/productPartRequirements/' . $order['id'] . '/item/' . $item['id']) ?>" class="badge bg-info-subtle text-info border border-info-subtle ms-1 text-decoration-none" style="font-size:11px;" target="_blank" title="Part Requirements for this product"><i class="bi bi-list-check"></i> Part Req</a>
            <?php endif; ?>
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
                    <select class="form-select form-select-sm" name="pattern_id" onchange="onExistingPatternChange(this)">
                        <option value="">-- No Pattern (Base BOM) --</option>
                        <?php foreach ($item['patterns'] as $pt): ?>
                        <option value="<?= $pt['id'] ?>" <?= $item['pattern_id'] == $pt['id'] ? 'selected' : '' ?>><?= esc(!empty($pt['tamil_name']) ? $pt['tamil_name'] : $pt['name']) ?><?= $pt['is_default'] ? ' (default)' : '' ?></option>
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
                    <tr class="qty-row">
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
                    <tr class="touch-row d-none" style="background:#fff8e1;">
                        <?php foreach ($vars as $v): ?>
                        <td class="text-center p-1">
                            <span class="var-touch-value fw-bold" style="color:#e65c00;" data-vid="<?= $v['id'] ?>">—</span>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr class="table-light">
                        <?php foreach ($vars as $v): ?>
                        <td class="text-center p-0"><small class="text-muted"><span class="var-wt-value" data-vid="<?= $v['id'] ?>">0.0000</span> g</small></td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-muted" style="font-size:12px;">No variations defined for this product type.</div>
        <?php endif; ?>

        <?php if (!empty($item['variation_groups'])): ?>
        <div class="mt-1 pt-1 border-top d-flex flex-wrap gap-3" style="font-size:12px;">
            <?php foreach (array_keys($item['variation_groups']) as $_g): ?>
            <span class="text-muted"><?= esc($_g) ?>: <span class="fw-semibold text-dark item-grp-total" data-group="<?= esc($_g) ?>">0</span></span>
            <?php endforeach; ?>
            <span class="text-muted ms-1">| Total: <span class="fw-semibold text-primary item-total-pcs">0</span> pcs</span>
        </div>
        <?php endif; ?>

        <?php if ($canEdit): ?>
            <button type="submit" class="btn btn-success btn-sm mt-1 item-save-btn"><i class="bi bi-check"></i> Save</button>
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
<div id="stickyWeightBar" style="position:fixed;bottom:0;left:0;right:0;z-index:1050;background:#212529;color:#fff;padding:8px 20px;display:flex;align-items:center;justify-content:flex-end;gap:16px;font-size:14px;box-shadow:0 -2px 8px rgba(0,0,0,0.3);"><span><i class="bi bi-calculator"></i> Order Est. Weight:</span><span class="fw-bold fs-6"><span id="grandEstWeightSticky">0.0000</span> g</span></div>
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
var _vTouchMap  = <?= json_encode($variationTouchMap ?? []) ?>;
var _touchMode  = false;

function toggleTouchMode() {
    if (Object.keys(_vTouchMap).length === 0) {
        alert('No touch values found. Please save touch values on the Touch Analysis page first.');
        return;
    }
    _touchMode = !_touchMode;
    var btn = document.getElementById('touchToggleBtn');

    document.querySelectorAll('.order-item-card').forEach(function(card) {
        var itemId = card.dataset.itemId;
        var touchData = _vTouchMap[itemId] || {};

        if (_touchMode) {
            card.querySelectorAll('.var-touch-value').forEach(function(span) {
                var vid = span.dataset.vid;
                var val = touchData[vid];
                span.textContent = (val !== undefined && val > 0) ? val.toFixed(2) + '%' : '—';
            });
        }

        card.querySelectorAll('.qty-row').forEach(function(tr) {
            tr.classList.toggle('d-none', _touchMode);
        });
        card.querySelectorAll('.touch-row').forEach(function(tr) {
            tr.classList.toggle('d-none', !_touchMode);
        });
    });

    if (_touchMode) {
        btn.innerHTML = '<i class="bi bi-droplet-fill"></i> Show Qty';
        btn.classList.remove('btn-outline-warning');
        btn.classList.add('btn-warning');
    } else {
        btn.innerHTML = '<i class="bi bi-droplet"></i> Show Touch %';
        btn.classList.remove('btn-warning');
        btn.classList.add('btn-outline-warning');
    }
}

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
                var varWt = qty * wpp;
                itemWt += varWt;
                var varSpan = card.querySelector('.var-wt-value[data-vid="' + m[1] + '"]');
                if (varSpan) varSpan.textContent = varWt.toFixed(4);
            }
        });
        var badge = card.querySelector('.item-wt-value');
        if (badge) badge.textContent = itemWt.toFixed(4);
        grand += itemWt;
    });

    // New product forms (not yet saved) — keyed by productId_patternId
    Object.keys(_newFormWeightMaps).forEach(function(key) {
        var wmap = _newFormWeightMaps[key] || {};
        var rowEl = document.getElementById('patrow_' + key);
        if (!rowEl) return; // row was removed
        var rowWt = 0;
        rowEl.querySelectorAll('input[type="number"][name*="[qty]["]').forEach(function(inp) {
            var m = inp.name.match(/\[qty\]\[(\d+)\]/);
            if (m) {
                var qty = parseFloat(inp.value) || 0;
                var wpp = parseFloat(wmap[m[1]]) || 0;
                rowWt += qty * wpp;
            }
        });
        var wtSpan = document.getElementById('wt_' + key);
        if (wtSpan) wtSpan.textContent = rowWt.toFixed(4);
        grand += rowWt;
    });

    var el = document.getElementById('grandEstWeight'); if (el) el.textContent = grand.toFixed(4); var el2 = document.getElementById('grandEstWeightSticky'); if (el2) el2.textContent = grand.toFixed(4);

    // ---- PCS totals (per-card + grand header) ----
    var grandGroups = {};
    var grandTotal  = 0;

    document.querySelectorAll('.order-item-card').forEach(function(card) {
        var cardGroupTotals = {};
        var cardTotal = 0;
        card.querySelectorAll('input.qty-input').forEach(function(inp) {
            // Group label div is a direct sibling BEFORE the table inside .table-responsive
            var table = inp.closest('table');
            var grp = '';
            if (table) {
                var prev = table.previousElementSibling;
                if (prev) grp = prev.textContent.trim();
            }
            var qty = parseInt(inp.value) || 0;
            cardGroupTotals[grp] = (cardGroupTotals[grp] || 0) + qty;
            cardTotal += qty;
            grandGroups[grp] = (grandGroups[grp] || 0) + qty;
            grandTotal += qty;
        });
        // Update per-card group spans
        card.querySelectorAll('.item-grp-total').forEach(function(el) {
            el.textContent = cardGroupTotals[el.dataset.group] || 0;
        });
        var t = card.querySelector('.item-total-pcs');
        if (t) t.textContent = cardTotal;
    });

    // Update grand header
    var pcsEl = document.getElementById('summary-total-pcs');
    if (pcsEl) pcsEl.textContent = grandTotal;
    Object.keys(grandGroups).forEach(function(g) {
        var spanId = 'summary-grp-' + g.toLowerCase().replace(/\s+/g, '_');
        var gEl = document.getElementById(spanId);
        if (gEl) gEl.textContent = grandGroups[g];
    });
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

// stores variation groups per product for reuse when checking patterns
var _productVarGroups = {};

function addProductForm(p) {
    var container = document.getElementById('selectedProductForms');
    var div = document.createElement('div');
    div.id = 'pform_' + p.id;
    div.className = 'border rounded p-2 mb-2';

    var stampOpts = '<option value="">-- No Stamp --</option>' +
        _stamps.map(function(s){ return '<option value="' + s.id + '">' + esc(s.name) + '</option>'; }).join('');

    div.innerHTML =
        '<div class="d-flex align-items-center gap-2 mb-2 flex-wrap">' +
        '<strong>' + esc(p.name) + '</strong>' +
        '<small class="text-muted">' + esc(p.sku||'') + '</small>' +
        '<span class="badge bg-light text-dark border">' + esc(p.type_name||'') + '</span>' +
        '</div>' +
        '<div class="row g-2 mb-2">' +
        '<div class="col-md-4"><label style="font-size:12px;">Stamp <small class="text-muted">(shared)</small></label>' +
        '<select class="form-select form-select-sm" id="stamp_' + p.id + '">' + stampOpts + '</select></div>' +
        '</div>' +
        '<div class="mb-1"><label style="font-size:12px;" class="fw-semibold">Patterns — check to add:</label></div>' +
        '<div id="pat_checks_' + p.id + '" class="mb-1"><small class="text-muted"><i class="bi bi-hourglass-split"></i> Loading patterns...</small></div>' +
        '<div id="pat_rows_' + p.id + '"></div>';

    container.appendChild(div);
    document.getElementById('addItemsForm').style.display = 'block';

    // Load patterns and variations in parallel
    Promise.all([
        post('orders/getProductPatterns', {product_id: p.id}),
        post('orders/getProductVariations', {product_id: p.id})
    ]).then(function(results) {
        var patterns = results[0].patterns || [];
        var groups   = results[1].groups   || {};
        _productVarGroups[p.id] = groups;

        // Render pattern checkboxes
        var checksHtml = patterns.map(function(pt) {
            var label = esc(pt.tamil_name || pt.name) + (parseInt(pt.is_default) === 1 ? ' <span class="text-muted">(default)</span>' : '');
            var checked = parseInt(pt.is_default) === 1 ? 'checked' : '';
            return '<div class="form-check form-check-inline me-3">' +
                '<input class="form-check-input" type="checkbox" id="patcb_' + p.id + '_' + pt.id + '" ' + checked +
                ' onchange="togglePatternRow(' + p.id + ', ' + JSON.stringify(pt).replace(/"/g,'&quot;') + ', this.checked)">' +
                '<label class="form-check-label" style="font-size:13px;" for="patcb_' + p.id + '_' + pt.id + '">' + label + '</label>' +
                '</div>';
        }).join('');
        document.getElementById('pat_checks_' + p.id).innerHTML = checksHtml || '<small class="text-muted">No patterns.</small>';

        // Auto-add default pattern row
        patterns.forEach(function(pt) {
            if (parseInt(pt.is_default) === 1) {
                togglePatternRow(p.id, pt, true);
            }
        });
    });
}

function togglePatternRow(productId, pt, checked) {
    var rowsContainer = document.getElementById('pat_rows_' + productId);
    var rowId = 'patrow_' + productId + '_' + pt.id;
    var key   = productId + '_' + pt.id;

    if (!checked) {
        var el = document.getElementById(rowId);
        if (el) el.remove();
        // remove weight tracking for this key
        delete _newFormWeightMaps[key];
        calcAllWeights();
        return;
    }

    if (document.getElementById(rowId)) return; // already exists

    var stampSel = document.getElementById('stamp_' + productId);
    var stampId  = stampSel ? stampSel.value : '';

    var patLabel = parseInt(pt.is_default) === 1
        ? '<span class="text-muted fst-italic">Default</span>'
        : '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">' + esc(pt.name) + '</span>';

    var groups = _productVarGroups[productId] || {};
    var varsHtml = renderVariationGridHtml(groups, key);

    var row = document.createElement('div');
    row.id = rowId;
    row.className = 'border-start border-2 border-primary ps-2 mb-2 pt-1';
    row.innerHTML =
        '<div class="d-flex align-items-center gap-2 mb-1">' +
        patLabel +
        '<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size:11px;"><i class="bi bi-calculator"></i> <span id="wt_' + key + '">0.0000</span> g</span>' +
        '</div>' +
        '<input type="hidden" name="products[' + key + '][product_id]" value="' + productId + '">' +
        '<input type="hidden" name="products[' + key + '][pattern_id]" value="' + pt.id + '">' +
        '<input type="hidden" name="products[' + key + '][stamp_id]" id="stamp_hidden_' + key + '" value="' + esc(stampId) + '">' +
        varsHtml;
    rowsContainer.appendChild(row);

    // Sync stamp hidden input when stamp dropdown changes
    if (stampSel) {
        stampSel.addEventListener('change', function() {
            var h = document.getElementById('stamp_hidden_' + key);
            if (h) h.value = this.value;
        });
    }

    // Fetch weight map for this pattern
    var data = {product_id: productId, order_id: _orderId, pattern_id: pt.id};
    post('orders/getProductWeightData', data).then(function(d) {
        _newFormWeightMaps[key] = d.weight_map || {};
        calcAllWeights();
    });
}

function fetchWeightMap(productId, patternId) {
    // kept for backward compatibility (existing item pattern change)
    var data = {product_id: productId, order_id: _orderId};
    if (patternId) data.pattern_id = patternId;
    post('orders/getProductWeightData', data).then(function(d) {
        _newFormWeightMaps[productId] = d.weight_map || {};
        calcAllWeights();
    });
}

function renderVariationGridHtml(groups, key) {
    if (!Object.keys(groups).length) return '<small class="text-muted">No variations.</small>';
    var html = '';
    Object.keys(groups).forEach(function(gn) {
        var vars = groups[gn];
        html += '<div class="mb-1"><small class="text-muted fw-bold">' + esc(gn) + '</small></div>';
        html += '<div class="table-responsive"><table class="table table-bordered table-sm mb-2" style="font-size:12px;min-width:max-content;"><thead style="background:#eef2f7;"><tr>';
        vars.forEach(function(v) { html += '<th class="text-center" style="min-width:70px;">' + esc(v.name) + '<br><small class="text-muted">' + (v.size ? v.size+'"' : '') + '</small></th>'; });
        html += '</tr></thead><tbody><tr>';
        vars.forEach(function(v) {
            html += '<td class="text-center p-1"><input type="number" name="products[' + key + '][qty][' + v.id + ']" min="0" step="1" inputmode="numeric" pattern="[0-9]*" class="form-control form-control-sm text-center p-0" style="width:65px;margin:auto;" placeholder="0" oninput="this.value=this.value.replace(/[^0-9]/g,\'\');calcAllWeights();"></td>';
        });
        html += '</tr></tbody></table></div>';
    });
    return html;
}

function renderVariationGrid(container, groups, productId) {
    container.innerHTML = renderVariationGridHtml(groups, productId);
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

// ========== EXISTING ITEM PATTERN CHANGE ==========
function onExistingPatternChange(sel) {
    var card = sel.closest('.order-item-card');
    if (!card) return;
    var productId = card.dataset.productId;
    var patternId = sel.value || '';
    var data = {product_id: productId, order_id: _orderId};
    if (patternId) data.pattern_id = patternId;
    post('orders/getProductWeightData', data).then(function(d) {
        card.dataset.weightMap = JSON.stringify(d.weight_map || {});
        calcAllWeights();
        markDirty(card);
    });
}

// ========== DIRTY TRACKING ==========
function markDirty(card) {
    if (!card) return;
    card.dataset.dirty = '1';
    var saveBtn = card.querySelector('.item-save-btn');
    if (saveBtn) { saveBtn.classList.remove('btn-success'); saveBtn.classList.add('btn-warning'); }
    updateDirtyCount();
}
function clearDirty(card) {
    delete card.dataset.dirty;
    var saveBtn = card.querySelector('.item-save-btn');
    if (saveBtn) { saveBtn.classList.remove('btn-warning'); saveBtn.classList.add('btn-success'); }
    updateDirtyCount();
}
function updateDirtyCount() {
    var n = document.querySelectorAll('.order-item-card[data-dirty="1"]').length;
    var el = document.getElementById('dirtyCount'); if (el) el.textContent = n;
    var btn = document.getElementById('saveAllBtn'); if (btn) btn.classList.toggle('d-none', n === 0);
}
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('qty-input')) markDirty(e.target.closest('.order-item-card'));
});
document.addEventListener('change', function(e) {
    var card = e.target.closest('.order-item-card');
    if (card && e.target.name === 'stamp_id') markDirty(card);
});

// ========== SAVE ALL ==========
function saveAllItems() {
    var dirty = document.querySelectorAll('.order-item-card[data-dirty="1"]');
    if (!dirty.length) return;
    var btn = document.getElementById('saveAllBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';
    var promises = Array.from(dirty).map(function(card) {
        var itemId = card.dataset.itemId;
        var data = {
            pattern_id: (card.querySelector('[name="pattern_id"]') || {}).value || '',
            stamp_id:   (card.querySelector('[name="stamp_id"]')   || {}).value || '',
        };
        card.querySelectorAll('input.qty-input').forEach(function(inp) {
            var m = inp.name.match(/qty\[(\d+)\]/);
            if (m) data['qty[' + m[1] + ']'] = inp.value || '0';
        });
        return post('orders/saveItemQtyAjax/' + itemId, data).then(function(r) {
            if (r.success) clearDirty(card);
        });
    });
    Promise.all(promises).then(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-floppy"></i> Save All Changes (<span id="dirtyCount">0</span>)';
        updateDirtyCount();
        var t = document.createElement('div');
        t.className = 'alert alert-success alert-dismissible position-fixed top-0 end-0 m-3';
        t.style.zIndex = 9999;
        t.innerHTML = '<i class="bi bi-check-circle"></i> All changes saved. <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>';
        document.body.appendChild(t);
        setTimeout(function(){ if (t.parentElement) t.remove(); }, 3000);
    });
}

// ========== UNSAVED WARNING ==========
window.addEventListener('beforeunload', function(e) {
    var dirty = document.querySelectorAll('.order-item-card[data-dirty="1"]');
    if (dirty.length > 0) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>

<div id="orderThumbModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:8px;padding:12px;max-width:340px;width:90%;text-align:center;position:relative;">
    <button id="orderThumbModalClose" style="position:absolute;top:6px;right:10px;background:none;border:none;font-size:18px;cursor:pointer;">&times;</button>
    <p id="orderThumbModalName" style="font-weight:600;margin-bottom:8px;"></p>
    <img id="orderThumbModalImg" src="" style="max-width:100%;max-height:300px;object-fit:contain;border-radius:4px;">
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  document.addEventListener('click', function(e) {
    if (!e.target.classList.contains('order-thumb-preview')) return;
    document.getElementById('orderThumbModalImg').src = e.target.dataset.img;
    document.getElementById('orderThumbModalName').textContent = e.target.dataset.name;
    document.getElementById('orderThumbModal').style.display = 'flex';
  });
  document.getElementById('orderThumbModalClose').addEventListener('click', function() {
    document.getElementById('orderThumbModal').style.display = 'none';
  });
  document.getElementById('orderThumbModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
  });
});
</script>
<?php $this->endSection() ?>