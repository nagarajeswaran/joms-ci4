<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0"><?= count($items) ?> Podi</h6>
    <a href="<?= base_url('podies/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Add New</a>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th>#</th><th>Name</th><th>Number</th><th>Weight/Pc</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= esc($item['name']) ?></td>
                    <td><?= esc($item['number'] ?? '') ?></td>
                    <td><?= esc($item['weight'] ?? '') ?></td>
                    <td>
                        <a href="<?= base_url('podies/edit/' . $item['id']) ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                        <a href="<?= base_url('podies/delete/' . $item['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?><tr><td colspan="5" class="text-center text-muted py-4">No podi found</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection() ?>
