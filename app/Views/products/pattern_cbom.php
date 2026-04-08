<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="<?= base_url('products/view/' . $product['id'] . '#pat_' . $pattern['id']) ?>" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Product
        </a>
        <strong class="ms-2"><?= esc($product['name']) ?></strong>
        <span class="text-muted ms-1">/ <?= esc($pattern['pattern_name'] ?? 'Pattern #' . $pattern['id']) ?></span>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <strong><i class="bi bi-grid-3x2"></i> Size-wise Changes (CBOM Level)</strong>
        <small class="text-muted ms-2">ADD / REMOVE / REPLACE per part, with qty per size</small>
    </div>
    <div class="card-body">
        <?php if (empty($patternVariations)): ?>
        <div class="alert alert-warning">No variations found for this product type.</div>
        <?php else: ?>
        <?php
        $cbomVarGrouped = [];
        foreach ($patternVariations as $v) {
            $cbomVarGrouped[$v['group_name']][] = $v;
        }
        ?>
        <form action="<?= base_url('products/savePatternCbomChanges/' . $pattern['id']) ?>" method="post">
            <?= csrf_field() ?>
            <div id="cbom_changes_main">
                <?php foreach ($cbomChanges as $ci => $cRow): ?>
                <div class="cbom-change-row border rounded p-2 mb-2" style="background:#f8f9ff;">
                    <div class="row g-2 mb-2 align-items-end flex-wrap">
                        <div class="col-auto">
                            <label class="form-label mb-0" style="font-size:11px;">Action</label>
                            <select class="form-select form-select-sm cbom-action-sel" name="cbom_change[<?= $ci ?>][action]" onchange="onCbomActionChange(this)" style="width:110px;">
                                <option value="replace" <?= $cRow['action']==='replace'?'selected':'' ?>>REPLACE</option>
                                <option value="add"     <?= $cRow['action']==='add'    ?'selected':'' ?>>ADD</option>
                                <option value="remove"  <?= $cRow['action']==='remove' ?'selected':'' ?>>REMOVE</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <label class="form-label mb-0" style="font-size:11px;">Part</label>
                            <select class="form-select form-select-sm" name="cbom_change[<?= $ci ?>][part_id]" style="min-width:180px;">
                                <option value="">Select Part</option>
                                <?php foreach ($parts as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ($cRow['part_id']==$p['id'])?'selected':'' ?>><?= esc($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto cbom-replace-col" style="<?= $cRow['action']==='replace'?'':'display:none;' ?>">
                            <label class="form-label mb-0" style="font-size:11px;">Replace With</label>
                            <select class="form-select form-select-sm" name="cbom_change[<?= $ci ?>][replace_part_id]" style="min-width:180px;">
                                <option value="">Select Part</option>
                                <?php foreach ($parts as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= (($cRow['replace_part_id'] ?? '')==$p['id'])?'selected':'' ?>><?= esc($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <label class="form-label mb-0" style="font-size:11px;">Podi</label>
                            <select class="form-select form-select-sm" name="cbom_change[<?= $ci ?>][podi_id]" style="min-width:130px;">
                                <option value="">Select Podi</option>
                                <?php foreach ($podies as $po): ?>
                                <option value="<?= $po['id'] ?>" <?= (($cRow['podi_id'] ?? '')==$po['id'])?'selected':'' ?>><?= esc($po['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.cbom-change-row').remove()"><i class="bi bi-x"></i> Remove</button>
                        </div>
                    </div>
                    <div class="table-responsive cbom-qty-table" style="<?= $cRow['action']==='remove'?'display:none;':'' ?>">
                        <table class="table table-sm table-bordered mb-0" style="font-size:12px;">
                            <thead><tr class="table-light"><th>Variation</th><th>Size</th><th style="width:110px;">Qty</th></tr></thead>
                            <tbody>
                                <?php foreach ($cbomVarGrouped as $gName => $gVars): ?>
                                <tr class="table-secondary"><td colspan="3"><strong><?= esc($gName) ?></strong></td></tr>
                                <?php foreach ($gVars as $v): ?>
                                <tr>
                                    <td><?= esc($v['name']) ?></td>
                                    <td><?= esc($v['size']) ?></td>
                                    <td><input type="number" step="0.001" class="form-control form-control-sm" name="cbom_change[<?= $ci ?>][qty][<?= $v['id'] ?>]" value="<?= esc($cRow['qtys'][$v['id']] ?? '') ?>" style="width:100px;"></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <template id="cbom_change_tpl">
                <div class="cbom-change-row border rounded p-2 mb-2" style="background:#f8f9ff;">
                    <div class="row g-2 mb-2 align-items-end flex-wrap">
                        <div class="col-auto">
                            <label class="form-label mb-0" style="font-size:11px;">Action</label>
                            <select class="form-select form-select-sm cbom-action-sel" name="cbom_change[__IDX__][action]" onchange="onCbomActionChange(this)" style="width:110px;">
                                <option value="replace">REPLACE</option>
                                <option value="add">ADD</option>
                                <option value="remove">REMOVE</option>
                            </select>
                        </div>
                        <div class="col-auto">
                            <label class="form-label mb-0" style="font-size:11px;">Part</label>
                            <select class="form-select form-select-sm" name="cbom_change[__IDX__][part_id]" style="min-width:180px;">
                                <option value="">Select Part</option>
                                <?php foreach ($parts as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= esc($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto cbom-replace-col">
                            <label class="form-label mb-0" style="font-size:11px;">Replace With</label>
                            <select class="form-select form-select-sm" name="cbom_change[__IDX__][replace_part_id]" style="min-width:180px;">
                                <option value="">Select Part</option>
                                <?php foreach ($parts as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= esc($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <label class="form-label mb-0" style="font-size:11px;">Podi</label>
                            <select class="form-select form-select-sm" name="cbom_change[__IDX__][podi_id]" style="min-width:130px;">
                                <option value="">Select Podi</option>
                                <?php foreach ($podies as $po): ?>
                                <option value="<?= $po['id'] ?>"><?= esc($po['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.cbom-change-row').remove()"><i class="bi bi-x"></i> Remove</button>
                        </div>
                    </div>
                    <div class="table-responsive cbom-qty-table">
                        <table class="table table-sm table-bordered mb-0" style="font-size:12px;">
                            <thead><tr class="table-light"><th>Variation</th><th>Size</th><th style="width:110px;">Qty</th></tr></thead>
                            <tbody>
                                <?php foreach ($cbomVarGrouped as $gName => $gVars): ?>
                                <tr class="table-secondary"><td colspan="3"><strong><?= esc($gName) ?></strong></td></tr>
                                <?php foreach ($gVars as $v): ?>
                                <tr>
                                    <td><?= esc($v['name']) ?></td>
                                    <td><?= esc($v['size']) ?></td>
                                    <td><input type="number" step="0.001" class="form-control form-control-sm" name="cbom_change[__IDX__][qty][<?= $v['id'] ?>]" value="" style="width:100px;"></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

            <div class="d-flex gap-2 mt-3">
                <button type="button" class="btn btn-outline-secondary" onclick="addCbomChangeRow()"><i class="bi bi-plus"></i> Add Part Change</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check"></i> Save Changes</button>
                <a href="<?= base_url('products/view/' . $product['id'] . '#pat_' . $pattern['id']) ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
function onCbomActionChange(sel) {
    var row = sel.closest('.cbom-change-row');
    var action = sel.value;
    var rc = row.querySelector('.cbom-replace-col');
    var qt = row.querySelector('.cbom-qty-table');
    if (rc) rc.style.display = (action === 'replace') ? '' : 'none';
    if (qt) qt.style.display = (action === 'remove')  ? 'none' : '';
}
function addCbomChangeRow() {
    var tpl = document.getElementById('cbom_change_tpl');
    if (!tpl) return;
    var container = document.getElementById('cbom_changes_main');
    var idx = container.querySelectorAll('.cbom-change-row').length;
    var tmp = document.createElement('div');
    tmp.innerHTML = tpl.innerHTML.replace(/__IDX__/g, idx);
    container.appendChild(tmp.firstElementChild);
}
</script>

<?php $this->endSection() ?>
