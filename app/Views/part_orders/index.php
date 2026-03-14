<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Part Orders (PARTORD)</h5>
    <a href="<?= base_url('part-orders/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> New Part Order</a>
</div>
<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="karigar" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Karigar</option>
            <?php foreach ($karigars as $k): ?>
            <option value="<?= $k['id'] ?>" <?= $karigarFilter == $k['id'] ? 'selected' : '' ?>><?= esc($k['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="draft" <?= $statusFilter==='draft'?'selected':'' ?>>Draft</option>
            <option value="posted" <?= $statusFilter==='posted'?'selected':'' ?>>Posted</option>
        </select>
    </div>
</form>
<div class="table-responsive">
<table class="table table-sm table-bordered table-hover">
<thead class="table-dark"><tr><th>Order No</th><th>Karigar</th><th>Linked Order</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach ($items as $row): ?>
<tr>
    <td><strong><?= esc($row['order_number']) ?></strong></td>
    <td><?= esc($row['karigar_name']) ?></td>
    <td><?= $row['client_order_id'] ? 'ORD-'.str_pad($row['client_order_id'],3,'0',STR_PAD_LEFT) : '-' ?></td>
    <td><span class="badge <?= $row['status']==='posted'?'bg-success':'bg-warning text-dark' ?>"><?= ucfirst($row['status']) ?></span></td>
    <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
    <td><a href="<?= base_url('part-orders/view/'.$row['id']) ?>" class="btn btn-sm btn-outline-primary">View</a></td>
</tr>
<?php endforeach; ?>
<?php if (!$items): ?><tr><td colspan="6" class="text-center text-muted">No part orders found</td></tr><?php endif; ?>
</tbody>
</table>
</div>
<?= $this->endSection() ?>
