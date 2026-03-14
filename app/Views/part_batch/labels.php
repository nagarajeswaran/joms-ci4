<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h5 class="mb-3">Generate Batch Labels</h5>
<div class="row">
<div class="col-md-5">
<div class="card mb-3">
<div class="card-header"><strong>Step 1: Select Parts & Quantity</strong></div>
<div class="card-body">
    <div id="itemRows"></div>
    <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="addRow()"><i class="bi bi-plus"></i> Add Part</button>
    <hr>
    <button type="button" class="btn btn-primary" onclick="generateBatches()"><i class="bi bi-qr-code"></i> Generate Batch Numbers</button>
</div>
</div>
</div>
<div class="col-md-7">
<div class="card" id="batchResultCard" style="display:none">
<div class="card-header d-flex justify-content-between align-items-center">
    <strong>Step 2: Configure & Print</strong>
</div>
<div class="card-body">
<div class="row mb-3">
    <div class="col"><label class="form-label">Paper Size</label>
    <select id="paperSize" class="form-select form-select-sm"><option value="A4">A4</option><option value="A5">A5</option><option value="Letter">Letter</option></select></div>
    <div class="col"><label class="form-label">Rows</label><input type="number" id="printRows" class="form-control form-control-sm" value="4" min="1" max="10"></div>
    <div class="col"><label class="form-label">Columns</label><input type="number" id="printCols" class="form-control form-control-sm" value="3" min="1" max="6"></div>
</div>
<div id="batchList" class="mb-3"></div>
<button type="button" class="btn btn-success" onclick="printLabels()"><i class="bi bi-printer"></i> Print Labels</button>
</div>
</div>
</div>
</div>

<form id="printForm" method="post" action="<?= base_url('part-stock/print-labels') ?>" target="_blank">
<?= csrf_field() ?>
<input type="hidden" name="paper" id="fPaper">
<input type="hidden" name="rows" id="fRows">
<input type="hidden" name="cols" id="fCols">
<div id="batchIdInputs"></div>
</form>

<template id="rowTpl">
<div class="d-flex gap-2 mb-2 item-row">
    <select name="items[IDX][part_id]" class="form-select form-select-sm">
        <option value="">-- Select Part --</option>
        <?php foreach ($parts as $p): ?>
        <option value="<?= $p['id'] ?>"><?= esc($p['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="number" name="items[IDX][qty]" class="form-control form-control-sm" placeholder="Qty" min="1" value="1" style="width:80px">
    <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.item-row').remove()"><i class="bi bi-x"></i></button>
</div>
</template>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
var rowIdx = 0;
function addRow() {
    var tpl = document.getElementById('rowTpl').innerHTML.replace(/IDX/g, rowIdx++);
    document.getElementById('itemRows').insertAdjacentHTML('beforeend', tpl);
}
addRow();

function getItems() {
    var rows = document.querySelectorAll('.item-row');
    var items = [];
    rows.forEach(function(row) {
        var sel = row.querySelector('select');
        var qty = row.querySelector('input[type=number]');
        if (sel.value) items.push({part_id: sel.value, qty: qty.value});
    });
    return items;
}

function generateBatches() {
    var items = getItems();
    if (!items.length) { alert('Add at least one part'); return; }
    var formData = new FormData();
    formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
    items.forEach(function(item, i) {
        formData.append('items['+i+'][part_id]', item.part_id);
        formData.append('items['+i+'][qty]', item.qty);
    });
    fetch('<?= base_url('part-stock/generate-batch-numbers') ?>', {method:'POST', body: formData})
    .then(r => r.json()).then(function(data) {
        if (!data.success) { alert('Error'); return; }
        var html = '<p class="text-success">'+data.batches.length+' batch numbers generated:</p>';
        html += '<div class="table-responsive"><table class="table table-sm table-bordered"><thead class="table-dark"><tr><th>Batch No</th><th>Part</th><th>Include?</th></tr></thead><tbody>';
        data.batches.forEach(function(b) {
            html += '<tr><td>'+b.batch_number+'</td><td>'+b.part_name+'</td><td><input type="checkbox" class="batch-cb" value="'+b.id+'" checked></td></tr>';
        });
        html += '</tbody></table></div>';
        document.getElementById('batchList').innerHTML = html;
        document.getElementById('batchResultCard').style.display = '';
    });
}

function printLabels() {
    var checked = document.querySelectorAll('.batch-cb:checked');
    if (!checked.length) { alert('Select at least one batch'); return; }
    document.getElementById('fPaper').value = document.getElementById('paperSize').value;
    document.getElementById('fRows').value  = document.getElementById('printRows').value;
    document.getElementById('fCols').value  = document.getElementById('printCols').value;
    var cont = document.getElementById('batchIdInputs');
    cont.innerHTML = '';
    checked.forEach(function(cb) {
        cont.insertAdjacentHTML('beforeend', '<input type="hidden" name="batch_ids[]" value="'+cb.value+'">');
    });
    document.getElementById('printForm').submit();
}
</script>
<?= $this->endSection() ?>
