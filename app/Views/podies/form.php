<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="card">
    <div class="card-body">
        <form action="<?= base_url('podies/' . (isset($item) ? 'update/' . $item['id'] : 'store')) ?>" method="post">
            <?= csrf_field() ?>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" class="form-control" name="name" value="<?= esc($item['name'] ?? '') ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Number</label>
                    <input type="text" class="form-control" name="number" value="<?= esc($item['number'] ?? '') ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Weight Per Pc</label>
                    <input type="text" class="form-control" name="weight" value="<?= esc($item['weight'] ?? '') ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-success"><i class="bi bi-check"></i> Save</button>
            <a href="<?= base_url('podies') ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php $this->endSection() ?>
