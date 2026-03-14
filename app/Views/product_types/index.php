<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0"><?= count($items) ?> Product Types</h6>
    <a href="<?= base_url('product-types/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Add New</a>
</div>
<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" style="font-size:13px;">
            <thead>
                <tr><th>#</th><th>Name</th><th>Tamil Name</th><th>Mult. Factor</th><th>Variations</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><strong><?= esc($item['name']) ?></strong></td>
                    <td><?= esc($item['tamil_name'] ?? '') ?></td>
                    <td><span class="badge bg-secondary">x<?= esc($item['multiplication_factor'] ?? '1') ?></span></td>
                    <td>
                        <?php if (!empty($item['variation_names'])): ?>
                            <?php foreach ($item['variation_names'] as $vname): ?>
                            <span class="badge bg-light text-dark border me-1 mb-1" style="font-size:11px;"><?= esc($vname) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= base_url('product-types/edit/' . $item['id']) ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                        <a href="<?= base_url('product-types/delete/' . $item['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete?')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No product types found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection() ?>
