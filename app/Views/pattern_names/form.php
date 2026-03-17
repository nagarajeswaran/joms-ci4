<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="card" style="max-width:500px;">
    <div class="card-body">
        <form action="<?= base_url('pattern-names/' . (isset($item) ? 'update/' . $item['id'] : 'store')) ?>" method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Pattern Name</label>
                <input type="text" class="form-control" name="name" value="<?= esc($item['name'] ?? '') ?>" required placeholder="e.g. 1+1 Cutting, Colour, Rasagaula Bunch">
            </div>
            <div class="mb-3">
                <label class="form-label">Tamil Name <small class="text-muted">(optional)</small></label>
                <input type="text" class="form-control" name="tamil_name" value="<?= esc($item['tamil_name'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-success">Save</button>
            <a href="<?= base_url('pattern-names') ?>" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
<?php $this->endSection() ?>
