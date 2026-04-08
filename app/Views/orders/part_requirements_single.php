<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="mb-3 d-flex gap-2 flex-wrap align-items-center">
    <a href="<?= base_url('orders/view/' . $order['id']) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Order</a>
    <a href="<?= base_url('orders/partRequirements/' . $order['id']) ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-list-ul"></i> Full Order Part Req</a>
    <a href="<?= base_url('orders/productPartRequirementsPdf/' . $order['id'] . '/item/' . $item['id']) ?>" class="btn btn-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> Download PDF</a>
    <button onclick="window.print()" class="btn btn-outline-dark btn-sm"><i class="bi bi-printer"></i> Print</button>
</div>

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <strong class="fs-6"><?= esc($item['product_name'] ?? '') ?></strong>
                <?php if (!empty($item['sku'])): ?>
                <span class="badge bg-secondary ms-1" style="font-size:11px;"><?= esc($item['sku']) ?></span>
                <?php endif; ?>
                <?php if (!empty($item['pattern_name'])): ?>
                <span class="badge bg-light text-dark border ms-1" style="font-size:11px;"><?= esc($item['pattern_name']) ?></span>
                <?php endif; ?>
                <div class="text-muted small mt-1">Part Requirements &mdash; <?= esc($order['title']) ?></div>
            </div>
            <small class="text-muted"><?= esc($order['client_name'] ?? '') ?></small>
        </div>
    </div>

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
                $part     = $parts[$partId] ?? null;
                $pName    = $part ? $part['name'] : '(Part #' . $partId . ')';
                $deptName = $part['dept_name'] ?? '&mdash;';
                $isMain   = !empty($part['is_main_part']);
                $gattiPkg = (float)($part['gatti'] ?? 0);

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
                            data-item-id="<?= $item['id'] ?>"
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
                        <span><?= number_format($wpp, 6) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end"><?= $wt > 0 ? number_format($wt, 4) : '0.0000' ?></td>
                    <td class="text-end"><?= $gattiReq > 0 ? number_format($gattiReq, 4) : '&mdash;' ?></td>
                    <td><?= $podi ? esc($podi['name']) : '&mdash;' ?></td>
                    <td class="text-center"><?= $podi ? number_format((float)($podi['weight'] ?? 0), 6) : '&mdash;' ?></td>
                    <td class="text-end"><?= $podiQty > 0 ? number_format($podiQty, 2) : '&mdash;' ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($aggregated)): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">No part requirements found. Check that this product has BOM data.</td></tr>
                <?php else: ?>
                <tr class="table-dark fw-bold">
                    <td colspan="3" class="text-end">TOTAL</td>
                    <td class="text-end"><?= number_format($totalPcs, 2) ?></td>
                    <td></td>
                    <td class="text-end"><?= number_format($totalWt, 4) ?></td>
                    <td class="text-end"><?= $totalGatti > 0 ? number_format($totalGatti, 4) : '&mdash;' ?></td>
                    <td colspan="3"></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Calculation Detail Modal -->
<div class="modal fade" id="calcDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-bold">
                    <i class="bi bi-calculator text-info"></i>
                    Calculation Detail &mdash; <span id="calcPartName" class="text-info"></span>
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

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
var BASE_URL = '<?= base_url() ?>';

document.addEventListener('click', function(e) {
    var btn = e.target.closest('.btn-calc-detail');
    if (!btn) return;
    var orderId  = btn.dataset.orderId;
    var partId   = btn.dataset.partId;
    var itemId   = btn.dataset.itemId;
    var partName = btn.dataset.partName;

    document.getElementById('calcPartName').textContent = partName;
    document.getElementById('calcGrandTotal').textContent = '';
    document.getElementById('calcDetailBody').innerHTML = '<div class="text-center text-muted py-3"><i class="bi bi-hourglass-split"></i> Loading...</div>';

    var modal = new bootstrap.Modal(document.getElementById('calcDetailModal'));
    modal.show();

    fetch(BASE_URL + 'orders/partCalcDetail/' + orderId + '/' + partId + '/' + itemId)
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
    blocks.forEach(function(b) {
        var badgeColor = b.source.indexOf('Main') >= 0 ? '#198754' : (b.scale === 'CBOM' ? '#6f42c1' : '#0d6efd');
        html += '<div class="mb-3 border rounded">';
        html += '<div class="px-3 py-2 d-flex align-items-center gap-2 flex-wrap" style="background:#f0f4f8;border-radius:6px 6px 0 0;">';
        html += '<span class="fw-bold" style="font-size:13px;">' + escHtml(b.product) + '</span>';
        if (b.sku) html += '<a href="' + BASE_URL + 'index.php/products/view/' + b.product_id + '" target="_blank" class="badge bg-secondary ms-1 text-decoration-none" style="font-size:11px;">SKU: ' + escHtml(b.sku) + '</a>';
        html += '<span class="badge ms-1" style="background:' + badgeColor + '">' + escHtml(b.source) + '</span>';
        if (b.scale === 'Per Inch' || b.scale === 'Per Kanni') {
            html += '<small class="text-muted ms-2">Factor: <strong>' + b.factor + '</strong></small>';
            html += '<small class="text-muted ms-2">Clasp: <strong>' + b.clasp_size + '"</strong></small>';
            if (b.scale === 'Per Kanni') html += '<small class="text-muted ms-2">Kanni/inch: <strong>' + b.kanni_per_inch + '</strong></small>';
            if (b.bom_pcs && b.bom_pcs != 1) html += '<small class="text-muted ms-2">BOM pcs: <strong>' + b.bom_pcs + '</strong></small>';
            if (b.vg_filter) html += '<small class="text-muted ms-2">Group filter: <strong>' + escHtml(b.vg_filter) + '</strong></small>';
        } else if (b.scale === 'Per Pair') {
            html += '<small class="text-muted ms-2">BOM pcs: <strong>' + b.bom_pcs + '</strong></small>';
            if (b.vg_filter) html += '<small class="text-muted ms-2">Group filter: <strong>' + escHtml(b.vg_filter) + '</strong></small>';
        }
        html += '</div><div class="p-2">';

        if (b.scale === 'Per Inch' || b.scale === 'Per Kanni' || b.source === 'Main Part (Recompute)') {
            html += '<table class="table table-sm table-bordered mb-1" style="font-size:12px;"><thead style="background:#f8f9fa;"><tr><th>Variation</th><th class="text-end">Size"</th><th class="text-end">&minus;Clasp</th><th class="text-end">Actual"</th><th class="text-end">Qty</th><th class="text-end">&times;Factor</th><th class="text-end text-primary">Length (inch)</th></tr></thead><tbody>';
            (b.rows || []).forEach(function(r) {
                html += '<tr><td>' + escHtml(r.variation) + '</td><td class="text-end">' + r.size + '</td><td class="text-end text-danger">&minus;' + r.clasp + '</td><td class="text-end">' + r.actual.toFixed(2) + '</td><td class="text-end">' + r.qty + '</td><td class="text-end">&times;' + r.factor + '</td><td class="text-end fw-bold text-primary">' + r.length.toFixed(4) + '</td></tr>';
            });
            html += '</tbody></table>';
            html += '<div class="ps-2" style="font-size:12px;line-height:1.8;">Total Length = <strong>' + (b.sum_length||0).toFixed(4) + ' inch</strong>';
            if (b.scale === 'Per Kanni' || b.source === 'Main Part (Recompute)') {
                var afterK = (b.sum_length||0) * b.kanni_per_inch;
                html += ' &times; Kanni/inch (' + b.kanni_per_inch + ') = <strong>' + afterK.toFixed(2) + ' kanni</strong>';
            }
            if (b.bom_pcs && b.bom_pcs != 1) html += ' &times; BOM pcs (' + b.bom_pcs + ')';
            html += ' = <span class="badge bg-success fs-6">' + (b.contribution||0).toFixed(2) + ' pcs</span></div>';
        } else if (b.scale === 'Per Pair') {
            html += '<table class="table table-sm table-bordered mb-1" style="font-size:12px;"><thead style="background:#f8f9fa;"><tr><th>Variation</th><th class="text-end">Order Qty (pcs)</th></tr></thead><tbody>';
            (b.rows || []).forEach(function(r) {
                html += '<tr><td>' + escHtml(r.variation) + '</td><td class="text-end">' + r.qty + '</td></tr>';
            });
            html += '</tbody></table>';
            html += '<div class="ps-2" style="font-size:12px;">Total Qty = <strong>' + (b.sum_raw||0) + '</strong> &times; BOM pcs (' + b.bom_pcs + ') = <span class="badge bg-success fs-6">' + (b.contribution||0).toFixed(2) + ' pcs</span></div>';
        } else if (b.scale === 'CBOM') {
            html += '<table class="table table-sm table-bordered mb-1" style="font-size:12px;"><thead style="background:#f8f9fa;"><tr><th>Variation</th><th class="text-end">Order Qty</th><th class="text-end">CBOM Pcs</th><th class="text-end text-primary">= Contribution</th></tr></thead><tbody>';
            (b.rows || []).forEach(function(r) {
                html += '<tr><td>' + escHtml(r.variation) + '</td><td class="text-end">' + r.order_qty + '</td><td class="text-end">' + r.cbom_pcs + '</td><td class="text-end fw-bold text-primary">' + (r.contrib||0).toFixed(2) + '</td></tr>';
            });
            html += '<tr class="table-light fw-bold"><td colspan="3" class="text-end">Total</td><td class="text-end"><span class="badge bg-success">' + (b.contribution||0).toFixed(2) + ' pcs</span></td></tr></tbody></table>';
        }
        html += '</div></div>';
    });
    document.getElementById('calcDetailBody').innerHTML = html;
    var gt = data.grand_total || 0;
    document.getElementById('calcGrandTotal').innerHTML = 'Grand Total: <strong class="text-success">' + gt.toFixed(2) + ' pcs</strong>';
}

function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
<?= $this->endSection() ?>
<?= $this->section('styles') ?>
<style>
@media print {
    .sidebar, .sidebar-toggle, .top-bar, .btn, .alert { display:none!important; }
    .main-content { margin-left:0!important; padding:0!important; }
    .card { border:1px solid #ddd!important; box-shadow:none!important; }
}
</style>
<?= $this->endSection() ?>
