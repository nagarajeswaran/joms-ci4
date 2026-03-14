<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
    <h5>QR Labels - <?= esc($product['name']) ?></h5>
    <div class="d-flex gap-2">
        <a href="<?= base_url('stock') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
        <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer"></i> Print Labels</button>
    </div>
</div>

<style>
@media print {
    .d-print-none { display: none !important; }
    body { margin: 0; }
    .label-grid { display: flex; flex-wrap: wrap; gap: 8px; padding: 4px; }
    .label-card { border: 1px solid #333; border-radius: 4px; padding: 6px; text-align: center; width: 140px; break-inside: avoid; }
    .label-card img { width: 100px; height: 100px; }
    .label-card .prod-name { font-size: 9px; font-weight: bold; word-break: break-word; margin: 2px 0; }
    .label-card .var-name  { font-size: 8px; color: #555; }
    .label-card .pat-name  { font-size: 7px; color: #777; font-style: italic; }
}
.label-grid { display: flex; flex-wrap: wrap; gap: 10px; }
.label-card { border: 1px solid #ccc; border-radius: 6px; padding: 8px; text-align: center; width: 150px; }
.label-card img { width: 110px; height: 110px; }
.label-card .prod-name { font-size: 11px; font-weight: bold; margin: 4px 0 2px; word-break: break-word; }
.label-card .var-name  { font-size: 10px; color: #555; }
.label-card .pat-name  { font-size: 9px; color: #888; font-style: italic; }
</style>

<div class="label-grid">
<?php foreach ($patterns as $pat): ?>
    <?php foreach ($variations as $var): ?>
        <div class="label-card">
            <img src="<?= base_url("stock/qr-image/{$product['id']}/{$pat['id']}/{$var['id']}") ?>" alt="QR">
            <div class="prod-name"><?= esc($product['name']) ?><?= $product['sku'] ? ' ('.$product['sku'].')' : '' ?></div>
            <?php if (!$pat['is_default']): ?>
                <div class="pat-name"><?= esc($pat['name']) ?></div>
            <?php endif; ?>
            <div class="var-name"><?= esc($var['name']) ?><?= $var['size'] ? ' '.$var['size'].'"' : '' ?></div>
        </div>
    <?php endforeach; ?>
<?php endforeach; ?>
</div>
<?= $this->endSection() ?>
