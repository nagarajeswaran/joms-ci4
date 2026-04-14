<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="card" style="max-width:750px">
<div class="card-header"><strong><?= esc($title) ?></strong></div>
<div class="card-body">
<form method="post" action="<?= isset($item) ? base_url('karigar/update/'.$item['id']) : base_url('karigar/store') ?>">
<?= csrf_field() ?>
<div class="mb-3">
    <label class="form-label">Name *</label>
    <input type="text" name="name" class="form-control" value="<?= esc($item['name'] ?? '') ?>" required>
</div>
<div class="mb-3">
    <label class="form-label">Tamil Name</label>
    <input type="text" name="tamil_name" class="form-control" value="<?= esc($item['tamil_name'] ?? '') ?>">
</div>
<div class="mb-3">
    <label class="form-label">Department</label>
    <select name="department_id" class="form-select">
        <option value="">-- Select --</option>
        <?php foreach ($departments as $d): ?>
        <option value="<?= $d['id'] ?>" <?= ($item['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= esc($d['name']) ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div class="row">
<div class="col mb-3">
    <label class="form-label">Default Cash Rate (&#8377;/kg)</label>
    <input type="number" step="0.01" name="default_cash_rate" class="form-control" value="<?= $item['default_cash_rate'] ?? 0 ?>">
</div>
<div class="col mb-3">
    <label class="form-label">Default Fine %</label>
    <input type="number" step="0.0001" name="default_fine_pct" class="form-control" value="<?= $item['default_fine_pct'] ?? 0 ?>">
</div>
</div>
<div class="mb-3">
    <label class="form-label">Notes</label>
    <textarea name="notes" class="form-control" rows="2"><?= esc($item['notes'] ?? '') ?></textarea>
</div>
<button type="submit" class="btn btn-primary">Save</button>
<a href="<?= base_url('karigar') ?>" class="btn btn-secondary ms-2">Cancel</a>
</form>

<?php if (isset($item)): ?>
<hr class="mt-4">
<h6>Making Charge Rules</h6>

<?php if (!empty($chargeRules)): ?>
<table class="table table-sm table-bordered mb-3">
<thead class="table-dark">
<tr><th>Basis</th><th>Filter</th><th>Fine%</th><th>Cash &#8377;/kg</th><th>Notes</th><th></th></tr>
</thead>
<tbody>
<?php foreach ($chargeRules as $rule):
    $fids = $rule['filter_ids'] ? json_decode($rule['filter_ids'], true) : [];
    $filterLabel = 'All';
    if ($rule['filter_type'] === 'by_part') {
        $names = [];
        foreach ($parts as $p) { if (in_array((int)$p['id'], $fids)) $names[] = esc($p['name']); }
        $filterLabel = 'Parts: '.implode(', ', $names);
    } elseif ($rule['filter_type'] === 'by_gatti') {
        $names = [];
        foreach ($gattiList as $g) { if (in_array((int)$g['id'], $fids)) $names[] = esc($g['label']); }
        $filterLabel = 'Gatti: '.implode(', ', $names);
    }
?>
<tr>
    <td><?= ucwords(str_replace('_', ' ', $rule['basis'])) ?></td>
    <td><?= $filterLabel ?></td>
    <td><?= $rule['fine_pct'] > 0 ? $rule['fine_pct'].'%' : '-' ?></td>
    <td><?= $rule['cash_rate_per_kg'] > 0 ? '&#8377;'.number_format($rule['cash_rate_per_kg'],2) : '-' ?></td>
    <td><?= esc($rule['notes'] ?? '') ?></td>
    <td><a href="<?= base_url('karigar/charge-rule/'.$rule['id'].'/delete') ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete rule?')">Del</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php else: ?>
<p class="text-muted small">No charge rules yet. Add one below.</p>
<?php endif; ?>

<div class="card bg-light mb-2">
<div class="card-body">
<strong class="d-block mb-2">Add Rule</strong>
<form method="post" action="<?= base_url('karigar/'.$item['id'].'/charge-rule/store') ?>">
<?= csrf_field() ?>
<input type="hidden" name="filter_type" id="hiddenFilterType" value="none">

<div class="row g-2 mb-3">
    <div class="col-md-5">
        <label class="form-label form-label-sm">Basis *</label>
        <select name="basis" class="form-select form-select-sm" id="ruleBasis" onchange="updateRuleFilter()" required>
            <option value="issued_all">Issued — All gatti</option>
            <option value="issued_filtered">Issued — Specific gatti</option>
            <option value="received_all">Received — All</option>
            <option value="received_filtered">Received — Specific parts</option>
        </select>
    </div>
    <div class="col">
        <label class="form-label form-label-sm">Fine %</label>
        <input type="number" step="0.0001" name="fine_pct" class="form-control form-control-sm" value="0" min="0">
    </div>
    <div class="col">
        <label class="form-label form-label-sm">Cash &#8377;/kg</label>
        <input type="number" step="0.01" name="cash_rate_per_kg" class="form-control form-control-sm" value="0" min="0">
    </div>
    <div class="col-md-3">
        <label class="form-label form-label-sm">Notes</label>
        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional">
    </div>
</div>

<!-- GATTI GRID (issued_filtered) -->
<div id="gattiGrid" style="display:none" class="mb-3">
    <label class="form-label form-label-sm fw-semibold">Select Gatti Batches</label>
    <input type="text" class="form-control form-control-sm mb-2" placeholder="Search batch / touch..." id="gattiSearch" oninput="filterGrid('gattiTable', this.value)">
    <div style="max-height:200px;overflow-y:auto;border:1px solid #dee2e6;border-radius:4px;">
    <table class="table table-sm table-hover mb-0" id="gattiTable">
    <thead class="table-secondary sticky-top"><tr>
        <th style="width:36px"><input type="checkbox" class="form-check-input" onchange="toggleAll('gattiTable',this)"></th>
        <th>Batch No</th><th>Touch%</th><th>Available (g)</th>
    </tr></thead>
    <tbody>
    <?php foreach ($gattiList ?? [] as $g): ?>
    <tr>
        <td><input type="checkbox" class="form-check-input" name="filter_ids[]" value="<?= $g['id'] ?>"></td>
        <td><?= esc($g['label']) ?></td>
        <td><?= $g['touch_pct'] ?>%</td>
        <td><?= number_format($g['avail'],2) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
</div>

<!-- PARTS GRID (received_filtered) -->
<div id="partsGrid" style="display:none" class="mb-3">
    <label class="form-label form-label-sm fw-semibold">Select Parts</label>
    <input type="text" class="form-control form-control-sm mb-2" placeholder="Search part name..." id="partsSearch" oninput="filterGrid('partsTable', this.value)">
    <div style="max-height:200px;overflow-y:auto;border:1px solid #dee2e6;border-radius:4px;">
    <table class="table table-sm table-hover mb-0" id="partsTable">
    <thead class="table-secondary sticky-top"><tr>
        <th style="width:36px"><input type="checkbox" class="form-check-input" onchange="toggleAll('partsTable',this)"></th>
        <th>Part Name</th>
    </tr></thead>
    <tbody>
    <?php foreach ($parts ?? [] as $p): ?>
    <tr>
        <td><input type="checkbox" class="form-check-input" name="filter_ids[]" value="<?= $p['id'] ?>"></td>
        <td><?= esc($p['name']) ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
</div>

<button type="submit" class="btn btn-sm btn-primary">Add Rule</button>
</form>
</div>
</div>
<?php endif; ?>

</div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function updateRuleFilter() {
    var basis = document.getElementById('ruleBasis').value;
    var gattiGrid  = document.getElementById('gattiGrid');
    var partsGrid  = document.getElementById('partsGrid');
    var hiddenFT   = document.getElementById('hiddenFilterType');

    // Uncheck everything when switching
    document.querySelectorAll('#gattiTable input[type=checkbox]').forEach(function(c){ c.checked=false; });
    document.querySelectorAll('#partsTable input[type=checkbox]').forEach(function(c){ c.checked=false; });

    if (basis === 'issued_filtered') {
        gattiGrid.style.display  = '';
        partsGrid.style.display  = 'none';
        hiddenFT.value = 'by_gatti';
    } else if (basis === 'received_filtered') {
        gattiGrid.style.display  = 'none';
        partsGrid.style.display  = '';
        hiddenFT.value = 'by_part';
    } else {
        gattiGrid.style.display  = 'none';
        partsGrid.style.display  = 'none';
        hiddenFT.value = 'none';
    }
}

function filterGrid(tableId, query) {
    var q = query.toLowerCase();
    document.querySelectorAll('#' + tableId + ' tbody tr').forEach(function(row) {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

function toggleAll(tableId, master) {
    document.querySelectorAll('#' + tableId + ' tbody input[type=checkbox]').forEach(function(c) {
        if (c.closest('tr').style.display !== 'none') c.checked = master.checked;
    });
}
</script>
<?= $this->endSection() ?>
