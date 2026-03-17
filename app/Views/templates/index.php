<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted"><?= count($items) ?> templates</span>
    <a href="<?= base_url('templates/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Create Template</a>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" style="font-size:13px;">
            <thead><tr><th>#</th><th>Name</th><th>Product Type</th><th>Tamil Name</th><th>BOM</th><th>CBOM</th><th>Description</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($items as $t): ?>
                <tr>
                    <td><?= $t['id'] ?></td>
                    <td><strong><a href="<?= base_url('templates/view/' . $t['id']) ?>"><?= esc($t['name']) ?></a></strong></td>
                    <td>
                        <?php if (!empty($t['product_type_name'])): ?>
                        <span class="badge bg-secondary"><?= esc($t['product_type_name']) ?></span>
                        <?php else: ?>
                        <span class="text-muted" style="font-size:12px;">General</span>
                        <?php endif; ?>
                    </td>
                    <td><?= esc($t['tamil_name']) ?></td>
                    <td><span class="badge bg-primary"><?= $t['bom_count'] ?? 0 ?></span></td>
                    <td><span class="badge bg-info"><?= $t['cbom_count'] ?? 0 ?></span></td>
                    <td><small class="text-muted"><?= esc(mb_strimwidth($t['description'] ?? '', 0, 60, '...')) ?></small></td>
                    <td>
                        <a href="<?= base_url('templates/edit/' . $t['id']) ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                        <a href="<?= base_url('templates/delete/' . $t['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this template?')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?><tr><td colspan="8" class="text-center text-muted py-4">No templates yet. <a href="<?= base_url('templates/create') ?>">Create one</a></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection() ?>
