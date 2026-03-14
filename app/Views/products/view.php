<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="<?= base_url('products') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
        <a href="<?= base_url('products/edit/' . $product['id']) ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i> Edit</a>
        <a href="<?= base_url('products/duplicate/' . $product['id']) ?>" class="btn btn-outline-secondary btn-sm" onclick="return confirm('Duplicate this product? All BOM, CBOM and patterns will be copied.')" title="Duplicate Product"><i class="bi bi-copy"></i> Duplicate</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row" style="font-size:13px;">
            <div class="col-md-2"><strong>Name:</strong> <?= esc($product['name']) ?></div>
            <?php if (!empty($product['sku'])): ?>
            <div class="col-md-2"><strong>SKU:</strong> <code><?= esc($product['sku']) ?></code></div>
            <?php endif; ?>
            <div class="col-md-2"><strong>Tamil:</strong> <?= esc($product['tamil_name'] ?? '') ?></div>
            <div class="col-md-2"><strong>Type:</strong> <?= esc($product['product_type_name'] ?? '') ?> (x<?= esc($product['multiplication_factor'] ?? '1') ?>)</div>
            <div class="col-md-2"><strong>Body:</strong> <?= esc($product['body_name'] ?? '') ?> (clasp: <?= esc($product['clasp_size'] ?? '0') ?>)</div>
            <div class="col-md-1"><strong>Main Part:</strong> <?= esc($product['main_part_name'] ?? '') ?></div>
            <div class="col-md-1"><strong>Pidi:</strong> <?= esc($product['pidi'] ?? '') ?></div>
        </div>
    </div>
</div>

<?php if (!empty($bom)): ?>
<div class="card mb-3">
    <div class="card-header"><strong>Base Bill of Material (Logic Based)</strong></div>
    <div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0" style="font-size:13px;">
            <thead><tr><th>Part</th><th>Pcs</th><th>Scale</th><th>Var Group</th><th>Podi</th><th>Podi Pcs</th></tr></thead>
            <tbody>
                <?php foreach ($bom as $b): ?>
                <tr>
                    <td><?= esc($b['part_name'] ?? '') ?></td>
                    <td><?= esc($b['part_pcs'] ?? '') ?></td>
                    <td><?= esc($b['scale'] ?? '') ?></td>
                    <td><?= esc($b['variation_group'] ?? '') ?></td>
                    <td><?= esc($b['podi_name'] ?? '') ?></td>
                    <td><?= esc($b['podi_pcs'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($uncoveredBomGroups)): ?>
<div class="alert alert-warning py-2 mb-3" style="font-size:13px;">
    <strong><i class="bi bi-exclamation-triangle"></i> Logic BOM coverage gap:</strong>
    Variation group(s) <strong><?= esc(implode(', ', $uncoveredBomGroups)) ?></strong>
    are in the product type but no BOM row covers them.
    <a href="<?= base_url('products/edit/' . $product['id']) ?>" class="alert-link">Edit BOM</a>
    and add rows for these groups, or clear the "Var Group" filter on existing rows to cover all groups.
</div>
<?php endif; ?>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Customize Bill of Material (Variation Wise)</strong>
        <div class="d-flex align-items-center gap-2">
            <?php if (!empty($missingCbomCount)): ?>
            <span class="badge bg-warning text-dark" title="<?= $missingCbomCount ?> CBOM part(s) have missing quantities for new variations. Edit CBOM to fill them in.">
                <i class="bi bi-exclamation-triangle"></i> <?= $missingCbomCount ?> incomplete
            </span>
            <?php endif; ?>
            <a href="<?= base_url('products/cbom/' . $product['id']) ?>" class="btn btn-primary btn-sm"><i class="bi bi-grid"></i> Manage CBOM (<?= $cbomCount ?? 0 ?> parts)</a>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Product Patterns</strong>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPatternModal"><i class="bi bi-plus"></i> Add Pattern</button>
    </div>
    <div class="card-body">
        <?php if (empty($patterns)): ?>
        <p class="text-muted">No patterns yet.</p>
        <?php endif; ?>

        <?php foreach ($patterns as $pat): ?>
        <div class="border rounded p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                    <?php if (!empty($pat['image'])): ?>
                    <img src="<?= base_url('uploads/patterns/' . $pat['image']) ?>" alt="" style="height:40px;width:40px;object-fit:cover;border-radius:4px;border:1px solid #ddd;">
                    <?php endif; ?>
                    <h6 class="mb-0">
                        <?= esc($pat['name']) ?>
                        <?php if (!empty($pat['global_pattern_name']) && $pat['global_pattern_name'] != $pat['name']): ?>
                        <small class="text-muted">(<?= esc($pat['global_pattern_name']) ?>)</small>
                        <?php endif; ?>
                        <?php if ($pat['is_default']): ?><span class="badge bg-primary ms-1">Default</span><?php endif; ?>
                        <?php if (!empty($pat['tamil_name'])): ?><small class="text-muted ms-1">(<?= esc($pat['tamil_name']) ?>)</small><?php endif; ?>
                    </h6>
                </div>
                <div>
                    <?php if (!empty($templates)): ?>
                    <button type="button" class="btn btn-outline-info btn-sm me-1"
                        onclick="openPatternImport(<?= $pat['id'] ?>)"
                        title="Import template BOM items as ADD changes into this pattern">
                        <i class="bi bi-download"></i> Import Template
                    </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm me-1"
                        data-bs-toggle="modal" data-bs-target="#editPatternModal_<?= $pat['id'] ?>"
                        title="Edit pattern name/image"><i class="bi bi-pencil"></i></button>
                    <?php if (!$pat['is_default']): ?>
                    <a href="<?= base_url('products/deletePattern/' . $pat['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this pattern?')"><i class="bi bi-trash"></i></a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Edit Pattern Modal -->
            <div class="modal fade" id="editPatternModal_<?= $pat['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="<?= base_url('products/updatePattern/' . $pat['id']) ?>" method="post" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Pattern</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-2">
                                    <label class="form-label">Pattern Name</label>
                                    <input type="text" class="form-control" name="name" value="<?= esc($pat['name']) ?>" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Tamil Name</label>
                                    <input type="text" class="form-control" name="tamil_name" value="<?= esc($pat['tamil_name'] ?? '') ?>">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Short Label Name <small class="text-muted">&lt;20 chars</small></label>
                                    <input type="text" class="form-control" name="short_name" maxlength="60" value="<?= esc($pat['short_name'] ?? '') ?>" placeholder="e.g. 1+1CUT">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Image</label>
                                    <?php if (!empty($pat['image'])): ?>
                                    <div class="mb-1">
                                        <img src="<?= base_url('uploads/patterns/' . $pat['image']) ?>" style="height:60px;border-radius:4px;border:1px solid #ddd;">
                                        <label class="ms-2 text-danger" style="font-size:12px;">
                                            <input type="checkbox" name="remove_image" value="1"> Remove image
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                    <input type="file" class="form-control form-control-sm" name="pattern_image" accept="image/*">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php if (!$pat['is_default']): ?>
            <form action="<?= base_url('products/savePatternChanges/' . $pat['id']) ?>" method="post">
                <?= csrf_field() ?>
                <div class="pattern-changes" id="changes_<?= $pat['id'] ?>">
                    <?php if (!empty($pat['changes'])): ?>
                    <?php foreach ($pat['changes'] as $ci => $ch): ?>
                    <div class="change-row row mb-1 gx-1 align-items-center" style="background:#fff8f0; padding:5px 3px; border-radius:4px;">
                        <div class="col-md-1">
                            <select class="form-select form-select-sm change-action-sel" name="change_action[]">
                                <option value="add" <?= $ch['action'] == 'add' ? 'selected' : '' ?>>ADD</option>
                                <option value="remove" <?= $ch['action'] == 'remove' ? 'selected' : '' ?>>REMOVE</option>
                                <option value="replace" <?= $ch['action'] == 'replace' ? 'selected' : '' ?>>REPLACE</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select form-select-sm" name="change_part_id[]">
                                <option value="">Part</option>
                                <?php foreach ($parts as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $ch['part_id'] == $p['id'] ? 'selected' : '' ?>><?= esc($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-1"><input type="text" class="form-control form-control-sm" name="change_part_pcs[]" value="<?= esc($ch['part_pcs'] ?? '') ?>" placeholder="Pcs"></div>
                        <div class="col-md-1">
                            <select class="form-select form-select-sm" name="change_scale[]">
                                <option value="">Scale</option>
                                <option value="Per Inch" <?= ($ch['scale'] ?? '') == 'Per Inch' ? 'selected' : '' ?>>Per Inch</option>
                                <option value="Per Pair" <?= ($ch['scale'] ?? '') == 'Per Pair' ? 'selected' : '' ?>>Per Pair</option>
                                <option value="Per Kanni" <?= ($ch['scale'] ?? '') == 'Per Kanni' ? 'selected' : '' ?>>Per Kanni</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <?php $chVg = array_map('trim', explode(',', $ch['variation_group'] ?? '')); ?>
                            <div class="vg-check-wrap" style="max-height:60px;overflow-y:auto;border:1px solid #ccc;padding:2px 5px;border-radius:4px;font-size:11px;background:#fff;">
                                <?php if (empty($variationGroups)): ?>
                                <span class="text-muted">No groups</span>
                                <?php else: foreach ($variationGroups as $vg): ?>
                                <label class="d-block mb-0 text-nowrap"><input type="checkbox" name="change_variation_group[<?= $ci ?>][]" value="<?= esc($vg) ?>" <?= in_array($vg, $chVg) ? 'checked' : '' ?>> <?= esc($vg) ?></label>
                                <?php endforeach; endif; ?>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select form-select-sm" name="change_podi_id[]">
                                <option value="">Podi</option>
                                <?php foreach ($podies as $po): ?>
                                <option value="<?= $po['id'] ?>" <?= ($ch['podi_id'] ?? '') == $po['id'] ? 'selected' : '' ?>><?= esc($po['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-1"><input type="text" class="form-control form-control-sm" name="change_podi_pcs[]" value="<?= esc($ch['podi_pcs'] ?? '') ?>" placeholder="P.Pcs"></div>
                        <div class="col-md-1 replace-col" style="<?= $ch['action'] != 'replace' ? 'display:none' : '' ?>">
                            <select class="form-select form-select-sm" name="change_replace_part_id[]">
                                <option value="">Replace?</option>
                                <?php foreach ($parts as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ($ch['replace_part_id'] ?? '') == $p['id'] ? 'selected' : '' ?>><?= esc($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.change-row').remove()" title="Remove row">&times;</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm mt-1 add-change-btn" data-pattern="<?= $pat['id'] ?>">+ Add Change</button>
                <button type="submit" class="btn btn-success btn-sm mt-1">Save Changes</button>
            </form>
            <?php else: ?>
            <p class="text-muted mb-0" style="font-size:13px;">Default pattern uses the base BOM as-is. Create new patterns to define variants.</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if (!empty($templates)): ?>
<!-- Import Template to Pattern Modal -->
<div class="modal fade" id="patternImportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="patternImportForm" method="post">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-download"></i> Import Template into Pattern</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted" style="font-size:13px;">Select one or more templates. BOM items will be appended as ADD changes to this pattern.</p>
                    <div style="max-height:220px;overflow-y:auto;border:1px solid #dee2e6;padding:8px;border-radius:4px;">
                        <?php foreach ($templates as $t): ?>
                        <label class="d-flex align-items-center gap-2 mb-2" style="cursor:pointer;">
                            <input type="checkbox" name="template_id[]" value="<?= $t['id'] ?>">
                            <span>
                                <strong><?= esc($t['name']) ?></strong>
                                <?php if (!empty($t['type_name'])): ?><small class="text-muted">[<?= esc($t['type_name']) ?>]</small><?php endif; ?>
                                <?php if (!empty($t['description'])): ?><small class="text-muted">— <?= esc($t['description']) ?></small><?php endif; ?>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Import Selected</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Add Pattern Modal -->
<div class="modal fade" id="addPatternModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('products/addPattern/' . $product['id']) ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="modal-header"><h5 class="modal-title">Add Pattern</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select Existing Pattern Name</label>
                        <select class="form-select" name="pattern_name_id" id="pattern_name_id_sel" onchange="toggleNewPatternFields(this.value)">
                            <option value="">-- Select or type new below --</option>
                            <?php foreach ($patternNames as $pn): ?>
                            <option value="<?= $pn['id'] ?>"><?= esc($pn['name']) ?><?= !empty($pn['tamil_name']) ? ' (' . esc($pn['tamil_name']) . ')' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Short Label Name <small class="text-muted">&lt;20 chars</small></label>
                        <input type="text" class="form-control" name="short_name" maxlength="60" placeholder="e.g. 1+1CUT">
                    </div>
                    <div id="new_pattern_fields">
                        <hr><p class="text-muted" style="font-size:12px;">Or create a new pattern name:</p>
                        <div class="mb-2">
                            <label class="form-label">New Pattern Name</label>
                            <input type="text" class="form-control" name="new_pattern_name" placeholder="e.g., 1+1 Cutting">
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Tamil Name (optional)</label>
                            <input type="text" class="form-control" name="new_pattern_tamil">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Pattern Image <small class="text-muted">(optional)</small></label>
                        <input type="file" class="form-control form-control-sm" name="pattern_image" accept="image/*">
                    </div>
                    <?php if (!empty($templates)): ?>
                    <hr>
                    <div class="mb-2">
                        <label class="form-label">Import BOM from Templates <small class="text-muted">(optional — select one or more)</small></label>
                        <div style="max-height:160px;overflow-y:auto;border:1px solid #dee2e6;padding:8px;border-radius:4px;">
                            <?php foreach ($templates as $t): ?>
                            <label class="d-flex align-items-center gap-2 mb-1" style="cursor:pointer;">
                                <input type="checkbox" name="import_template_id[]" value="<?= $t['id'] ?>">
                                <span>
                                    <strong><?= esc($t['name']) ?></strong>
                                    <?php if (!empty($t['type_name'])): ?><small class="text-muted">[<?= esc($t['type_name']) ?>]</small><?php endif; ?>
                                    <?php if (!empty($t['description'])): ?><small class="text-muted">— <?= esc($t['description']) ?></small><?php endif; ?>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted">Checked templates' BOM items will be added as changes to the new pattern.</small>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Pattern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $this->endSection() ?>
<?php $this->section('scripts') ?>
<script>
var partsOpts = '';
<?php foreach ($parts as $p): ?>
partsOpts += '<option value="<?= $p['id'] ?>"><?= addslashes($p['name']) ?></option>';
<?php endforeach; ?>
var podiesOpts = '';
<?php foreach ($podies as $po): ?>
podiesOpts += '<option value="<?= $po['id'] ?>"><?= addslashes($po['name']) ?></option>';
<?php endforeach; ?>

// Build checkbox HTML for variation groups
var vgChecksHtml = '';
<?php foreach ($variationGroups as $vg): ?>
vgChecksHtml += '<label class="d-block mb-0 text-nowrap"><input type="checkbox" value="<?= esc($vg) ?>"> <?= esc($vg) ?></label>';
<?php endforeach; ?>

function toggleNewPatternFields(val) {
    document.getElementById('new_pattern_fields').style.display = val ? 'none' : '';
}

function openPatternImport(patternId) {
    var baseUrl = '<?= base_url('products/importTemplateToPattern/') ?>';
    document.getElementById('patternImportForm').action = baseUrl + patternId;
    // Uncheck all
    document.querySelectorAll('#patternImportModal input[type=checkbox]').forEach(function(cb){ cb.checked = false; });
    var modal = new bootstrap.Modal(document.getElementById('patternImportModal'));
    modal.show();
}

var changeIdx = {};
document.querySelectorAll('.add-change-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var patId = this.dataset.pattern;
        if (!changeIdx[patId]) changeIdx[patId] = document.querySelectorAll('#changes_' + patId + ' .change-row').length;
        var ci = changeIdx[patId];
        var container = document.getElementById('changes_' + patId);

        // Build checkbox inputs with correct name for this row index
        var vgWrap = '<div class="vg-check-wrap" style="max-height:60px;overflow-y:auto;border:1px solid #ccc;padding:2px 5px;border-radius:4px;font-size:11px;background:#fff;">';
        <?php foreach ($variationGroups as $vg): ?>
        vgWrap += '<label class="d-block mb-0 text-nowrap"><input type="checkbox" name="change_variation_group[' + ci + '][]" value="<?= esc($vg) ?>"> <?= esc($vg) ?></label>';
        <?php endforeach; ?>
        <?php if (empty($variationGroups)): ?>vgWrap += '<span class="text-muted">No groups</span>';<?php endif; ?>
        vgWrap += '</div>';

        var html = '<div class="change-row row mb-1 gx-1 align-items-center" style="background:#fff8f0; padding:5px 3px; border-radius:4px;">' +
            '<div class="col-md-1"><select class="form-select form-select-sm change-action-sel" name="change_action[]"><option value="add">ADD</option><option value="remove">REMOVE</option><option value="replace">REPLACE</option></select></div>' +
            '<div class="col-md-2"><select class="form-select form-select-sm" name="change_part_id[]"><option value="">Part</option>' + partsOpts + '</select></div>' +
            '<div class="col-md-1"><input type="text" class="form-control form-control-sm" name="change_part_pcs[]" placeholder="Pcs"></div>' +
            '<div class="col-md-1"><select class="form-select form-select-sm" name="change_scale[]"><option value="">Scale</option><option value="Per Inch">Per Inch</option><option value="Per Pair">Per Pair</option><option value="Per Kanni">Per Kanni</option></select></div>' +
            '<div class="col-md-2">' + vgWrap + '</div>' +
            '<div class="col-md-2"><select class="form-select form-select-sm" name="change_podi_id[]"><option value="">Podi</option>' + podiesOpts + '</select></div>' +
            '<div class="col-md-1"><input type="text" class="form-control form-control-sm" name="change_podi_pcs[]" placeholder="P.Pcs"></div>' +
            '<div class="col-md-1 replace-col" style="display:none"><select class="form-select form-select-sm" name="change_replace_part_id[]"><option value="">Replace?</option>' + partsOpts + '</select></div>' +
            '<div class="col-auto"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest(\'.change-row\').remove()" title="Remove">&times;</button></div>' +
            '</div>';
        container.insertAdjacentHTML('beforeend', html);
        changeIdx[patId] = ci + 1;
    });
});

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('change-action-sel')) {
        var row = e.target.closest('.change-row');
        var replaceCol = row.querySelector('.replace-col');
        replaceCol.style.display = (e.target.value === 'replace') ? '' : 'none';
    }
});
</script>
<?php $this->endSection() ?>
