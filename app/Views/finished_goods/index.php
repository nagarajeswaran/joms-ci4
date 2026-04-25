<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Finished Goods</h5>
    <a href="<?= base_url('finished-goods/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Add Finished Good</a>
</div>
<div class="table-responsive">
    <table class="table table-sm table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Name</th>
                <th>Tamil Name</th>
                <th style="width:140px">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= esc($item['name']) ?></td>
                <td><?= esc($item['tamil_name'] ?? '-') ?></td>
                <td>
                    <a href="<?= base_url('finished-goods/edit/'.$item['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                    <a href="<?= base_url('finished-goods/delete/'.$item['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete finished good?')">Del</a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?><tr><td colspan="3" class="text-center text-muted">No finished goods found</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>