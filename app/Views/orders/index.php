<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<?php
$statuses = ['draft','confirmed','production','completed'];
$colors   = ['draft'=>'secondary','confirmed'=>'primary','production'=>'warning','completed'=>'success','closed'=>'dark'];
$showClosed = isset($_GET['show_closed']);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <?php foreach ($statuses as $s): ?>
        <a href="<?= base_url('orders') . ($s === ($statusFilter ?: '') ? '' : '?status=' . $s) ?>"
           class="btn btn-sm <?= ($statusFilter === $s) ? 'btn-' . $colors[$s] : 'btn-outline-' . $colors[$s] ?> me-1">
            <?= ucfirst($s) ?>
        </a>
        <?php endforeach; ?>
        <?php if ($statusFilter): ?>
        <a href="<?= base_url('orders') ?>" class="btn btn-sm btn-outline-dark ms-1">All</a>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <a href="<?= base_url('orders') . ($showClosed ? '' : '?show_closed=1') ?>" class="btn btn-sm btn-outline-dark">
            <i class="bi bi-eye<?= $showClosed ? '-slash' : '' ?>"></i> <?= $showClosed ? 'Hide' : 'Show' ?> Closed
        </a>
        <button id="btnMerge" class="btn btn-warning btn-sm" style="display:none" onclick="goMerge()">
            <i class="bi bi-layers"></i> Merge Selected (<span id="mergeCount">0</span>)
        </button>
        <button id="btnCombinedPR" class="btn btn-info btn-sm" style="display:none" onclick="goCombinedPR()">
            <i class="bi bi-list-check"></i> Combined Part Req. (<span id="cprCount">0</span>)
        </button>
        <a href="<?= base_url('orders/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> New Order</a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0" style="font-size:13px;">
            <thead>
                <tr>
                    <th style="width:36px"><input type="checkbox" id="chkAll" onchange="toggleAll(this.checked)"></th>
                    <th>#</th>
                    <th>Title</th>
                    <th>Client</th>
                    <th>Status</th>
                    <th>Items</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $i => $o): ?>
                <?php if ($o['status'] === 'closed' && !$showClosed) continue; ?>
                <tr class="<?= $o['status'] === 'closed' ? 'text-muted' : '' ?>">
                    <td><input type="checkbox" class="order-chk" value="<?= $o['id'] ?>" onchange="updateMerge()"></td>
                    <td><?= $o['order_number'] ?: ('#' . $o['id']) ?></td>
                    <td><a href="<?= base_url('orders/view/' . $o['id']) ?>" class="fw-bold text-decoration-none"><?= esc($o['title']) ?></a></td>
                    <td><?= esc($o['client_name'] ?? '—') ?></td>
                    <td><span class="badge bg-<?= $colors[$o['status']] ?? 'secondary' ?>"><?= ucfirst($o['status']) ?></span></td>
                    <td><?= $o['item_count'] ?></td>
                    <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                    <td>
                        <a href="<?= base_url('orders/view/' . $o['id']) ?>" class="btn btn-info btn-sm"><i class="bi bi-eye"></i></a>
                        <?php if ($o['status'] === 'draft'): ?>
                        <a href="<?= base_url('orders/edit/' . $o['id']) ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i></a>
                        <?php endif; ?>
                        <?php if ($o['status'] !== 'closed'): ?>
                        <a href="<?= base_url('orders/split/' . $o['id']) ?>" class="btn btn-outline-secondary btn-sm" title="Split Order"><i class="bi bi-scissors"></i></a>
                        <?php endif; ?>
                        <a href="<?= base_url('orders/delete/' . $o['id']) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this order?')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No orders found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function updateMerge() {
    var checked = document.querySelectorAll('.order-chk:checked').length;
    var btn = document.getElementById('btnMerge');
    var chkAll = document.getElementById('chkAll');
    btn.style.display = checked >= 2 ? '' : 'none';
    document.getElementById('mergeCount').textContent = checked;
    var cprBtn = document.getElementById('btnCombinedPR');
    cprBtn.style.display = checked >= 2 ? '' : 'none';
    document.getElementById('cprCount').textContent = checked;
    var all = document.querySelectorAll('.order-chk');
    chkAll.checked = (checked === all.length);
    chkAll.indeterminate = (checked > 0 && checked < all.length);
}
function goCombinedPR() {
    var ids = [];
    document.querySelectorAll('.order-chk:checked').forEach(cb => ids.push(cb.value));
    if (ids.length < 2) { alert('Select at least 2 orders'); return; }
    var url = BASE_URL + 'index.php/orders/combined-main-part-setup?' + ids.map(id => 'order_ids[]=' + id).join('&');
    window.location.href = url;
}
function toggleAll(val) {
    document.querySelectorAll('.order-chk').forEach(cb => cb.checked = val);
    updateMerge();
}
function goMerge() {
    var ids = [];
    document.querySelectorAll('.order-chk:checked').forEach(cb => ids.push(cb.value));
    if (ids.length < 2) { alert('Select at least 2 orders'); return; }
    var url = BASE_URL + 'index.php/orders/merge-preview?' + ids.map(id => 'order_ids[]=' + id).join('&');
    window.location.href = url;
}
</script>
<?= $this->endSection() ?>