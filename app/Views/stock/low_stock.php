<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.low-card {
    border-radius: 12px; border: 1.5px solid #ffc10740;
    background: #fff; overflow: hidden;
    box-shadow: 0 2px 8px rgba(220,53,69,.08);
}
.low-card-header {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px; background: #fff8e1; border-bottom: 1px solid #ffe082;
}
.low-card-header img, .low-card-header .low-no-img {
    width: 48px; height: 48px; border-radius: 8px; object-fit: cover;
    background: #f4f6f9; display: flex; align-items: center; justify-content: center;
    font-size: 22px; color: #bdbdbd; flex-shrink: 0;
}
.low-card-header .prod-info .prod-name { font-size: 14px; font-weight: 700; }
.low-card-header .prod-info .prod-sku  { font-size: 11px; color: #888; }
.shortage-badge {
    margin-left: auto; background: #dc3545; color: #fff;
    border-radius: 8px; padding: 4px 10px; font-size: 12px; font-weight: 700; white-space: nowrap;
}
.low-var-row { font-size: 13px; }
.low-var-row td { padding: 6px 14px; vertical-align: middle; }
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5><i class="bi bi-exclamation-triangle text-danger"></i> Low Stock Alert</h5>
    <div class="d-flex gap-2">
        <form method="post" action="<?= base_url('orders/fromLowStock') ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-clipboard-plus"></i> Create Order from Low Stock
            </button>
        </form>
        <a href="<?= base_url('stock') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<?php if (empty($rows)): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle"></i> All stock levels are above minimum.</div>
<?php else:
    // Group rows by product_id
    $grouped = [];
    foreach ($rows as $r) {
        $pid = $r['product_id'];
        if (!isset($grouped[$pid])) {
            $grouped[$pid] = ['row' => $r, 'items' => []];
        }
        $grouped[$pid]['items'][] = $r;
    }
?>
<div class="alert alert-warning mb-3">
    <i class="bi bi-exclamation-triangle"></i>
    <strong><?= count($rows) ?> item(s)</strong> across <strong><?= count($grouped) ?> product(s)</strong> are at or below minimum stock level.
</div>

<div class="row g-3">
<?php foreach ($grouped as $pid => $g):
    $prod  = $g['row'];
    $items = $g['items'];
    $totalShortage = array_sum(array_map(fn($r) => max(0, $r['min_qty'] - $r['qty']), $items));

    // Try to get product image from the first available low stock row (stored in product table)
    // We'll use a separate mini-query approach; since $rows come from lowStock(), image not included.
    // We embed product_id for the image path lookup — image column needs to be fetched.
    // For now, use placeholder; image will be added if controller provides it.
    $hasImage = !empty($prod['product_image'] ?? null);
?>
<div class="col-12 col-md-6 col-xl-4">
    <div class="low-card">
        <div class="low-card-header">
            <?php if ($hasImage): ?>
                <img src="<?= upload_url('products/'.$prod['product_image']) ?>" alt="">
            <?php else: ?>
                <div class="low-no-img d-flex align-items-center justify-content-center">
                    <i class="bi bi-gem"></i>
                </div>
            <?php endif; ?>
            <div class="prod-info">
                <div class="prod-name"><?= esc($prod['product_name']) ?></div>
                <?php if (!empty($prod['sku'])): ?>
                    <div class="prod-sku"><?= esc($prod['sku']) ?></div>
                <?php endif; ?>
                <div class="mt-1">
                    <span class="badge bg-danger"><?= count($items) ?> item(s) low</span>
                </div>
            </div>
            <div class="shortage-badge ms-auto">Need <?= $totalShortage ?> more</div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-light" style="font-size:11px;">
                    <tr>
                        <th>Pattern</th>
                        <th>Variation</th>
                        <th>Location</th>
                        <th class="text-center">Qty</th>
                        <th class="text-center">Min</th>
                        <th class="text-center">Need</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $r):
                    $shortage = max(0, $r['min_qty'] - $r['qty']);
                ?>
                <tr class="low-var-row">
                    <td><?= $r['pat_is_default'] ? '<span class="text-muted fst-italic">Default</span>' : esc($r['pattern_name']) ?></td>
                    <td><?= esc($r['variation_name']) ?><?= $r['variation_size'] ? ' '.$r['variation_size'].'"' : '' ?></td>
                    <td><span class="badge bg-secondary" style="font-size:10px;"><?= esc($r['location_name']) ?></span></td>
                    <td class="text-center fw-bold text-danger"><?= $r['qty'] ?></td>
                    <td class="text-center text-muted"><?= $r['min_qty'] ?></td>
                    <td class="text-center fw-bold text-danger">+<?= $shortage ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="p-2 text-end" style="background:#fafafa;border-top:1px solid #f0f0f0;">
            <a href="<?= base_url("stock/entry?product_id=$pid") ?>" class="btn btn-success btn-sm">
                <i class="bi bi-plus-circle"></i> Add Stock
            </a>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?= $this->endSection() ?>
