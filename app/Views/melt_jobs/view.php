<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0"><?= esc($job['job_number']) ?></h5>
        <small class="text-muted">
            Karigar: <strong><?= esc($job['karigar_name']) ?></strong>
            | Cash: ₹<?= number_format($job['cash_rate_per_kg'],2) ?>/kg
            | Fine: <?= $job['fine_pct'] ?>%
            <?php if ($job['required_touch_pct']): ?> | Req. Touch: <strong><?= number_format($job['required_touch_pct'],2) ?>%</strong><?php endif; ?>
            <?php if ($job['required_weight_g']): ?> | Req. Weight: <strong><?= number_format($job['required_weight_g'],3) ?>g</strong><?php endif; ?>
        </small>
    </div>
    <span class="badge <?= $job['status'] === 'posted' ? 'bg-success' : 'bg-warning text-dark' ?> fs-6"><?= ucfirst($job['status']) ?></span>
</div>

<?php if (session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show py-2"><?= session()->getFlashdata('success') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show py-2"><?= session()->getFlashdata('error') ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- ISSUED INPUTS -->
<div class="card mb-3">
<div class="card-header d-flex justify-content-between align-items-center">
    <strong>Issued Inputs</strong>
    <?php if ($job['status'] === 'draft'): ?>
    <button class="btn btn-outline-secondary btn-sm" id="btnToggleAddInput">+ Add Input</button>
    <?php endif; ?>
</div>

<?php if ($job['status'] === 'draft'): ?>
<div id="addInputPanel" style="display:none" class="card-body border-bottom bg-light">
<div class="row g-2 align-items-end">
    <div class="col" style="position:relative">
        <label class="form-label form-label-sm mb-1">Item <small class="text-muted">(type to search)</small></label>
        <input type="text" id="viewAcInput" class="form-control form-control-sm" placeholder="e.g. Fine Silver, KAC..." autocomplete="off" oninput="onViewAcInput(this)">
        <div class="ac-dropdown list-group shadow-sm" id="viewAcDd" style="display:none;position:absolute;z-index:9999;width:100%;max-height:220px;overflow-y:auto;top:100%;left:0"></div>
    </div>
    <div class="col-auto"><label class="form-label form-label-sm mb-1">Weight (g)</label><input type="number" step="0.0001" id="viewInpWeight" class="form-control form-control-sm" placeholder="Weight" oninput="calcViewFine()"></div>
    <div class="col-auto"><label class="form-label form-label-sm mb-1">Touch %</label><input type="number" step="0.0001" id="viewInpTouch" class="form-control form-control-sm" value="0" oninput="calcViewFine()"></div>
    <div class="col-auto"><label class="form-label form-label-sm mb-1">Fine (g)</label><input type="text" id="viewInpFine" class="form-control form-control-sm bg-light" readonly style="width:90px"></div>
    <div class="col-auto pt-1">
        <button type="button" class="btn btn-primary btn-sm" onclick="submitViewInput()">Add</button>
    </div>
</div>
<!-- Hidden form for POST -->
<form id="addInputForm" method="post" action="<?= base_url('melt-jobs/add-input/'.$job['id']) ?>" style="display:none">
    <?= csrf_field() ?>
    <input type="hidden" name="input_type" id="hInputType" value="">
    <input type="hidden" name="item_id"    id="hItemId"    value="">
    <input type="hidden" name="item_name"  id="hItemName"  value="">
    <input type="hidden" name="weight_g"   id="hWeight"    value="">
    <input type="hidden" name="touch_pct"  id="hTouch"     value="">
</form>
</div>
<?php endif; ?>

<div class="card-body p-0">
<table class="table table-sm table-bordered mb-0">
<thead class="table-dark"><tr><th>Type</th><th>Item</th><th class="text-end">Weight (g)</th><th class="text-end">Touch%</th><th class="text-end">Fine (g)</th><?php if ($job['status']==='draft'): ?><th></th><?php endif; ?></tr></thead>
<tbody>
<?php foreach ($inputs as $row): ?>
<tr>
    <td><span class="badge bg-secondary"><?= ucfirst(str_replace('_',' ',$row['input_type'])) ?></span></td>
    <td><?= esc($row['item_name']) ?></td>
    <td class="text-end"><?= number_format($row['weight_g'],4) ?></td>
    <td class="text-end"><?= $row['touch_pct'] ?>%</td>
    <td class="text-end"><?= number_format($row['fine_g'],4) ?></td>
    <?php if ($job['status']==='draft'): ?>
    <td><a href="<?= base_url('melt-jobs/delete-input/'.$row['id']) ?>" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="return confirm('Delete?')"><i class="bi bi-x"></i></a></td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
<?php if (!$inputs): ?><tr><td colspan="6" class="text-center text-muted py-2">No inputs yet</td></tr><?php endif; ?>
</tbody>
<tfoot class="table-light">
<tr>
    <td colspan="2" class="text-end fw-semibold">Total</td>
    <td class="text-end fw-semibold"><?= number_format($totalIssuedWeight,4) ?></td>
    <td></td>
    <td class="text-end fw-semibold"><?= number_format($totalIssuedFine,4) ?></td>
    <?php if ($job['status']==='draft'): ?><td></td><?php endif; ?>
</tr>
</tfoot>
</table>
</div>
</div>

<!-- RECEIVED -->
<div class="card mb-3">
<div class="card-header d-flex justify-content-between align-items-center">
    <strong>Received (from Karigar)</strong>
    <?php if ($job['status'] === 'draft'): ?>
    <button class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('addRecvForm').style.display=document.getElementById('addRecvForm').style.display===''?'none':''">+ Add Receive</button>
    <?php endif; ?>
</div>
<?php if ($job['status'] === 'draft'): ?>
<div id="addRecvForm" style="display:none" class="card-body border-bottom bg-light">
<form method="post" action="<?= base_url('melt-jobs/add-receive/'.$job['id']) ?>">
<?= csrf_field() ?>
<div class="row g-2">
    <div class="col-auto"><select name="receive_type" class="form-select form-select-sm" onchange="toggleByprod(this)">
        <option value="gatti">Gatti</option><option value="byproduct">Byproduct</option>
    </select></div>
    <div class="col-auto" id="byprodDiv" style="display:none"><select name="byproduct_type_id" class="form-select form-select-sm">
        <option value="">-- Type --</option>
        <?php foreach ($byprods as $b): ?><option value="<?= $b['id'] ?>"><?= esc($b['name']) ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-auto"><input type="number" step="0.0001" name="weight_g" class="form-control form-control-sm" placeholder="Weight (g)" required></div>
    <div class="col-auto"><input type="number" step="0.0001" name="touch_pct" class="form-control form-control-sm" placeholder="Touch%" value="0" required></div>
    <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Add</button></div>
</div>
</form>
</div>
<?php endif; ?>
<div class="card-body p-0">
<table class="table table-sm table-bordered mb-0">
<thead class="table-dark"><tr><th>Type</th><th>Detail</th><th class="text-end">Weight (g)</th><th class="text-end">Touch%</th><th class="text-end">Fine (g)</th><?php if ($job['status']==='draft'): ?><th></th><?php endif; ?></tr></thead>
<tbody>
<?php foreach ($receives as $row): ?>
<tr>
    <td><?= ucfirst($row['receive_type']) ?></td>
    <td><?= $row['receive_type']==='byproduct' ? esc($row['byprod_name']) : 'Gatti' ?></td>
    <td class="text-end"><?= number_format($row['weight_g'],4) ?></td>
    <td class="text-end"><?= $row['touch_pct'] ?>%</td>
    <td class="text-end"><?= number_format($row['weight_g']*$row['touch_pct']/100,4) ?></td>
    <?php if ($job['status']==='draft'): ?>
    <td><a href="<?= base_url('melt-jobs/delete-receive/'.$row['id']) ?>" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="return confirm('Delete?')"><i class="bi bi-x"></i></a></td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
<?php if (!$receives): ?><tr><td colspan="6" class="text-center text-muted py-2">No receives yet</td></tr><?php endif; ?>
</tbody>
<tfoot class="table-light">
<tr>
    <td colspan="2" class="text-end fw-semibold">Total</td>
    <td class="text-end fw-semibold"><?= number_format($totalRecvWeight,4) ?></td>
    <td></td>
    <td class="text-end fw-semibold"><?= number_format($totalRecvFine,4) ?></td>
    <?php if ($job['status']==='draft'): ?><td></td><?php endif; ?>
</tr>
</tfoot>
</table>
</div>
</div>

<!-- SUMMARY -->
<div class="row g-3 mb-3">
<div class="col-md-5">
<div class="card">
<div class="card-header fw-semibold">Making Charge Summary</div>
<table class="table table-sm table-borderless mb-0">
<tr><td>Total Issued Fine (g)</td><td class="text-end fw-semibold"><?= number_format($totalIssuedFine,4) ?></td></tr>
<tr><td>Total Received Fine (g)</td><td class="text-end"><?= number_format($totalRecvFine,4) ?></td></tr>
<tr class="table-warning"><td>Fine Difference (loss)</td><td class="text-end fw-semibold"><?= number_format($fineDiff,4) ?></td></tr>
<tr><td>Making Charge Fine (<?= $job['fine_pct'] ?>%)</td><td class="text-end"><?= number_format($mcFine,4) ?></td></tr>
<tr class="table-danger"><td><strong>Net Fine Karigar Owes (g)</strong></td><td class="text-end fw-semibold"><?= number_format($netFine,4) ?></td></tr>
<tr class="table-success"><td><strong>Cash Making Charge (₹)</strong></td><td class="text-end fw-semibold"><?= number_format($mcCash,2) ?></td></tr>
</table>
</div>
</div>
<?php if ($job['required_weight_g'] || $job['required_touch_pct']): ?>
<div class="col-md-4">
<div class="card">
<div class="card-header fw-semibold">Target vs Actual</div>
<table class="table table-sm table-borderless mb-0">
<?php if ($job['required_weight_g']): ?>
<tr>
    <td>Required Weight</td>
    <td class="text-end"><?= number_format($job['required_weight_g'],3) ?>g</td>
</tr>
<tr>
    <td>Gatti Received</td>
    <td class="text-end <?= $gattiWeightSum >= $job['required_weight_g'] ? 'text-success' : 'text-danger' ?>"><?= number_format($gattiWeightSum,3) ?>g</td>
</tr>
<?php endif; ?>
<?php if ($job['required_touch_pct']): ?>
<tr>
    <td>Required Touch</td>
    <td class="text-end"><?= number_format($job['required_touch_pct'],2) ?>%</td>
</tr>
<tr>
    <td>Avg Gatti Touch</td>
    <td class="text-end <?= $avgGattiTouch >= $job['required_touch_pct'] ? 'text-success' : 'text-danger' ?>"><?= number_format($avgGattiTouch,2) ?>%</td>
</tr>
<?php endif; ?>
</table>
</div>
</div>
<?php endif; ?>
</div>

<?php if ($job['status'] === 'draft'): ?>
<form method="post" action="<?= base_url('melt-jobs/post/'.$job['id']) ?>" onsubmit="return confirm('Post to karigar ledger? This cannot be undone.')">
<?= csrf_field() ?>
<button type="submit" class="btn btn-danger"><i class="bi bi-check-circle"></i> Post to Ledger</button>
</form>
<?php endif; ?>

<!-- Kacha Modal -->
<div class="modal fade" id="kachaModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
    <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-gem"></i> Select Kacha Lots</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body p-0">
    <table class="table table-sm table-hover mb-0">
    <thead class="table-dark">
        <tr><th style="width:36px"><input type="checkbox" id="kachaCheckAll"></th><th>Lot No</th><th class="text-end">Weight (g)</th><th class="text-end">Touch %</th><th class="text-end">Fine (g)</th></tr>
    </thead>
    <tbody id="kachaModalBody"></tbody>
    </table>
    </div>
    <div class="modal-footer">
        <span id="kachaSelCount" class="me-auto text-muted small">0 selected</span>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" id="btnAddKacha">Add Selected</button>
    </div>
</div></div></div>

<script id="kachasJson" type="application/json"><?= json_encode($kachas) ?></script>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
var kachas = JSON.parse(document.getElementById('kachasJson').textContent);
var kachaModal;
var viewAcTimer;
var viewAcItem = {};

document.addEventListener('DOMContentLoaded', function() {
    kachaModal = new bootstrap.Modal(document.getElementById('kachaModal'));
    document.getElementById('kachaCheckAll').addEventListener('change', function() {
        document.querySelectorAll('#kachaModalBody .kacha-cb').forEach(cb => cb.checked = this.checked);
        updateKachaCount();
    });
    document.getElementById('btnAddKacha').addEventListener('click', addKachasViaPost);
    document.getElementById('kachaModalBody').addEventListener('change', updateKachaCount);

    var btnToggle = document.getElementById('btnToggleAddInput');
    if (btnToggle) btnToggle.addEventListener('click', function() {
        var p = document.getElementById('addInputPanel');
        p.style.display = p.style.display === 'none' ? '' : 'none';
    });
});

function onViewAcInput(inp) {
    clearTimeout(viewAcTimer);
    var q = inp.value.trim();
    var dd = document.getElementById('viewAcDd');
    if (q.length < 2) { dd.style.display = 'none'; return; }
    viewAcTimer = setTimeout(function() {
        fetch(BASE_URL + 'index.php/melt-jobs/search-items?q=' + encodeURIComponent(q))
            .then(r => r.json()).then(items => renderViewAcDd(items, inp));
    }, 280);
}

function renderViewAcDd(items, inp) {
    var dd = document.getElementById('viewAcDd');
    if (!items.length) { dd.style.display = 'none'; return; }
    var typeLabel = {'raw_material':'Raw Material','kacha':'Kacha','byproduct':'Byproduct','other':'Other'};
    var lastType = null;
    dd.innerHTML = '';
    items.forEach(function(item) {
        if (item.type !== lastType) {
            var hdr = document.createElement('div');
            hdr.className = 'list-group-item list-group-item-secondary py-1 px-2';
            hdr.style.fontSize = '11px';
            hdr.textContent = typeLabel[item.type] || item.type;
            dd.appendChild(hdr);
            lastType = item.type;
        }
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'list-group-item list-group-item-action py-1 px-3';
        btn.style.fontSize = '13px';
        var extra = item.type === 'kacha' ? ' — ' + item.weight + 'g @ ' + item.touch + '%' : (item.touch ? ' (' + item.touch + '%)' : '');
        btn.textContent = item.name + extra;
        btn.addEventListener('mousedown', function(e) {
            e.preventDefault();
            dd.style.display = 'none';
            if (item.type === 'kacha') {
                openKachaModal();
            } else {
                inp.value = item.name;
                viewAcItem = item;
                document.getElementById('viewInpTouch').value = item.touch || 0;
                calcViewFine();
            }
        });
        dd.appendChild(btn);
    });
    dd.style.display = '';
    document.addEventListener('click', function hideDD(e) {
        if (!inp.closest('div').contains(e.target)) { dd.style.display = 'none'; document.removeEventListener('click', hideDD); }
    });
}

function calcViewFine() {
    var w = parseFloat(document.getElementById('viewInpWeight').value) || 0;
    var t = parseFloat(document.getElementById('viewInpTouch').value) || 0;
    document.getElementById('viewInpFine').value = (w * t / 100).toFixed(4);
}

function submitViewInput() {
    var inp = document.getElementById('viewAcInput');
    if (!inp.value.trim()) { alert('Please select an item first.'); return; }
    document.getElementById('hInputType').value = viewAcItem.type || 'other';
    document.getElementById('hItemId').value    = viewAcItem.id   || '';
    document.getElementById('hItemName').value  = viewAcItem.name || inp.value.trim();
    document.getElementById('hWeight').value    = document.getElementById('viewInpWeight').value;
    document.getElementById('hTouch').value     = document.getElementById('viewInpTouch').value;
    document.getElementById('addInputForm').submit();
}

function openKachaModal() {
    var tbody = document.getElementById('kachaModalBody');
    document.getElementById('kachaCheckAll').checked = false;
    tbody.innerHTML = '';
    kachas.forEach(function(k) {
        var tr = document.createElement('tr');
        tr.innerHTML = '<td><input type="checkbox" class="kacha-cb" data-id="'+k.id+'" data-name="'+k.name+'" data-weight="'+k.weight+'" data-touch="'+k.touch_pct+'"></td>' +
            '<td class="fw-semibold">'+k.name+'</td>' +
            '<td class="text-end">'+parseFloat(k.weight).toFixed(3)+'</td>' +
            '<td class="text-end">'+parseFloat(k.touch_pct).toFixed(2)+'%</td>' +
            '<td class="text-end">'+parseFloat(k.fine).toFixed(4)+'</td>';
        tbody.appendChild(tr);
    });
    updateKachaCount();
    kachaModal.show();
}

function updateKachaCount() {
    var n = document.querySelectorAll('#kachaModalBody .kacha-cb:checked').length;
    document.getElementById('kachaSelCount').textContent = n + ' selected';
    document.getElementById('btnAddKacha').textContent = n > 0 ? 'Add Selected (' + n + ')' : 'Add Selected';
}

function addKachasViaPost() {
    var checked = document.querySelectorAll('#kachaModalBody .kacha-cb:checked');
    if (!checked.length) { kachaModal.hide(); return; }
    var jobId = <?= $job['id'] ?>;
    var csrfName = '<?= csrf_token() ?>';
    var csrfHash = '<?= csrf_hash() ?>';
    var promises = [];
    checked.forEach(function(cb) {
        var fd = new FormData();
        fd.append(csrfName, csrfHash);
        fd.append('input_type', 'kacha');
        fd.append('item_id',    cb.dataset.id);
        fd.append('item_name',  cb.dataset.name);
        fd.append('weight_g',   cb.dataset.weight);
        fd.append('touch_pct',  cb.dataset.touch);
        promises.push(fetch(BASE_URL + 'index.php/melt-jobs/add-input/' + jobId, {method:'POST', body:fd}));
    });
    Promise.all(promises).then(function() { location.reload(); });
}

function toggleByprod(sel) {
    document.getElementById('byprodDiv').style.display = sel.value === 'byproduct' ? '' : 'none';
}
</script>
<?= $this->endSection() ?>
