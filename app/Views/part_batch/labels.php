<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
<h5 class="mb-0">Generate Batch Labels</h5>
<div class="d-flex gap-2">
    <a href="<?= base_url('part-stock/serial-settings') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-gear"></i> Serial Settings</a>
    <a href="<?= base_url('part-stock') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>
</div>
<div class="row">
<div class="col-md-5">
<div class="card mb-3">
<div class="card-header"><strong>Step 1: Select Parts &amp; Quantity</strong></div>
<div class="card-body">
    <p class="text-muted small">Next batch number: <strong><?= esc($nextPreview) ?></strong></p>
    <div id="itemRows"></div>
    <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="addRow()"><i class="bi bi-plus"></i> Add Part</button>
    <hr>
    <button type="button" class="btn btn-primary" onclick="generateBatches()"><i class="bi bi-upc-scan"></i> Generate Batch Numbers</button>
</div>
</div>
</div>
<div class="col-md-7">
<div class="card" id="batchResultCard" style="display:none">
<div class="card-header"><strong>Step 2: Configure &amp; Print</strong></div>
<div class="card-body">
<div class="row mb-3">
    <div class="col"><label class="form-label">Paper Size</label>
    <select id="paperSize" class="form-select form-select-sm"><option value="A4">A4</option><option value="A5">A5</option><option value="Letter">Letter</option></select></div>
    <div class="col"><label class="form-label">Rows</label><input type="number" id="printRows" class="form-control form-control-sm" value="14" min="1" max="50"></div>
    <div class="col"><label class="form-label">Columns</label><input type="number" id="printCols" class="form-control form-control-sm" value="4" min="1" max="10"></div>
</div>
<div id="batchList" class="mb-3"></div>
<button type="button" class="btn btn-success" onclick="printLabels()"><i class="bi bi-printer"></i> Print Labels</button>
</div>
</div>
</div>
</div>


<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js" data-turbo-track="reload"></script>
<script>
var rowIdx = 0;
var generatedBatches = [];
var partOptions = <?= json_encode(array_reduce($parts, function($carry, $p) {
    return $carry . '<option value="' . $p['id'] . '">' . htmlspecialchars($p['name'], ENT_QUOTES) . '</option>';
}, '')) ?>;

function addRow() {
    var opts = '<option value="">-- No Part --</option>' + partOptions;
    var html = '<div class="d-flex gap-2 mb-2 item-row">'
        + '<select name="items[' + rowIdx + '][part_id]" class="form-select form-select-sm">' + opts + '</select>'
        + '<input type="number" name="items[' + rowIdx + '][qty]" class="form-control form-control-sm" placeholder="Qty" min="1" value="1" style="width:80px">'
        + '<button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest(\'.item-row\').remove()"><i class="bi bi-x"></i></button>'
        + '</div>';
    document.getElementById('itemRows').insertAdjacentHTML('beforeend', html);
    rowIdx++;
}
addRow();

function getItems() {
    var rows = document.querySelectorAll('.item-row');
    var items = [];
    rows.forEach(function(row) {
        var sel = row.querySelector('select');
        var qty = parseInt(row.querySelector('input[type=number]').value) || 1;
        if (qty > 0) items.push({part_id: sel.value || '', qty: qty});
    });
    return items;
}

function generateBatches() {
    var items = getItems();
    if (!items.length) { alert('Add at least one row with qty > 0'); return; }
    var formData = new FormData();
    formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
    items.forEach(function(item, i) {
        formData.append('items['+i+'][part_id]', item.part_id);
        formData.append('items['+i+'][qty]', item.qty);
    });
    fetch('<?= base_url('part-stock/generate-batch-numbers') ?>', {method:'POST', body: formData})
    .then(r => r.json()).then(function(data) {
        if (!data.success) { alert(data.error || 'Error generating batches'); return; }
        generatedBatches = data.batches;
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

    var selectedIds = Array.from(checked).map(function(cb){ return String(cb.value); });
    var batches = generatedBatches.filter(function(b){ return selectedIds.includes(String(b.id)); });
    if (!batches.length) { alert('No batch data found. Please regenerate.'); return; }

    var paper = document.getElementById('paperSize').value;
    var cols  = Math.max(1, parseInt(document.getElementById('printCols').value) || 4);
    var rows  = Math.max(1, parseInt(document.getElementById('printRows').value) || 14);

    var paperDims = { 'A4':{w:210,h:297}, 'A5':{w:148,h:210}, 'Letter':{w:216,h:279} };
    var dim  = paperDims[paper] || paperDims['A4'];
    var lw   = dim.w / cols;
    var lh   = dim.h / rows;
    var fsName  = Math.max(4, Math.round(lh * 0.13));
    var fsBatch = Math.max(5, Math.round(lh * 0.16));
    var fsPcwt  = Math.max(4, Math.round(lh * 0.11));

    var css = '@page{size:'+paper+' portrait;margin:0}'
        + 'html,body{margin:0;padding:0;width:100%;height:100%;font-family:Arial,sans-serif}'
        + '.label-page{display:grid;'
        + 'grid-template-columns:repeat('+cols+',1fr);'
        + 'grid-template-rows:repeat('+rows+',1fr);'
        + 'width:100%;height:100vh;overflow:hidden;gap:0;'
        + 'page-break-after:always;break-after:page}'
        + '.label-page:last-child{page-break-after:avoid;break-after:avoid}'
        + '.label-cell{box-sizing:border-box;border:0.4pt solid #aaa;display:flex;flex-direction:column;'
        + 'align-items:center;justify-content:center;padding:0.5mm;overflow:hidden;text-align:center}'
        + '.lc-name{font-size:'+fsName+'pt;font-weight:bold;line-height:1.1;'
        + 'overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:100%}'
        + '.lc-batch{font-size:'+fsBatch+'pt;font-weight:bold;color:#111;line-height:1.1;margin-bottom:0.5mm}'
        + '.lc-bc svg{display:block;max-width:100%;height:auto}'
        + '.lc-pcwt{font-size:'+fsPcwt+'pt;border-top:0.5pt dashed #aaa;'
        + 'padding-top:0.5mm;width:100%;text-align:center;line-height:1.2}';

    var perPage = cols * rows;
    var pages = [];
    for (var i = 0; i < batches.length; i += perPage) {
        var chunk = batches.slice(i, i + perPage);
        var cards = chunk.map(function(b) {
            return '<div class="label-cell">'
                + '<div class="lc-name">' + b.part_name + '</div>'
                + '<div class="lc-batch">' + b.batch_number + '</div>'
                + '<div class="lc-bc"><svg class="bc" data-val="' + b.batch_number + '"></svg></div>'
                + '<div class="lc-pcwt">Pc Wt: ________ g</div>'
                + '</div>';
        }).join('');
        pages.push('<div class="label-page">' + cards + '</div>');
    }

    var hint = '<div class="print-hint" style="font-family:Arial;font-size:11px;background:#fff3cd;padding:6px 10px;border-bottom:1px solid #ffc107;"><b>Print tip:</b> Set Margins = <b>None</b> and Scale = <b>100%</b> in print dialog. <a href="javascript:void(0)" onclick="this.parentElement.style.display=\'none\'">Dismiss</a></div>';
    var w = window.open('', '_blank', 'width=900,height=700');
    w.document.write('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Batch Labels</title>'
        + '<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"><\/script>'
        + '<style>' + css + '@media print{.print-hint{display:none}}</style></head><body><div class="print-hint">' + hint + '</div>' + pages.join('') + '</body></html>');
    w.document.close();
    w.focus();
    var _lh = lh;
    setTimeout(function() {
        var svgs = w.document.querySelectorAll('svg.bc');
        svgs.forEach(function(svg) {
            w.JsBarcode(svg, svg.getAttribute('data-val'), {
                format: 'CODE128',
                width: 1.2,
                height: Math.max(8, _lh * 2.2),
                displayValue: false,
                margin: 0
            });
        });
        setTimeout(function(){ w.print(); }, 300);
    }, 800);
}
</script>
<?= $this->endSection() ?>
