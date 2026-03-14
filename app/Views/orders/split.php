<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0"><i class="bi bi-scissors"></i> Split Order — <?= esc($order['order_number']) ?></h5>
        <small class="text-muted"><?= esc($order['title']) ?><?= $order['client_name'] ? ' · ' . esc($order['client_name']) : '' ?></small>
    </div>
    <a href="<?= base_url('orders/view/' . $order['id']) ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Cancel</a>
</div>

<?php if (!in_array($order['status'], ['draft'])): ?>
<div class="alert alert-warning py-2"><i class="bi bi-exclamation-triangle"></i> This order is <strong><?= ucfirst($order['status']) ?></strong>. After split, the new order will be <strong>Draft</strong> — Part Requirements must be regenerated.</div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<form method="post" action="<?= base_url('orders/do-split/' . $order['id']) ?>" id="splitForm">
<?= csrf_field() ?>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Select products to move to the NEW order</strong>
        <div>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAll(true)">All</button>
            <button type="button" class="btn btn-outline-secondary btn-sm ms-1" onclick="toggleAll(false)">None</button>
            <span class="ms-2 text-muted small" id="selCounter">0 of <?= count($items) ?> selected</span>
        </div>
    </div>
    <div class="table-responsive">
    <table class="table table-sm table-hover mb-0">
        <thead class="table-dark">
            <tr>
                <th style="width:40px"><input type="checkbox" id="chkAll" onchange="toggleAll(this.checked)"></th>
                <th>#</th>
                <th>SKU</th>
                <th>Product</th>
                <th>Pattern</th>
                <th class="text-end">Total Qty</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $i => $item): ?>
        <tr class="item-row" data-id="<?= $item['id'] ?>">
            <td><input type="checkbox" name="selected_ids[]" value="<?= $item['id'] ?>" class="item-chk" onchange="updateCounter()"></td>
            <td><?= $i + 1 ?></td>
            <td><span class="badge bg-secondary"><?= esc($item['sku'] ?? '—') ?></span></td>
            <td class="fw-semibold"><?= esc($item['product_name']) ?></td>
            <td><?= esc($item['pattern_name']) ?></td>
            <td class="text-end"><?= (int)($item['total_qty'] ?? 0) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header fw-semibold">New Order Details</div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-5">
                <label class="form-label">New Order Title</label>
                <input type="text" name="new_title" class="form-control" value="<?= esc($order['title']) ?> (Split)" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Client</label>
                <select name="new_client_id" class="form-select">
                    <option value="">-- No Client --</option>
                    <?php foreach ($clients as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($c['id'] == ($order['client_id'] ?? 0)) ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">After Split:</label>
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio" name="split_mode" id="modeA" value="A" checked onchange="toggleMode()">
                <label class="form-check-label" for="modeA">
                    <strong>Option A</strong> — Keep remaining <span id="remainCount"><?= count($items) ?></span> products in <strong><?= esc($order['order_number']) ?></strong> (reset to Draft)
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="split_mode" id="modeB" value="B" onchange="toggleMode()">
                <label class="form-check-label" for="modeB">
                    <strong>Option B</strong> — Close <?= esc($order['order_number']) ?> and create TWO new orders (selected + remaining)
                </label>
            </div>
        </div>

        <div id="modeB_extra" style="display:none" class="border rounded p-3 bg-light">
            <p class="text-muted small mb-2">Details for the second new order (remaining products):</p>
            <div class="row g-2">
                <div class="col-md-5">
                    <label class="form-label form-label-sm">Title for Remaining Order</label>
                    <input type="text" name="title2" class="form-control form-control-sm" value="<?= esc($order['title']) ?> (Remainder)">
                </div>
                <div class="col-md-4">
                    <label class="form-label form-label-sm">Client</label>
                    <select name="client_id2" class="form-select form-select-sm">
                        <option value="">-- No Client --</option>
                        <?php foreach ($clients as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($c['id'] == ($order['client_id'] ?? 0)) ? 'selected' : '' ?>><?= esc($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2">
    <button type="submit" class="btn btn-primary" id="btnSplit" disabled>
        <i class="bi bi-scissors"></i> Split → Create New Order
    </button>
    <a href="<?= base_url('orders/view/' . $order['id']) ?>" class="btn btn-secondary">Cancel</a>
</div>
</form>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
var totalItems = <?= count($items) ?>;

function updateCounter() {
    var checked = document.querySelectorAll('.item-chk:checked').length;
    var remain  = totalItems - checked;
    document.getElementById('selCounter').textContent = checked + ' of ' + totalItems + ' selected';
    document.getElementById('remainCount').textContent = remain;
    document.getElementById('btnSplit').disabled = (checked === 0 || checked >= totalItems);
    document.getElementById('chkAll').checked = (checked === totalItems);
    document.getElementById('chkAll').indeterminate = (checked > 0 && checked < totalItems);
}

function toggleAll(val) {
    document.querySelectorAll('.item-chk').forEach(cb => cb.checked = val);
    updateCounter();
}

function toggleMode() {
    var modeB = document.getElementById('modeB').checked;
    document.getElementById('modeB_extra').style.display = modeB ? '' : 'none';
}
</script>
<?= $this->endSection() ?>
