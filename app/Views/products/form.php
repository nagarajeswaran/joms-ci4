<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <form action="<?= base_url('products/' . (isset($item) ? 'update/' . $item['id'] : 'store')) ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <h6 class="border-bottom pb-2 mb-3">Product Details</h6>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Product Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="name" value="<?= esc($item['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Short Label Name <small class="text-muted">&lt;20 chars</small></label>
                    <input type="text" class="form-control" name="short_name" maxlength="60" value="<?= esc($item['short_name'] ?? '') ?>" placeholder="e.g. GETPOO">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">SKU</label>
                    <input type="text" class="form-control" name="sku" value="<?= esc($item['sku'] ?? '') ?>" placeholder="Optional code">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Tamil Name</label>
                    <input type="text" class="form-control" name="tamil_name" value="<?= esc($item['tamil_name'] ?? '') ?>">
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Product Type <span class="text-danger">*</span></label>
                    <select class="form-select" name="product_type_id" id="product_type_id" required>
                        <option value="">Select</option>
                        <?php foreach ($productTypes as $pt): ?>
                        <option value="<?= $pt['id'] ?>" <?= (isset($item) && $item['product_type_id'] == $pt['id']) ? 'selected' : '' ?>><?= esc($pt['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Body</label>
                    <select class="form-select" name="body_id" id="body_id">
                        <option value="">Select</option>
                        <?php foreach ($bodies as $b): ?>
                        <option value="<?= $b['id'] ?>" data-pt="<?= $b['product_type_id'] ?? '' ?>" <?= (isset($item) && $item['body_id'] == $b['id']) ? 'selected' : '' ?>><?= esc($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Main Part</label>
                    <select class="form-select" name="main_part_id">
                        <option value="">Select</option>
                        <?php foreach ($mainParts as $mp): ?>
                        <option value="<?= $mp['id'] ?>" <?= (isset($item) && $item['main_part_id'] == $mp['id']) ? 'selected' : '' ?>><?= esc($mp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 mb-3">
                    <label class="form-label">Pidi</label>
                    <input type="text" class="form-control" name="pidi" value="<?= esc($item['pidi'] ?? '') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Product Image</label>
                    <?php if (!empty($item['image'])): ?>
                    <div class="mb-2">
                        <img src="<?= base_url('uploads/products/' . $item['image']) ?>" style="height:64px;border-radius:4px;border:1px solid #ddd;object-fit:cover;" alt="Current image">
                        <div class="mt-1"><label class="small text-danger"><input type="checkbox" name="remove_image" value="1"> Remove image</label></div>
                    </div>
                    <?php endif; ?>
                    <input type="file" class="form-control" name="product_image" accept="image/*">
                    <small class="text-muted">Default image shown for patterns without their own image</small>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3 mt-4">
                <h6 class="mb-0">Bill of Material (Logic Based)</h6>
                <?php if (!empty($templates)): ?>
                <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#importBomModal">
                    <i class="bi bi-download"></i> Import Template
                </button>
                <?php endif; ?>
            </div>
            <p class="text-muted mb-2" style="font-size:12px;">Base BOM - applies to all patterns. Use Patterns on product view to add/remove/replace parts per variant.</p>
            <div id="bom_container">
                <?php
                $bomItems = $bom ?? [[]];
                foreach ($bomItems as $i => $bomRow):
                ?>
                <div class="bom-row row mb-2 gx-1" style="background:#f9f9f9; padding:6px 4px; border-radius:4px;" data-index="<?= $i ?>">
                    <div class="col-md-3">
                        <label class="form-label" style="font-size:11px;">Part</label>
                        <select class="form-select form-select-sm" name="bom_part_id[]">
                            <option value="">Select</option>
                            <?php foreach ($parts as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= (($bomRow['part_id'] ?? '') == $p['id']) ? 'selected' : '' ?>><?= esc($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label" style="font-size:11px;">Pcs</label>
                        <input type="text" class="form-control form-control-sm" name="bom_part_pcs[]" value="<?= esc($bomRow['part_pcs'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" style="font-size:11px;">Scale</label>
                        <select class="form-select form-select-sm" name="bom_scale[]">
                            <option value="">Select</option>
                            <option value="Per Inch" <?= (($bomRow['scale'] ?? '') == 'Per Inch') ? 'selected' : '' ?>>Per Inch</option>
                            <option value="Per Pair" <?= (($bomRow['scale'] ?? '') == 'Per Pair') ? 'selected' : '' ?>>Per Pair</option>
                            <option value="Per Kanni" <?= (($bomRow['scale'] ?? '') == 'Per Kanni') ? 'selected' : '' ?>>Per Kanni</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" style="font-size:11px;">Variation Group</label>
                        <?php $selVg = array_map('trim', explode(',', $bomRow['variation_group'] ?? '')); ?>
                        <div class="bom_vg vg-check-wrap" data-rowindex="<?= $i ?>" style="max-height:60px;overflow-y:auto;border:1px solid #ccc;padding:2px 5px;border-radius:4px;font-size:11px;background:#fff;">
                            <?php foreach ($variationGroups as $vg): ?>
                            <label class="d-block mb-0 text-nowrap"><input type="checkbox" name="bom_variation_group[<?= $i ?>][]" value="<?= esc($vg) ?>" <?= in_array($vg, $selVg) ? 'checked' : '' ?>> <?= esc($vg) ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" style="font-size:11px;">Podi</label>
                        <select class="form-select form-select-sm" name="bom_podi_id[]">
                            <option value="">Select</option>
                            <?php foreach ($podies as $po): ?>
                            <option value="<?= $po['id'] ?>" <?= (($bomRow['podi_id'] ?? '') == $po['id']) ? 'selected' : '' ?>><?= esc($po['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label" style="font-size:11px;">Podi Pcs</label>
                        <input type="text" class="form-control form-control-sm" name="bom_podi_pcs[]" value="<?= esc($bomRow['podi_pcs'] ?? '') ?>">
                    </div>
                    <div class="col-md-1 d-flex align-items-end pb-1">
                        <?php if ($i > 0): ?><button type="button" class="btn btn-outline-danger btn-sm remove-bom">X</button><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm mb-4" id="add_bom">+ Add BOM Row</button>

            <div class="mt-3">
                <button type="submit" class="btn btn-success"><i class="bi bi-check"></i> Save Product</button>
                <a href="<?= base_url('products') ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($templates)): ?>
<div class="modal fade" id="importBomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= base_url('products/importBomTemplate/' . ($item['id'] ?? 0)) ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title">Import BOM Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted" style="font-size:13px;">Template items will be APPENDED to existing BOM rows.</p>
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
var bomIdx = <?= count($bomItems ?? [[]]) ?>;
var partsOpts = '';
<?php foreach ($parts as $p): ?>
partsOpts += '<option value="<?= $p['id'] ?>"><?= addslashes($p['name']) ?></option>';
<?php endforeach; ?>
var podiesOpts = '';
<?php foreach ($podies as $po): ?>
podiesOpts += '<option value="<?= $po['id'] ?>"><?= addslashes($po['name']) ?></option>';
<?php endforeach; ?>
var vgChecksHtml = '';
<?php foreach ($variationGroups as $vg): ?>
vgChecksHtml += '<label class="d-block mb-0 text-nowrap"><input type="checkbox" name="bom_variation_group[__IDX__][]" value="<?= esc($vg) ?>"> <?= esc($vg) ?></label>';
<?php endforeach; ?>

document.getElementById('product_type_id').addEventListener('change', function() {
    var ptId = this.value;
    filterBodies(ptId);
    if (ptId) {
        fetch('<?= rtrim(base_url(), "/") ?>/products/getVariations', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'},
            body: 'product_type_id=' + ptId + '&<?= csrf_token() ?>=<?= csrf_hash() ?>'
        }).then(r => r.json()).then(d => {
            var groups = d.variation_groups || [];
            vgChecksHtml = '';
            groups.forEach(function(vg) {
                vgChecksHtml += '<label class="d-block mb-0 text-nowrap"><input type="checkbox" name="bom_variation_group[__IDX__][]" value="' + vg + '"> ' + vg + '</label>';
            });
            document.querySelectorAll('.bom_vg').forEach(function(div) {
                var idx = div.getAttribute('data-rowindex') || '0';
                div.innerHTML = vgChecksHtml.replace(/__IDX__/g, idx);
            });
        });
    }
});

function filterBodies(ptId) {
    document.querySelectorAll('#body_id option').forEach(function(o) {
        if (!o.value) return;
        var optPt = o.getAttribute('data-pt');
        o.style.display = (!ptId || !optPt || optPt == ptId) ? '' : 'none';
    });
}

document.getElementById('add_bom').addEventListener('click', function() {
    var vgHtml = vgChecksHtml.replace(/__IDX__/g, bomIdx);
    var html = '<div class="bom-row row mb-2 gx-1" style="background:#f9f9f9; padding:6px 4px; border-radius:4px;" data-index="' + bomIdx + '">' +
        '<div class="col-md-3"><label class="form-label" style="font-size:11px;">Part</label><select class="form-select form-select-sm" name="bom_part_id[]"><option value="">Select</option>' + partsOpts + '</select></div>' +
        '<div class="col-md-1"><label class="form-label" style="font-size:11px;">Pcs</label><input type="text" class="form-control form-control-sm" name="bom_part_pcs[]"></div>' +
        '<div class="col-md-2"><label class="form-label" style="font-size:11px;">Scale</label><select class="form-select form-select-sm" name="bom_scale[]"><option value="">Select</option><option value="Per Inch">Per Inch</option><option value="Per Pair">Per Pair</option><option value="Per Kanni">Per Kanni</option></select></div>' +
        '<div class="col-md-2"><label class="form-label" style="font-size:11px;">Var Group</label><div class="bom_vg vg-check-wrap" data-rowindex="' + bomIdx + '" style="max-height:60px;overflow-y:auto;border:1px solid #ccc;padding:2px 5px;border-radius:4px;font-size:11px;background:#fff;">' + vgHtml + '</div></div>' +
        '<div class="col-md-2"><label class="form-label" style="font-size:11px;">Podi</label><select class="form-select form-select-sm" name="bom_podi_id[]"><option value="">Select</option>' + podiesOpts + '</select></div>' +
        '<div class="col-md-1"><label class="form-label" style="font-size:11px;">Podi Pcs</label><input type="text" class="form-control form-control-sm" name="bom_podi_pcs[]"></div>' +
        '<div class="col-md-1 d-flex align-items-end pb-1"><button type="button" class="btn btn-outline-danger btn-sm remove-bom">X</button></div></div>';
    document.getElementById('bom_container').insertAdjacentHTML('beforeend', html);
    bomIdx++;
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-bom')) {
        e.target.closest('.bom-row').remove();
        reindexBomRows();
    }
});

function reindexBomRows() {
    document.querySelectorAll('#bom_container .bom-row').forEach(function(row, newIdx) {
        row.setAttribute('data-index', newIdx);
        var vgDiv = row.querySelector('.bom_vg');
        if (vgDiv) {
            vgDiv.setAttribute('data-rowindex', newIdx);
            vgDiv.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
                cb.name = 'bom_variation_group[' + newIdx + '][]';
            });
        }
    });
}

<?php if (isset($item) && $item['product_type_id']): ?>filterBodies('<?= $item['product_type_id'] ?>');<?php endif; ?>
</script>
<?php $this->endSection() ?>
