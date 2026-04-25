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
            <span class="badge bg-warning text-dark" title="<?= $missingCbomCount ?> CBOM part(s) have missing quantities for new variations.">
                <i class="bi bi-exclamation-triangle"></i> <?= $missingCbomCount ?> incomplete
            </span>
            <?php endif; ?>
            <a href="<?= base_url('products/cbom/' . $product['id']) ?>" class="btn btn-primary btn-sm"><i class="bi bi-grid"></i> Manage CBOM (<?= $cbomCount ?? 0 ?> parts)</a>
        </div>
    </div>
</div>


<!-- PATTERNS: Sidebar + Content Panel -->
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Product Patterns</strong>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPatternModal"><i class="bi bi-plus"></i> Add Pattern</button>
    </div>
    <?php if (empty($patterns)): ?>
    <div class="card-body"><p class="text-muted mb-0">No patterns yet.</p></div>
    <?php else: ?>
    <div class="d-flex" style="min-height:300px;">

        <!-- Sidebar nav -->
        <div style="width:210px;min-width:180px;border-right:1px solid #dee2e6;padding:8px 6px;background:#f8f9fa;overflow-y:auto;">
            <ul class="nav flex-column nav-pills gap-1" id="patternTabNav">
                <?php foreach ($patterns as $pi => $pat): ?>
                <?php $chgCnt = count($pat['changes'] ?? []); $cbomCnt = count($pat['cbom_changes'] ?? []); ?>
                <li class="nav-item">
                    <a class="nav-link pat-nav-link py-2 px-2 <?= $pi === 0 ? 'active' : '' ?>"
                       href="#pat_panel_<?= $pat['id'] ?>" data-pat-id="<?= $pat['id'] ?>"
                       style="font-size:12px;line-height:1.4;">
                        <span class="text-muted me-1" style="font-size:10px;">#<?= $pi + 1 ?></span>
                        <?php $sideImgSrc = !empty($pat['image']) ? upload_url('patterns/' . $pat['image']) : (!empty($product['image']) ? upload_url('products/' . $product['image']) : null); ?>
                        <?php if ($sideImgSrc): ?><img src="<?= $sideImgSrc ?>" onerror="this.style.display='none'" style="height:20px;width:20px;object-fit:cover;border-radius:3px;vertical-align:middle;margin-right:3px;"><?php endif; ?>
                        <strong><?= esc($pat['name']) ?></strong>
                        <?php if (!empty($pat['pattern_code'])): ?><span class="badge bg-secondary ms-1" style="font-size:9px;"><?= esc($pat['pattern_code']) ?></span><?php endif; ?>
                        <?php if ($pat['is_default']): ?> <span class="badge bg-primary" style="font-size:9px;">DEF</span><?php endif; ?>
                        <br><span class="ms-3" style="font-size:10px;color:#888;">
                            <?php if ($chgCnt): ?><span class="badge bg-light text-dark border"><?= $chgCnt ?> chg</span> <?php endif; ?>
                            <?php if ($cbomCnt): ?><span class="badge bg-light text-dark border"><?= $cbomCnt ?> cbom</span><?php endif; ?>
                        </span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Content panels -->
        <div class="flex-fill p-3" id="patternTabContent" style="overflow-x:auto;">
            <?php foreach ($patterns as $pi => $pat): ?>
            <div class="pat-panel <?= $pi === 0 ? '' : 'd-none' ?>" id="pat_panel_<?= $pat['id'] ?>">

                <!-- Pattern header -->
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <?php
                        $patImgSrc = !empty($pat['image']) ? upload_url('patterns/' . $pat['image']) : (!empty($product['image']) ? upload_url('products/' . $product['image']) : null);
                        $patImgTitle = !empty($pat['image']) ? 'Pattern image' : 'Product default image';
                        ?>
                        <?php if ($patImgSrc): ?>
                        <img src="<?= $patImgSrc ?>" alt="" title="<?= $patImgTitle ?>"
                             onerror="this.onerror=null;this.style.display='none';this.parentElement.insertAdjacentHTML('afterbegin','<span class=\'d-inline-flex align-items-center justify-content-center text-muted\' style=\'height:48px;width:48px;border-radius:5px;border:1px solid #ddd;background:#f0f0f0;\'><i class=\'bi bi-image\' style=\'font-size:20px;\'></i></span>')"
                             style="height:48px;width:48px;object-fit:cover;border-radius:5px;border:1px solid #ddd;cursor:pointer;"
                             data-img="<?= $patImgSrc ?>" data-name="<?= esc($pat['name']) ?> (<?= $patImgTitle ?>)"
                             class="pattern-thumb-preview">
                        <?php endif; ?>
                        <div>
                            <h6 class="mb-0">
                                <?php if (!empty($pat['pattern_code'])): ?><span class="badge bg-secondary me-1" style="font-size:11px;"><?= esc($pat['pattern_code']) ?></span><?php endif; ?>
                                <?= esc($pat['name']) ?>
                                <?php if (!empty($pat['global_pattern_name']) && $pat['global_pattern_name'] != $pat['name']): ?>
                                <small class="text-muted">(<?= esc($pat['global_pattern_name']) ?>)</small>
                                <?php endif; ?>
                                <?php if ($pat['is_default']): ?><span class="badge bg-primary ms-1">Default</span><?php endif; ?>
                            </h6>
                            <?php if (!empty($pat['tamil_name'])): ?><small class="text-muted"><?= esc($pat['tamil_name']) ?></small><?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex gap-1 flex-wrap">
                        <?php if (!empty($templates)): ?>
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="openPatternImport(<?= $pat['id'] ?>)" title="Import template"><i class="bi bi-download"></i> Import Template</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="openPatternFromPattern(<?= $pat['id'] ?>)" title="Copy BOM changes from another pattern"><i class="bi bi-arrow-down-up"></i> Copy from Pattern</button>
                        <?php endif; ?>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editPatternModal_<?= $pat['id'] ?>" title="Edit pattern"><i class="bi bi-pencil"></i></button>
                        <?php if (!$pat['is_default']): ?>
                        <a href="<?= base_url('products/deletePattern/' . $pat['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this pattern?')"><i class="bi bi-trash"></i></a>
                        <?php endif; ?>
                    </div>
                </div>


                <!-- Edit Pattern Modal -->
                <div class="modal fade" id="editPatternModal_<?= $pat['id'] ?>" tabindex="-1">
                    <div class="modal-dialog"><div class="modal-content">
                        <form action="<?= base_url('products/updatePattern/' . $pat['id']) ?>" method="post" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <div class="modal-header"><h5 class="modal-title">Edit Pattern</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                            <div class="modal-body">
                                <div class="mb-2"><label class="form-label">Pattern Name</label><input type="text" class="form-control" name="name" value="<?= esc($pat['name']) ?>" required></div>
                                <div class="mb-2"><label class="form-label">Tamil Name</label><input type="text" class="form-control" name="tamil_name" value="<?= esc($pat['tamil_name'] ?? '') ?>"></div>
<div class="mb-2"><label class="form-label">Pattern Code</label><input type="text" class="form-control form-control-sm" value="<?= esc($pat['pattern_code'] ?? '') ?>" readonly><div class="form-text text-muted">Auto-generated. Cannot be changed.</div></div>
                                <div class="mb-2"><label class="form-label">Short Label Name <small class="text-muted">&lt;20 chars</small></label><input type="text" class="form-control" name="short_name" maxlength="60" value="<?= esc($pat['short_name'] ?? '') ?>" placeholder="e.g. 1+1CUT"></div>
                                <div class="mb-2">
                                    <label class="form-label">Image</label>
                                    <?php $modalImgSrc = !empty($pat['image']) ? upload_url('patterns/' . $pat['image']) : (!empty($product['image']) ? upload_url('products/' . $product['image']) : null); ?>
                                    <?php if ($modalImgSrc): ?>
                                    <div class="mb-1"><img src="<?= $modalImgSrc ?>" onerror="this.style.display='none'" style="height:60px;border-radius:4px;border:1px solid #ddd;">
                                    <label class="ms-2 text-danger" style="font-size:12px;"><input type="checkbox" name="remove_image" value="1"> Remove image</label></div>
                                    <?php endif; ?>
                                    <input type="file" class="form-control form-control-sm" name="pattern_image" accept="image/*">
                                </div>
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
                        </form>
                    </div></div>
                </div>

                <?php if (!$pat['is_default']): ?>
                <!-- BOM Changes -->
                <div class="mb-3">
                    <div class="fw-bold mb-1" style="font-size:13px;">BOM Changes</div>
                    <form action="<?= base_url('products/savePatternChanges/' . $pat['id']) ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="pattern-changes" id="changes_<?= $pat['id'] ?>">
                            <?php if (!empty($pat['changes'])): ?>
                            <?php foreach ($pat['changes'] as $ci => $ch): ?>
                            <div class="change-row row mb-1 gx-1 align-items-center" style="background:#fff8f0;padding:5px 3px;border-radius:4px;">
                                <div class="col-md-1"><select class="form-select form-select-sm change-action-sel" name="change_action[]"><option value="add" <?= $ch['action']=='add'?'selected':'' ?>>ADD</option><option value="remove" <?= $ch['action']=='remove'?'selected':'' ?>>REMOVE</option><option value="replace" <?= $ch['action']=='replace'?'selected':'' ?>>REPLACE</option></select></div>
                                <div class="col-md-2"><select class="form-select form-select-sm" name="change_part_id[]"><option value="">Part</option><?php foreach ($parts as $p): ?><option value="<?= $p['id'] ?>" <?= $ch['part_id']==$p['id']?'selected':'' ?>><?= esc($p['name']) ?></option><?php endforeach; ?></select></div>
                                <div class="col-md-1"><input type="text" class="form-control form-control-sm" name="change_part_pcs[]" value="<?= esc($ch['part_pcs'] ?? '') ?>" placeholder="Pcs"></div>
                                <div class="col-md-1"><select class="form-select form-select-sm" name="change_scale[]"><option value="">Scale</option><option value="Per Inch" <?= ($ch['scale']??'')=='Per Inch'?'selected':'' ?>>Per Inch</option><option value="Per Pair" <?= ($ch['scale']??'')=='Per Pair'?'selected':'' ?>>Per Pair</option><option value="Per Kanni" <?= ($ch['scale']??'')=='Per Kanni'?'selected':'' ?>>Per Kanni</option></select></div>
                                <div class="col-md-2"><?php $chVg = array_map('trim', explode(',', $ch['variation_group'] ?? '')); ?><div class="vg-check-wrap" style="max-height:60px;overflow-y:auto;border:1px solid #ccc;padding:2px 5px;border-radius:4px;font-size:11px;background:#fff;"><?php if (empty($variationGroups)): ?><span class="text-muted">No groups</span><?php else: foreach ($variationGroups as $vg): ?><label class="d-block mb-0 text-nowrap"><input type="checkbox" name="change_variation_group[<?= $ci ?>][]" value="<?= esc($vg) ?>" <?= in_array($vg,$chVg)?'checked':'' ?>> <?= esc($vg) ?></label><?php endforeach; endif; ?></div></div>
                                <div class="col-md-2"><select class="form-select form-select-sm" name="change_podi_id[]"><option value="">Podi</option><?php foreach ($podies as $po): ?><option value="<?= $po['id'] ?>" <?= ($ch['podi_id']??'')==$po['id']?'selected':'' ?>><?= esc($po['name']) ?></option><?php endforeach; ?></select></div>
                                <div class="col-md-1"><input type="text" class="form-control form-control-sm" name="change_podi_pcs[]" value="<?= esc($ch['podi_pcs'] ?? '') ?>" placeholder="P.Pcs"></div>
                                <div class="col-md-1 replace-col" style="<?= $ch['action']!='replace'?'display:none':'' ?>"><select class="form-select form-select-sm" name="change_replace_part_id[]"><option value="">Replace?</option><?php foreach ($parts as $p): ?><option value="<?= $p['id'] ?>" <?= ($ch['replace_part_id']??'')==$p['id']?'selected':'' ?>><?= esc($p['name']) ?></option><?php endforeach; ?></select></div>
                                <div class="col-auto"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.change-row').remove()">&times;</button></div>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm mt-1 add-change-btn" data-pattern="<?= $pat['id'] ?>">+ Add Change</button>
                        <button type="submit" class="btn btn-success btn-sm mt-1">Save Changes</button>
                    </form>
                </div>

                <!-- Size-wise CBOM button -->
                <?php if (!empty($patternVariations)): ?>
                <div class="mt-1">
                    <a href="<?= base_url('products/patternCbom/' . $pat['id']) ?>" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-grid-3x2"></i> Size-wise Changes (CBOM Level)
                        <?php if (!empty($pat['cbom_changes'])): ?><span class="badge bg-secondary ms-1"><?= count($pat['cbom_changes']) ?></span><?php endif; ?>
                    </a>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <p class="text-muted mb-0" style="font-size:13px;">Default pattern uses the base BOM as-is. Create new patterns to define variants.</p>
                <?php endif; ?>

            </div><!-- end pat_panel -->
            <?php endforeach; ?>
        </div><!-- end content panels -->
    </div><!-- end d-flex -->
    <?php endif; ?>
</div><!-- end patterns card -->


<?php if (!empty($templates)): ?>
<div class="modal fade" id="patternImportModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form id="patternImportForm" method="post">
            <?= csrf_field() ?>
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-download"></i> Import Template into Pattern</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="text-muted" style="font-size:13px;">BOM items will be appended as ADD changes to this pattern.</p>
                <div style="max-height:220px;overflow-y:auto;border:1px solid #dee2e6;padding:8px;border-radius:4px;">
                    <?php foreach ($templates as $t): ?>
                    <label class="d-flex align-items-center gap-2 mb-2" style="cursor:pointer;">
                        <input type="checkbox" name="template_id[]" value="<?= $t['id'] ?>">
                        <span><strong><?= esc($t['name']) ?></strong><?php if (!empty($t['type_name'])): ?> <small class="text-muted">[<?= esc($t['type_name']) ?>]</small><?php endif; ?><?php if (!empty($t['description'])): ?> <small class="text-muted">— <?= esc($t['description']) ?></small><?php endif; ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-info">Import Selected</button></div>
        </form>
    </div></div>
</div>
<?php endif; ?>

<!-- Copy from Pattern Modal -->
<div class="modal fade" id="patternFromPatternModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form id="patternFromPatternForm" method="post">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-down-up"></i> Copy from Pattern</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted" style="font-size:13px;">Select a source pattern. Its BOM changes will be <strong>appended</strong> to the current pattern.</p>
                <div class="mb-3">
                    <label class="form-label">Source Pattern</label>
                    <select class="form-select" id="sourcePatternSelect" name="source_pattern_id" required></select>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="also_cbom" value="1" id="alsoCbomCheck">
                    <label class="form-check-label" for="alsoCbomCheck">Also copy Size-wise CBOM changes</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Copy</button>
            </div>
        </form>
    </div></div>
</div>

<div class="modal fade" id="addPatternModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <form action="<?= base_url('products/addPattern/' . $product['id']) ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="modal-header"><h5 class="modal-title">Add Pattern</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3"><label class="form-label">Select Existing Pattern Name</label>
                <select class="form-select" name="pattern_name_id" id="pattern_name_id_sel" onchange="toggleNewPatternFields(this.value)">
                    <option value="">-- Select or type new below --</option>
                    <?php foreach ($patternNames as $pn): ?>
                    <option value="<?= $pn['id'] ?>"><?= esc($pn['name']) ?><?= !empty($pn['tamil_name']) ? ' (' . esc($pn['tamil_name']) . ')' : '' ?></option>
                    <?php endforeach; ?>
                </select></div>
                <div class="mb-3"><label class="form-label">Short Label Name <small class="text-muted">&lt;20 chars</small></label><input type="text" class="form-control" name="short_name" maxlength="60" placeholder="e.g. 1+1CUT"></div>
                <div id="new_pattern_fields">
                    <hr><p class="text-muted" style="font-size:12px;">Or create a new pattern name:</p>
                    <div class="mb-2"><label class="form-label">New Pattern Name</label><input type="text" class="form-control" name="new_pattern_name" placeholder="e.g., 1+1 Cutting"></div>
                    <div class="mb-2"><label class="form-label">Tamil Name (optional)</label><input type="text" class="form-control" name="new_pattern_tamil"></div>
                </div>
                <div class="mb-2"><label class="form-label">Pattern Image <small class="text-muted">(optional)</small></label><input type="file" class="form-control form-control-sm" name="pattern_image" accept="image/*"></div>
                <?php if (!empty($templates)): ?>
                <hr><div class="mb-2"><label class="form-label">Import BOM from Templates <small class="text-muted">(optional)</small></label>
                <div style="max-height:160px;overflow-y:auto;border:1px solid #dee2e6;padding:8px;border-radius:4px;">
                    <?php foreach ($templates as $t): ?>
                    <label class="d-flex align-items-center gap-2 mb-1" style="cursor:pointer;"><input type="checkbox" name="import_template_id[]" value="<?= $t['id'] ?>">
                    <span><strong><?= esc($t['name']) ?></strong><?php if (!empty($t['type_name'])): ?> <small class="text-muted">[<?= esc($t['type_name']) ?>]</small><?php endif; ?></span></label>
                    <?php endforeach; ?>
                </div></div>
                <?php endif; ?>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Add Pattern</button></div>
        </form>
    </div></div>
</div>

<div id="patThumbModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:8px;padding:12px;max-width:340px;width:90%;text-align:center;position:relative;">
    <button id="patThumbModalClose" style="position:absolute;top:6px;right:10px;background:none;border:none;font-size:18px;cursor:pointer;">&times;</button>
    <p id="patThumbModalName" style="font-weight:600;margin-bottom:8px;"></p>
    <img id="patThumbModalImg" src="" style="max-width:100%;max-height:300px;object-fit:contain;border-radius:4px;">
  </div>
</div>

<?php $this->endSection() ?>
<?php $this->section('scripts') ?>
<script>
// Sidebar tab switching
document.querySelectorAll('.pat-nav-link').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelectorAll('.pat-nav-link').forEach(function(l) { l.classList.remove('active'); });
        document.querySelectorAll('.pat-panel').forEach(function(p) { p.classList.add('d-none'); });
        this.classList.add('active');
        var panelId = this.getAttribute('href').replace('#', '');
        var panel = document.getElementById(panelId);
        if (panel) panel.classList.remove('d-none');
        history.replaceState(null, '', '#' + panelId);
    });
});
(function() {
    var hash = window.location.hash;
    if (hash) {
        var link = document.querySelector('.pat-nav-link[href="' + hash + '"]');
        if (link) link.click();
    }
})();

var partsOpts = '';
<?php foreach ($parts as $p): ?>
partsOpts += '<option value="<?= $p['id'] ?>"><?= addslashes($p['name']) ?></option>';
<?php endforeach; ?>
var podiesOpts = '';
<?php foreach ($podies as $po): ?>
podiesOpts += '<option value="<?= $po['id'] ?>"><?= addslashes($po['name']) ?></option>';
<?php endforeach; ?>

var allPatterns = <?= json_encode(array_map(fn($p) => ['id' => $p['id'], 'name' => $p['name'], 'is_default' => (bool)$p['is_default']], $patterns ?? [])) ?>;

function openPatternFromPattern(patId) {
    var form = document.getElementById('patternFromPatternForm');
    form.action = '<?= base_url('products/importPatternToPattern/') ?>' + patId;
    var sel = document.getElementById('sourcePatternSelect');
    sel.innerHTML = '<option value="">-- Select source pattern --</option>';
    allPatterns.forEach(function(p) {
        if (p.id == patId || p.is_default) return;
        var opt = document.createElement('option');
        opt.value = p.id;
        opt.textContent = p.name;
        sel.appendChild(opt);
    });
    document.getElementById('alsoCbomCheck').checked = false;
    new bootstrap.Modal(document.getElementById('patternFromPatternModal')).show();
}

function toggleNewPatternFields(val) { document.getElementById('new_pattern_fields').style.display = val ? 'none' : ''; }

function openPatternImport(patternId) {
    document.getElementById('patternImportForm').action = '<?= base_url('products/importTemplateToPattern/') ?>' + patternId;
    document.querySelectorAll('#patternImportModal input[type=checkbox]').forEach(function(cb){ cb.checked = false; });
    new bootstrap.Modal(document.getElementById('patternImportModal')).show();
}

var changeIdx = {};
document.querySelectorAll('.add-change-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var patId = this.dataset.pattern;
        if (!changeIdx[patId]) changeIdx[patId] = document.querySelectorAll('#changes_' + patId + ' .change-row').length;
        var ci = changeIdx[patId];
        var container = document.getElementById('changes_' + patId);
        var vgWrap = '<div class="vg-check-wrap" style="max-height:60px;overflow-y:auto;border:1px solid #ccc;padding:2px 5px;border-radius:4px;font-size:11px;background:#fff;">';
        <?php foreach ($variationGroups as $vg): ?>
        vgWrap += '<label class="d-block mb-0 text-nowrap"><input type="checkbox" name="change_variation_group[' + ci + '][]" value="<?= esc($vg) ?>"> <?= esc($vg) ?></label>';
        <?php endforeach; ?>
        <?php if (empty($variationGroups)): ?>vgWrap += '<span class="text-muted">No groups</span>';<?php endif; ?>
        vgWrap += '</div>';
        var html = '<div class="change-row row mb-1 gx-1 align-items-center" style="background:#fff8f0;padding:5px 3px;border-radius:4px;">' +
            '<div class="col-md-1"><select class="form-select form-select-sm change-action-sel" name="change_action[]"><option value="add">ADD</option><option value="remove">REMOVE</option><option value="replace">REPLACE</option></select></div>' +
            '<div class="col-md-2"><select class="form-select form-select-sm" name="change_part_id[]"><option value="">Part</option>' + partsOpts + '</select></div>' +
            '<div class="col-md-1"><input type="text" class="form-control form-control-sm" name="change_part_pcs[]" placeholder="Pcs"></div>' +
            '<div class="col-md-1"><select class="form-select form-select-sm" name="change_scale[]"><option value="">Scale</option><option value="Per Inch">Per Inch</option><option value="Per Pair">Per Pair</option><option value="Per Kanni">Per Kanni</option></select></div>' +
            '<div class="col-md-2">' + vgWrap + '</div>' +
            '<div class="col-md-2"><select class="form-select form-select-sm" name="change_podi_id[]"><option value="">Podi</option>' + podiesOpts + '</select></div>' +
            '<div class="col-md-1"><input type="text" class="form-control form-control-sm" name="change_podi_pcs[]" placeholder="P.Pcs"></div>' +
            '<div class="col-md-1 replace-col" style="display:none"><select class="form-select form-select-sm" name="change_replace_part_id[]"><option value="">Replace?</option>' + partsOpts + '</select></div>' +
            '<div class="col-auto"><button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest(\'.change-row\').remove()">&times;</button></div>' +
            '</div>';
        container.insertAdjacentHTML('beforeend', html);
        changeIdx[patId] = ci + 1;
    });
});
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('change-action-sel')) {
        var row = e.target.closest('.change-row');
        row.querySelector('.replace-col').style.display = (e.target.value === 'replace') ? '' : 'none';
    }
});
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.pattern-thumb-preview').forEach(function(img) {
        img.addEventListener('click', function() {
            document.getElementById('patThumbModalImg').src = this.dataset.img;
            document.getElementById('patThumbModalName').textContent = this.dataset.name;
            document.getElementById('patThumbModal').style.display = 'flex';
        });
    });
    document.getElementById('patThumbModalClose').addEventListener('click', function() { document.getElementById('patThumbModal').style.display = 'none'; });
    document.getElementById('patThumbModal').addEventListener('click', function(e) { if (e.target === this) this.style.display = 'none'; });
});
</script>
<?php $this->endSection() ?>
