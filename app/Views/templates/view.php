<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <a href="<?= base_url('templates') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
        <a href="<?= base_url('templates/edit/' . $template['id']) ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i> Edit</a>
    </div>
</div>
<div class="card mb-3">
    <div class="card-body">
        <div class="row" style="font-size:13px;">
            <div class="col-md-3"><strong>Name:</strong> <?= esc($template['name']) ?></div>
            <div class="col-md-3"><strong>Tamil:</strong> <?= esc($template['tamil_name']) ?></div>
            <div class="col-md-6"><strong>Description:</strong> <?= esc($template['description']) ?></div>
        </div>
    </div>
</div>

<?php
$bomItems = array_filter($items, fn($i) => $i['type'] === 'bom');
$cbomItems = array_filter($items, fn($i) => $i['type'] === 'cbom');
?>

<?php if (!empty($bomItems)): ?>
<div class="card mb-3">
    <div class="card-header"><strong>BOM Items (Logic Based) - <?= count($bomItems) ?> parts</strong></div>
    <div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0" style="font-size:13px;">
            <thead><tr><th>Part</th><th>Pcs</th><th>Scale</th><th>Var Group</th><th>Podi</th><th>Podi Pcs</th></tr></thead>
            <tbody>
                <?php foreach ($bomItems as $b): ?>
                <tr>
                    <td><?= esc($b['part_name'] ?? '') ?></td>
                    <td><?= esc($b['part_pcs'] ?? '') ?></td>
                    <td><?= esc($b['scale'] ?? '') ?></td>
                    <td><?= esc($b['variation_group'] ?? '') ?></td>
                    <td><?= esc($b['podi_name'] ?? '') ?></td>
                    <td><?= esc($b['podi_pcs'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($cbomItems)): ?>
<div class="card mb-3">
    <div class="card-header"><strong>CBOM Items (Variation Wise) - <?= count($cbomItems) ?> parts</strong></div>
    <div class="card-body">
        <?php foreach ($cbomItems as $c): ?>
        <div class="mb-3 border rounded p-2">
            <div class="mb-1" style="font-size:13px;"><strong>Part:</strong> <?= esc($c['part_name'] ?? '') ?> | <strong>Podi:</strong> <?= esc($c['podi_name'] ?? '') ?></div>
            <?php if (!empty($c['quantities'])): ?>
            <table class="table table-sm table-bordered mb-0" style="font-size:12px; width:auto;">
                <thead><tr><th>Variation</th><th>Size</th><th>Part Qty</th><th>Podi Qty</th></tr></thead>
                <tbody>
                    <?php foreach ($c['quantities'] as $q): ?>
                    <tr><td><?= esc($q['variation_name'] ?? '') ?></td><td><?= esc($q['size'] ?? '') ?></td><td><?= esc($q['part_quantity'] ?? '') ?></td><td><?= esc($q['podi_quantity'] ?? '') ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (empty($items)): ?>
<div class="alert alert-info">This template has no items yet. <a href="<?= base_url('templates/edit/' . $template['id']) ?>">Edit to add items</a>.</div>
<?php endif; ?>
<?php $this->endSection() ?>
