<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
    $isDraft       = $job['status'] === 'draft';
    $pureRequired  = ($job['required_weight_g'] && $job['required_touch_pct'])
                     ? $job['required_weight_g'] * $job['required_touch_pct'] / 100
                     : 0;
    $currentTouch  = $totalIssuedWeight > 0
                     ? $totalIssuedFine / $totalIssuedWeight * 100
                     : 0;
    $reqTouch      = (float)($job['required_touch_pct'] ?? 0);
?>

<!-- JOB HEADER -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0"><?= esc($job['job_number']) ?></h5>
        <small class="text-muted" id="jobHeaderLine">
            Karigar: <strong><?= esc($job['karigar_name']) ?></strong>
            | Cash: <?php if ($isDraft): ?><span class="live-edit-val" data-field="cash_rate_per_kg" title="Click to edit">₹<?= number_format($job['cash_rate_per_kg'],2) ?>/kg</span><?php else: ?>₹<?= number_format($job['cash_rate_per_kg'],2) ?>/kg<?php endif; ?>
            | Fine: <?php if ($isDraft): ?><span class="live-edit-val" data-field="fine_pct" title="Click to edit"><?= $job['fine_pct'] ?>%</span><?php else: ?><?= $job['fine_pct'] ?>%<?php endif; ?>
            | Req. Touch: <?php if ($isDraft): ?><span class="live-edit-val" data-field="required_touch_pct" title="Click to edit"><strong><?= $job['required_touch_pct'] !== null ? number_format($job['required_touch_pct'],2).'%' : '—' ?></strong></span><?php else: ?><strong><?= $job['required_touch_pct'] !== null ? number_format($job['required_touch_pct'],2).'%' : '—' ?></strong><?php endif; ?>
            | Req. Weight: <?php if ($isDraft): ?><span class="live-edit-val" data-field="required_weight_g" title="Click to edit"><strong><?= $job['required_weight_g'] !== null ? number_format($job['required_weight_g'],3).'g' : '—' ?></strong></span><?php else: ?><strong><?= $job['required_weight_g'] !== null ? number_format($job['required_weight_g'],3).'g' : '—' ?></strong><?php endif; ?>
        </small>
        <?php if ($isDraft): ?><div><small class="text-primary" style="font-size:11px"><i class="bi bi-pencil-fill"></i> Click any value above to edit</small></div><?php endif; ?>
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
    <?php if ($isDraft): ?>
    <div class="d-flex gap-2"><button class="btn btn-outline-secondary btn-sm" id="btnToggleAddInput">+ Add Input</button><button class="btn btn-outline-info btn-sm" id="btnOpenKachaDirectly"><i class="bi bi-gem"></i> Pick Kacha</button></div>
    <?php endif; ?>
</div>

<?php if ($isDraft): ?>
<div id="addInputPanel" style="display:none" class="card-body border-bottom bg-light">
<div class="row g-2 align-items-end">
    <div class="col" style="position:relative">
        <label class="form-label form-label-sm mb-1">Item <small class="text-muted">(type to search)</small></label>
        <input type="text" id="viewAcInput" class="form-control form-control-sm" placeholder="e.g. Fine Silver, KAC..." autocomplete="off" oninput="onViewAcInput(this)">
        <div class="ac-dropdown list-group shadow-sm" id="viewAcDd" style="display:none;position:absolute;z-index:9999;width:100%;max-height:220px;overflow-y:auto;top:100%;left:0"></div>
    </div>
    <div class="col-auto"><label class="form-label form-label-sm mb-1">Weight (g)</label><input type="number" step="0.0001" id="viewInpWeight" class="form-control form-control-sm" placeholder="Weight" oninput="calcViewFine()"><div id="viewWeightHint" class="form-text text-info" style="display:none"></div></div>
    <div class="col-auto"><label class="form-label form-label-sm mb-1">Touch %</label><input type="number" step="0.0001" id="viewInpTouch" class="form-control form-control-sm" value="0" oninput="calcViewFine()"></div>
    <div class="col-auto"><label class="form-label form-label-sm mb-1">Fine (g)</label><input type="text" id="viewInpFine" class="form-control form-control-sm bg-light" readonly style="width:90px"></div>
    <div class="col-auto pt-1">
        <button type="button" class="btn btn-primary btn-sm" onclick="submitViewInput()">Add</button>
    </div>
</div>
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
<thead class="table-dark"><tr><th>Type</th><th>Item</th><th class="text-end">Weight (g)</th><th class="text-end">Touch%</th><th class="text-end">Fine (g)</th><?php if ($isDraft): ?><th></th><?php endif; ?></tr></thead>
<tbody>
<?php foreach ($inputs as $row): ?>
<tr>
    <td><span class="badge bg-secondary"><?= ucfirst(str_replace('_',' ',$row['input_type'])) ?></span></td>
    <td><?= esc($row['item_name']) ?></td>
    <td class="text-end"><?= number_format($row['weight_g'],4) ?></td>
    <td class="text-end" id="touch-cell-<?= $row['id'] ?>">
        <?php if ($isDraft && $row['input_type'] === 'raw_material'): ?>
        <span class="input-touch-edit" data-input-id="<?= $row['id'] ?>"
              data-weight="<?= $row['weight_g'] ?>"
              data-touch="<?= $row['touch_pct'] ?>"
              title="Click to edit touch%"><?= number_format($row['touch_pct'],4) ?>%</span>
        <?php else: ?>
        <?= number_format($row['touch_pct'],4) ?>%
        <?php endif; ?>
    </td>
    <td class="text-end" id="fine-cell-<?= $row['id'] ?>"><?= number_format($row['fine_g'],4) ?></td>
    <?php if ($isDraft): ?>
    <td><a href="<?= base_url('melt-jobs/delete-input/'.$row['id']) ?>" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="return confirm('Delete?')"><i class="bi bi-x"></i></a></td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
<?php if (!$inputs): ?><tr><td colspan="6" class="text-center text-muted py-2">No inputs yet</td></tr><?php endif; ?>
</tbody>
<tfoot class="table-light">
<tr>
    <td colspan="2" class="text-end fw-semibold">Total</td>
    <td class="text-end fw-semibold" id="footer-weight"><?= number_format($totalIssuedWeight,4) ?></td>
    <td class="text-end fw-semibold text-primary" id="footer-avg-touch"><?= $totalIssuedWeight > 0 ? number_format($totalIssuedFine / $totalIssuedWeight * 100, 4) . '%' : '—' ?></td>
    <td class="text-end fw-semibold" id="footer-fine"><?= number_format($totalIssuedFine,4) ?></td>
    <?php if ($isDraft): ?><td></td><?php endif; ?>
</tr>
</tfoot>
</table>
</div>
</div>

<!-- TOUCH SUGGESTION PANEL -->
<?php
    $reqWeight        = (float)($job['required_weight_g'] ?? 0);
    $hasWeightTarget  = ($reqWeight > 0);
    $weightRemaining  = $hasWeightTarget ? ($reqWeight - $totalIssuedWeight) : null;
    $overWeight       = ($hasWeightTarget && $totalIssuedWeight > $reqWeight);
    $weightUsedPct    = ($hasWeightTarget && $reqWeight > 0) ? min($totalIssuedWeight / $reqWeight * 100, 150) : 0;

    $touchAbove = $currentTouch > $reqTouch;
    $touchBelow = $currentTouch < $reqTouch;
    $touchDiff  = abs($currentTouch - $reqTouch);
    $alloyName  = $defaultAlloy ? $defaultAlloy['name'] : 'Alloy';

    // Required Fine = target weight x target touch
    $requiredFine = ($hasWeightTarget && $reqTouch > 0) ? $reqWeight * $reqTouch / 100 : 0;
    $fineNeeded   = $requiredFine > 0 ? max(0, $requiredFine - $totalIssuedFine) : 0;
    $fineExcess   = $requiredFine > 0 ? max(0, $totalIssuedFine - $requiredFine) : 0;

    // Alloy to add to bring current touch down to reqTouch (touch is priority)
    $alloyToAdd = 0;
    if ($touchAbove && $reqTouch > 0) {
        $alloyToAdd = ($totalIssuedFine / ($reqTouch / 100)) - $totalIssuedWeight;
    }
?>
<?php if (($reqTouch > 0 || $hasWeightTarget) && $totalIssuedWeight > 0): ?>
<div class="card mb-3 border-<?= $touchAbove ? 'warning' : ($touchBelow ? 'danger' : 'success') ?>">
<div class="card-header d-flex justify-content-between align-items-center py-2 <?= $touchAbove ? 'bg-warning bg-opacity-25' : ($touchBelow ? 'bg-danger bg-opacity-10' : 'bg-success bg-opacity-10') ?>">
    <strong><i class="bi bi-lightbulb"></i> Touch Suggestion</strong>
    <span class="badge <?= $touchAbove ? 'bg-warning text-dark' : ($touchBelow ? 'bg-danger' : 'bg-success') ?>">
        <?php if ($reqTouch > 0): ?>
            <?= $touchAbove ? 'ABOVE TARGET' : ($touchBelow ? 'BELOW TARGET' : 'ON TARGET') ?>
        <?php else: ?>
            WEIGHT ONLY
        <?php endif; ?>
    </span>
</div>
<div class="card-body py-3">
    <?php if ($reqTouch > 0): ?>
    <!-- Touch + Fine Status -->
    <div class="row mb-2">
        <div class="col-auto">
            <span class="text-muted">Current Touch:</span>
            <strong class="ms-1"><?= number_format($currentTouch, 4) ?>%</strong>
        </div>
        <div class="col-auto"><i class="bi bi-arrow-right"></i></div>
        <div class="col-auto">
            <span class="text-muted">Required Touch:</span>
            <strong class="ms-1"><?= number_format($reqTouch, 2) ?>%</strong>
        </div>
        <div class="col-auto">
            <span class="text-muted">Diff:</span>
            <strong class="ms-1 <?= $touchAbove ? 'text-warning' : ($touchBelow ? 'text-danger' : 'text-success') ?>"><?= $touchAbove ? '+' : ($touchBelow ? '-' : '') ?><?= number_format($touchDiff, 4) ?>%</strong>
        </div>
    </div>
    <?php if ($requiredFine > 0): ?>
    <div class="row mb-2" style="font-size:12px">
        <div class="col-auto">
            <span class="text-muted">Required Fine:</span>
            <strong><?= number_format($requiredFine, 4) ?>g</strong>
            <span class="text-muted ms-1">(<?= number_format($reqWeight, 3) ?> x <?= number_format($reqTouch, 2) ?>%)</span>
        </div>
        <div class="col-auto">
            <span class="text-muted">Current Fine:</span>
            <strong><?= number_format($totalIssuedFine, 4) ?>g</strong>
        </div>
        <div class="col-auto">
            <?php if ($fineNeeded > 0): ?>
            <span class="text-danger fw-semibold">Still need: <?= number_format($fineNeeded, 4) ?>g fine</span>
            <?php else: ?>
            <span class="text-success fw-semibold">Fine met (+<?= number_format($fineExcess, 4) ?>g excess)</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($hasWeightTarget): ?>
    <!-- WEIGHT STATUS BAR -->
    <div class="mb-2">
        <div class="d-flex justify-content-between align-items-center mb-1" style="font-size:12px">
            <span>
                <span class="text-muted">Weight:</span>
                <strong><?= number_format($totalIssuedWeight, 3) ?>g</strong>
                / <strong><?= number_format($reqWeight, 3) ?>g</strong>
            </span>
            <?php if ($overWeight): ?>
            <span class="text-danger fw-semibold"><i class="bi bi-exclamation-triangle-fill"></i> Excess: <?= number_format(abs($weightRemaining), 3) ?>g</span>
            <?php else: ?>
            <span class="text-success">Remaining: <?= number_format($weightRemaining, 3) ?>g</span>
            <?php endif; ?>
        </div>
        <?php
            $barColor = 'bg-success';
            if ($weightUsedPct > 100) $barColor = 'bg-danger';
            elseif ($weightUsedPct > 90) $barColor = 'bg-warning';
            $barWidth = min($weightUsedPct, 100);
        ?>
        <div class="progress" style="height:6px">
            <div class="progress-bar <?= $barColor ?>" style="width:<?= $barWidth ?>%"></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($reqTouch > 0 && $touchAbove): ?>
    <!-- ABOVE TARGET: suggest alloy -->
    <div class="alert alert-warning mb-0 py-2">
        <div class="d-flex align-items-center">
            <i class="bi bi-plus-circle-fill me-2 fs-5"></i>
            <div>
                <strong>Add <?= number_format($alloyToAdd, 3) ?> g</strong> of <strong><?= esc($alloyName) ?></strong> (0% touch) to reach <?= number_format($reqTouch, 2) ?>%
                <?php if (!$defaultAlloy): ?>
                <br><small class="text-muted">No default alloy configured. Go to Raw Material Types to set one.</small>
                <?php endif; ?>
            </div>
        </div>
        <div class="mt-2 text-muted" style="font-size:12px">
            After adding: Weight = <?= number_format($totalIssuedWeight + $alloyToAdd, 3) ?>g | Fine = <?= number_format($totalIssuedFine, 4) ?>g | Touch = <?= number_format($reqTouch, 2) ?>%
            <?php if ($hasWeightTarget): ?>
            <?php $weightAfterAlloy = $totalIssuedWeight + $alloyToAdd; ?>
            <?php if ($weightAfterAlloy > $reqWeight): ?>
            | <span class="text-danger">Exceeds target weight by <?= number_format($weightAfterAlloy - $reqWeight, 3) ?>g</span>
            <?php else: ?>
            | Remaining capacity: <?= number_format($reqWeight - $weightAfterAlloy, 3) ?>g
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($fineNeeded > 0 && $requiredFine > 0): ?>
    <!-- Also need more silver to meet required fine -->
    <div class="alert alert-info mt-2 mb-0 py-2">
        <div class="d-flex align-items-center mb-2">
            <i class="bi bi-info-circle-fill me-2 fs-5"></i>
            <div>
                <strong>But you still need <?= number_format($fineNeeded, 4) ?>g more fine</strong> to meet target (<?= number_format($requiredFine, 4) ?>g).
                <br>Add a silver item below, then alloy quantity will be recalculated.
            </div>
        </div>
        <div class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label form-label-sm mb-1">Select Silver Item to add fine</label>
                <select class="form-select form-select-sm" id="silverItemSelect" onchange="calcSilverSuggestion()">
                    <option value="">-- Pick an item --</option>
                    <?php foreach ($silverItems as $si): ?>
                    <option value="<?= $si['id'] ?>" data-touch="<?= $si['touch_pct'] ?>" data-name="<?= esc($si['type_name'].' - '.$si['batch_number']) ?>" data-avail="<?= $si['weight_in_stock_g'] ?>">
                        <?= esc($si['type_name']) ?> - <?= esc($si['batch_number']) ?> (<?= number_format($si['touch_pct'],2) ?>% | <?= number_format($si['weight_in_stock_g'],3) ?>g avail)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col">
                <div id="silverSuggestionResult" class="fw-semibold"></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php elseif ($reqTouch > 0 && $touchBelow): ?>
    <!-- BELOW TARGET: need silver to boost -->
    <div class="alert alert-danger mb-0 py-2">
        <div class="d-flex align-items-center mb-2">
            <i class="bi bi-plus-circle-fill me-2 fs-5"></i>
            <div>
                <strong>Current touch is below required.</strong> Add a silver group material to boost purity.
                <?php if ($fineNeeded > 0): ?>
                <br><span style="font-size:12px">Need <strong><?= number_format($fineNeeded, 4) ?>g</strong> more fine to reach target.</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label form-label-sm mb-1">Select Silver Item</label>
                <select class="form-select form-select-sm" id="silverItemSelect" onchange="calcSilverSuggestion()">
                    <option value="">-- Pick an item --</option>
                    <?php foreach ($silverItems as $si): ?>
                    <option value="<?= $si['id'] ?>" data-touch="<?= $si['touch_pct'] ?>" data-name="<?= esc($si['type_name'].' - '.$si['batch_number']) ?>" data-avail="<?= $si['weight_in_stock_g'] ?>">
                        <?= esc($si['type_name']) ?> - <?= esc($si['batch_number']) ?> (<?= number_format($si['touch_pct'],2) ?>% | <?= number_format($si['weight_in_stock_g'],3) ?>g avail)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col">
                <div id="silverSuggestionResult" class="fw-semibold"></div>
            </div>
        </div>
    </div>

    <?php elseif ($reqTouch > 0): ?>
    <!-- ON TARGET -->
    <div class="alert alert-success mb-0 py-2">
        <i class="bi bi-check-circle-fill me-2"></i> Touch is at target!
        <?php if ($hasWeightTarget): ?>
        <br><span style="font-size:12px" class="<?= $overWeight ? 'text-danger' : 'text-success' ?>">
            Weight: <?= number_format($totalIssuedWeight, 3) ?>g / <?= number_format($reqWeight, 3) ?>g
            — <?= $overWeight ? 'Exceeds by '.number_format(abs($weightRemaining), 3).'g' : number_format($weightRemaining, 3).'g remaining' ?>
        </span>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
</div>
<?php elseif (($reqTouch > 0 || $hasWeightTarget) && $totalIssuedWeight == 0): ?>
<div class="card mb-3 border-secondary">
<div class="card-header py-2 bg-light"><strong><i class="bi bi-lightbulb"></i> Touch Suggestion</strong></div>
<div class="card-body py-2 text-muted">No inputs yet — add materials first to see touch suggestions.</div>
</div>
<?php endif; ?>

<!-- RECEIVED -->
<div class="card mb-3" id="receivedSection">
<div class="card-header d-flex justify-content-between align-items-center">
    <strong>Received (from Karigar)</strong>
    <?php if ($isDraft): ?>
    <button class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('addRecvForm').style.display=document.getElementById('addRecvForm').style.display===''?'none':''">+ Add Receive</button>
    <?php endif; ?>
</div>
<?php if ($isDraft): ?>
<div id="addRecvForm" style="display:none" class="card-body border-bottom bg-light">
<form method="post" action="<?= base_url('melt-jobs/add-receive/'.$job['id']) ?>">
<?= csrf_field() ?>
<div class="row g-2">
    <div class="col-auto"><select name="receive_type" class="form-select form-select-sm" onchange="toggleByprod(this)">
        <option value="gatti">Gatti</option>
        <option value="byproduct">Byproduct</option>
    </select></div>
    <div class="col-auto" id="byprodDiv" style="display:none"><select name="byproduct_type_id" class="form-select form-select-sm">
        <option value="">-- Type --</option>
        <?php foreach ($byprods as $b): ?><option value="<?= $b['id'] ?>"><?= esc($b['name']) ?></option><?php endforeach; ?>
    </select></div>
    <div class="col-auto"><input type="number" step="0.0001" name="weight_g" id="recvWeightInput" class="form-control form-control-sm" placeholder="Weight (g)" required></div>
    <div class="col-auto"><input type="number" step="0.0001" name="touch_pct" id="recvTouchInput" class="form-control form-control-sm" placeholder="Touch%" value="0"></div>
    <div class="col-auto" id="batchDiv"><input type="text" name="batch_number" id="recvBatchInput" class="form-control form-control-sm" placeholder="Batch#" maxlength="30" required value="<?= esc($job['job_number']) ?>"></div>
    <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Add</button></div>
</div>
</form>
</div>
<?php endif; ?>
<div class="card-body p-0">
<table class="table table-sm table-bordered mb-0">
<thead class="table-dark"><tr>
    <th>Type</th>
    <th>Detail</th>
    <th>Batch#</th>
    <th class="text-end">Weight (g)</th>
    <th class="text-end">Touch%</th>
    <th class="text-end">Fine (g)</th>
    <?php if ($isDraft): ?><th></th><?php endif; ?>
</tr></thead>
<tbody>
<?php foreach ($receives as $row):
    $issued = $issuedMap[$row['id']] ?? null;
?>
<tr data-recv-id="<?= $row['id'] ?>">
    <td><?= ucfirst($row['receive_type']) ?></td>
    <td><?= $row['receive_type'] === 'byproduct' ? esc($row['byprod_name']) : 'Gatti' ?></td>
    <td data-cell="batch">
        <?php if ($isDraft): ?>
        <span class="recv-edit-val" data-recv-id="<?= $row['id'] ?>" data-field="batch_number" title="Click to edit"><?= esc($row['batch_number'] ?: '—') ?></span>
        <?php else: ?>
        <?= esc($row['batch_number'] ?: '—') ?>
        <?php endif; ?>
    </td>
    <td class="text-end" data-cell="weight">
        <?php if ($isDraft): ?>
        <span class="recv-edit-val" data-recv-id="<?= $row['id'] ?>" data-field="weight_g" title="Click to edit"><?= number_format($row['weight_g'],4) ?></span>
        <?php else: ?>
        <?= number_format($row['weight_g'],4) ?>
        <?php endif; ?>
    </td>
    <td class="text-end" data-cell="touch">
        <?php if ($isDraft): ?>
        <span class="recv-edit-val" data-recv-id="<?= $row['id'] ?>" data-field="touch_pct" title="Click to edit"><?= number_format($row['touch_pct'],2) ?>%</span>
        <?php else: ?>
        <?= number_format($row['touch_pct'],2) ?>%
        <?php endif; ?>
    </td>
    <td class="text-end" data-cell="fine"><?= number_format($row['weight_g']*$row['touch_pct']/100,4) ?></td>
    <?php if ($isDraft): ?>
    <td style="white-space:nowrap">
        <?php if ($row['receive_type'] === 'gatti' || $row['receive_type'] === 'byproduct'): ?>
            <?php if ($issued): ?>
                <?php if ($issued['received_at']): ?>
                <?php if ($row['receive_type'] === 'gatti'): ?>
                <button class="badge bg-success border-0 text-decoration-none py-1 px-2"
                    onclick="confirmApplyTouchToAll(<?= $job['id'] ?>, <?= (float)$issued['touch_result_pct'] ?>)"
                    title="Touch: <?= number_format((float)$issued['touch_result_pct'],2) ?>% — Click to apply to all gatti receives">
                    <i class="bi bi-flask"></i> <?= esc($issued['serial_number']) ?> <i class="bi bi-check-circle"></i>
                </button>
                <?php else: ?>
                <a href="<?= base_url('touch-shops') ?>" class="badge bg-success text-decoration-none"
                   title="Touch Entry <?= esc($issued['serial_number']) ?> (<?= number_format((float)$issued['touch_result_pct'],2) ?>%)">
                    <i class="bi bi-flask"></i> <?= esc($issued['serial_number']) ?> <i class="bi bi-check-circle"></i>
                </a>
                <?php endif; ?>
                <?php else: ?>
                <a href="<?= base_url('touch-shops') ?>" class="badge bg-warning text-dark text-decoration-none"
                   title="Touch Entry <?= esc($issued['serial_number']) ?> — Pending">
                    <i class="bi bi-flask"></i> <?= esc($issued['serial_number']) ?>
                </a>
                <?php endif; ?>
            <?php else: ?>
            <button class="btn btn-sm btn-outline-info py-0 px-1" onclick="openIssueTSModal(<?= $row['id'] ?>, <?= (float)$row['weight_g'] ?>, <?= esc(json_encode($row['batch_number'] ?? '')) ?>)" title="Issue to Touch Ledger">
                <i class="bi bi-flask"></i> Touch
            </button>
            <?php endif; ?>
        <?php endif; ?>
        <a href="<?= base_url('melt-jobs/delete-receive/'.$row['id']) ?>" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="return confirm('Delete?')"><i class="bi bi-x"></i></a>
    </td>
    <?php endif; ?>
</tr>
<?php endforeach; ?>
<?php if (!$receives): ?><tr><td colspan="7" class="text-center text-muted py-2">No receives yet</td></tr><?php endif; ?>
</tbody>
<tfoot class="table-light" id="recvTotalsRow">
<tr>
    <td colspan="3" class="text-end fw-semibold">Total</td>
    <td class="text-end fw-semibold" id="recvTotalWeight"><?= number_format($totalRecvWeight,4) ?></td>
    <td></td>
    <td class="text-end fw-semibold" id="recvTotalFine"><?= number_format($totalRecvFine,4) ?></td>
    <?php if ($isDraft): ?><td></td><?php endif; ?>
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
<tr><td>Required Weight</td><td class="text-end"><?= number_format($job['required_weight_g'],3) ?>g</td></tr>
<tr><td>Gatti Received</td><td class="text-end <?= $gattiWeightSum >= $job['required_weight_g'] ? 'text-success' : 'text-danger' ?>"><?= number_format($gattiWeightSum,3) ?>g</td></tr>
<?php endif; ?>
<?php if ($job['required_touch_pct']): ?>
<tr><td>Required Touch</td><td class="text-end"><?= number_format($job['required_touch_pct'],2) ?>%</td></tr>
<tr><td>Avg Gatti Touch</td><td class="text-end <?= $avgGattiTouch >= $job['required_touch_pct'] ? 'text-success' : 'text-danger' ?>"><?= number_format($avgGattiTouch,2) ?>%</td></tr>
<?php endif; ?>
<?php if ($pureRequired > 0): ?>
<tr class="table-info"><td><strong>Pure Required (g)</strong></td><td class="text-end fw-semibold"><?= number_format($pureRequired,3) ?>g</td></tr>
<?php endif; ?>
</table>
</div>
</div>
<?php endif; ?>
</div>

<?php if ($isDraft): ?>
<form method="post" action="<?= base_url('melt-jobs/post/'.$job['id']) ?>" onsubmit="return confirm('Post to karigar ledger? This cannot be undone.')">
<?= csrf_field() ?>
<button type="submit" class="btn btn-danger"><i class="bi bi-check-circle"></i> Post to Ledger</button>
</form>
<?php endif; ?>

<!-- ===================== KACHA MODAL ===================== -->
<div class="modal fade" id="kachaModal" tabindex="-1">
<div class="modal-dialog modal-xl">
<div class="modal-content">
    <div class="modal-header py-2">
        <h6 class="modal-title"><i class="bi bi-gem"></i> Select Kacha Lots</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>

    <!-- Search + sort toolbar -->
    <div class="modal-body border-bottom pb-2 pt-2">
        <div class="row g-2 align-items-center">
            <div class="col">
                <input type="text" id="kachaSearchInput" class="form-control form-control-sm" placeholder="Search lot number / party..." oninput="renderKachaTable()">
            </div>
            <div class="col-auto">
                <select id="kachaSortField" class="form-select form-select-sm" onchange="renderKachaTable()">
                    <option value="name">Sort: Lot No</option>
                    <option value="weight">Sort: Weight</option>
                    <option value="touch_pct">Sort: Touch %</option>
                    <option value="fine">Sort: Fine</option>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-secondary btn-sm" id="btnSortDir" onclick="toggleSortDir()" title="Toggle sort direction">&#9650; Asc</button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="modal-body p-0" style="max-height:340px;overflow-y:auto">
    <table class="table table-sm table-hover mb-0">
    <thead class="table-dark sticky-top">
        <tr>
            <th style="width:36px"><input type="checkbox" id="kachaCheckAll" title="Select all visible"></th>
            <th>Lot No</th>
            <th>Party</th>
            <th class="text-end">Weight (g)</th>
            <th class="text-end">Touch %</th>
            <th class="text-end">Fine (g)</th>
        </tr>
    </thead>
    <tbody id="kachaModalBody"></tbody>
    </table>
    </div>

    <!-- Live summary panel -->
    <div class="modal-body border-top border-bottom py-2 bg-light">
        <div class="row text-center g-0">
            <div class="col border-end">
                <div class="text-muted" style="font-size:11px">Selected Weight</div>
                <strong id="ks-weight">0.000 g</strong>
            </div>
            <div class="col border-end">
                <div class="text-muted" style="font-size:11px">Selected Fine</div>
                <strong id="ks-fine">0.0000 g</strong>
            </div>
            <div class="col border-end">
                <div class="text-muted" style="font-size:11px">Avg Touch</div>
                <strong id="ks-touch">0.00%</strong>
            </div>
            <?php if ($pureRequired > 0): ?>
            <div class="col border-end">
                <div class="text-muted" style="font-size:11px">Pure Required</div>
                <strong><?= number_format($pureRequired,3) ?> g</strong>
            </div>
            <div class="col">
                <div class="text-muted" style="font-size:11px">Still Needed</div>
                <strong id="ks-needed"><?= number_format($pureRequired,3) ?> g</strong>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal-footer py-2">
        <?php if ($pureRequired > 0): ?>
        <button type="button" class="btn btn-outline-info btn-sm me-auto" id="btnSmartPick">
            Smart Pick (target <?= number_format($pureRequired,3) ?>g pure @ <?= number_format($job['required_touch_pct'],2) ?>%)
        </button>
        <?php endif; ?>
        <span id="kachaSelCount" class="text-muted small">0 selected</span>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" id="btnAddKacha">Add Selected</button>
    </div>
</div></div></div>

<script id="kachasJson" type="application/json"><?= json_encode(array_values($kachas)) ?></script>
<script id="usedKachaIds" type="application/json"><?php
$_kachaMap = [];
foreach ($inputs as $_r) {
    if ($_r['input_type'] === 'kacha' && $_r['item_id']) {
        $_kachaMap[(string)$_r['item_id']] = (int)$_r['id'];
    }
}
echo json_encode($_kachaMap);
?></script>

<!-- Issue to Touch Ledger Modal -->
<?php if ($isDraft): ?>
<div class="modal fade" id="issueTSModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content">
    <div class="modal-header py-2">
        <h6 class="modal-title"><i class="bi bi-flask"></i> Issue to Touch Ledger</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <form method="post" id="tsFormAction" action="" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" id="tsRecvId" name="recv_id">
    <div class="modal-body">
        <div class="row g-2">
            <div class="col-6">
                <label class="form-label form-label-sm">Issue Weight (g) <span class="text-danger">*</span></label>
                <input type="number" step="0.0001" min="0.0001" name="issue_weight_g" id="tsWeight" class="form-control form-control-sm" required>
            </div>
            <div class="col-6">
                <label class="form-label form-label-sm">Stamp</label>
                <select name="stamp_id" class="form-select form-select-sm">
                    <option value="">— Select Stamp —</option>
                    <?php foreach ($stamps as $st): ?>
                    <option value="<?= $st['id'] ?>"><?= esc($st['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label form-label-sm">Link Gatti Batch <small class="text-muted">(optional)</small></label>
                <select name="gatti_stock_id" id="tsGattiBatch" class="form-select form-select-sm">
                    <option value="">— Not Linked —</option>
                    <?php foreach ($gattiOptions as $go): ?>
                    <option value="<?= $go['id'] ?>" data-batch="<?= esc($go['batch_number'] ?: '') ?>">
                        <?= esc($go['batch_number'] ?: 'No batch') ?>
                        <?= $go['job_number'] ? ' / Job '.$go['job_number'] : '' ?>
                        — <?= number_format($go['weight_g'], 4) ?>g
                        <?= $go['touch_pct'] ? ' (T:'.$go['touch_pct'].'%)' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label form-label-sm">Sample Image <small class="text-muted">(jpg/png/webp, optional)</small></label>
                <input type="file" name="sample_image" accept="image/*" class="form-control form-control-sm">
            </div>
            <div class="col-12">
                <label class="form-label form-label-sm">Touch Shop Name</label>
                <select name="touch_shop_name" id="tsTouchShop" class="form-select form-select-sm">
                    <option value="">— Select Touch Shop —</option>
                    <?php foreach ($touchShopNames as $tsn): ?>
                    <option value="<?= esc($tsn['touch_shop_name']) ?>"><?= esc($tsn['touch_shop_name']) ?></option>
                    <?php endforeach; ?>
                    <option value="__new__">＋ Add New Shop Name…</option>
                </select>
                <input type="text" name="touch_shop_name_new" id="tsNewShop" class="form-control form-control-sm mt-1"
                       placeholder="Type new touch shop name…" style="display:none" maxlength="100">
            </div>
            <div class="col-12">
                <label class="form-label form-label-sm">Notes</label>
                <input type="text" name="notes" class="form-control form-control-sm" placeholder="Optional">
            </div>
        </div>
    </div>
    <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-info btn-sm"><i class="bi bi-flask"></i> Create Touch Entry</button>
    </div>
    </form>
</div></div></div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<style>
.live-edit-val { border-bottom: 1px dashed #0d6efd; cursor:pointer; color:#0d6efd; padding:0 2px; border-radius:2px; }
.live-edit-val:hover { background:#e7f1ff; }
.recv-edit-val { border-bottom: 1px dashed #198754; cursor:pointer; color:#198754; padding:0 2px; border-radius:2px; }
.recv-edit-val:hover { background:#d1e7dd; }
</style>
<script>
/* ===================== GLOBALS ===================== */
var kachas       = JSON.parse(document.getElementById('kachasJson').textContent);
var usedKachaMap = JSON.parse(document.getElementById('usedKachaIds').textContent); /* {item_id: input_row_id} */
var usedKachaIds = Object.keys(usedKachaMap); /* just the item_id strings */
var kachaModal;
var viewAcTimer;
var viewAcItem = {};
var sortDir    = 'asc';
var JOB_ID            = <?= (int)$job['id'] ?>;
var PURE_REQUIRED     = <?= (float)$pureRequired ?>;
var REQ_TOUCH         = <?= (float)($job['required_touch_pct'] ?? 0) ?>;
var IS_DRAFT          = <?= $isDraft ? 'true' : 'false' ?>;
var SEARCH_URL        = '<?= base_url('melt-jobs/search-items') ?>';
var UPDATE_FIELD_URL  = '<?= base_url('melt-jobs/update-field/'.$job['id']) ?>';
var CSRF_NAME         = '<?= csrf_token() ?>';
var CSRF_HASH         = '<?= csrf_hash() ?>';
var ADD_INPUT_URL     = '<?= base_url('melt-jobs/add-input/'.$job['id']) ?>';
var DELETE_INPUT_BASE = '<?= base_url('melt-jobs/delete-input/') ?>';

/* Touch suggestion globals */
var CUR_WEIGHT    = <?= (float)$totalIssuedWeight ?>;
var CUR_FINE      = <?= (float)$totalIssuedFine ?>;
var REQ_WEIGHT    = <?= (float)($job['required_weight_g'] ?? 0) ?>;
var REQ_FINE      = <?= (float)$requiredFine ?>;
var FINE_NEEDED   = <?= (float)$fineNeeded ?>;
var ALLOY_NAME    = '<?= esc($alloyName) ?>';
var ALLOY_TO_ADD  = <?= (float)$alloyToAdd ?>;

/* ===================== SILVER SUGGESTION CALC ===================== */
function calcSilverSuggestion() {
    var sel = document.getElementById('silverItemSelect');
    var resultDiv = document.getElementById('silverSuggestionResult');
    if (!sel || !resultDiv) return;

    var opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) { resultDiv.innerHTML = ''; return; }

    var P     = parseFloat(opt.dataset.touch);
    var avail = parseFloat(opt.dataset.avail);
    var name  = opt.dataset.name;

    if (P <= 0) {
        resultDiv.innerHTML = '<span class="text-danger">This item has 0% touch — cannot boost purity.</span>';
        return;
    }

    var silverToAdd, html = '';

    if (REQ_FINE > 0 && FINE_NEEDED > 0) {
        /* Fine-based calculation: how much of this item to add to reach required fine */
        silverToAdd = FINE_NEEDED / (P / 100);
    } else {
        /* Touch-based fallback: reach the required touch% */
        silverToAdd = ((REQ_TOUCH / 100) * CUR_WEIGHT - CUR_FINE) / (P / 100 - REQ_TOUCH / 100);
    }

    if (silverToAdd <= 0) {
        resultDiv.innerHTML = '<span class="text-success">Fine/Touch target already met!</span>';
        return;
    }

    var newWeight = CUR_WEIGHT + silverToAdd;
    var newFine   = CUR_FINE + silverToAdd * P / 100;
    var newTouch  = newWeight > 0 ? (newFine / newWeight * 100) : 0;

    /* Main suggestion */
    html += '<i class="bi bi-arrow-right-circle-fill me-1 text-primary"></i> ';
    html += 'Add <strong>' + silverToAdd.toFixed(3) + ' g</strong> of <strong>' + name + '</strong>';

    /* Stock warning */
    if (silverToAdd > avail) {
        html += '<br><span class="text-warning"><i class="bi bi-exclamation-triangle"></i> Only ' + avail.toFixed(3) + 'g available in stock!</span>';
    }

    /* After adding silver */
    html += '<br><span class="text-muted" style="font-size:12px">After silver: Weight=' + newWeight.toFixed(3) + 'g | Fine=' + newFine.toFixed(4) + 'g | Touch=' + newTouch.toFixed(4) + '%</span>';

    /* If after adding silver, touch goes above required → auto-show alloy needed */
    if (REQ_TOUCH > 0 && newTouch > REQ_TOUCH) {
        var alloyNeeded = (newFine / (REQ_TOUCH / 100)) - newWeight;
        if (alloyNeeded > 0) {
            var finalWeight = newWeight + alloyNeeded;
            html += '<br><br><i class="bi bi-plus-circle-fill me-1 text-warning"></i> ';
            html += 'Then add <strong>' + alloyNeeded.toFixed(3) + 'g</strong> of <strong>' + ALLOY_NAME + '</strong> (0%) to bring touch down to ' + REQ_TOUCH.toFixed(2) + '%';
            html += '<br><span class="text-muted" style="font-size:12px">Final: Weight=' + finalWeight.toFixed(3) + 'g | Fine=' + newFine.toFixed(4) + 'g | Touch=' + REQ_TOUCH.toFixed(2) + '%';
            if (REQ_WEIGHT > 0) {
                if (finalWeight > REQ_WEIGHT) {
                    html += ' | <span class="text-danger">Exceeds target weight by ' + (finalWeight - REQ_WEIGHT).toFixed(3) + 'g</span>';
                } else {
                    html += ' | Remaining: ' + (REQ_WEIGHT - finalWeight).toFixed(3) + 'g';
                }
            }
            html += '</span>';
        }
    }

    /* Weight info */
    if (REQ_WEIGHT > 0) {
        if (newWeight > REQ_WEIGHT) {
            html += '<br><span class="text-warning" style="font-size:12px"><i class="bi bi-exclamation-triangle"></i> Silver alone exceeds target weight by ' + (newWeight - REQ_WEIGHT).toFixed(3) + 'g</span>';
        }
    }

    resultDiv.innerHTML = html;
}

/* ===================== INIT ===================== */
document.addEventListener('DOMContentLoaded', function() {
    kachaModal = new bootstrap.Modal(document.getElementById('kachaModal'));

    document.getElementById('kachaCheckAll').addEventListener('change', function() {
        document.querySelectorAll('#kachaModalBody .kacha-cb').forEach(cb => cb.checked = this.checked);
        updateKachaSummary();
    });
    document.getElementById('btnAddKacha').addEventListener('click', addKachasViaPost);
    document.getElementById('kachaModalBody').addEventListener('change', updateKachaSummary);

    var smartBtn = document.getElementById('btnSmartPick');
    if (smartBtn) smartBtn.addEventListener('click', smartPick);

    var btnToggle = document.getElementById('btnToggleAddInput');
    if (btnToggle) btnToggle.addEventListener('click', function() {
        var p = document.getElementById('addInputPanel');
        p.style.display = p.style.display === 'none' ? '' : 'none';
    });
    var btnDirect = document.getElementById('btnOpenKachaDirectly');
    if (btnDirect) btnDirect.addEventListener('click', function() { openKachaModal(); });

    /* live-edit header */
    if (IS_DRAFT) {
        document.querySelectorAll('.live-edit-val').forEach(function(span) {
            span.addEventListener('click', function() { startLiveEdit(span); });
        });
    }
});

/* ===================== ITEM SEARCH (FIXED URL) ===================== */
function onViewAcInput(inp) {
    clearTimeout(viewAcTimer);
    var q = inp.value.trim();
    var dd = document.getElementById('viewAcDd');
    if (q.length < 1) { dd.style.display = 'none'; return; }
    /* if user typed a kacha-related keyword, go straight to modal */
    if (q.toLowerCase().match(/^kac/)) {
        dd.innerHTML = '<button type="button" class="list-group-item list-group-item-action list-group-item-info py-2 px-3" id="ddOpenKacha"><i class="bi bi-gem"></i> Browse all Kacha lots...</button>';
        dd.style.display = '';
        document.getElementById('ddOpenKacha').addEventListener('mousedown', function(e) { e.preventDefault(); dd.style.display='none'; openKachaModal(); });
        return;
    }
    viewAcTimer = setTimeout(function() {
        fetch(SEARCH_URL + '?q=' + encodeURIComponent(q))
            .then(function(r) {
                if (!r.ok) { console.error('Search failed: ' + r.status); dd.style.display='none'; return []; }
                return r.json();
            })
            .then(function(items) {
                if (!items || !items.length) { dd.style.display='none'; return; }
                renderViewAcDd(items, inp);
            })
            .catch(function(e) { console.error('Search error', e); dd.style.display='none'; });
    }, 280);
}

function renderViewAcDd(items, inp) {
    var dd = document.getElementById('viewAcDd');
    if (!items.length) { dd.style.display = 'none'; return; }
    var typeLabel = {'raw_material':'Raw Material Batch','kacha':'Kacha','byproduct':'Byproduct','other':'Other'};
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
        var extra = '';
        if (item.type === 'raw_material') {
            extra = ' [' + item.type_name + '] — ' + parseFloat(item.remaining).toFixed(3) + 'g avail @ ' + parseFloat(item.touch).toFixed(4) + '%';
        } else if (item.type === 'kacha') {
            extra = ' — ' + parseFloat(item.weight).toFixed(3) + 'g @ ' + parseFloat(item.touch).toFixed(2) + '%';
        } else if (item.touch) {
            extra = ' (' + item.touch + '%)';
        }
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

                var hint     = document.getElementById('viewWeightHint');
                var wInp     = document.getElementById('viewInpWeight');
                var hintLines = [];
                var suggestedWeight = null;

                /* ---- AUTO-FILL WEIGHT FROM TOUCH SUGGESTION ---- */
                if (item.type === 'raw_material') {
                    var group = item.material_group || 'other';
                    var touch = parseFloat(item.touch) || 0;

                    if (group === 'alloy' && ALLOY_TO_ADD > 0) {
                        /* Alloy: fill with the amount needed to dilute touch down to target */
                        suggestedWeight = ALLOY_TO_ADD;
                        hintLines.push('<span class="text-success"><i class="bi bi-lightbulb-fill"></i> Suggested: ' + suggestedWeight.toFixed(4) + 'g ' + ALLOY_NAME + ' to reach ' + REQ_TOUCH.toFixed(2) + '% touch</span>');

                    } else if (group === 'silver' && touch > 0 && FINE_NEEDED > 0) {
                        /* Silver: fill with weight needed to supply the missing fine */
                        suggestedWeight = FINE_NEEDED / (touch / 100);
                        hintLines.push('<span class="text-success"><i class="bi bi-lightbulb-fill"></i> Suggested: ' + suggestedWeight.toFixed(4) + 'g to supply ' + FINE_NEEDED.toFixed(4) + 'g fine needed</span>');
                    }

                    /* Cap to available stock */
                    if (suggestedWeight !== null && item.remaining && suggestedWeight > parseFloat(item.remaining)) {
                        hintLines.push('<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> Capped to available stock (' + parseFloat(item.remaining).toFixed(4) + 'g)</span>');
                        suggestedWeight = parseFloat(item.remaining);
                    }

                    /* Set weight field */
                    if (suggestedWeight !== null && suggestedWeight > 0) {
                        wInp.value = parseFloat(suggestedWeight.toFixed(4));
                    }

                    /* Always show max available */
                    if (item.remaining) {
                        hintLines.push('<span class="text-info">Max available: ' + parseFloat(item.remaining).toFixed(4) + 'g</span>');
                    }

                    /* Set max */
                    if (item.remaining) {
                        wInp.max = item.remaining;
                    } else {
                        wInp.removeAttribute('max');
                    }
                } else {
                    wInp.removeAttribute('max');
                }

                /* Show/hide hint */
                if (hint) {
                    if (hintLines.length > 0) {
                        hint.innerHTML  = hintLines.join('<br>');
                        hint.className  = 'form-text';
                        hint.style.display = '';
                    } else {
                        hint.style.display = 'none';
                    }
                }

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

/* ===================== LIVE-EDIT HEADER ===================== */
function startLiveEdit(span) {
    var field   = span.dataset.field;
    var rawVal  = span.dataset.rawval !== undefined ? span.dataset.rawval : extractRawValue(span.textContent);
    var inp = document.createElement('input');
    inp.type  = 'number';
    inp.step  = '0.0001';
    inp.value = rawVal;
    inp.className = 'form-control form-control-sm d-inline-block';
    inp.style.width = '110px';
    span.replaceWith(inp);
    inp.focus();
    inp.select();

    function save() {
        var fd = new FormData();
        fd.append(CSRF_NAME, CSRF_HASH);
        fd.append('field', field);
        fd.append('value', inp.value);
        fetch(UPDATE_FIELD_URL, {method:'POST', body:fd})
            .then(r => r.json())
            .then(function(res) {
                if (!res.success) { alert(res.error || 'Save failed'); restoreSpan(); return; }
                /* update CSRF hash for next call */
                if (res.csrf_hash) CSRF_HASH = res.csrf_hash;
                var newSpan = document.createElement('span');
                newSpan.className   = 'live-edit-val';
                newSpan.dataset.field = field;
                newSpan.title       = 'Click to edit';
                newSpan.dataset.rawval = res.value !== null ? res.value : '';
                newSpan.textContent = formatHeaderVal(field, res.value);
                newSpan.addEventListener('click', function() { startLiveEdit(newSpan); });
                inp.replaceWith(newSpan);
                /* also update pureRequired live if touch or weight changed */
                if (field === 'required_touch_pct' || field === 'required_weight_g') {
                    location.reload();
                }
            })
            .catch(function() { alert('Network error — could not save.'); restoreSpan(); });
    }

    function restoreSpan() {
        var s2 = document.createElement('span');
        s2.className = span.className; s2.dataset.field = field; s2.title = 'Click to edit';
        s2.textContent = span.textContent;
        s2.addEventListener('click', function() { startLiveEdit(s2); });
        inp.replaceWith(s2);
    }

    inp.addEventListener('blur', save);
    inp.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { inp.blur(); }
        if (e.key === 'Escape') { inp.removeEventListener('blur', save); restoreSpan(); }
    });
}

function extractRawValue(text) {
    /* strip currency/units to get the number */
    return text.replace(/[₹,g%\/kg—]/g,'').trim();
}

function formatHeaderVal(field, val) {
    if (val === null || val === undefined || val === '') return '—';
    var n = parseFloat(val);
    if (field === 'cash_rate_per_kg') return '₹' + n.toFixed(2) + '/kg';
    if (field === 'fine_pct')         return n.toFixed(4) + '%';
    if (field === 'required_touch_pct') return n.toFixed(2) + '%';
    if (field === 'required_weight_g')  return n.toFixed(3) + 'g';
    return val;
}

/* ===================== KACHA MODAL ===================== */
function openKachaModal() {
    document.getElementById('kachaCheckAll').checked = false;
    document.getElementById('kachaSearchInput').value = '';
    renderKachaTable();          /* renders rows first */
    /* pre-check already-added kacha lots */
    document.querySelectorAll('#kachaModalBody .kacha-cb').forEach(function(cb) {
        if (usedKachaIds.indexOf(cb.dataset.id) !== -1) cb.checked = true;
    });
    updateKachaSummary();
    kachaModal.show();
}

function toggleSortDir() {
    sortDir = sortDir === 'asc' ? 'desc' : 'asc';
    document.getElementById('btnSortDir').textContent = sortDir === 'asc' ? '▲ Asc' : '▼ Desc';
    renderKachaTable();
}

function renderKachaTable() {
    var q     = (document.getElementById('kachaSearchInput').value || '').toLowerCase();
    var field = document.getElementById('kachaSortField').value;

    var filtered = kachas.filter(function(k) {
        return !q || (k.name && k.name.toLowerCase().includes(q))
                  || (k.party && k.party.toLowerCase().includes(q));
    });

    filtered.sort(function(a, b) {
        var av = a[field] !== undefined ? a[field] : a.name;
        var bv = b[field] !== undefined ? b[field] : b.name;
        if (typeof av === 'string') av = av.toLowerCase();
        if (typeof bv === 'string') bv = bv.toLowerCase();
        if (av < bv) return sortDir === 'asc' ? -1 : 1;
        if (av > bv) return sortDir === 'asc' ?  1 : -1;
        return 0;
    });

    /* preserve checked state + always keep already-added ones checked */
    var checkedIds = {};
    document.querySelectorAll('#kachaModalBody .kacha-cb:checked').forEach(function(cb) {
        checkedIds[cb.dataset.id] = true;
    });
    usedKachaIds.forEach(function(id) { checkedIds[id] = true; });

    var tbody = document.getElementById('kachaModalBody');
    tbody.innerHTML = '';
    filtered.forEach(function(k) {
        var tr = document.createElement('tr');
        var chk = checkedIds[k.id] ? 'checked' : '';
        tr.innerHTML =
            '<td><input type="checkbox" class="kacha-cb" '+chk+
                ' data-id="'   +k.id+
                '" data-name="' +escAttr(k.name)+
                '" data-weight="'+parseFloat(k.weight)+
                '" data-touch="' +parseFloat(k.touch_pct)+
                '" data-fine="'  +parseFloat(k.fine)+'"></td>' +
            '<td class="fw-semibold">'+esc(k.name)+'</td>' +
            '<td class="text-muted small">'+(k.party ? esc(k.party) : '')+'</td>' +
            '<td class="text-end">'+parseFloat(k.weight).toFixed(3)+'</td>' +
            '<td class="text-end">'+parseFloat(k.touch_pct).toFixed(2)+'%</td>' +
            '<td class="text-end">'+parseFloat(k.fine).toFixed(4)+'</td>';
        tbody.appendChild(tr);
    });

    updateKachaSummary();
}

function updateKachaSummary() {
    var totalWeight = 0, totalFine = 0, count = 0;
    document.querySelectorAll('#kachaModalBody .kacha-cb:checked').forEach(function(cb) {
        totalWeight += parseFloat(cb.dataset.weight) || 0;
        totalFine   += parseFloat(cb.dataset.fine)   || 0;
        count++;
    });
    var avgTouch = totalWeight > 0 ? (totalFine / totalWeight * 100) : 0;

    document.getElementById('ks-weight').textContent = totalWeight.toFixed(3) + ' g';
    document.getElementById('ks-fine').textContent   = totalFine.toFixed(4) + ' g';
    document.getElementById('ks-touch').textContent  = avgTouch.toFixed(2) + '%';

    var neededEl = document.getElementById('ks-needed');
    if (neededEl && PURE_REQUIRED > 0) {
        var needed = PURE_REQUIRED - totalFine;
        neededEl.textContent = needed.toFixed(3) + ' g';
        neededEl.className   = needed > 0.001 ? 'text-danger' : 'text-success';
    }

    document.getElementById('kachaSelCount').textContent = count + ' selected';
    document.getElementById('btnAddKacha').textContent   = count > 0 ? 'Add Selected (' + count + ')' : 'Add Selected';
}

/* ===================== SMART PICK ===================== */
function smartPick() {
    if (PURE_REQUIRED <= 0) return;

    /* uncheck all */
    document.querySelectorAll('#kachaModalBody .kacha-cb').forEach(function(cb) { cb.checked = false; });

    /* work on full kachas array sorted by |touch - reqTouch| asc */
    var pool = kachas.slice().sort(function(a, b) {
        return Math.abs(parseFloat(a.touch_pct) - REQ_TOUCH) - Math.abs(parseFloat(b.touch_pct) - REQ_TOUCH);
    });

    var selected = [];
    var cumFine  = 0;

    for (var i = 0; i < pool.length; i++) {
        var lot      = pool[i];
        var lotFine  = parseFloat(lot.fine);
        var withLot  = Math.abs(cumFine + lotFine - PURE_REQUIRED);
        var withoutLot = Math.abs(cumFine - PURE_REQUIRED);

        /* include this lot if it brings cumFine closer to PURE_REQUIRED
           OR if cumFine is still below target (must add more) */
        if (cumFine < PURE_REQUIRED || withLot < withoutLot) {
            selected.push(lot.id);
            cumFine += lotFine;
            /* stop if we're close enough */
            if (Math.abs(cumFine - PURE_REQUIRED) < 0.5) break;
        }
    }

    /* check the chosen lots in the rendered table */
    document.querySelectorAll('#kachaModalBody .kacha-cb').forEach(function(cb) {
        if (selected.indexOf(cb.dataset.id) !== -1) cb.checked = true;
    });

    updateKachaSummary();
}

/* ===================== ADD KACHA (POST EACH) ===================== */
function addKachasViaPost() {
    var promises = [];
    /* DELETE_INPUT_BASE is set in globals */

    /* collect currently checked ids */
    var nowChecked = {};
    document.querySelectorAll('#kachaModalBody .kacha-cb:checked').forEach(function(cb) {
        nowChecked[cb.dataset.id] = cb;
    });

    /* ADD: newly checked lots not yet in the table */
    Object.keys(nowChecked).forEach(function(id) {
        if (usedKachaIds.indexOf(id) !== -1) return; /* already in table, skip */
        var cb = nowChecked[id];
        var fd = new FormData();
        fd.append(CSRF_NAME, CSRF_HASH);
        fd.append('input_type', 'kacha');
        fd.append('item_id',    id);
        fd.append('item_name',  cb.dataset.name);
        fd.append('weight_g',   cb.dataset.weight);
        fd.append('touch_pct',  cb.dataset.touch);
        promises.push(fetch(ADD_INPUT_URL, {method:'POST', body:fd}));
    });

    /* REMOVE: lots that were in the table but are now unchecked */
    usedKachaIds.forEach(function(id) {
        if (nowChecked[id]) return; /* still checked, keep it */
        var inputRowId = usedKachaMap[id];
        if (!inputRowId) return;
        var fd = new FormData();
        fd.append(CSRF_NAME, CSRF_HASH);
        promises.push(fetch(DELETE_INPUT_BASE + inputRowId, {method:'GET'}));
    });

    if (!promises.length) { kachaModal.hide(); return; }
    Promise.all(promises).then(function() { location.reload(); });
}

/* ===================== INLINE TOUCH% EDIT ===================== */
var UPDATE_INPUT_BASE = '<?= base_url('melt-jobs/update-input/') ?>';

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.input-touch-edit').forEach(function (span) {
        span.style.borderBottom = '1px dashed #0d6efd';
        span.style.cursor = 'pointer';
        span.style.color = '#0d6efd';
        span.addEventListener('click', function () { startTouchEdit(span); });
    });
});

function startTouchEdit(span) {
    var inputId  = span.dataset.inputId;
    var origVal  = parseFloat(span.dataset.touch);
    var inp = document.createElement('input');
    inp.type      = 'number';
    inp.step      = '0.0001';
    inp.min       = '0';
    inp.max       = '101';
    inp.value     = origVal;
    inp.className = 'form-control form-control-sm text-end d-inline-block';
    inp.style.width = '100px';
    span.replaceWith(inp);
    inp.focus();
    inp.select();

    function save() {
        var newVal = parseFloat(inp.value);
        if (isNaN(newVal) || newVal < 0 || newVal > 101) {
            alert('Touch % must be between 0 and 101');
            restore();
            return;
        }
        if (newVal === origVal) { restore(); return; }

        var fd = new FormData();
        fd.append(CSRF_NAME, CSRF_HASH);
        fd.append('touch_pct', newVal);
        fetch(UPDATE_INPUT_BASE + inputId, { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success) { alert(res.error || 'Save failed'); restore(); return; }
                /* rebuild span */
                var newSpan = document.createElement('span');
                newSpan.className       = 'input-touch-edit';
                newSpan.dataset.inputId = inputId;
                newSpan.dataset.weight  = span.dataset.weight || inp.closest('tr') ? '' : '';
                newSpan.dataset.touch   = res.touch_pct;
                newSpan.dataset.weight  = res.weight_g;
                newSpan.title           = 'Click to edit touch%';
                newSpan.textContent     = parseFloat(res.touch_pct).toFixed(4) + '%';
                newSpan.style.borderBottom = '1px dashed #0d6efd';
                newSpan.style.cursor = 'pointer';
                newSpan.style.color  = '#0d6efd';
                newSpan.addEventListener('click', function () { startTouchEdit(newSpan); });
                inp.replaceWith(newSpan);
                /* update fine cell in same row */
                var fineCell = document.getElementById('fine-cell-' + inputId);
                if (fineCell) fineCell.textContent = parseFloat(res.fine_g).toFixed(4);
                /* recalculate footer totals */
                recalcInputFooter();
            })
            .catch(function () { alert('Network error'); restore(); });
    }

    function restore() {
        var s2 = document.createElement('span');
        s2.className       = 'input-touch-edit';
        s2.dataset.inputId = inputId;
        s2.dataset.touch   = origVal;
        s2.title           = 'Click to edit touch%';
        s2.textContent     = origVal.toFixed(4) + '%';
        s2.style.borderBottom = '1px dashed #0d6efd';
        s2.style.cursor = 'pointer';
        s2.style.color  = '#0d6efd';
        s2.addEventListener('click', function () { startTouchEdit(s2); });
        inp.replaceWith(s2);
    }

    inp.addEventListener('blur', save);
    inp.addEventListener('keydown', function (e) {
        if (e.key === 'Enter')  { inp.blur(); }
        if (e.key === 'Escape') { inp.removeEventListener('blur', save); restore(); }
    });
}

function recalcInputFooter() {
    var totalWeight = 0, totalFine = 0;
    document.querySelectorAll('.input-touch-edit').forEach(function (span) {
        var w = parseFloat(span.dataset.weight) || 0;
        var t = parseFloat(span.dataset.touch)  || 0;
        totalWeight += w;
        totalFine   += w * t / 100;
    });
    /* also add rows without editable spans (posted / non-draft) using fine cells */
    /* footer cells */
    var avgTouchEl = document.getElementById('footer-avg-touch');
    var fineEl     = document.getElementById('footer-fine');
    if (avgTouchEl) avgTouchEl.textContent = totalWeight > 0 ? (totalFine / totalWeight * 100).toFixed(4) + '%' : '—';
    if (fineEl)     fineEl.textContent     = totalFine.toFixed(4);
}
function toggleByprod(sel) {
    document.getElementById('byprodDiv').style.display = sel.value === 'byproduct' ? '' : 'none';
    var batchInp = document.getElementById('recvBatchInput');
    if (batchInp) batchInp.required = (sel.value === 'gatti');
}

/* ===================== RECEIVE FORM — PREVENT ENTER SUBMIT ===================== */
document.addEventListener('DOMContentLoaded', function() {
    var recvForm = document.getElementById('addRecvForm');
    if (recvForm) {
        recvForm.querySelectorAll('input').forEach(function(inp) {
            inp.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') { e.preventDefault(); }
            });
        });
    }
});

/* ===================== RECEIVE ROWS — INLINE EDIT ===================== */
var UPDATE_RECV_BASE = '<?= base_url('melt-jobs/update-receive/') ?>';

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.recv-edit-val').forEach(function(span) {
        span.addEventListener('click', function() { startRecvEdit(span); });
    });
});

function startRecvEdit(span) {
    var recvId = span.dataset.recvId;
    var field  = span.dataset.field;
    var isTouch  = field === 'touch_pct';
    var isBatch  = field === 'batch_number';
    var rawTxt   = span.textContent.replace('%','').trim();
    var origVal  = isBatch ? (rawTxt === '—' ? '' : rawTxt) : parseFloat(rawTxt);

    var inp = document.createElement('input');
    if (isBatch) {
        inp.type      = 'text';
        inp.maxLength = 30;
        inp.value     = origVal;
        inp.className = 'form-control form-control-sm d-inline-block';
        inp.style.width = '120px';
    } else {
        inp.type      = 'number';
        inp.step      = isTouch ? '0.01' : '0.0001';
        inp.min       = isTouch ? '0' : '0.0001';
        if (isTouch) inp.max = '101';
        inp.value     = origVal;
        inp.className = 'form-control form-control-sm text-end d-inline-block';
        inp.style.width = '100px';
    }
    span.replaceWith(inp);
    inp.focus(); inp.select();

    function save() {
        if (isBatch) {
            var newStr = inp.value.trim();
            if (newStr === origVal) { restore(); return; }
            var fd = new FormData();
            fd.append(CSRF_NAME, CSRF_HASH);
            fd.append('field', field);
            fd.append('value', newStr);
            fetch(UPDATE_RECV_BASE + recvId, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (!res.success) { alert(res.error || 'Save failed'); restore(); return; }
                    if (res.csrf_hash) CSRF_HASH = res.csrf_hash;
                    var newSpan = document.createElement('span');
                    newSpan.className      = 'recv-edit-val';
                    newSpan.dataset.recvId = recvId;
                    newSpan.dataset.field  = field;
                    newSpan.title          = 'Click to edit';
                    newSpan.textContent    = res.batch_number || '—';
                    newSpan.addEventListener('click', function() { startRecvEdit(newSpan); });
                    inp.replaceWith(newSpan);
                })
                .catch(function() { alert('Network error'); restore(); });
            return;
        }

        var newVal = parseFloat(inp.value);
        if (isNaN(newVal)) { restore(); return; }
        if (newVal === origVal) { restore(); return; }

        var fd = new FormData();
        fd.append(CSRF_NAME, CSRF_HASH);
        fd.append('field', field);
        fd.append('value', newVal);
        fetch(UPDATE_RECV_BASE + recvId, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (!res.success) { alert(res.error || 'Save failed'); restore(); return; }
                if (res.csrf_hash) CSRF_HASH = res.csrf_hash;

                var tr = inp.closest('tr');
                var newSpan = document.createElement('span');
                newSpan.className      = 'recv-edit-val';
                newSpan.dataset.recvId = recvId;
                newSpan.dataset.field  = field;
                newSpan.title          = 'Click to edit';
                newSpan.textContent    = isTouch
                    ? parseFloat(res.touch_pct).toFixed(2) + '%'
                    : parseFloat(res.weight_g).toFixed(4);
                newSpan.addEventListener('click', function() { startRecvEdit(newSpan); });
                inp.replaceWith(newSpan);

                /* update fine cell */
                var fineCell = tr.querySelector('[data-cell="fine"]');
                if (fineCell) fineCell.textContent = parseFloat(res.fine_g).toFixed(4);

                /* update weight cell if touch was edited (to keep data in sync for totals) */
                recalcRecvFooter();
            })
            .catch(function() { alert('Network error'); restore(); });
    }

    function restore() {
        var s2 = document.createElement('span');
        s2.className      = 'recv-edit-val';
        s2.dataset.recvId = recvId;
        s2.dataset.field  = field;
        s2.title          = 'Click to edit';
        s2.textContent    = isBatch ? (origVal || '—') : (isTouch ? origVal.toFixed(2) + '%' : origVal.toFixed(4));
        s2.addEventListener('click', function() { startRecvEdit(s2); });
        inp.replaceWith(s2);
    }

    inp.addEventListener('blur', save);
    inp.addEventListener('keydown', function(e) {
        if (e.key === 'Enter')  { inp.blur(); }
        if (e.key === 'Escape') { inp.removeEventListener('blur', save); restore(); }
    });
}

function recalcRecvFooter() {
    var totalW = 0, totalF = 0;
    document.querySelectorAll('tbody tr[data-recv-id]').forEach(function(tr) {
        var wEl = tr.querySelector('[data-cell="weight"]');
        var fEl = tr.querySelector('[data-cell="fine"]');
        if (wEl) totalW += parseFloat(wEl.textContent) || 0;
        if (fEl) totalF += parseFloat(fEl.textContent) || 0;
    });
    var wTot = document.getElementById('recvTotalWeight');
    var fTot = document.getElementById('recvTotalFine');
    if (wTot) wTot.textContent = totalW.toFixed(4);
    if (fTot) fTot.textContent = totalF.toFixed(4);
}

/* ===================== ISSUE TO TOUCH LEDGER MODAL ===================== */
var tsMod;
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('issueTSModal');
    if (el) tsMod = new bootstrap.Modal(el);
});
function openIssueTSModal(recvId, weight, batchNumber) {
    // Lazy-init modal in case Bootstrap loaded after DOMContentLoaded
    if (!tsMod) {
        var el = document.getElementById('issueTSModal');
        if (!el) { alert('Touch modal not found. Is the job in draft status?'); return; }
        tsMod = new bootstrap.Modal(el);
    }
    document.getElementById('tsRecvId').value    = recvId;
    document.getElementById('tsWeight').value    = parseFloat(weight).toFixed(4);
    document.getElementById('tsFormAction').action = '<?= base_url('touch-shops/issue/') ?>' + recvId;
    // Pre-select gatti batch matching batchNumber
    var gs = document.getElementById('tsGattiBatch');
    if (gs) {
        gs.value = '';
        if (batchNumber) {
            for (var i = 0; i < gs.options.length; i++) {
                if (gs.options[i].dataset.batch === batchNumber) {
                    gs.value = gs.options[i].value;
                    break;
                }
            }
        }
    }
    // Pre-select last used touch shop from localStorage
    var selShop = document.getElementById('tsTouchShop');
    var inpNew  = document.getElementById('tsNewShop');
    if (selShop) {
        var lastShop = localStorage.getItem('last_touch_shop') || '';
        selShop.value = lastShop;
        if (selShop.value !== lastShop) selShop.value = ''; // not in list
        if (inpNew) inpNew.style.display = 'none';
        selShop.onchange = function() {
            if (inpNew) inpNew.style.display = this.value === '__new__' ? '' : 'none';
            if (this.value === '__new__' && inpNew) inpNew.focus();
        };
    }
    tsMod.show();
    setTimeout(function() { document.getElementById('tsWeight').focus(); }, 400);
}
// Save touch shop name on modal form submit
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('tsFormAction');
    if (form) form.addEventListener('submit', function() {
        var sel = document.getElementById('tsTouchShop');
        var inp = document.getElementById('tsNewShop');
        if (sel) {
            var name = sel.value === '__new__' ? (inp ? inp.value.trim() : '') : sel.value;
            if (name && name !== '__new__') localStorage.setItem('last_touch_shop', name);
        }
    });
});

function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function escAttr(s) {
    return String(s).replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

/* ===================== APPLY TOUCH TO ALL GATTI ===================== */
function confirmApplyTouchToAll(jobId, touchPct) {
    if (!confirm('Apply touch ' + parseFloat(touchPct).toFixed(2) + '% to ALL gatti receives in this job?')) return;
    var fd = new FormData();
    fd.append(CSRF_NAME, CSRF_HASH);
    fd.append('touch_pct', touchPct);
    fetch('<?= base_url('melt-jobs/apply-touch-to-all/') ?>' + jobId, { method: 'POST', body: fd })
        .then(function(r) { return r.text(); })
        .then(function() { window.location.reload(); })
        .catch(function() { alert('Network error'); });
}
</script>
<?= $this->endSection() ?>
