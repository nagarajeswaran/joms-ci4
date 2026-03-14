<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="<?= base_url('products/view/' . $product['id']) ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Product</a>
        <strong class="ms-2"><?= esc($product['name']) ?></strong>
        <span class="text-muted">(<?= esc($product['product_type_name'] ?? '') ?>)</span>
    </div>
    <?php if (!empty($templates)): ?>
    <button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#importCbomModal">
        <i class="bi bi-download"></i> Import Template
    </button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-header"><strong>Customize Bill of Material (Variation Wise)</strong></div>
    <div class="card-body">
        <?php if (empty($variations)): ?>
        <div class="alert alert-warning">No variations found for this product type. Add variations to the product type first.</div>
        <?php else: ?>
        <p class="text-muted" style="font-size:12px;">Enter exact part quantity per variation. No calculation logic - directly multiplied with order quantity.</p>
        <form action="<?= base_url('products/saveCbom/' . $product['id']) ?>" method="post">
            <?= csrf_field() ?>
            <div id="cbom_container">
                <?php
                $cbomItems = !empty($cbom) ? $cbom : [[]];
                foreach ($cbomItems as $ci => $cbomRow):
                ?>
                <div class="cbom-row mb-3 p-3 border rounded" style="background:#f8faff;" data-index="<?= $ci ?>">
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <label class="form-label">Part</label>
                            <select class="form-select form-select-sm" name="cbom_part_id[]">
                                <option value="">Select Part</option>
                                <?php foreach ($parts as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= (($cbomRow['part_id'] ?? '') == $p['id']) ? 'selected' : '' ?>><?= esc($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Podi</label>
                            <select class="form-select form-select-sm" name="cbom_podi_id[]">
                                <option value="">Select Podi</option>
                                <?php foreach ($podies as $po): ?>
                                <option value="<?= $po['id'] ?>" <?= (($cbomRow['podi_id'] ?? '') == $po['id']) ? 'selected' : '' ?>><?= esc($po['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <?php if ($ci > 0): ?>
                            <button type="button" class="btn btn-outline-danger btn-sm remove-cbom"><i class="bi bi-trash"></i> Remove</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                    $qtyMap = [];
                    if (!empty($cbomRow['quantities'])) {
                        foreach ($cbomRow['quantities'] as $q) {
                            $qtyMap[$q['variation_id']] = $q;
                        }
                    }
                    $grouped = [];
                    foreach ($variations as $v) {
                        $grouped[$v['group_name']][] = $v;
                    }
                    ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" style="font-size:12px;">
                            <thead>
                                <tr class="table-light">
                                    <th style="width:150px;">Variation</th>
                                    <th style="width:60px;">Size</th>
                                    <th style="width:90px;">Part Qty</th>
                                    <th style="width:90px;">Podi Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grouped as $gName => $gVars): ?>
                                <tr class="table-secondary"><td colspan="4"><strong><?= esc($gName) ?></strong></td></tr>
                                <?php foreach ($gVars as $v):
                                    $q = $qtyMap[$v['id']] ?? [];
                                ?>
                                <tr>
                                    <td><?= esc($v['name']) ?></td>
                                    <td><?= esc($v['size']) ?></td>
                                    <td><input type="text" class="form-control form-control-sm" name="cbom_qty_<?= $v['id'] ?>_part[]" value="<?= esc($q['part_quantity'] ?? '') ?>" style="width:80px;"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="cbom_qty_<?= $v['id'] ?>_podi[]" value="<?= esc($q['podi_quantity'] ?? '') ?>" style="width:80px;"></td>
                                </tr>
                                <?php endforeach; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm mb-3" id="add_cbom"><i class="bi bi-plus"></i> Add Another Part</button>
            <div>
                <button type="submit" class="btn btn-success"><i class="bi bi-check"></i> Save Customize BOM</button>
                <a href="<?= base_url('products/view/' . $product['id']) ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($templates)): ?>
<div class="modal fade" id="importCbomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('products/importCbomTemplate/' . $product['id']) ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Import CBOM Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted" style="font-size:13px;">CBOM template items will be APPENDED to existing rows.</p>
                    <div class="mb-3">
                        <label class="form-label">Select Template</label>
                        <select class="form-select" name="template_id" required>
                            <option value="">-- Choose Template --</option>
                            <?php foreach ($templates as $t): ?>
                            <option value="<?= $t['id'] ?>"><?= esc($t['name']) ?><?= !empty($t['description']) ? ' - ' . esc($t['description']) : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php $this->endSection() ?>
<?php $this->section('scripts') ?>
<script>
var cbomIdx = <?= count($cbomItems) ?>;
var partsOpts = '<option value="">Select Part</option>';
<?php foreach ($parts as $p): ?>
partsOpts += '<option value="<?= $p['id'] ?>"><?= addslashes($p['name']) ?></option>';
<?php endforeach; ?>
var podiesOpts = '<option value="">Select Podi</option>';
<?php foreach ($podies as $po): ?>
podiesOpts += '<option value="<?= $po['id'] ?>"><?= addslashes($po['name']) ?></option>';
<?php endforeach; ?>

var varTable = '';
<?php
$grouped = [];
foreach ($variations as $v) $grouped[$v['group_name']][] = $v;
?>
<?php foreach ($grouped as $gName => $gVars): ?>
varTable += '<tr class="table-secondary"><td colspan="4"><strong><?= addslashes($gName) ?></strong></td></tr>';
<?php foreach ($gVars as $v): ?>
varTable += '<tr><td><?= addslashes($v['name']) ?></td><td><?= $v['size'] ?></td>';
varTable += '<td><input type="text" class="form-control form-control-sm" name="cbom_qty_<?= $v['id'] ?>_part[]" style="width:80px;"></td>';
varTable += '<td><input type="text" class="form-control form-control-sm" name="cbom_qty_<?= $v['id'] ?>_podi[]" style="width:80px;"></td></tr>';
<?php endforeach; endforeach; ?>

document.getElementById('add_cbom').addEventListener('click', function() {
    var html = '<div class="cbom-row mb-3 p-3 border rounded" style="background:#f8faff;" data-index="' + cbomIdx + '">' +
        '<div class="row mb-2">' +
        '<div class="col-md-4"><label class="form-label">Part</label><select class="form-select form-select-sm" name="cbom_part_id[]">' + partsOpts + '</select></div>' +
        '<div class="col-md-4"><label class="form-label">Podi</label><select class="form-select form-select-sm" name="cbom_podi_id[]">' + podiesOpts + '</select></div>' +
        '<div class="col-md-4 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm remove-cbom"><i class="bi bi-trash"></i> Remove</button></div>' +
        '</div><div class="table-responsive"><table class="table table-sm table-bordered mb-0" style="font-size:12px;">' +
        '<thead><tr class="table-light"><th style="width:150px;">Variation</th><th style="width:60px;">Size</th><th style="width:90px;">Part Qty</th><th style="width:90px;">Podi Qty</th></tr></thead>' +
        '<tbody>' + varTable + '</tbody></table></div></div>';
    document.getElementById('cbom_container').insertAdjacentHTML('beforeend', html);
    cbomIdx++;
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-cbom')) e.target.closest('.cbom-row').remove();
});
</script>
<?php $this->endSection() ?>
