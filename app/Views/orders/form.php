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
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="3"><?= esc($item['notes'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-success"><i class="bi bi-check"></i> Save</button>
            <a href="<?= base_url($isEdit ? 'orders/view/' . $item['id'] : 'orders') ?>" class="btn btn-secondary ms-1">Cancel</a>
        </form>
    </div>
</div>
<?php $this->endSection() ?>
