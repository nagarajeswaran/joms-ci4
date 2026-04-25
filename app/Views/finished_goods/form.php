<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="card" style="max-width:700px">
    <div class="card-body">
        <h5 class="mb-3"><?= esc($title) ?></h5>
        <form method="post" action="<?= $item ? base_url('finished-goods/update/'.$item['id']) : base_url('finished-goods/store') ?>">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Name *</label>
                <input type="text" name="name" class="form-control" required value="<?= esc(old('name', $item['name'] ?? '')) ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Tamil Name</label>
                <input type="text" name="tamil_name" class="form-control" value="<?= esc(old('tamil_name', $item['tamil_name'] ?? '')) ?>">
            </div>
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="<?= base_url('finished-goods') ?>" class="btn btn-secondary ms-2">Cancel</a>
        </form>
    </div>
</div>
<?= $this->endSection() ?>