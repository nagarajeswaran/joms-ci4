<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>
<?php
$statuses = ['draft','confirmed','production','completed'];
$colors   = ['draft'=>'secondary','confirmed'=>'primary','production'=>'warning','completed'=>'success','closed'=>'dark'];
$showClosed = isset($_GET['show_closed']);
?>

<!-- Filters Bar -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <div class="row g-2 align-items-center">

            <!-- Search -->
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="liveSearch" class="form-control" placeholder="Search order, title, client...">
                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('liveSearch').value='';liveFilter()"><i class="bi bi-x"></i></button>
                </div>
            </div>

            <!-- Client filter -->
            <div class="col-md-3">
                <select id="clientFilter" class="form-select form-select-sm" onchange="applyClientFilter(this.value)">
                    <option value="">All Clients</option>
                    <?php foreach ($clients as $cl): ?>
                    <option value="<?= $cl['id'] ?>" <?= ($clientFilter == $cl['id']) ? 'selected' : '' ?>><?= esc($cl['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status pills -->
            <div class="col-md-5 d-flex flex-wrap gap-1 align-items-center justify-content-end">
                <?php foreach ($statuses as $s): ?>
                <a href="<?= buildUrl(['status' => $s, 'client_id' => $clientFilter ?: null]) ?>"
                   class="btn btn-sm <?= ($statusFilter === $s) ? 'btn-'.$colors[$s] : 'btn-outline-'.$colors[$s] ?>">
                    <?= ucfirst($s) ?>
                </a>
                <?php endforeach; ?>
                <?php if ($statusFilter): ?>
                <a href="<?= buildUrl(['status' => null, 'client_id' => $clientFilter ?: null]) ?>" class="btn btn-sm btn-outline-dark">All</a>
                <?php endif; ?>
                <a href="<?= buildUrl(['show_closed' => $showClosed ? null : '1', 'status' => $statusFilter ?: null, 'client_id' => $clientFilter ?: null]) ?>" class="btn btn-sm btn-outline-dark">
                    <i class="bi bi-eye<?= $showClosed ? '-slash' : '' ?>"></i> <?= $showClosed ? 'Hide' : 'Show' ?> Closed
                </a>
            </div>

        </div>
    </div>
</div>

<?php
function buildUrl(array $params): string {
    $base = ['status'=>$_GET['status']??null,'client_id'=>$_GET['client_id']??null,'show_closed'=>$_GET['show_closed']??null];
    $merged = array_merge($base, $params);
    $filtered = array_filter($merged, fn($v) => $v !== null && $v !== '');
    return base_url('orders') . ($filtered ? '?' . http_build_query($filtered) : '');
}
?>

<!-- Top action bar -->
<div class="d-flex justify-content-between align-items-center mb-2">
    <div class="d-flex gap-2">
        <button id="btnMerge" class="btn btn-warning btn-sm" style="display:none" onclick="goMerge()">
            <i class="bi bi-layers"></i> Merge Selected (<span id="mergeCount">0</span>)
        </button>
        <button id="btnCombinedPR" class="btn btn-info btn-sm" style="display:none" onclick="goCombinedPR()">
            <i class="bi bi-list-check"></i> Combined Part Req. (<span id="cprCount">0</span>)
        </button>
    </div>
    <a href="<?= base_url('orders/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> New Order</a>
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
                    <th>Est. Weight</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="ordersTableBody">
                <?php foreach ($items as $i => $o): ?>
                <?php if ($o['status'] === 'closed' && !$showClosed) continue; ?>
                <tr class="order-row <?= $o['status'] === 'closed' ? 'text-muted' : '' ?>"
                    data-search="<?= strtolower(esc($o['order_number'] . ' ' . $o['title'] . ' ' . ($o['client_name'] ?? ''))) ?>">
                    <td><input type="checkbox" class="order-chk" value="<?= $o['id'] ?>" onchange="updateMerge()"></td>
                    <td><?= $o['order_number'] ?: ('#' . $o['id']) ?></td>
                    <td><a href="<?= base_url('orders/view/' . $o['id']) ?>" class="fw-bold text-decoration-none"><?= esc($o['title']) ?></a></td>
                    <td><?= esc($o['client_name'] ?? '—') ?></td>
                    <td><span class="badge bg-<?= $colors[$o['status']] ?? 'secondary' ?>"><?= ucfirst($o['status']) ?></span></td>
                    <td><?= $o['item_count'] ?></td>
                    <td><?= isset($o['estimated_weight']) && $o['estimated_weight'] !== null ? number_format($o['estimated_weight'], 2) . ' g' : '—' ?></td>
                    <td><?= date('d M Y H:i', strtotime($o['created_at'])) ?></td>
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
                <tr id="emptyRow"><td colspan="9" class="text-center text-muted py-4">No orders found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div id="noResultsRow" class="text-center text-muted py-4" style="display:none">No orders match your search</div>
    </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
// ---- Live search ----
var searchInput = document.getElementById('liveSearch');
searchInput.addEventListener('input', liveFilter);

function liveFilter() {
    var q = searchInput.value.toLowerCase().trim();
    var rows = document.querySelectorAll('.order-row');
    var visible = 0;
    rows.forEach(function(row) {
        var match = !q || row.dataset.search.indexOf(q) !== -1;
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById('noResultsRow').style.display = (visible === 0 && rows.length > 0) ? '' : 'none';
    updateMerge();
}

// ---- Client filter (reloads page) ----
function applyClientFilter(val) {
    var url = new URL(window.location.href);
    if (val) url.searchParams.set('client_id', val);
    else url.searchParams.delete('client_id');
    window.location.href = url.toString();
}

// ---- Merge / checkboxes ----
function updateMerge() {
    var checked = document.querySelectorAll('.order-chk:checked').length;
    document.getElementById('btnMerge').style.display = checked >= 2 ? '' : 'none';
    document.getElementById('mergeCount').textContent = checked;
    document.getElementById('btnCombinedPR').style.display = checked >= 2 ? '' : 'none';
    document.getElementById('cprCount').textContent = checked;
    var all = document.querySelectorAll('.order-chk');
    var chkAll = document.getElementById('chkAll');
    chkAll.checked = (checked === all.length && all.length > 0);
    chkAll.indeterminate = (checked > 0 && checked < all.length);
}
function toggleAll(val) {
    document.querySelectorAll('.order-chk').forEach(cb => cb.checked = val);
    updateMerge();
}
function goCombinedPR() {
    var ids = [];
    document.querySelectorAll('.order-chk:checked').forEach(cb => ids.push(cb.value));
    if (ids.length < 2) { alert('Select at least 2 orders'); return; }
    window.location.href = BASE_URL + 'index.php/orders/combined-main-part-setup?' + ids.map(id => 'order_ids[]=' + id).join('&');
}
function goMerge() {
    var ids = [];
    document.querySelectorAll('.order-chk:checked').forEach(cb => ids.push(cb.value));
    if (ids.length < 2) { alert('Select at least 2 orders'); return; }
    window.location.href = BASE_URL + 'index.php/orders/merge-preview?' + ids.map(id => 'order_ids[]=' + id).join('&');
}
</script>
<?= $this->endSection() ?>