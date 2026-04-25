<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0"><?= esc($po['order_number']) ?></h5>
        <small class="text-muted">Karigar: <strong><?= esc($po['karigar_name']) ?></strong><?= ($po['dept_name'] ?? '') ? ' ('.esc($po['dept_name']).')' : '' ?> | <?= $hasChargeRules ? '<span class="badge bg-info text-dark">Rules-based charges</span>' : 'Flat-rate charges' ?></small>
    </div>
    <span class="badge <?= $po['status']==='posted'?'bg-success':'bg-warning text-dark' ?> fs-6"><?= ucfirst($po['status']) ?></span>
    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#allocPanel" id="allocToggleBtn">Manufacturing Plan ▼</button>
</div>

<!-- ISSUED -->
<div class="card mb-3">
<div class="card-header d-flex justify-content-between">
    <strong>Issued (Gatti / Parts)</strong>
    <?php if ($po['status']==='draft'): ?>
    <button class="btn btn-outline-secondary btn-sm" onclick="toggleForm('issueForm')">+ Add Issue</button>
    <?php endif; ?>
</div>
<?php if ($po['status']==='draft'): ?>
<div id="issueForm" style="display:none" class="card-body border-bottom bg-light">
<form method="post" action="<?= base_url('part-orders/add-issue/'.$po['id']) ?>">
<?= csrf_field() ?>
<!-- Type toggle -->
<div class="mb-2">
    <div class="btn-group btn-group-sm" role="group">
        <input type="radio" class="btn-check" name="issue_type" id="typeGatti" value="gatti" checked onchange="switchIssueType('gatti')">
        <label class="btn btn-outline-primary" for="typeGatti">Gatti</label>
        <input type="radio" class="btn-check" name="issue_type" id="typePart" value="part" onchange="switchIssueType('part')">
        <label class="btn btn-outline-primary" for="typePart">Part</label>
    </div>
</div>
<!-- Gatti fields -->
<div id="gattiFields">
<div class="row g-2">
    <div class="col">
        <select name="gatti_stock_id" id="gattiSel" class="form-select form-select-sm" onchange="fillGattiTouch(this)">
            <option value="">-- Select Gatti --</option>
            <?php foreach ($gattiStock as $g): ?>
            <?php $avail = number_format($g['weight_g']-$g['qty_issued_g'],2); ?>
            <?php $label = $g['batch_number'] ?? ($g['job_number'] ?? 'ID #'.$g['id']); ?>
            <option value="<?= $g['id'] ?>" data-touch="<?= $g['touch_pct'] ?>" data-stamp="<?= esc($g['stamp_name'] ?? '') ?>">
                <?= esc($label) ?> | <?= $avail ?>g avail | <?= $g['touch_pct'] ?>% | Stamp: <?= esc($g['stamp_name'] ?? '-') ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <select name="stamp_id" class="form-select form-select-sm" id="issueStamp">
            <option value="">Stamp (opt)</option>
            <?php foreach ($stamps as $s): ?><option value="<?= $s['id'] ?>"><?= esc($s['name']) ?></option><?php endforeach; ?>
        </select>
    </div>
</div>
</div>
<!-- Part fields -->
<div id="partFields" style="display:none">
<div class="row g-2">
    <div class="col">
        <select name="part_batch_id" id="partBatchSel" class="form-select form-select-sm">
            <option value="">-- Select Part Batch --</option>
            <?php foreach ($partBatches as $pb): ?>
            <option value="<?= $pb['id'] ?>" data-touch="<?= $pb['touch_pct'] ?>">
                <?= esc($pb['part_name']) ?> | Batch: <?= esc($pb['batch_number'] ?? '-') ?> | <?= number_format($pb['weight_in_stock_g'],2) ?>g avail | <?= $pb['touch_pct'] ?>%
            </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
</div>
<!-- Shared weight input -->
<div class="row g-2 mt-1">
    <div class="col-auto"><input type="number" step="0.0001" name="weight_g" id="issueWeight" class="form-control form-control-sm" placeholder="Weight (g)" required style="width:140px"></div>
    <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Add</button></div>
</div>
</form>
</div>
<?php endif; ?>
<div class="card-body p-0">
<table class="table table-sm table-bordered mb-0">
<thead class="table-dark"><tr><th>Type</th><th>Item</th><th>Weight (g)</th><th>Touch%</th><th>Fine (g)</th><th>Stamp</th><th>Issued At</th><?php if ($po['status']==='draft'): ?><th></th><?php endif; ?></tr></thead>
<tbody>
<?php foreach ($issues as $row): ?>
<tr id="issue-row-<?= $row['id'] ?>">
    <td><span class="badge <?= $row['issue_type']==='part' ? 'bg-info text-dark' : 'bg-secondary' ?>"><?= $row['issue_type']==='part' ? 'Part' : 'Gatti' ?></span></td>
    <td><?php
        if ($row['issue_type'] === 'part') {
            echo esc($row['issued_part_name'] ?? '-');
            if ($row['issued_part_batch']) echo ' <small class="text-muted">('.esc($row['issued_part_batch']).')</small>';
        } else {
            echo esc($row['gatti_batch'] ?? ($row['job_number'] ?? '-'));
        }
    ?></td>
    <td><?= number_format($row['weight_g'],4) ?></td>
    <td><?= $row['touch_pct'] ?>%</td>
    <td><?= number_format($row['weight_g']*$row['touch_pct']/100,4) ?></td>
    <td><?= esc($row['stamp_name'] ?? '-') ?></td>
    <td><?= date('d/m/Y H:i', strtotime($row['issued_at'])) ?></td>
    <?php if ($po['status']==='draft'): ?>
    <td class="text-nowrap">
        <button class="btn btn-sm btn-outline-warning" onclick="toggleEditIssue(<?= $row['id'] ?>, <?= $row['weight_g'] ?>, <?= $row['gatti_stock_id'] ?>, <?= (float)$row['touch_pct'] ?>)">Edit</button>
        <a href="<?= base_url('part-orders/delete-issue/'.$row['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')">Del</a>
    </td>
    <?php endif; ?>
</tr>
<?php if ($po['status']==='draft'): ?>
<tr id="issue-edit-<?= $row['id'] ?>" style="display:none" class="table-warning">
    <td colspan="8">
        <form method="post" action="<?= base_url('part-orders/issue/'.$row['id'].'/update') ?>" class="row g-2 align-items-center">
        <?= csrf_field() ?>
            <div class="col-auto"><input type="number" step="0.0001" name="weight_g" class="form-control form-control-sm" placeholder="Weight (g)" id="edit-issue-wt-<?= $row['id'] ?>" required style="width:130px"></div>
            <div class="col-auto"><input type="number" step="0.0001" name="touch_pct" class="form-control form-control-sm" placeholder="Touch%" id="edit-issue-tp-<?= $row['id'] ?>" style="width:100px"></div>
            <div class="col-auto">
                <select name="stamp_id" class="form-select form-select-sm" style="width:140px">
                    <option value="">-- Stamp --</option>
                    <?php foreach ($stamps as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $s['id']==$row['stamp_id']?'selected':'' ?>><?= esc($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-sm btn-warning">Update</button></div>
            <div class="col-auto"><button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleEditIssue(<?= $row['id'] ?>)">Cancel</button></div>
        </form>
    </td>
</tr>
<?php endif; ?>
<?php endforeach; ?>
<?php if (!$issues): ?><tr><td colspan="8" class="text-center text-muted">No issues yet</td></tr><?php endif; ?>
</tbody>
<tfoot class="table-secondary fw-bold">
<tr>
    <td colspan="2">Totals</td>
    <td class="text-end"><?= number_format($totalIssuedWeight,4) ?> g</td>
    <td></td>
    <td class="text-end"><?= number_format($totalIssuedFine,4) ?> g</td>
    <td colspan="<?= $po['status']==='draft' ? 3 : 2 ?>"></td>
</tr>
</tfoot>
</table>
</div>
</div>

<!-- MANUFACTURING ALLOCATION PLAN -->
<div class="collapse" id="allocPanel">
<div class="card mb-3">
<div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <strong>Manufacturing Allocation Plan</strong>
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="d-flex gap-3 small">
            <span>Total Issued: <strong><?= number_format($totalIssuedWeight,4) ?> g</strong></span>
            <span>Allocated: <strong><?= number_format($totalAllocatedWeight,4) ?> g</strong></span>
            <span class="<?= $remainingBalance >= 0 ? 'text-success' : 'text-danger' ?>">
                Remaining: <strong><?= number_format($remainingBalance,4) ?> g</strong>
            </span>
        </div>
        <form method="post" action="<?= base_url('part-orders/'.$po['id'].'/update-display-touch') ?>" class="d-inline-flex align-items-center gap-1">
            <?= csrf_field() ?>
            <label class="small mb-0 text-nowrap">டச் %</label>
            <input type="number" step="0.01" name="display_touch" value="<?= number_format((float)($po['display_touch']??0),2,'.','') ?>"
                   class="form-control form-control-sm" style="width:75px">
            <button type="submit" class="btn btn-sm btn-outline-secondary py-0">✓</button>
        </form>
        <a href="<?= base_url('part-orders/'.$po['id'].'/manf-plan-pdf') ?>" target="_blank" class="btn btn-sm btn-outline-dark">⬇ Print Plan</a>
    </div>
</div>
<div class="card-body p-0">
<table class="table table-sm table-bordered mb-0">
<thead class="table-dark">
<tr><th>#</th><th>Part</th><th>Alloc Weight (g)</th><th>Gatti/Kg</th><th class="text-end">Expected Output (g)</th><th></th></tr>
</thead>
<tbody>
<?php if ($allocations): ?>
<?php foreach ($allocations as $idx => $al): ?>
<?php
    $label = $al['part_name'] ?? $al['manual_label'] ?? '—';
    $isManual = !$al['part_id'];
    $expOut = ($al['gatti_per_kg'] > 0) ? number_format(($al['allocated_weight_g'] / $al['gatti_per_kg']) * 1000, 4) : '—';
?>
<tr>
    <td><?= $idx+1 ?></td>
    <td><?= esc($label) ?><?= $isManual ? ' <span class="badge bg-secondary ms-1">Manual</span>' : '' ?>
        <?php if (!empty($al['tamil_name'])): ?><br><small class="text-muted"><?= esc($al['tamil_name']) ?></small><?php endif; ?></td>
    <td><?= number_format($al['allocated_weight_g'],4) ?></td>
    <td><?= $al['gatti_per_kg'] ? number_format($al['gatti_per_kg'],2) : '—' ?></td>
    <td class="text-end"><?= $expOut ?></td>
    <td class="text-nowrap">
        <button class="btn btn-sm btn-outline-warning" onclick="editAlloc(<?= $al['id'] ?>,<?= (int)$al['part_id'] ?>,'<?= esc(addslashes($al['manual_label']??'')) ?>',<?= $al['allocated_weight_g'] ?>,<?= $al['gatti_per_kg']??0 ?>,'<?= esc(addslashes($al['tamil_name']??'')) ?>')">Edit</button>
        <a href="<?= base_url('part-orders/'.$po['id'].'/delete-allocation/'.$al['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this allocation?')">Del</a>
    </td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr><td colspan="6" class="text-center text-muted">No allocations yet</td></tr>
<?php endif; ?>
</tbody>
<tfoot class="table-secondary fw-bold">
<tr>
    <td colspan="2" class="text-end">Total</td>
    <td><?= number_format($totalAllocatedWeight,4) ?></td>
    <td colspan="3"></td>
</tr>
</tfoot>
</table>
</div>
<!-- Add / Edit form -->
<div class="card-footer bg-light" id="allocForm">
<form method="post" action="<?= base_url('part-orders/'.$po['id'].'/save-allocation') ?>">
<?= csrf_field() ?>
<input type="hidden" name="allocation_id" id="allocId" value="0">
<div class="row g-2 align-items-end">
    <div class="col-md-3">
        <label class="form-label form-label-sm mb-1">Part</label>
        <select name="part_id" id="allocPartSel" class="form-select form-select-sm" onchange="onPartChange(this)">
            <option value="" data-gatti="">-- Manual Entry --</option>
            <?php foreach ($parts as $p): ?>
            <option value="<?= $p['id'] ?>" data-gatti="<?= $p['gatti'] ?? '' ?>"><?= esc($p['name']) ?><?= ($p['gatti'] ? ' ('.$p['gatti'].'g/kg)' : '') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2" id="manualDiv">
        <label class="form-label form-label-sm mb-1">Manual Label</label>
        <input type="text" name="manual_label" id="allocManual" class="form-control form-control-sm" placeholder="Part name">
    </div>
    <div class="col-auto">
        <label class="form-label form-label-sm mb-1">Weight (g)</label>
        <input type="number" step="0.0001" name="allocated_weight_g" id="allocWeight" class="form-control form-control-sm" placeholder="0.0000" style="width:120px" oninput="calcExpected()">
    </div>
    <div class="col-md-2">
        <label class="form-label form-label-sm mb-1">Tamil Name (தமிழ்)</label>
        <input type="text" name="tamil_name" id="allocTamil" class="form-control form-control-sm" placeholder="தமிழ் பெயர்">
    </div>
    <div class="col-auto">
        <label class="form-label form-label-sm mb-1">Gatti/Kg</label>
        <input type="number" step="0.0001" name="gatti_per_kg" id="allocGattiKg" class="form-control form-control-sm" placeholder="e.g. 2200" style="width:110px" oninput="calcExpected()" readonly>
    </div>
    <div class="col-auto">
        <label class="form-label form-label-sm mb-1">Expected Output</label>
        <div class="form-control form-control-sm bg-white" id="expectedOutput" style="width:110px;min-height:31px">—</div>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-sm btn-primary">Save</button>
        <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="cancelAllocEdit()">Cancel</button>
    </div>
</div>
</form>
</div><!-- /card-footer -->
</div><!-- /card -->
</div><!-- /allocPanel collapse -->

<!-- RECEIVED -->
<div class="card mb-3">
<div class="card-header d-flex justify-content-between">
    <strong>Received (Parts + Byproducts)</strong>
    <?php if ($po['status']==='draft'): ?>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm" onclick="toggleForm('pendingImportForm')">Pick Pending Receive</button>
        <button class="btn btn-outline-secondary btn-sm" onclick="toggleForm('recvForm')">+ Add Receive</button>
    </div>
    <?php endif; ?>
</div>
<?php if ($po['status']==='draft'): ?>
<div id="pendingImportForm" style="display:none" class="card-body border-bottom bg-white">
<div class="d-flex justify-content-between align-items-center mb-2">
    <strong>Pick from Pending Receive Entry</strong>
    <a href="<?= base_url('pending-receive-entry') ?>" class="btn btn-sm btn-outline-secondary">Open Queue</a>
</div>
<?php if (!empty($pendingReceives)): ?>
<form method="post" action="<?= base_url('part-orders/import-pending-receives/'.$po['id']) ?>">
<?= csrf_field() ?>
<div class="table-responsive">
<table class="table table-sm table-bordered mb-2">
    <thead class="table-light">
        <tr>
            <th style="width:40px"></th>
            <th>ID</th>
            <th>Part</th>
            <th>Batch</th>
            <th>Weight (g)</th>
            <th>Pc Wt (g)</th>
            <th>Pcs</th>
            <th>Touch%</th>
            <th>Stamp</th>
            <th>Note</th>
            <th>Created</th>
            <th>By</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($pendingReceives as $pending): ?>
        <tr>
            <td><input type="checkbox" name="pending_receive_ids[]" value="<?= $pending['id'] ?>"></td>
            <td><?= $pending['id'] ?></td>
            <td><?= esc($pending['part_name'] ?? '-') ?></td>
            <td><?= esc($pending['batch_number']) ?></td>
            <td><?= number_format($pending['weight_g'], 4) ?></td>
            <td><?= $pending['piece_weight_g'] ? number_format($pending['piece_weight_g'], 4) : '-' ?></td>
            <td><?= (int)$pending['qty'] ?></td>
            <td><?= number_format((float)$pending['touch_pct'], 4) ?></td>
            <td><?= esc($pending['stamp_name'] ?? '-') ?></td>
            <td><?= esc($pending['note'] ?? '-') ?></td>
            <td><?= !empty($pending['created_at']) ? date('d/m/Y H:i', strtotime($pending['created_at'])) : '-' ?></td>
            <td><?= esc($pending['created_by'] ?? '-') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
<button type="submit" class="btn btn-primary btn-sm">Import Selected</button>
</form>
<?php else: ?>
<div class="text-muted">No pending receive rows available, or the pending receive table has not been created yet.</div>
<?php endif; ?>
</div>
<div id="recvForm" style="display:none" class="card-body border-bottom bg-light">
<form method="post" action="<?= base_url('part-orders/add-receive/'.$po['id']) ?>">
<?= csrf_field() ?>
<div class="row g-2 mb-2">
    <div class="col-auto"><select name="receive_type" class="form-select form-select-sm" onchange="toggleRecvType(this)">
        <option value="part">Part</option><option value="byproduct">Byproduct</option>
    </select></div>
    <div class="col-auto" id="partDiv">
        <select name="part_id" class="form-select form-select-sm"><option value="">-- Part --</option>
        <?php foreach ($parts as $p): ?><option value="<?= $p['id'] ?>"><?= esc($p['name']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto" id="batchNoDiv"><input type="text" name="batch_number" class="form-control form-control-sm" placeholder="Batch No (from label)"></div>
    <div class="col-auto" id="byprodDiv" style="display:none">
        <select name="byproduct_type_id" class="form-select form-select-sm"><option value="">-- Byproduct --</option>
        <?php foreach ($byprods as $b): ?><option value="<?= $b['id'] ?>"><?= esc($b['name']) ?></option><?php endforeach; ?>
        </select>
    </div>
</div>
<div class="row g-2">
    <div class="col-auto"><input type="number" step="0.0001" name="weight_g" id="recvWeight" class="form-control form-control-sm" placeholder="Weight (g)" required oninput="calcPcs()"></div>
    <div class="col-auto" id="pcWtDiv"><input type="number" step="0.0001" name="piece_weight_g" id="pcWt" class="form-control form-control-sm" placeholder="Pc Weight (g)" oninput="calcPcs()"></div>
    <div class="col-auto"><span class="form-control-plaintext form-control-sm" id="pcsCalc" style="min-width:80px">Pcs: -</span></div>
    <div class="col-auto"><input type="number" step="0.0001" name="touch_pct" class="form-control form-control-sm" placeholder="Touch%" value="0"></div>
    <div class="col-auto">
        <select name="stamp_id" class="form-select form-select-sm">
            <option value="">Stamp (opt)</option>
            <?php foreach ($stamps as $s): ?><option value="<?= $s['id'] ?>"><?= esc($s['name']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Add</button></div>
</div>
</form>
</div>
<?php endif; ?>
<div class="card-body p-0">
<table class="table table-sm table-bordered mb-0">
<thead class="table-dark"><tr><th>Type</th><th>Part / Byproduct</th><th>Batch No</th><th>Weight (g)</th><th>Pc Wt (g)</th><th>Pcs</th><th>Touch%</th><th>Fine (g)</th><th>Stamp</th><?php if ($po['status']==='draft'): ?><th></th><?php endif; ?></tr></thead>
<tbody>
<?php foreach ($receives as $row): ?>
<tr id="recv-row-<?= $row['id'] ?>">
    <td><?= ucfirst($row['receive_type']) ?></td>
    <td><?= $row['receive_type']==='part' ? esc($row['part_name']) : esc($row['byprod_name']) ?></td>
    <td><?= esc($row['batch_number'] ?? '-') ?></td>
    <td><?= number_format($row['weight_g'],4) ?></td>
    <td><?= $row['piece_weight_g'] ? number_format($row['piece_weight_g'],4) : '-' ?></td>
    <td><?= $row['qty'] ?></td>
    <td><?= $row['touch_pct'] ?>%</td>
    <td><?= number_format($row['weight_g']*$row['touch_pct']/100,4) ?></td>
    <td><?= esc($row['stamp_name'] ?? '-') ?></td>
    <?php if ($po['status']==='draft'): ?>
    <td class="text-nowrap">
        <button class="btn btn-sm btn-outline-warning" onclick="toggleEditRecv(<?= $row['id'] ?>, <?= $row['weight_g'] ?>, <?= (float)($row['piece_weight_g']??0) ?>, <?= $row['touch_pct'] ?>)">Edit</button>
        <a href="<?= base_url('part-orders/delete-receive/'.$row['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')">Del</a>
    </td>
    <?php endif; ?>
</tr>
<?php if ($po['status']==='draft'): ?>
<tr id="recv-edit-<?= $row['id'] ?>" style="display:none" class="table-warning">
    <td colspan="10">
        <form method="post" action="<?= base_url('part-orders/receive/'.$row['id'].'/update') ?>" class="row g-2 align-items-center">
        <?= csrf_field() ?>
            <div class="col-auto"><input type="number" step="0.0001" name="weight_g" class="form-control form-control-sm" placeholder="Weight (g)" id="edit-recv-wt-<?= $row['id'] ?>" required style="width:130px"></div>
            <div class="col-auto"><input type="number" step="0.0001" name="piece_weight_g" class="form-control form-control-sm" placeholder="Pc Wt (g)" id="edit-recv-pw-<?= $row['id'] ?>" style="width:120px"></div>
            <div class="col-auto"><input type="number" step="0.0001" name="touch_pct" class="form-control form-control-sm" placeholder="Touch%" id="edit-recv-tp-<?= $row['id'] ?>" style="width:100px"></div>
            <div class="col-auto"><button type="submit" class="btn btn-sm btn-warning">Update</button></div>
            <div class="col-auto"><button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleEditRecv(<?= $row['id'] ?>)">Cancel</button></div>
        </form>
    </td>
</tr>
<?php endif; ?>
<?php endforeach; ?>
<?php if (!$receives): ?><tr><td colspan="10" class="text-center text-muted">No receives yet</td></tr><?php endif; ?>
</tbody>
<tfoot class="table-secondary fw-bold">
<tr>
    <td colspan="3">Totals</td>
    <td><?= number_format($totalRecvWeight,4) ?> g</td>
    <td></td><td></td><td></td>
    <td class="text-end"><?= number_format($totalRecvFine,4) ?> g</td>
    <td></td><!-- Stamp -->
    <?php if ($po['status']==='draft'): ?><td></td><?php endif; ?>
</tr>
</tfoot>
</table>
</div>
</div>

<!-- SUMMARY + CHARGE OVERRIDE GRID -->
<div class="row g-3 mb-3 align-items-start">

<!-- LEFT: Summary -->
<div class="col-md-4">
<div class="card h-100">
<div class="card-header"><strong>Making Charge Summary</strong><?= $hasOverrides ? ' <span class="badge bg-warning text-dark ms-1">Overridden</span>' : '' ?></div>
<table class="table table-sm table-borderless mb-0">
<tr><td>Total Issued Fine (g)</td><td class="text-end"><strong><?= number_format($totalIssuedFine,4) ?></strong></td></tr>
<tr><td>Total Received Fine (g)</td><td class="text-end"><?= number_format($totalRecvFine,4) ?></td></tr>
<tr class="table-warning"><td>Fine Difference</td><td class="text-end"><strong><?= number_format($fineDiff,4) ?></strong></td></tr>
<tr><td>Total Making Charge Fine (g)</td><td class="text-end" id="sumMcFine"><?= number_format($mcFine,4) ?></td></tr>
<tr class="table-danger"><td><strong>Net Fine Karigar Owes (g)</strong></td><td class="text-end" id="sumNetFine"><strong><?= number_format($netFine,4) ?></strong></td></tr>
<tr class="table-success"><td><strong>Cash Making Charge (&#8377;)</strong></td><td class="text-end" id="sumMcCash"><strong><?= number_format($mcCash,2) ?></strong></td></tr>
</table>
</div>
</div>

<!-- RIGHT: Charge Calculation Override Grid -->
<div class="col-md-8">
<div class="card">
<div class="card-header d-flex justify-content-between align-items-center">
    <strong>Charge Calculation</strong>
    <?php if ($po['status']==='draft' && $hasOverrides): ?>
    <a href="<?= base_url('part-orders/'.$po['id'].'/reset-charge-overrides') ?>" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Reset to auto-calculated values?')">Reset to Auto</a>
    <?php endif; ?>
</div>
<form method="post" action="<?= base_url('part-orders/'.$po['id'].'/save-charge-overrides') ?>">
<?= csrf_field() ?>
<div class="table-responsive" style="overflow-y:visible;">
<table class="table table-sm table-bordered mb-0" id="chargeGrid">
<thead class="table-dark">
<tr>
    <th>Basis / Label</th>
    <th>Weight (g)</th>
    <th>Fine %</th>
    <th>Cash &#8377;/kg</th>
    <th class="text-end">Fine (g)</th>
    <th class="text-end">Cash (&#8377;)</th>
    <?php if ($po['status']==='draft'): ?><th></th><?php endif; ?>
</tr>
</thead>
<tbody id="chargeGridBody">
<?php foreach ($chargeBreakdown as $idx => $cb): ?>
<tr data-row="<?= $idx ?>">
    <td>
        <input type="hidden" name="rule_id[]" value="<?= $cb['rule']['id'] ?>">
        <?php if ($po['status']==='draft'): ?>
        <input type="text" name="basis_label[]" class="form-control form-control-sm" value="<?= esc(ucwords(str_replace('_',' ',$cb['rule']['basis'])).($cb['rule']['notes'] ? ' – '.$cb['rule']['notes'] : '')) ?>" style="min-width:140px">
        <?php else: ?>
        <small><?= esc(ucwords(str_replace('_',' ',$cb['rule']['basis']))) ?><?= $cb['rule']['notes'] ? ' – '.esc($cb['rule']['notes']) : '' ?></small>
        <?php endif; ?>
    </td>
    <?php if ($po['status']==='draft'): ?>
    <td><input type="number" step="0.0001" name="weight_g[]" class="form-control form-control-sm" data-col="w" value="<?= number_format($cb['ov_weight'],4,'.','') ?>" style="width:110px" oninput="recalcRow(this)"></td>
    <td><input type="number" step="0.0001" name="fine_pct[]" class="form-control form-control-sm" data-col="f" value="<?= number_format($cb['ov_fine_pct'],4,'.','') ?>" style="width:80px" oninput="recalcRow(this)"></td>
    <td><input type="number" step="0.01"   name="cash_rate_per_kg[]" class="form-control form-control-sm" data-col="c" value="<?= number_format($cb['ov_cash'],2,'.','') ?>" style="width:80px" oninput="recalcRow(this)"></td>
    <?php else: ?>
    <td><?= number_format($cb['ov_weight'],4) ?></td>
    <td><?= $cb['ov_fine_pct'] ?>%</td>
    <td>&#8377;<?= number_format($cb['ov_cash'],2) ?></td>
    <?php endif; ?>
    <td class="text-end row-fine"><?= number_format($cb['ov_fine'],4) ?></td>
    <td class="text-end row-cash"><?= number_format($cb['ov_cash_amt'],2) ?></td>
    <?php if ($po['status']==='draft'): ?><td><button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteRow(this)">✕</button></td><?php endif; ?>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot class="table-secondary fw-bold">
<tr>
    <td colspan="4" class="text-end">Total</td>
    <td class="text-end" id="footFine"><?= number_format($mcFine,4) ?></td>
    <td class="text-end" id="footCash"><?= number_format($mcCash,2) ?></td>
    <?php if ($po['status']==='draft'): ?><td></td><?php endif; ?>
</tr>
</tfoot>
</table>
</div>
<?php if ($po['status']==='draft'): ?>
<div class="card-footer d-flex gap-2 align-items-center">
    <button type="submit" class="btn btn-sm btn-primary">Save</button>
    <button type="button" class="btn btn-sm btn-outline-success" onclick="addRow()">+ Add Row</button>
    <small class="text-muted ms-1">Totals update live as you type</small>
</div>
<?php endif; ?>
</form>
</div>
</div>

</div><!-- end row -->

<!-- NARRATION / NOTES -->
<div class="card mb-3">
<div class="card-header"><strong>Narration / Notes</strong></div>
<div class="card-body">
<?php if ($po['status']==='draft'): ?>
<form method="post" action="<?= base_url('part-orders/'.$po['id'].'/update-notes') ?>">
<?= csrf_field() ?>
<textarea name="notes" class="form-control form-control-sm mb-2" rows="3"><?= esc($po['notes'] ?? '') ?></textarea>
<button type="submit" class="btn btn-sm btn-primary">Save Notes</button>
</form>
<?php else: ?>
<p class="mb-0"><?= nl2br(esc($po['notes'] ?? '—')) ?></p>
<?php endif; ?>
</div>
</div>

<?php if ($po['status']==='draft'): ?>
<form method="post" action="<?= base_url('part-orders/post/'.$po['id']) ?>" onsubmit="return confirm('Post to karigar ledger? This cannot be undone.')">
<?= csrf_field() ?>
<button type="submit" class="btn btn-danger"><i class="bi bi-check-circle"></i> Post to Ledger</button>
</form>
<?php endif; ?>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
function toggleForm(id) { var el=document.getElementById(id); el.style.display=el.style.display===''?'none':''; }

function switchIssueType(type) {
    document.getElementById('gattiFields').style.display = type === 'gatti' ? '' : 'none';
    document.getElementById('partFields').style.display  = type === 'part'  ? '' : 'none';
    document.getElementById('gattiSel').required = (type === 'gatti');
    document.getElementById('partBatchSel').required = (type === 'part');
}

function recalcRow(input) {
    var row  = input.closest('tr');
    var w    = parseFloat(row.querySelector('[data-col=w]').value) || 0;
    var f    = parseFloat(row.querySelector('[data-col=f]').value) || 0;
    var c    = parseFloat(row.querySelector('[data-col=c]').value) || 0;
    row.querySelector('.row-fine').textContent = (w * f / 100).toFixed(4);
    row.querySelector('.row-cash').textContent = (w / 1000 * c).toFixed(2);
    recalcTotals();
}

function recalcTotals() {
    var totalFine = 0, totalCash = 0;
    document.querySelectorAll('#chargeGridBody tr').forEach(function(row) {
        totalFine += parseFloat(row.querySelector('.row-fine').textContent) || 0;
        totalCash += parseFloat(row.querySelector('.row-cash').textContent) || 0;
    });
    document.getElementById('footFine').textContent = totalFine.toFixed(4);
    document.getElementById('footCash').textContent = totalCash.toFixed(2);
    var fineDiff = <?= $fineDiff ?>;
    document.getElementById('sumMcFine').textContent  = totalFine.toFixed(4);
    document.getElementById('sumNetFine').innerHTML   = '<strong>' + (fineDiff - totalFine).toFixed(4) + '</strong>';
    document.getElementById('sumMcCash').innerHTML    = '<strong>' + totalCash.toFixed(2) + '</strong>';
}

function addRow() {
    var tbody = document.getElementById('chargeGridBody');
    var tr = document.createElement('tr');
    tr.innerHTML =
        '<td><input type="hidden" name="rule_id[]" value="">' +
        '<input type="text" name="basis_label[]" class="form-control form-control-sm" placeholder="Label e.g. Issued Gatti" style="min-width:140px"></td>' +
        '<td><input type="number" step="0.0001" name="weight_g[]" class="form-control form-control-sm" data-col="w" value="0" style="width:110px" oninput="recalcRow(this)"></td>' +
        '<td><input type="number" step="0.0001" name="fine_pct[]" class="form-control form-control-sm" data-col="f" value="0" style="width:80px" oninput="recalcRow(this)"></td>' +
        '<td><input type="number" step="0.01" name="cash_rate_per_kg[]" class="form-control form-control-sm" data-col="c" value="0" style="width:80px" oninput="recalcRow(this)"></td>' +
        '<td class="text-end row-fine">0.0000</td>' +
        '<td class="text-end row-cash">0.00</td>' +
        '<td><button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteRow(this)">✕</button></td>';
    tbody.appendChild(tr);
    tr.querySelector('input[name="basis_label[]"]').focus();
}

function deleteRow(btn) {
    btn.closest('tr').remove();
    recalcTotals();
}

function fillGattiTouch(sel) {
    var opt = sel.options[sel.selectedIndex];
    // auto-select stamp if available
    var stampName = opt.getAttribute('data-stamp') || '';
    var stampSel = document.getElementById('issueStamp');
    if (stampSel && stampName) {
        for (var i = 0; i < stampSel.options.length; i++) {
            if (stampSel.options[i].text === stampName) { stampSel.selectedIndex = i; break; }
        }
    }
}

function toggleEditIssue(id, wt, gsId, touch) {
    var editRow = document.getElementById('issue-edit-' + id);
    var wtInput = document.getElementById('edit-issue-wt-' + id);
    var tpInput = document.getElementById('edit-issue-tp-' + id);
    if (editRow.style.display === 'none') {
        editRow.style.display = '';
        if (wtInput && wt !== undefined) wtInput.value = wt;
        if (tpInput && touch !== undefined) tpInput.value = touch;
    } else {
        editRow.style.display = 'none';
    }
}

function toggleEditRecv(id, wt, pw, tp) {
    var editRow = document.getElementById('recv-edit-' + id);
    var wtInput = document.getElementById('edit-recv-wt-' + id);
    var pwInput = document.getElementById('edit-recv-pw-' + id);
    var tpInput = document.getElementById('edit-recv-tp-' + id);
    if (editRow.style.display === 'none') {
        editRow.style.display = '';
        if (wtInput && wt !== undefined) wtInput.value = wt;
        if (pwInput && pw !== undefined) pwInput.value = pw || '';
        if (tpInput && tp !== undefined) tpInput.value = tp;
    } else {
        editRow.style.display = 'none';
    }
}

function toggleRecvType(sel) {
    var isPart = sel.value === 'part';
    document.getElementById('partDiv').style.display    = isPart ? '' : 'none';
    document.getElementById('batchNoDiv').style.display = isPart ? '' : 'none';
    document.getElementById('byprodDiv').style.display  = isPart ? 'none' : '';
    document.getElementById('pcWtDiv').style.display    = isPart ? '' : 'none';
}
function calcPcs() {
    var w = parseFloat(document.getElementById('recvWeight').value)||0;
    var p = parseFloat(document.getElementById('pcWt').value)||0;
    document.getElementById('pcsCalc').textContent = p > 0 ? 'Pcs: '+Math.round(w/p) : 'Pcs: -';
}
// ---------- Allocation Plan ----------
// localStorage collapse persistence
(function(){
    var panel = document.getElementById('allocPanel');
    var poId  = <?= (int)$po['id'] ?>;
    var key   = 'allocOpen_' + poId;
    if (localStorage.getItem(key) === '1') panel.classList.add('show');
    panel.addEventListener('show.bs.collapse', function(){ localStorage.setItem(key, '1'); });
    panel.addEventListener('hide.bs.collapse', function(){ localStorage.removeItem(key); });
})();

function onPartChange(sel) {
    var gatti = parseFloat(sel.options[sel.selectedIndex].getAttribute('data-gatti')) || 0;
    var manDiv = document.getElementById('manualDiv');
    var gEl    = document.getElementById('allocGattiKg');
    var isManual = sel.value === '';
    manDiv.style.display = isManual ? '' : 'none';
    if (gatti) {
        gEl.value    = gatti;
        gEl.readOnly = true;
    } else {
        gEl.value    = '';
        gEl.readOnly = isManual ? false : true;
    }
    calcExpected();
}
function calcExpected() {
    var w = parseFloat(document.getElementById('allocWeight').value) || 0;
    var g = parseFloat(document.getElementById('allocGattiKg').value) || 0;
    var el = document.getElementById('expectedOutput');
    el.textContent = (w > 0 && g > 0) ? (w / g * 1000).toFixed(4) + ' g' : '—';
}
function editAlloc(id, partId, manualLabel, weight, gattiKg, tamilName) {
    document.getElementById('allocId').value      = id;
    document.getElementById('allocWeight').value  = weight;
    document.getElementById('allocGattiKg').value = gattiKg || '';
    document.getElementById('allocTamil').value   = tamilName || '';
    var sel = document.getElementById('allocPartSel');
    if (partId) {
        for (var i = 0; i < sel.options.length; i++) {
            if (parseInt(sel.options[i].value) === partId) { sel.selectedIndex = i; break; }
        }
        document.getElementById('manualDiv').style.display = 'none';
        document.getElementById('allocGattiKg').readOnly   = true;
    } else {
        sel.selectedIndex = 0;
        document.getElementById('manualDiv').style.display = '';
        document.getElementById('allocManual').value       = manualLabel;
        document.getElementById('allocGattiKg').readOnly   = !manualLabel;
    }
    calcExpected();
    document.getElementById('allocForm').scrollIntoView({behavior:'smooth', block:'center'});
}
function cancelAllocEdit() {
    document.getElementById('allocId').value      = 0;
    document.getElementById('allocPartSel').selectedIndex = 0;
    document.getElementById('allocManual').value  = '';
    document.getElementById('allocWeight').value  = '';
    document.getElementById('allocTamil').value   = '';
    document.getElementById('allocGattiKg').value = '';
    document.getElementById('expectedOutput').textContent = '—';
    document.getElementById('manualDiv').style.display = '';
}
// Init: hide manual div if a part is pre-selected
document.addEventListener('DOMContentLoaded', function(){
    var sel = document.getElementById('allocPartSel');
    if (sel) onPartChange(sel);
});
</script>
<?= $this->endSection() ?>
