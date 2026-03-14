<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <form action="<?= base_url('departments/' . (isset($item) ? 'update/' . $item['id'] : 'store')) ?>" method="post">
            <?= csrf_field() ?>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" value="<?= esc($item['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Tamil Name</label>
                    <input type="text" class="form-control" name="tamil_name" value="<?= esc($item['tamil_name'] ?? '') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Group</label>
                    <select class="form-select" name="department_group_id">
                        <option value="">Select Group</option>
                        <?php foreach ($groups as $g): ?>
                        <option value="<?= $g['id'] ?>" <?= (isset($item) && $item['department_group_id'] == $g['id']) ? 'selected' : '' ?>><?= esc($g['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Wastage</label>
                    <input type="text" class="form-control" name="wastage" value="<?= esc($item['wastage'] ?? '') ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-success"><i class="bi bi-check"></i> Save</button>
            <a href="<?= base_url('departments') ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php $this->endSection() ?>
