<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<div class="mb-3 d-flex gap-2">
    <a href="<?= base_url('orders/view/' . $order['id']) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Order</a>
    <a href="<?= base_url('orders/partRequirements/' . $order['id']) ?>" class="btn btn-success btn-sm"><i class="bi bi-list-check"></i> View Part Requirements</a>
</div>
<div class="card">
    <div class="card-header"><strong>Main Part Setup</strong> <small class="text-muted ms-2">Adjust kanni/inch and weight/kanni for accurate calculations</small></div>
    <div class="card-body">
        <?php if (empty($setup)): ?>
        <div class="text-muted">No main parts detected in this order. Parts Requirements will use default kanni values (12/inch).</div>
        <?php else: ?>
        <form action="<?= base_url('orders/saveMainPartSetup/' . $order['id']) ?>" method="post">
            <?= csrf_field() ?>
            <table class="table table-sm" style="font-size:13px;">
                <thead>
                    <tr><th>Part Name</th><th style="width:160px;">Kanni / Inch</th><th style="width:180px;">Weight / Kanni (g)</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($setup as $s): ?>
                    <tr>
                        <input type="hidden" name="part_id[]" value="<?= $s['part_id'] ?>">
                        <td><?= esc($s['part_name']) ?></td>
                        <td><input type="number" class="form-control form-control-sm" name="kanni_per_inch[]" value="<?= esc($s['kanni_per_inch']) ?>" step="0.0001" min="0" required></td>
                        <td><input type="number" class="form-control form-control-sm" name="weight_per_kanni[]" value="<?= esc($s['weight_per_kanni']) ?>" step="0.000001" min="0" required></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" class="btn btn-success"><i class="bi bi-check"></i> Save & View Part Requirements</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php $this->endSection() ?>
