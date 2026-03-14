<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h5 class="mb-3"><i class="bi bi-fire"></i> Create Melt Job — <?= esc($nextNum) ?></h5>
<form method="post" action="<?= base_url('melt-jobs/store') ?>">
<?= csrf_field() ?>
<div class="row g-3">

<!-- Job Details -->
<div class="col-md-5">
<div class="card">
<div class="card-header fw-semibold">Job Details</div>
<div class="card-body">
    <div class="mb-2">
        <label class="form-label">Karigar *</label>
        <select name="karigar_id" id="karigarSel" class="form-select" required onchange="fillRates(this)">
            <option value="">-- Select --</option>
            <?php foreach ($karigars as $k): ?>
            <option value="<?= $k['id'] ?>" data-cash="<?= $k['default_cash_rate'] ?>" data-fine="<?= $k['default_fine_pct'] ?>"><?= esc($k['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="row g-2 mb-2">
        <div class="col"><label class="form-label">Cash Rate (₹/kg)</label><input type="number" step="0.01" name="cash_rate_per_kg" id="cashRate" class="form-control" value="0"></div>
        <div class="col"><label class="form-label">Fine %</label><input type="number" step="0.0001" name="fine_pct" id="finePct" class="form-control" value="0"></div>
    </div>
    <div class="mb-2"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
    <div class="row g-2">
        <div class="col"><label class="form-label">Required Touch %</label><input type="number" step="0.0001" name="required_touch_pct" class="form-control" placeholder="Target touch %"></div>
        <div class="col"><label class="form-label">Required Weight (g)</label><input type="number" step="0.001" name="required_weight_g" class="form-control" placeholder="Target gatti weight"></div>
    </div>
</div>
</div>
</div>

<!-- Issue Inputs -->
<div class="col-md-7">
<div class="card">
<div class="card-header d-flex justify-content-between align-items-center fw-semibold">
    Issue Inputs
    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addEmptyRow()"><i class="bi bi-plus"></i> Add Row</button>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-sm mb-0" id="inputTable">
<thead class="table-light">
<tr>
    <th style="min-width:200px">Item</th>
    <th style="width:110px">Weight (g)</th>
    <th style="width:90px">Touch %</th>
    <th style="width:90px">Fine (g)</th>
    <th style="width:36px"></th>
</tr>
</thead>
<tbody id="inputBody"></tbody>
<tfoot class="table-light">
<tr><td class="text-end fw-semibold">Total</td><td id="totWeight" class="fw-semibold">0.0000</td><td></td><td id="totFine" class="fw-semibold">0.0000</td><td></td></tr>
</tfoot>
</table>
</div>
</div>
</div>
</div>

</div><!-- /row -->

<div class="mt-3">
    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save & Continue</button>
    <a href="<?= base_url('melt-jobs') ?>" class="btn btn-secondary ms-2">Cancel</a>
</div>
</form>

<!-- Kacha Modal -->
<div class="modal fade" id="kachaModal" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
    <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-gem"></i> Select Kacha Lots</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body p-0">
    <table class="table table-sm table-hover mb-0" id="kachaModalTable">
    <thead class="table-dark">
        <tr><th style="width:36px"><input type="checkbox" id="kachaCheckAll"></th><th>Lot No</th><th class="text-end">Weight (g)</th><th class="text-end">Touch %</th><th class="text-end">Fine (g)</th></tr>
    </thead>
    <tbody id="kachaModalBody"></tbody>
    </table>
    </div>
    <div class="modal-footer">
        <span id="kachaSelCount" class="me-auto text-muted small">0 selected</span>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" id="btnAddKacha"><i class="bi bi-check2"></i> Add Selected</button>
    </div>
</div></div></div>

<script id="kachasJson" type="application/json"><?= json_encode($kachas) ?></script>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
var kachas  = JSON.parse(document.getElementById('kachasJson').textContent);
var rowIdx  = 0;
var searchTimers = {};
var kachaModal;
var activeRowIdx = null;

document.addEventListener('DOMContentLoaded', function() {
    kachaModal = new bootstrap.Modal(document.getElementById('kachaModal'));
    document.getElementById('kachaCheckAll').addEventListener('change', function() {
        document.querySelectorAll('#kachaModalBody input[type=checkbox]').forEach(cb => cb.checked = this.checked);
        updateKachaCount();
    });
    document.getElementById('btnAddKacha').addEventListener('click', addSelectedKachas);
    document.getElementById('kachaModalBody').addEventListener('change', updateKachaCount);
    addEmptyRow();
});

function fillRates(sel) {
    var opt = sel.options[sel.selectedIndex];
    document.getElementById('cashRate').value = opt.dataset.cash || 0;
    document.getElementById('finePct').value  = opt.dataset.fine || 0;
}

function addEmptyRow(prefill) {
    var idx = rowIdx++;
    var tr = document.createElement('tr');
    tr.id = 'ir_' + idx;
    tr.innerHTML =
        '<td><div class="ac-wrap" style="position:relative">' +
            '<input type="text" class="form-control form-control-sm ac-input" placeholder="Type to search item..." autocomplete="off" oninput="onAcInput('+idx+',this)">' +
            '<input type="hidden" name="input_type[]" class="r-type" value="">' +
            '<input type="hidden" name="item_id[]" class="r-itemid" value="">' +
            '<input type="hidden" name="item_name[]" class="r-itemname" value="">' +
            '<div class="ac-dropdown list-group shadow-sm" id="ac_'+idx+'" style="display:none;position:absolute;z-index:9999;width:100%;max-height:220px;overflow-y:auto;top:100%;left:0"></div>' +
        '</div></td>' +
        '<td><input type="number" step="0.0001" name="weight_g[]" class="form-control form-control-sm r-weight" oninput="calcRow('+idx+')" value=""></td>' +
        '<td><input type="number" step="0.0001" name="touch_pct[]" id="touch_'+idx+'" class="form-control form-control-sm" oninput="calcRow('+idx+')" value="0"></td>' +
        '<td><input type="text" id="fine_'+idx+'" class="form-control form-control-sm bg-light" readonly value="0.0000"></td>' +
        '<td><button type="button" class="btn btn-outline-danger btn-sm py-0 px-1" onclick="removeRow('+idx+')"><i class="bi bi-x"></i></button></td>';
    document.getElementById('inputBody').appendChild(tr);
    if (prefill) fillRow(idx, prefill);
    return idx;
}

function removeRow(idx) {
    var tr = document.getElementById('ir_' + idx);
    if (tr) { tr.remove(); calcTotals(); }
}

function onAcInput(idx, inp) {
    clearTimeout(searchTimers[idx]);
    var q = inp.value.trim();
    var dd = document.getElementById('ac_' + idx);
    if (q.length < 2) { dd.style.display = 'none'; return; }
    searchTimers[idx] = setTimeout(function() {
        fetch(BASE_URL + 'index.php/melt-jobs/search-items?q=' + encodeURIComponent(q))
            .then(r => r.json()).then(items => renderAcDropdown(idx, items, inp));
    }, 280);
}

function renderAcDropdown(idx, items, inp) {
    var dd = document.getElementById('ac_' + idx);
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
                activeRowIdx = idx;
                openKachaModal(idx);
            } else {
                inp.value = item.name;
                fillRow(idx, {type: item.type, id: item.id, name: item.name, touch: item.touch});
            }
        });
        dd.appendChild(btn);
    });
    dd.style.display = '';
    document.addEventListener('click', function hideDD(e) {
        if (!inp.closest('.ac-wrap').contains(e.target)) { dd.style.display = 'none'; document.removeEventListener('click', hideDD); }
    });
}

function fillRow(idx, data) {
    var tr = document.getElementById('ir_' + idx);
    if (!tr) return;
    tr.querySelector('.r-type').value     = data.type || '';
    tr.querySelector('.r-itemid').value   = data.id   || '';
    tr.querySelector('.r-itemname').value = data.name || '';
    tr.querySelector('.ac-input').value   = data.name || '';
    if (data.weight != null) tr.querySelector('.r-weight').value = data.weight;
    document.getElementById('touch_' + idx).value = data.touch || 0;
    calcRow(idx);
}

function calcRow(idx) {
    var tr = document.getElementById('ir_' + idx);
    if (!tr) return;
    var w = parseFloat(tr.querySelector('.r-weight').value) || 0;
    var t = parseFloat(document.getElementById('touch_' + idx).value) || 0;
    document.getElementById('fine_' + idx).value = (w * t / 100).toFixed(4);
    calcTotals();
}

function calcTotals() {
    var totalW = 0, totalF = 0;
    document.querySelectorAll('#inputBody tr').forEach(function(tr) {
        var idx = tr.id.replace('ir_','');
        totalW += parseFloat(tr.querySelector('.r-weight')?.value) || 0;
        totalF += parseFloat(document.getElementById('fine_'+idx)?.value) || 0;
    });
    document.getElementById('totWeight').textContent = totalW.toFixed(4);
    document.getElementById('totFine').textContent   = totalF.toFixed(4);
}

// ---- Kacha Modal ----
function openKachaModal(triggerRowIdx) {
    activeRowIdx = triggerRowIdx;
    var tbody = document.getElementById('kachaModalBody');
    document.getElementById('kachaCheckAll').checked = false;
    tbody.innerHTML = '';
    kachas.forEach(function(k) {
        var tr = document.createElement('tr');
        tr.innerHTML = '<td><input type="checkbox" class="kacha-cb" data-id="'+k.id+'" data-name="'+k.name+'" data-weight="'+k.weight+'" data-touch="'+k.touch_pct+'" data-fine="'+k.fine+'"></td>' +
            '<td class="fw-semibold">' + k.name + '</td>' +
            '<td class="text-end">' + parseFloat(k.weight).toFixed(3) + '</td>' +
            '<td class="text-end">' + parseFloat(k.touch_pct).toFixed(2) + '%</td>' +
            '<td class="text-end">' + parseFloat(k.fine).toFixed(4) + '</td>';
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

function addSelectedKachas() {
    var checked = document.querySelectorAll('#kachaModalBody .kacha-cb:checked');
    if (!checked.length) return;
    // Remove the trigger row if it's empty (created by addEmptyRow for kacha picker)
    var triggerTr = activeRowIdx !== null ? document.getElementById('ir_' + activeRowIdx) : null;
    if (triggerTr && !triggerTr.querySelector('.r-type').value) triggerTr.remove();

    checked.forEach(function(cb) {
        var idx = addEmptyRow();
        fillRow(idx, {
            type:   'kacha',
            id:     cb.dataset.id,
            name:   cb.dataset.name,
            weight: cb.dataset.weight,
            touch:  cb.dataset.touch,
        });
    });
    calcTotals();
    kachaModal.hide();
}
</script>
<?= $this->endSection() ?>
