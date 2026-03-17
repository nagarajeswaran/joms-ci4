<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <form action="<?= base_url('parts/' . (isset($item) ? 'update/' . $item['id'] : 'store')) ?>" method="post">
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
                    <label class="form-label">Weight Per Pc (approx)</label>
                    <input type="text" class="form-control" name="weight" value="<?= esc($item['weight'] ?? '') ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Pcs Per Inch</label>
                    <input type="text" class="form-control" name="pcs" value="<?= esc($item['pcs'] ?? '') ?>">
                </div>
            </div>
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Is Main Part?</label>
                    <select class="form-select" name="is_main_part">
                        <option value="0" <?= (isset($item) && !$item['is_main_part']) ? 'selected' : '' ?>>No</option>
                        <option value="1" <?= (isset($item) && $item['is_main_part']) ? 'selected' : '' ?>>Yes</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Department</label>
                    <select class="form-select" name="department_id">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= (isset($item) && $item['department_id'] == $d['id']) ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Default Podi</label>
                    <select class="form-select" name="podi_id">
                        <option value="">Select Podi</option>
                        <?php foreach ($podies as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= (isset($item) && $item['podi_id'] == $p['id']) ? 'selected' : '' ?>><?= esc($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">Gatti Required Per Kg</label>
                    <input type="text" class="form-control" name="gatti" value="<?= esc($item['gatti'] ?? '') ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-success"><i class="bi bi-check"></i> Save</button>
            <a href="<?= base_url('parts') ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php $this->endSection() ?>
