<?= $this->extend('layouts/staff') ?>
<?= $this->section('content') ?>

<form method="get" action="<?= base_url('staff/stock-lookup') ?>" class="mb-3">
    <div class="search-box">
        <i class="bi bi-search"></i>
        <input type="text" name="q" class="form-control" placeholder="Search product, SKU, or pattern" value="<?= esc($query) ?>">
    </div>
    <button type="submit" class="btn btn-primary w-100 mt-2">Search Stock</button>
</form>

<?php if ($query === ''): ?>
    <div class="alert alert-info">Enter a product name, SKU, or pattern code to search.</div>
<?php endif; ?>

<div class="mobile-list">
    <?php foreach ($rows as $row): ?>
        <div class="mobile-item">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <div class="title"><?= esc($row['product_name']) ?></div>
                    <div class="meta"><?= esc($row['sku'] ?: 'No SKU') ?></div>
                </div>
                <span class="badge-soft"><?= (int) $row['qty'] ?> pcs</span>
            </div>
            <div class="mt-2 small text-muted">
                <div>Pattern: <?= esc(($row['pattern_code'] ?: $row['pattern_name']) ?: '-') ?></div>
                <div>Variation: <?= esc($row['variation_name']) ?><?= $row['variation_size'] ? ' / ' . esc($row['variation_size']) . '"' : '' ?></div>
                <div>Location: <?= esc($row['location_name']) ?></div>
                <div>Min Qty: <?= (int) $row['min_qty'] ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($query !== '' && !$rows): ?>
    <div class="alert alert-warning mt-3 mb-0">No stock found for this search.</div>
<?php endif; ?>

<?= $this->endSection() ?>