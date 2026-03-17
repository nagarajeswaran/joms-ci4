<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <form action="<?= base_url('bodies/' . (isset($item) ? 'update/' . $item['id'] : 'store')) ?>" method="post">
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
                    <label class="form-label">Clasp Size</label>
                    <input type="text" class="form-control" name="clasp_size" value="<?= esc($item['clasp_size'] ?? '') ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-success"><i class="bi bi-check"></i> Save</button>
            <a href="<?= base_url('bodies') ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php $this->endSection() ?>
