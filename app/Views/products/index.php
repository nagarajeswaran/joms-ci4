<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="mb-0"><?= count($items) ?> Products</h6>
    <a href="<?= base_url('products/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> Add Product</a>
</div>

<form method="get" action="<?= base_url('products') ?>" class="mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <input type="text" class="form-control form-control-sm" name="q" value="<?= esc($search ?? '') ?>" placeholder="Search by name, SKU, Tamil name...">
        </div>
        <div class="col-md-4">
            <select class="form-select form-select-sm" name="type">
                <option value="">-- All Product Types --</option>
                <?php foreach ($productTypes as $pt): ?>
                <option value="<?= $pt['id'] ?>" <?= ($filterType ?? '') == $pt['id'] ? 'selected' : '' ?>><?= esc($pt['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-auto">
            <button type="submit" class="btn btn-secondary btn-sm"><i class="bi bi-search"></i> Filter</button>
            <a href="<?= base_url('products') ?>" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
        </div>
    </div>
</form>

<?php
$sort = $sortBy ?? 'name';
$dir = $sortDir ?? 'asc';
$nextDir = $dir === 'asc' ? 'desc' : 'asc';
$q = $search ?? '';
$ft = $filterType ?? '';
function sortUrl($col, $sort, $nextDir, $q, $ft, $dir) {
    $d = ($sort === $col) ? $nextDir : 'asc';
    return base_url('products') . '?sort=' . $col . '&dir=' . $d . ($q ? '&q=' . urlencode($q) : '') . ($ft ? '&type=' . $ft : '');
}
function sortIcon($col, $sort, $dir) {
    if ($sort !== $col) return '<i class="bi bi-arrow-down-up text-muted ms-1" style="font-size:10px;"></i>';
    return $dir === 'asc' ? '<i class="bi bi-arrow-up ms-1" style="font-size:10px;"></i>' : '<i class="bi bi-arrow-down ms-1" style="font-size:10px;"></i>';
}
?>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" style="font-size:13px;">
            <thead>
                <tr>
                    <th>#</th>
                    <th><a href="<?= sortUrl('sku', $sort, $nextDir, $q, $ft, $dir) ?>" class="text-decoration-none text-dark">SKU<?= sortIcon('sku', $sort, $dir) ?></a></th>
                    <th><a href="<?= sortUrl('name', $sort, $nextDir, $q, $ft, $dir) ?>" class="text-decoration-none text-dark">Name<?= sortIcon('name', $sort, $dir) ?></a></th>
                    <th>Tamil Name</th>
                    <th><a href="<?= sortUrl('product_type_name', $sort, $nextDir, $q, $ft, $dir) ?>" class="text-decoration-none text-dark">Product Type<?= sortIcon('product_type_name', $sort, $dir) ?></a></th>
                    <th>Body</th>
                    <th>Main Part</th>
                    <th>Pidi</th>
                    <th><a href="<?= sortUrl('pattern_count', $sort, $nextDir, $q, $ft, $dir) ?>" class="text-decoration-none text-dark">Patterns<?= sortIcon('pattern_count', $sort, $dir) ?></a></th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i => $item): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><small class="text-muted"><?= esc($item['sku'] ?? '') ?></small></td>
                    <td><strong><?= esc($item['name']) ?></strong></td>
                    <td><?= esc($item['tamil_name'] ?? '') ?></td>
                    <td><?= esc($item['product_type_name'] ?? '') ?></td>
                    <td><?= esc($item['body_name'] ?? '') ?></td>
                    <td><?= esc($item['main_part_name'] ?? '') ?></td>
                    <td><?= esc($item['pidi'] ?? '') ?></td>
                    <td><span class="badge bg-info"><?= $item['pattern_count'] ?? 0 ?></span></td>
                    <td>
                        <a href="<?= base_url('products/view/' . $item['id']) ?>" class="btn btn-info btn-sm"><i class="bi bi-eye"></i></a>
                        <a href="<?= base_url('products/edit/' . $item['id']) ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                        <a href="<?= base_url('products/duplicate/' . $item['id']) ?>" class="btn btn-secondary btn-sm" onclick="return confirm('Duplicate this product?')" title="Duplicate"><i class="bi bi-copy"></i></a>
                        <a href="<?= base_url('products/delete/' . $item['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product and all its BOM data?')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?><tr><td colspan="10" class="text-center text-muted py-4">No products found</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $this->endSection() ?>
