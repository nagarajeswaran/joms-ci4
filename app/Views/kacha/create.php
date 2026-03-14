<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Add Kacha Lots</h5>
    <a href="<?= base_url('kacha') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<form method="post" action="<?= base_url('kacha/store') ?>">
    <?= csrf_field() ?>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Lot Entry</span>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddRow"><i class="bi bi-plus"></i> Add Row</button>
        </div>
        <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0" id="lotTable">
            <thead class="table-light">
                <tr>
                    <th style="width:110px;">Lot Number *</th>
                    <th style="width:110px;">Receipt Date</th>
                    <th style="width:100px;">Weight (g) *</th>
                    <th style="width:90px;">Touch % *</th>
                    <th style="width:100px;">Fine (g)</th>
                    <th style="width:110px;">Source</th>
                    <th style="width:140px;">Party</th>
                    <th style="width:90px;">Test Touch%</th>
                    <th style="width:90px;">Test No.</th>
                    <th style="width:130px;">Notes</th>
                    <th style="width:36px;"></th>
                </tr>
            </thead>
            <tbody id="lotRows"></tbody>
            <tfoot class="table-light">
                <tr>
                    <td colspan="2" class="text-end fw-semibold">Totals:</td>
                    <td class="text-end fw-semibold" id="totWeight">0.000</td>
                    <td class="text-end fw-semibold" id="totTouch">0.00%</td>
                    <td class="text-end fw-semibold" id="totFine">0.0000</td>
                    <td colspan="6"></td>
                </tr>
            </tfoot>
        </table>
        </div>
    </div>

    <div class="text-end mt-3">
        <a href="<?= base_url('kacha') ?>" class="btn btn-secondary me-2">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save All Lots</button>
    </div>
</form>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
var rowCount = 0;

function addRow(prefill) {
    prefill = prefill || {};
    rowCount++;
    var tr = document.createElement('tr');
    tr.dataset.row = rowCount;
    tr.innerHTML =
        '<td><input type="text" name="lot_number[]" class="form-control form-control-sm" required value="' + (prefill.lot_number||'') + '" placeholder="e.g. M1"></td>' +
        '<td><input type="date" name="receipt_date[]" class="form-control form-control-sm" value="' + (prefill.receipt_date||new Date().toISOString().slice(0,10)) + '"></td>' +
        '<td><input type="number" name="weight[]" class="form-control form-control-sm text-end r-weight" step="0.001" min="0.001" required value="' + (prefill.weight||'') + '" placeholder="0.000"></td>' +
        '<td><input type="number" name="touch_pct[]" class="form-control form-control-sm text-end r-touch" step="0.0001" min="0.0001" max="100" required value="' + (prefill.touch_pct||'') + '" placeholder="0.00"></td>' +
        '<td><input type="text" name="_fine[]" class="form-control form-control-sm text-end r-fine bg-light" readonly tabindex="-1" placeholder="auto"></td>' +
        '<td><select name="source_type[]" class="form-select form-select-sm r-source">' +
            '<option value="purchase"' + (prefill.source_type==='purchase'||!prefill.source_type?' selected':'') + '>Purchase</option>' +
            '<option value="internal"' + (prefill.source_type==='internal'?' selected':'') + '>Internal</option>' +
        '</select></td>' +
        '<td><input type="text" name="party[]" class="form-control form-control-sm" value="' + (prefill.party||'') + '" placeholder="Supplier name"></td>' +
        '<td><input type="number" name="test_touch[]" class="form-control form-control-sm text-end" step="0.0001" min="0" max="100" value="' + (prefill.test_touch||'') + '" placeholder="optional"></td>' +
        '<td><input type="text" name="test_number[]" class="form-control form-control-sm" value="' + (prefill.test_number||'') + '" placeholder="optional"></td>' +
        '<td><input type="text" name="notes[]" class="form-control form-control-sm" value="' + (prefill.notes||'') + '"></td>' +
        '<td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm py-0 px-1 btnDel"><i class="bi bi-x"></i></button></td>';

    var tbody = document.getElementById('lotRows');
    tbody.appendChild(tr);

    var wInp = tr.querySelector('.r-weight');
    var tInp = tr.querySelector('.r-touch');
    var fInp = tr.querySelector('.r-fine');

    function calcFine() {
        var w = parseFloat(wInp.value) || 0;
        var t = parseFloat(tInp.value) || 0;
        fInp.value = (w * t / 100).toFixed(4);
        updateTotals();
    }

    wInp.addEventListener('input', calcFine);
    tInp.addEventListener('input', calcFine);
    tr.querySelector('.btnDel').addEventListener('click', function() { tr.remove(); updateTotals(); });
    calcFine();
}

function updateTotals() {
    var rows = document.querySelectorAll('#lotRows tr');
    var totW = 0, totFine = 0;
    rows.forEach(function(r) {
        totW    += parseFloat(r.querySelector('.r-weight').value) || 0;
        totFine += parseFloat(r.querySelector('.r-fine').value) || 0;
    });
    var avgT = totW > 0 ? (totFine / totW * 100) : 0;
    document.getElementById('totWeight').textContent = totW.toFixed(3);
    document.getElementById('totTouch').textContent  = avgT.toFixed(2) + '%';
    document.getElementById('totFine').textContent   = totFine.toFixed(4);
}

document.getElementById('btnAddRow').addEventListener('click', function() { addRow(); });
addRow();
addRow();
</script>
<?= $this->endSection() ?>
