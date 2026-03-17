<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <form action="<?= base_url('templates/' . (isset($template) ? 'update/' . $template['id'] : 'store')) ?>" method="post">
            <?= csrf_field() ?>
            <div class="row mb-3">
                <div class="col-md-3">
                    <label class="form-label">Template Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" value="<?= esc($template['name'] ?? '') ?>" required placeholder="e.g. Bunch Parts">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tamil Name</label>
                    <input type="text" class="form-control" name="tamil_name" value="<?= esc($template['tamil_name'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Product Type <small class="text-muted">(scopes CBOM variations)</small></label>
                    <select class="form-select" name="product_type_id" id="tpl_product_type_id">
                        <option value="">-- General (all types) --</option>
                        <?php foreach ($productTypes as $pt): ?>
                        <option value="<?= $pt['id'] ?>" <?= (($template['product_type_id'] ?? '') == $pt['id']) ? 'selected' : '' ?>><?= esc($pt['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Description</label>
                    <input type="text" class="form-control" name="description" value="<?= esc($template['description'] ?? '') ?>" placeholder="What parts this template provides">
                </div>
            </div>

            <!-- BOM Section -->
            <h6 class="border-bottom pb-2 mb-2 mt-4">BOM Items (Logic Based)</h6>
            <p class="text-muted mb-2" style="font-size:12px;">These will be appended to the product's Logic Based BOM when imported.</p>
            <div id="bom_container">
                <?php $bomItems = $bomItems ?? [[]]; foreach ($bomItems as $i => $row): ?>
                <div class="bom-row row mb-2 gx-1" style="background:#f9f9f9;padding:6px 4px;border-radius:4px;" data-index="<?= $i ?>">
                    <div class="col-md-3"><label class="form-label" style="font-size:11px;">Part</label>
                        <select class="form-select form-select-sm" name="bom_part_id[]">
                            <option value="">Select</option>
                            <?php foreach ($parts as $p): ?><option value="<?= $p['id'] ?>" <?= (($row['part_id'] ?? '') == $p['id']) ? 'selected' : '' ?>><?= esc($p['name']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="col-md-1"><label class="form-label" style="font-size:11px;">Pcs</label>
                        <input type="text" class="form-control form-control-sm" name="bom_part_pcs[]" value="<?= esc($row['part_pcs'] ?? '') ?>"></div>
                    <div class="col-md-2"><label class="form-label" style="font-size:11px;">Scale</label>
                        <select class="form-select form-select-sm" name="bom_scale[]">
                            <option value="">Select</option>
                            <option value="Per Inch" <?= (($row['scale'] ?? '') == 'Per Inch') ? 'selected' : '' ?>>Per Inch</option>
                            <option value="Per Pair" <?= (($row['scale'] ?? '') == 'Per Pair') ? 'selected' : '' ?>>Per Pair</option>
                            <option value="Per Kanni" <?= (($row['scale'] ?? '') == 'Per Kanni') ? 'selected' : '' ?>>Per Kanni</option>
                        </select></div>
                    <div class="col-md-2"><label class="form-label" style="font-size:11px;">Var Group</label>
                        <select class="form-select form-select-sm bom_vg" name="bom_variation_group[<?= $i ?>][]" multiple style="height:55px;">
                            <?php $sel = array_map('trim', explode(',', $row['variation_group'] ?? '')); foreach ($variationGroups as $vg): ?>
                            <option value="<?= esc($vg) ?>" <?= in_array($vg, $sel) ? 'selected' : '' ?>><?= esc($vg) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <div class="col-md-2"><label class="form-label" style="font-size:11px;">Podi</label>
                        <select class="form-select form-select-sm" name="bom_podi_id[]">
                            <option value="">Select</option>
                            <?php foreach ($podies as $po): ?><option value="<?= $po['id'] ?>" <?= (($row['podi_id'] ?? '') == $po['id']) ? 'selected' : '' ?>><?= esc($po['name']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="col-md-1"><label class="form-label" style="font-size:11px;">Podi Pcs</label>
                        <input type="text" class="form-control form-control-sm" name="bom_podi_pcs[]" value="<?= esc($row['podi_pcs'] ?? '') ?>"></div>
                    <div class="col-md-1 d-flex align-items-end pb-1">
                        <?php if ($i > 0): ?><button type="button" class="btn btn-outline-danger btn-sm remove-bom">X</button><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm mb-4" id="add_bom">+ Add BOM Row</button>

            <!-- CBOM Section -->
            <h6 class="border-bottom pb-2 mb-2 mt-2">CBOM Items (Variation Wise)</h6>
            <p class="text-muted mb-2" style="font-size:12px;">These will be appended to the product's Customize BOM when imported. Scoped to selected product type's variations.</p>
            <?php if (empty($variations)): ?>
            <div class="alert alert-info py-2" style="font-size:13px;">Select a Product Type above to load its variations for the CBOM quantity grid.</div>
            <?php endif; ?>
            <div id="cbom_container">
                <?php $cbomItems = $cbomItems ?? []; foreach ($cbomItems as $ci => $crow):
                    $qtyMap = [];
                    if (!empty($crow['quantities'])) foreach ($crow['quantities'] as $q) $qtyMap[$q['variation_id']] = $q;
                    $grouped = [];
                    foreach ($variations as $v) $grouped[$v['group_name']][] = $v;
                ?>
                <div class="cbom-row mb-3 p-3 border rounded" style="background:#f8faff;" data-index="<?= $ci ?>">
                    <div class="row mb-2">
                        <div class="col-md-4"><label class="form-label">Part</label>
                            <select class="form-select form-select-sm" name="cbom_part_id[]">
                                <option value="">Select Part</option>
                                <?php foreach ($parts as $p): ?><option value="<?= $p['id'] ?>" <?= (($crow['part_id'] ?? '') == $p['id']) ? 'selected' : '' ?>><?= esc($p['name']) ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-md-4"><label class="form-label">Podi</label>
                            <select class="form-select form-select-sm" name="cbom_podi_id[]">
                                <option value="">Select Podi</option>
                                <?php foreach ($podies as $po): ?><option value="<?= $po['id'] ?>" <?= (($crow['podi_id'] ?? '') == $po['id']) ? 'selected' : '' ?>><?= esc($po['name']) ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger btn-sm remove-cbom"><i class="bi bi-trash"></i> Remove</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="cbom-var-table table table-sm table-bordered mb-0" style="font-size:12px;">
                            <thead><tr class="table-light"><th>Variation</th><th>Size</th><th>Part Qty</th><th>Podi Qty</th></tr></thead>
                            <tbody>
                                <?php foreach ($grouped as $gName => $gVars): ?>
                                <tr class="table-secondary"><td colspan="4"><strong><?= esc($gName) ?></strong></td></tr>
                                <?php foreach ($gVars as $v): $q = $qtyMap[$v['id']] ?? []; ?>
                                <tr>
                                    <td><?= esc($v['name']) ?></td><td><?= esc($v['size']) ?></td>
                                    <td><input type="text" class="form-control form-control-sm" name="cbom_qty_<?= $v['id'] ?>_part[]" value="<?= esc($q['part_quantity'] ?? '') ?>" style="width:75px;"></td>
                                    <td><input type="text" class="form-control form-control-sm" name="cbom_qty_<?= $v['id'] ?>_podi[]" value="<?= esc($q['podi_quantity'] ?? '') ?>" style="width:75px;"></td>
                                </tr>
                                <?php endforeach; endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm mb-4" id="add_cbom">+ Add CBOM Row</button>

            <div class="mt-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-check"></i> Save Template</button>
                <a href="<?= base_url('templates') ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php $this->endSection() ?>
<?php $this->section('scripts') ?>
<script>
var bomIdx = <?= count($bomItems) ?>, cbomIdx = <?= count($cbomItems ?? []) ?>;
var partsOpts = '<option value="">Select</option>';
<?php foreach ($parts as $p): ?>partsOpts += '<option value="<?= $p['id'] ?>"><?= addslashes($p['name']) ?></option>';<?php endforeach; ?>
var podiesOpts = '<option value="">Select</option>';
<?php foreach ($podies as $po): ?>podiesOpts += '<option value="<?= $po['id'] ?>"><?= addslashes($po['name']) ?></option>';<?php endforeach; ?>
var vgOpts = '';
<?php foreach ($variationGroups as $vg): ?>vgOpts += '<option value="<?= esc($vg) ?>"><?= esc($vg) ?></option>';<?php endforeach; ?>
var varTableRows = '';
<?php
$tplGrouped = [];
foreach ($variations as $v) $tplGrouped[$v['group_name']][] = $v;
foreach ($tplGrouped as $gName => $gVars):
?>
varTableRows += '<tr class="table-secondary"><td colspan="4"><strong><?= addslashes($gName) ?></strong></td></tr>';
<?php foreach ($gVars as $v): ?>
varTableRows += '<tr><td><?= addslashes($v['name']) ?></td><td><?= $v['size'] ?></td><td><input type="text" class="form-control form-control-sm" name="cbom_qty_<?= $v['id'] ?>_part[]" style="width:75px;"></td><td><input type="text" class="form-control form-control-sm" name="cbom_qty_<?= $v['id'] ?>_podi[]" style="width:75px;"></td></tr>';
<?php endforeach; endforeach; ?>

// AJAX: reload variations when product type changes
document.getElementById('tpl_product_type_id').addEventListener('change', function() {
    var ptId = this.value;
    var confirmed = true;
    if (document.querySelectorAll('#cbom_container .cbom-row').length > 0) {
        confirmed = confirm('Changing product type will rebuild the variation columns. Unsaved CBOM data will be reset. Continue?');
    }
    if (!confirmed) { this.value = this.dataset.prev || ''; return; }
    this.dataset.prev = ptId;

    fetch('<?= base_url('templates/getVariationsByType') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
        body: 'product_type_id=' + ptId + '&<?= csrf_token() ?>=<?= csrf_hash() ?>'
    }).then(r => r.json()).then(function(d) {
        // Rebuild vgOpts
        vgOpts = '';
        (d.variation_groups || []).forEach(function(vg) { vgOpts += '<option value="' + vg + '">' + vg + '</option>'; });
        document.querySelectorAll('.bom_vg').forEach(function(sel) { sel.innerHTML = vgOpts; });

        // Rebuild varTableRows for new CBOM rows
        varTableRows = '';
        var grouped = {};
        (d.variations || []).forEach(function(v) {
            if (!grouped[v.group_name]) grouped[v.group_name] = [];
            grouped[v.group_name].push(v);
        });
        Object.keys(grouped).forEach(function(gName) {
            varTableRows += '<tr class="table-secondary"><td colspan="4"><strong>' + gName + '</strong></td></tr>';
            grouped[gName].forEach(function(v) {
                varTableRows += '<tr><td>' + v.name + '</td><td>' + v.size + '</td>' +
                    '<td><input type="text" class="form-control form-control-sm" name="cbom_qty_' + v.id + '_part[]" style="width:75px;"></td>' +
                    '<td><input type="text" class="form-control form-control-sm" name="cbom_qty_' + v.id + '_podi[]" style="width:75px;"></td></tr>';
            });
        });

        // Update existing CBOM row tables with new variation columns
        document.querySelectorAll('#cbom_container .cbom-row .cbom-var-table tbody').forEach(function(tbody) {
            tbody.innerHTML = varTableRows;
        });
    });
});

document.getElementById('add_bom').addEventListener('click', function() {
    var h = '<div class="bom-row row mb-2 gx-1" style="background:#f9f9f9;padding:6px 4px;border-radius:4px;" data-index="' + bomIdx + '">' +
        '<div class="col-md-3"><label style="font-size:11px;" class="form-label">Part</label><select class="form-select form-select-sm" name="bom_part_id[]">' + partsOpts + '</select></div>' +
        '<div class="col-md-1"><label style="font-size:11px;" class="form-label">Pcs</label><input type="text" class="form-control form-control-sm" name="bom_part_pcs[]"></div>' +
        '<div class="col-md-2"><label style="font-size:11px;" class="form-label">Scale</label><select class="form-select form-select-sm" name="bom_scale[]"><option value="">Select</option><option value="Per Inch">Per Inch</option><option value="Per Pair">Per Pair</option><option value="Per Kanni">Per Kanni</option></select></div>' +
        '<div class="col-md-2"><label style="font-size:11px;" class="form-label">Var Group</label><select class="form-select form-select-sm bom_vg" name="bom_variation_group[' + bomIdx + '][]" multiple style="height:55px;">' + vgOpts + '</select></div>' +
        '<div class="col-md-2"><label style="font-size:11px;" class="form-label">Podi</label><select class="form-select form-select-sm" name="bom_podi_id[]">' + podiesOpts + '</select></div>' +
        '<div class="col-md-1"><label style="font-size:11px;" class="form-label">Podi Pcs</label><input type="text" class="form-control form-control-sm" name="bom_podi_pcs[]"></div>' +
        '<div class="col-md-1 d-flex align-items-end pb-1"><button type="button" class="btn btn-outline-danger btn-sm remove-bom">X</button></div></div>';
    document.getElementById('bom_container').insertAdjacentHTML('beforeend', h);
    bomIdx++;
});

document.getElementById('add_cbom').addEventListener('click', function() {
    var h = '<div class="cbom-row mb-3 p-3 border rounded" style="background:#f8faff;" data-index="' + cbomIdx + '">' +
        '<div class="row mb-2"><div class="col-md-4"><label class="form-label">Part</label><select class="form-select form-select-sm" name="cbom_part_id[]"><option value="">Select Part</option>' + partsOpts + '</select></div>' +
        '<div class="col-md-4"><label class="form-label">Podi</label><select class="form-select form-select-sm" name="cbom_podi_id[]"><option value="">Select Podi</option>' + podiesOpts + '</select></div>' +
        '<div class="col-md-4 d-flex align-items-end"><button type="button" class="btn btn-outline-danger btn-sm remove-cbom"><i class="bi bi-trash"></i> Remove</button></div></div>' +
        '<div class="table-responsive"><table class="cbom-var-table table table-sm table-bordered mb-0" style="font-size:12px;"><thead><tr class="table-light"><th>Variation</th><th>Size</th><th>Part Qty</th><th>Podi Qty</th></tr></thead><tbody>' + varTableRows + '</tbody></table></div></div>';
    document.getElementById('cbom_container').insertAdjacentHTML('beforeend', h);
    cbomIdx++;
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-bom')) e.target.closest('.bom-row').remove();
    if (e.target.closest('.remove-cbom')) e.target.closest('.cbom-row').remove();
});
</script>
<?php $this->endSection() ?>
