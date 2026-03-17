<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <form action="<?= base_url('product-types/' . (isset($item) ? 'update/' . $item['id'] : 'store')) ?>" method="post">
            <?= csrf_field() ?>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" value="<?= esc($item['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Tamil Name</label>
                    <input type="text" class="form-control" name="tamil_name" value="<?= esc($item['tamil_name'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Multiplication Factor</label>
                    <input type="number" class="form-control" name="multiplication_factor" value="<?= esc($item['multiplication_factor'] ?? '1') ?>" step="1" min="1">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Variations <small class="text-muted">(select all variations this product type supports)</small></label>
                <div class="mb-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAll(true)">Select All</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm ms-1" onclick="toggleAll(false)">Deselect All</button>
                </div>
                <?php
                $selected = $selectedVariations ?? [];
                foreach ($variationGroups as $groupName => $vars):
                ?>
                <div class="card mb-2">
                    <div class="card-header py-1 px-3 d-flex justify-content-between align-items-center" style="background:#f0f4f8;">
                        <strong style="font-size:13px;"><?= esc($groupName) ?></strong>
                        <button type="button" class="btn btn-link btn-sm py-0 text-secondary" onclick="toggleGroup('grp_<?= preg_replace('/\W/', '_', $groupName) ?>')">Toggle group</button>
                    </div>
                    <div class="card-body py-2 px-3">
                        <div class="row">
                            <?php foreach ($vars as $v): ?>
                            <div class="col-md-2 col-sm-3 col-4 mb-1">
                                <div class="form-check">
                                    <input class="form-check-input var-check grp_<?= preg_replace('/\W/', '_', $groupName) ?>"
                                           type="checkbox" name="variations[]"
                                           value="<?= $v['id'] ?>" id="var_<?= $v['id'] ?>"
                                           <?= in_array((string)$v['id'], array_map('strval', $selected)) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="var_<?= $v['id'] ?>" style="font-size:13px;">
                                        <?= esc($v['name']) ?>
                                        <?php if (!empty($v['size'])): ?><small class="text-muted">(<?= esc($v['size']) ?>)</small><?php endif; ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <button type="submit" class="btn btn-success"><i class="bi bi-check"></i> Save</button>
            <a href="<?= base_url('product-types') ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php $this->endSection() ?>
<?php $this->section('scripts') ?>
<script>
function toggleAll(state) {
    document.querySelectorAll('.var-check').forEach(function(cb) { cb.checked = state; });
}
function toggleGroup(cls) {
    var cbs = document.querySelectorAll('.' + cls);
    var anyUnchecked = Array.from(cbs).some(function(cb) { return !cb.checked; });
    cbs.forEach(function(cb) { cb.checked = anyUnchecked; });
}
</script>
<?php $this->endSection() ?>
