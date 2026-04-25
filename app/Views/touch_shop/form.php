<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-flask"></i> <?= esc($title) ?></h5>
    <a href="<?= base_url('touch-shops') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger py-2"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="card" style="max-width:620px">
<div class="card-body">
<form method="post" action="<?= isset($isEdit) ? base_url('touch-shops/update/'.$entry['id']) : base_url('touch-shops/store') ?>" enctype="multipart/form-data">
<?= csrf_field() ?>

<div class="row g-3">
    <!-- Serial Number -->
    <div class="col-sm-4">
        <label class="form-label">Serial Number</label>
        <input type="text" class="form-control" value="<?= esc($nextSerial) ?>" readonly>
        <div class="form-text text-muted">Auto-assigned</div>
    </div>

    <!-- Issue Weight -->
    <div class="col-sm-4">
        <label class="form-label">Issue Weight (g) <span class="text-danger">*</span></label>
        <input type="number" step="0.0001" min="0.0001" name="issue_weight_g"
               class="form-control" required value="<?= old('issue_weight_g') ?>">
    </div>

    <!-- Stamp + Touch Shop Name row -->
    <div class="col-sm-4">
        <label class="form-label">Stamp</label>
        <select name="stamp_id" class="form-select">
            <option value="">— Select Stamp —</option>
            <?php foreach ($stamps as $s): ?>
            <option value="<?= $s['id'] ?>" <?= old('stamp_id', $prefill['stamp_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                <?= esc($s['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Touch Shop Name (combo: existing + add new) -->
    <div class="col-sm-8">
        <label class="form-label">Touch Shop Name <small class="text-muted">(who does the testing)</small></label>
        <select name="touch_shop_name" id="selTouchShop" class="form-select">
            <option value="">— Select Touch Shop —</option>
            <?php foreach ($shopNames as $sn): ?>
            <option value="<?= esc($sn['touch_shop_name']) ?>"><?= esc($sn['touch_shop_name']) ?></option>
            <?php endforeach; ?>
            <option value="__new__">＋ Add New Shop Name…</option>
        </select>
        <input type="text" name="touch_shop_name_new" id="inpNewShop" class="form-control mt-2"
               placeholder="Type new touch shop name…" style="display:none" maxlength="100">
    </div>

    <!-- Karigar -->
    <div class="col-sm-6">
        <label class="form-label">Karigar</label>
        <select name="karigar_id" class="form-select">
            <option value="">— Select Karigar —</option>
            <?php
            $currentDept = null;
            foreach ($karigars as $k):
                if ($k['dept'] !== $currentDept):
                    if ($currentDept !== null) echo '</optgroup>';
                    $currentDept = $k['dept'];
                    echo '<optgroup label="'.esc($currentDept ?? 'Other').'">';
                endif;
            ?>
            <option value="<?= $k['id'] ?>" <?= (old('karigar_id', $prefill['karigar_id'] ?? '')) == $k['id'] ? 'selected' : '' ?>>
                <?= esc($k['name']) ?>
            </option>
            <?php endforeach; ?>
            <?php if ($currentDept !== null) echo '</optgroup>'; ?>
        </select>
    </div>

    <!-- Gatti Batch -->
    <div class="col-sm-6">
        <label class="form-label">Link Gatti Batch <small class="text-muted">(optional)</small></label>
        <select name="gatti_stock_id" class="form-select">
            <option value="">— Not Linked —</option>
            <?php foreach ($gattis as $g): ?>
            <option value="<?= $g['id'] ?>" <?= old('gatti_stock_id', $prefill['gatti_stock_id'] ?? '') == $g['id'] ? 'selected' : '' ?>>
                <?= esc($g['batch_number'] ?: 'No batch') ?>
                <?= $g['job_number'] ? ' / Job '.$g['job_number'] : '' ?>
                — <?= number_format($g['weight_g'], 4) ?>g
                <?= $g['touch_pct'] ? ' (T:'.$g['touch_pct'].'%)' : '' ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Sample Image -->
    <div class="col-12">
        <label class="form-label">Sample Image <small class="text-muted">(jpg/png/webp)</small></label>
        <?php if (!empty($prefill['sample_image'])): ?>
        <div class="mb-2">
            <a href="<?= base_url($prefill['sample_image']) ?>" target="_blank">
                <img src="<?= base_url($prefill['sample_image']) ?>" alt="sample" style="height:60px;border-radius:4px;border:1px solid #dee2e6;">
            </a>
            <small class="text-muted ms-2">Current image — upload new to replace</small>
        </div>
        <?php endif; ?>
        <input type="file" name="sample_image" accept="image/*" class="form-control">
    </div>

    <!-- Notes -->
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2"><?= old('notes', $prefill['notes'] ?? '') ?></textarea>
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <?php if (isset($isEdit)): ?>
    <button type="submit" class="btn btn-warning"><i class="bi bi-pencil-square"></i> Save Changes</button>
    <?php else: ?>
    <button type="submit" class="btn btn-primary"><i class="bi bi-flask"></i> Create Entry</button>
    <?php endif; ?>
    <a href="<?= base_url('touch-shops') ?>" class="btn btn-outline-secondary">Cancel</a>
</div>
</form>
</div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
var selShop  = document.getElementById('selTouchShop');
var inpNew   = document.getElementById('inpNewShop');
var LS_KEY   = 'last_touch_shop';

// Pre-select last used from localStorage
var lastShop = localStorage.getItem(LS_KEY) || '';
if (lastShop) {
    // try to find matching option
    for (var i=0; i < selShop.options.length; i++) {
        if (selShop.options[i].value === lastShop) { selShop.value = lastShop; break; }
    }
}

selShop.addEventListener('change', function() {
    inpNew.style.display = this.value === '__new__' ? '' : 'none';
    if (this.value === '__new__') { inpNew.focus(); }
});

// On submit, save resolved name to localStorage
document.querySelector('form').addEventListener('submit', function() {
    var name = selShop.value === '__new__' ? inpNew.value.trim() : selShop.value;
    if (name && name !== '__new__') localStorage.setItem(LS_KEY, name);
});
</script>
<?= $this->endSection() ?>
