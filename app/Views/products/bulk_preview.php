<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-eye text-warning"></i> Preview Changes</h5>
    <a href="<?= base_url('products/bulkEdit') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back / Cancel</a>
</div>

<div class="alert alert-info py-2" style="font-size:13px;">
    <i class="bi bi-info-circle"></i>
    <strong><?= count($changes) ?> row(s) with changes</strong> found.
    Cells highlighted in <span style="background:#fff3cd;padding:2px 6px;border-radius:3px;">yellow</span> will be updated.
    Review carefully before confirming.
</div>

<div class="card mb-3">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover table-bordered mb-0" style="font-size:12px;">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th colspan="4" class="text-center" style="background:#1a3a5c;">Product Fields</th>
                    <th colspan="3" class="text-center" style="background:#1a3c5e;">Pattern Fields</th>
                </tr>
                <tr class="table-secondary">
                    <th></th>
                    <th>SKU</th>
                    <th>Name</th>
                    <th>Tamil Name</th>
                    <th>Short Name</th>
                    <th>Pattern Name</th>
                    <th>Pattern Tamil</th>
                    <th>Pattern Short</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $seenProducts = [];
                foreach ($changes as $i => $ch):
                    $isNewProduct = !in_array($ch['product_id'], $seenProducts);
                    $seenProducts[] = $ch['product_id'];

                    $diffCell = function($old, $new) {
                        if ($old === $new) return '<td>' . esc($new) . '</td>';
                        return '<td style="background:#fff3cd;"><small class="text-muted text-decoration-line-through d-block">' .
                               esc($old ?: '(empty)') . '</small><strong class="text-success">' . esc($new ?: '(empty)') . '</strong></td>';
                    }
                ?>
                <tr <?= $isNewProduct && $i > 0 ? 'style="border-top:2px solid #dee2e6;"' : '' ?>>
                    <td class="text-muted" style="font-size:11px;"><?= $i + 1 ?><br><small class="text-muted">P<?= $ch['product_id'] ?> / PT<?= $ch['pattern_id'] ?></small></td>
                    <?php if ($isNewProduct && $ch['prod_changed']): ?>
                        <?= $diffCell($ch['old_sku'],   $ch['new_sku']) ?>
                        <?= $diffCell($ch['old_name'],  $ch['new_name']) ?>
                        <?= $diffCell($ch['old_tamil'], $ch['new_tamil']) ?>
                        <?= $diffCell($ch['old_short'], $ch['new_short']) ?>
                    <?php elseif ($isNewProduct): ?>
                        <td colspan="4" class="text-muted" style="font-size:11px;"><?= esc($ch['old_name']) ?> <em>(no change)</em></td>
                    <?php else: ?>
                        <td colspan="4" class="text-muted" style="font-size:11px;"><em>(same product — see row above)</em></td>
                    <?php endif; ?>

                    <?php if ($ch['pat_changed']): ?>
                        <?= $diffCell($ch['old_pat_name'],  $ch['new_pat_name']) ?>
                        <?= $diffCell($ch['old_pat_tamil'], $ch['new_pat_tamil']) ?>
                        <?= $diffCell($ch['old_pat_short'], $ch['new_pat_short']) ?>
                    <?php else: ?>
                        <td class="text-muted" style="font-size:11px;"><?= esc($ch['old_pat_name']) ?></td>
                        <td class="text-muted" style="font-size:11px;"><em>no change</em></td>
                        <td class="text-muted" style="font-size:11px;"><em>no change</em></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <form action="<?= base_url('products/bulkConfirm') ?>" method="post">
        <?= csrf_field() ?>
        <button type="submit" class="btn btn-success">
            <i class="bi bi-check-circle"></i> Confirm &amp; Save <?= count($changes) ?> Changes
        </button>
    </form>
    <a href="<?= base_url('products/bulkEdit') ?>" class="btn btn-outline-danger">
        <i class="bi bi-x-circle"></i> Cancel
    </a>
</div>

<?php $this->endSection() ?>
