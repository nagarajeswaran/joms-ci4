<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted"><?= count($items) ?> pattern names</span>
    <a href="<?= base_url('pattern-names/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Add Pattern Name</a>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" style="font-size:13px;">
            <thead><tr><th>#</th><th>Name</th><th>Tamil Name</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= $item['id'] ?></td>
                    <td><strong><?= esc($item['name']) ?></strong></td>
                    <td><?= esc($item['tamil_name']) ?></td>
                    <td>
                        <a href="<?= base_url('pattern-names/edit/' . $item['id']) ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                        <a href="<?= base_url('pattern-names/delete/' . $item['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?><tr><td colspan="4" class="text-center text-muted py-4">No pattern names yet</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection() ?>
