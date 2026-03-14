<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="<?= base_url('orders/view/' . $order['id']) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    <a href="<?= base_url('orders/mainPartSetup/' . $order['id']) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-gear"></i> Edit Main Part Setup</a>
    <a href="<?= base_url('orders/orderSheet/' . $order['id']) ?>" class="btn btn-info btn-sm"><i class="bi bi-file-earmark-text"></i> Order Sheet</a>
    <a href="<?= base_url('orders/touchAnalysis/' . $order['id']) ?>" class="btn btn-outline-warning btn-sm"><i class="bi bi-droplet"></i> Touch Analysis</a>
    <button onclick="window.print()" class="btn btn-outline-dark btn-sm"><i class="bi bi-printer"></i> Print</button>
    <a href="<?= base_url('orders/partRequirementsPdf/' . $order['id']) ?>" class="btn btn-danger btn-sm" target="_blank"><i class="bi bi-file-pdf"></i> PDF</a>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Part Requirements — <?= esc($order['title']) ?></strong>
        <small class="text-muted"><?= esc($order['client_name'] ?? '') ?></small>
    </div>

    <form action="<?= base_url('orders/updateMasterWeights/' . $order['id']) ?>" method="post" id="weightForm">
        <?= csrf_field() ?>
        <div class="card-body p-0">
            <table class="table table-hover mb-0" style="font-size:13px;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Part Name</th>
                        <th>Department</th>
                        <th class="text-end">Total Pcs</th>
                        <th class="text-center">Weight/pc (g)</th>
                        <th class="text-end">Est. Weight (g)</th>
                        <th class="text-end">Gatti Req (g)</th>
                        <th>Podi</th>
                        <th class="text-center">Podi Wt/pc (g)</th>
                        <th class="text-end">Podi Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $totalPcs = 0; $totalWt = 0; $totalGatti = 0; $i = 1; $currentDept = null; ?>
                    <?php foreach ($aggregated as $partId => $data): ?>
                    <?php
                    $part      = $parts[$partId] ?? null;
                    $pName     = $part ? $part['name'] : '(Part #' . $partId . ')';
                    $deptName  = $part['dept_name'] ?? '—';
                    $isMain    = !empty($part['is_main_part']);
                    $gattiPkg  = (float)($part['gatti'] ?? 0);

                    if ($isMain && isset($mainSetup[$partId])) {
                        $wpp = (float)$mainSetup[$partId]['weight_per_kanni'];
                    } else {
                        $wpp = (float)($part['weight'] ?? 0);
                    }

                    $pcs      = round($data['part_pcs'], 2);
                    $wt       = round($pcs * $wpp, 4);
                    $gattiReq = $gattiPkg > 0 ? round($wt * $gattiPkg / 1000, 4) : 0;
                    $podiId   = $data['podi_id'] ?? null;
                    $podi     = $podiId ? ($podies[$podiId] ?? null) : null;
                    $podiQty  = round($data['podi_pcs'] ?? 0, 2);
                    $totalPcs   += $pcs;
                    $totalWt    += $wt;
                    $totalGatti += $gattiReq;
                    ?>
                    <?php if ($deptName !== $currentDept): $currentDept = $deptName; ?>
                    <tr style="background:#e8f0fe;">
                        <td colspan="10" class="py-1 px-3">
                            <small class="fw-bold text-primary"><i class="bi bi-building"></i> <?= esc($deptName) ?></small>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td>
                            <strong><?= esc($pName) ?></strong>
                            <?php if ($isMain): ?><span class="badge bg-success ms-1" style="font-size:10px;">Main</span><?php endif; ?>
                            <button type="button"
                                class="btn btn-outline-info btn-sm btn-calc-detail ms-1"
                                style="padding:1px 6px;font-size:11px;"
                                data-order-id="<?= $order['id'] ?>"
                                data-part-id="<?= $partId ?>"
                                data-part-name="<?= esc($pName) ?>">
                                <i class="bi bi-info-circle"></i>
                            </button>
                            <?php if ($isMain && $pcs == 0): ?>
                            <small class="text-warning d-block" style="font-size:10px;">
                                <i class="bi bi-exclamation-triangle"></i> Set kanni/inch in Main Part Setup
                            </small>
                            <?php endif; ?>
                        </td>
                        <td><small class="text-muted"><?= esc($deptName) ?></small></td>
                        <td class="text-end"><?= number_format($pcs, 2) ?></td>
                        <td class="text-center">
                            <?php if ($isMain): ?>
                            <span class="text-muted" style="font-size:11px;">
                                <?= number_format($wpp, 6) ?><br>
                                <a href="<?= base_url('orders/mainPartSetup/' . $order['id']) ?>" class="text-info" style="font-size:10px;"><i class="bi bi-pencil"></i> setup</a>
                            </span>
                            <?php else: ?>
                            <input type="number"
                                   name="part_weight[<?= $partId ?>]"
                                   value="<?= $wpp ?>"
                                   min="0" step="0.000001"
                                   class="form-control form-control-sm text-end part-wt-input"
                                   style="width:90px;margin:auto;"
                                   data-pcs="<?= $pcs ?>"
                                   data-gatti="<?= $gattiPkg ?>"
                                   oninput="recalcRow(this)">
                            <?php endif; ?>
                        </td>
                        <td class="text-end est-weight"><?= $wt > 0 ? number_format($wt, 4) : '0.0000' ?></td>
                        <td class="text-end gatti-req"><?= $gattiReq > 0 ? number_format($gattiReq, 4) : '—' ?></td>
                        <td><?= $podi ? esc($podi['name']) : '—' ?></td>
                        <td class="text-center">
                            <?php if ($podi): ?>
                            <input type="number"
                                   name="podi_weight[<?= $podiId ?>]"
                                   value="<?= (float)($podi['weight'] ?? 0) ?>"
                                   min="0" step="0.000001"
                                   class="form-control form-control-sm text-end"
                                   style="width:90px;margin:auto;">
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end"><?= $podiQty > 0 ? number_format($podiQty, 2) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($aggregated)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">No part requirements calculated. Check that products have BOM data.</td></tr>
                    <?php else: ?>
                    <tr class="table-dark fw-bold">
                        <td colspan="3" class="text-end">TOTAL</td>
                        <td class="text-end"><?= number_format($totalPcs, 2) ?></td>
                        <td></td>
                        <td class="text-end" id="totalEstWeight"><?= number_format($totalWt, 4) ?></td>
                        <td class="text-end" id="totalGattiReq"><?= $totalGatti > 0 ? number_format($totalGatti, 4) : '—' ?></td>
                        <td colspan="3"></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($aggregated)): ?>
        <div class="p-3 border-top d-flex align-items-center gap-3">
            <button type="submit" class="btn btn-warning btn-sm">
                <i class="bi bi-save"></i> Save Weights to Master
            </button>
            <small class="text-muted">
                <i class="bi bi-exclamation-triangle text-warning"></i>
                Saving updates the master part/podi weight used for <strong>ALL products</strong>.
            </small>
        </div>
        <?php endif; ?>
    </form>
</div>

<!-- Calculation Detail Modal -->
<div class="modal fade" id="calcDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-calculator text-info"></i>
                    Calculation Detail — <span id="calcPartName" class="text-info"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="calcDetailBody" style="max-height:75vh;overflow-y:auto;">
                <div class="text-center text-muted py-3"><i class="bi bi-hourglass-split"></i> Loading...</div>
            </div>
            <div class="modal-footer py-2">
                <span class="me-auto text-muted" style="font-size:12px;" id="calcGrandTotal"></span>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
<script>
function recalcRow(input) {
    var row    = input.closest('tr');
    var pcs    = parseFloat(input.dataset.pcs)   || 0;
    var gatti  = parseFloat(input.dataset.gatti) || 0;
    var wt     = parseFloat(input.value)         || 0;
    var estWt  = pcs * wt;
    row.querySelector('.est-weight').textContent  = estWt.toFixed(4);
    var gattiCell = row.querySelector('.gatti-req');
    if (gattiCell) {
        var gr = gatti > 0 ? (estWt * gatti / 1000) : 0;
        gattiCell.textContent = gr > 0 ? gr.toFixed(4) : '—';
    }
    recalcTotal();
}

function recalcTotal() {
    var sumWt    = 0;
    var sumGatti = 0;
    document.querySelectorAll('.est-weight').forEach(function(cell) { sumWt += parseFloat(cell.textContent) || 0; });
    document.querySelectorAll('.gatti-req').forEach(function(cell)  { sumGatti += parseFloat(cell.textContent) || 0; });
    var tw = document.getElementById('totalEstWeight');
    var tg = document.getElementById('totalGattiReq');
    if (tw) tw.textContent = sumWt.toFixed(4);
    if (tg) tg.textContent = sumGatti > 0 ? sumGatti.toFixed(4) : '—';
}

// Calculation detail modal
document.addEventListener('click', function(e) {
    var btn = e.target.closest('.btn-calc-detail');
    if (!btn) return;
    var orderId  = btn.dataset.orderId;
    var partId   = btn.dataset.partId;
    var partName = btn.dataset.partName;

    document.getElementById('calcPartName').textContent = partName;
    document.getElementById('calcGrandTotal').textContent = '';
    document.getElementById('calcDetailBody').innerHTML = '<div class="text-center text-muted py-3"><i class="bi bi-hourglass-split"></i> Loading...</div>';

    var modal = new bootstrap.Modal(document.getElementById('calcDetailModal'));
    modal.show();

    fetch('<?= base_url('orders/partCalcDetail') ?>/' + orderId + '/' + partId)
        .then(function(r) { return r.json(); })
        .then(function(data) { renderCalcDetail(data); })
        .catch(function() {
            document.getElementById('calcDetailBody').innerHTML = '<div class="alert alert-danger">Failed to load detail.</div>';
        });
});

function renderCalcDetail(data) {
    if (data.error) {
        document.getElementById('calcDetailBody').innerHTML = '<div class="alert alert-danger">' + data.error + '</div>';
        return;
    }

    var blocks = data.blocks || [];
    if (blocks.length === 0) {
        document.getElementById('calcDetailBody').innerHTML = '<div class="text-muted text-center py-3">No contributing rows found for this part.</div>';
        return;
    }

    var html = '';
    blocks.forEach(function(b, idx) {
        var badgeClass = b.scale === 'CBOM' ? 'bg-purple' : (b.source.indexOf('Main') >= 0 ? 'bg-success' : 'bg-primary');
        html += '<div class="mb-3 border rounded">';
        html += '<div class="px-3 py-2 d-flex align-items-center gap-2" style="background:#f0f4f8;border-radius:6px 6px 0 0;">';
        html += '<span class="fw-bold" style="font-size:13px;">' + escHtml(b.product) + '</span>';
        if (b.sku) {
            html += '<a href="' + BASE_URL + 'index.php/products/view/' + b.product_id + '" target="_blank" class="badge bg-secondary ms-1 text-decoration-none" style="font-size:11px;" title="Open product in new tab">SKU: ' + escHtml(b.sku) + '</a>';
        }
        html += '<span class="badge ' + badgeClass + ' ms-1" style="background:' + (b.source.indexOf('Main')>=0?'#198754':(b.scale==='CBOM'?'#6f42c1':'#0d6efd')) + '">' + escHtml(b.source) + '</span>';

        if (b.scale === 'Per Inch' || b.scale === 'Per Kanni') {
            html += '<small class="text-muted ms-2">Factor: <strong>' + b.factor + '</strong></small>';
            html += '<small class="text-muted ms-2">Clasp: <strong>' + b.clasp_size + '"</strong></small>';
            if (b.scale === 'Per Kanni') {
                html += '<small class="text-muted ms-2">Kanni/inch: <strong>' + b.kanni_per_inch + '</strong></small>';
            }
            if (b.bom_pcs && b.bom_pcs != 1) {
                html += '<small class="text-muted ms-2">BOM pcs: <strong>' + b.bom_pcs + '</strong></small>';
            }
            if (b.vg_filter) {
                html += '<small class="text-muted ms-2">Group filter: <strong>' + escHtml(b.vg_filter) + '</strong></small>';
            }
        } else if (b.scale === 'Per Pair') {
            html += '<small class="text-muted ms-2">BOM pcs: <strong>' + b.bom_pcs + '</strong></small>';
            if (b.vg_filter) {
                html += '<small class="text-muted ms-2">Group filter: <strong>' + escHtml(b.vg_filter) + '</strong></small>';
            }
        }
        html += '</div>';

        html += '<div class="p-2">';

        if (b.scale === 'Per Inch' || b.scale === 'Per Kanni' || b.source === 'Main Part (Recompute)') {
            html += '<table class="table table-sm table-bordered mb-1" style="font-size:12px;">';
            html += '<thead style="background:#f8f9fa;"><tr><th>Variation</th><th class="text-end">Size"</th><th class="text-end">−Clasp</th><th class="text-end">Actual"</th><th class="text-end">Qty</th><th class="text-end">×Factor</th><th class="text-end text-primary">Length (inch)</th></tr></thead><tbody>';
            (b.rows || []).forEach(function(r) {
                html += '<tr><td>' + escHtml(r.variation) + '</td>';
                html += '<td class="text-end">' + r.size + '</td>';
                html += '<td class="text-end text-danger">−' + r.clasp + '</td>';
                html += '<td class="text-end">' + r.actual.toFixed(2) + '</td>';
                html += '<td class="text-end">' + r.qty + '</td>';
                html += '<td class="text-end">×' + r.factor + '</td>';
                html += '<td class="text-end fw-bold text-primary">' + r.length.toFixed(4) + '</td></tr>';
            });
            html += '</tbody></table>';
            html += '<div class="ps-2" style="font-size:12px;line-height:1.8;">';
            html += '<span class="text-muted">Total Length</span> = <strong>' + (b.sum_length||0).toFixed(4) + ' inch</strong>';
            if (b.scale === 'Per Kanni' || b.source === 'Main Part (Recompute)') {
                html += ' &times; <span class="text-muted">Kanni/inch</span> (' + b.kanni_per_inch + ')';
                var afterKanni = (b.sum_length||0) * b.kanni_per_inch;
                html += ' = <strong>' + afterKanni.toFixed(2) + ' kanni</strong>';
                if (b.bom_pcs && b.bom_pcs != 1) {
                    html += ' &times; <span class="text-muted">BOM pcs</span> (' + b.bom_pcs + ')';
                }
            } else {
                if (b.bom_pcs && b.bom_pcs != 1) {
                    html += ' &times; <span class="text-muted">BOM pcs</span> (' + b.bom_pcs + ')';
                }
            }
            html += ' = <span class="badge bg-success fs-6">' + (b.contribution||0).toFixed(2) + ' pcs</span>';
            html += '</div>';

        } else if (b.scale === 'Per Pair') {
            html += '<table class="table table-sm table-bordered mb-1" style="font-size:12px;">';
            html += '<thead style="background:#f8f9fa;"><tr><th>Variation</th><th class="text-end">Order Qty (pcs)</th></tr></thead><tbody>';
            (b.rows || []).forEach(function(r) {
                html += '<tr><td>' + escHtml(r.variation) + '</td>';
                html += '<td class="text-end">' + r.qty + '</td></tr>';
            });
            html += '</tbody></table>';
            html += '<div class="ps-2" style="font-size:12px;">';
            html += '<span class="text-muted">Total Qty</span> = <strong>' + (b.sum_raw||0) + '</strong>';
            html += ' &times; <span class="text-muted">BOM pcs</span> (' + b.bom_pcs + ')';
            html += ' = <span class="badge bg-success fs-6">' + (b.contribution||0).toFixed(2) + ' pcs</span>';
            html += '</div>';

        } else if (b.scale === 'CBOM') {
            html += '<table class="table table-sm table-bordered mb-1" style="font-size:12px;">';
            html += '<thead style="background:#f8f9fa;"><tr><th>Variation</th><th class="text-end">Order Qty</th><th class="text-end">CBOM Pcs</th><th class="text-end text-primary">= Contribution</th></tr></thead><tbody>';
            (b.rows || []).forEach(function(r) {
                html += '<tr><td>' + escHtml(r.variation) + '</td>';
                html += '<td class="text-end">' + r.order_qty + '</td>';
                html += '<td class="text-end">' + r.cbom_pcs + '</td>';
                html += '<td class="text-end fw-bold text-primary">' + (r.contrib||0).toFixed(2) + '</td></tr>';
            });
            html += '<tr class="table-light fw-bold"><td colspan="3" class="text-end">Total</td>';
            html += '<td class="text-end"><span class="badge bg-success">' + (b.contribution||0).toFixed(2) + ' pcs</span></td></tr>';
            html += '</tbody></table>';
        }

        html += '</div></div>';
    });

    document.getElementById('calcDetailBody').innerHTML = html;

    var gt = data.grand_total || 0;
    document.getElementById('calcGrandTotal').innerHTML =
        'Grand Total: <strong class="text-success">' + gt.toFixed(2) + ' pcs</strong> (sum of all contributing blocks)';
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
<?php $this->endSection() ?>

<?php $this->section('styles') ?>
<style>
@media print {
    .sidebar, .sidebar-toggle, .top-bar, .btn, .alert, form .border-top, .btn-calc-detail { display:none!important; }
    .main-content { margin-left:0!important; padding:0!important; }
    .card { border:1px solid #ddd!important; box-shadow:none!important; }
    input.form-control { border:none!important; background:transparent!important; padding:0!important; width:auto!important; }
}
</style>
<?php $this->endSection() ?>
