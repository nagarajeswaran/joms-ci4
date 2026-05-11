<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="card" style="max-width:600px;">
    <div class="card-body">
        <?php $isEdit = isset($item); ?>
        <form action="<?= base_url($isEdit ? 'orders/update/' . $item['id'] : 'orders/store') ?>" method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Order Title <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="title" value="<?= esc($item['title'] ?? '') ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Client</label>
                <select class="form-select" name="client_id">
                    <option value="">-- No Client --</option>
                    <?php foreach ($clients as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($item['client_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Stamp</label>
                <select class="form-select" name="stamp_id" id="stampSelect">
                    <option value="">-- No Stamp --</option>
                    <?php foreach ($stamps as $st): ?>
                    <option value="<?= $st['id'] ?>" <?= ($item['stamp_id'] ?? '') == $st['id'] ? 'selected' : '' ?>><?= esc($st['name']) ?></option>
                    <?php endforeach; ?>
                    <option value="__new__">+ Add New Stamp...</option>
                </select>
                <div id="newStampRow" class="mt-2 d-none">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="newStampName" placeholder="Enter new stamp name...">
                        <button type="button" class="btn btn-success" onclick="addNewStamp()"><i class="bi bi-plus"></i> Add</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="cancelNewStamp()">Cancel</button>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="3"><?= esc($item['notes'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-success"><i class="bi bi-check"></i> Save</button>
            <a href="<?= base_url($isEdit ? 'orders/view/' . $item['id'] : 'orders') ?>" class="btn btn-secondary ms-1">Cancel</a>
        </form>
    </div>
</div>
<script>
document.getElementById('stampSelect').addEventListener('change', function() {
    if (this.value === '__new__') {
        document.getElementById('newStampRow').classList.remove('d-none');
        document.getElementById('newStampName').focus();
        this.value = '';
    }
});
function addNewStamp() {
    var name = document.getElementById('newStampName').value.trim();
    if (!name) { alert('Enter a stamp name'); return; }
    var body = new URLSearchParams({name: name});
    fetch('<?= base_url("orders/createStamp") ?>', {method:'POST', body:body})
        .then(function(r){ return r.json(); })
        .then(function(d) {
            if (d.id) {
                var sel = document.getElementById('stampSelect');
                var opt = document.createElement('option');
                opt.value = d.id;
                opt.textContent = name;
                opt.selected = true;
                sel.insertBefore(opt, sel.querySelector('option[value="__new__"]'));
                cancelNewStamp();
            } else {
                alert(d.error || 'Failed to add stamp');
            }
        });
}
function cancelNewStamp() {
    document.getElementById('newStampRow').classList.add('d-none');
    document.getElementById('newStampName').value = '';
}
document.getElementById('newStampName').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); addNewStamp(); }
    if (e.key === 'Escape') cancelNewStamp();
});
</script>
<?php $this->endSection() ?>
