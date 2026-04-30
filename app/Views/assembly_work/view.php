<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <div id="globalFeedback"></div>
        <h5 class="mb-0"><?= esc($work['work_number']) ?></h5>
        <small class="text-muted">Karigar: <strong><?= esc($work['karigar_name']) ?></strong><?= !empty($work['dept_name']) ? ' ('.esc($work['dept_name']).')' : '' ?></small>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <a href="<?= base_url('assembly-work') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
        <span class="badge <?= $work['status'] === 'completed' ? 'bg-success' : ($work['status'] === 'finished' ? 'bg-info text-dark' : ($work['status'] === 'in_progress' ? 'bg-primary' : 'bg-warning text-dark')) ?> fs-6">
            <?= ucwords(str_replace('_', ' ', $work['status'])) ?>
        </span>
        <?php if (!in_array($work['status'], ['finished', 'completed'], true)): ?>
            <a href="<?= base_url('assembly-work/finish/'.$work['id']) ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Mark this work as finished?')">Mark Finished</a>
        <?php endif; ?>
        <?php if ($work['status'] === 'finished'): ?>
            <a href="<?= base_url('assembly-work/complete/'.$work['id']) ?>" class="btn btn-sm btn-success" onclick="return confirm('Complete this work?')">Complete</a>
        <?php endif; ?>
        <?php if (in_array($work['status'], ['finished', 'completed'], true)): ?>
            <a href="<?= base_url('assembly-work/reopen/'.$work['id']) ?>" class="btn btn-sm btn-outline-warning" onclick="return confirm('Reopen this work? Status will revert to In Progress.')">Reopen</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Issued Weight</div>
                <div class="fs-5 fw-bold" id="summaryIssuedWeight"><?= number_format($totalIssuedWeight, 4) ?> g</div>
                <div class="small text-muted" id="summaryIssuedPcs">&asymp; <?= number_format($totalIssuedPcs, 4) ?> pcs</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Received Weight</div>
                <div class="fs-5 fw-bold"><?= number_format($totalRecvWeight, 4) ?> g</div>
                <div class="small text-muted">All receive rows</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Finished Goods Weight</div>
                <div class="fs-5 fw-bold"><?= number_format($totalFinishedWeight, 4) ?> g</div>
                <div class="small text-muted">≈ <?= number_format($totalFinishedPcs, 4) ?> pcs</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Making Charge</div>
                <div class="fw-bold">₹ <?= number_format((float)($work['making_charge_cash'] ?? 0), 2) ?></div>
                <div class="small text-muted"><?= number_format((float)($work['making_charge_fine'] ?? 0), 4) ?> g fine</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Linked Orders</strong>
        <?php if (!in_array($work['status'], ['finished', 'completed'], true)): ?>
        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addOrderWrap">+ Link Order</button>
        <?php endif; ?>
    </div>
    <?php if (!in_array($work['status'], ['finished', 'completed'], true)): ?>
    <div id="addOrderWrap" class="collapse border-bottom">
        <div class="card-body bg-light">
            <form method="post" action="<?= base_url('assembly-work/'.$work['id'].'/add-order') ?>" id="addOrderForm" class="row g-2">
                <?= csrf_field() ?>
                <div class="col-md-8">
                    <select name="order_id" class="form-select form-select-sm">
                        <option value="">-- Select order --</option>
                        <?php foreach ($orders as $o): ?>
                        <option value="<?= $o['id'] ?>"><?= esc($o['order_number'] ?? ('ORD-'.$o['id'])) ?> — <?= esc($o['title'] ?? '') ?><?= !empty($o['client_name']) ? ' — '.esc($o['client_name']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary">Add</button></div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    <div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0">
            <thead class="table-dark"><tr><th>Order No</th><th>Title</th><th>Client</th><?php if (!in_array($work['status'], ['finished', 'completed'], true)): ?><th></th><?php endif; ?></tr></thead>
            <tbody id="orderTableBody">
                <?php foreach ($linkedOrders as $row): ?>
                <tr>
                    <td><?= esc($row['order_number']) ?></td>
                    <td><?= esc($row['title'] ?? '-') ?></td>
                    <td><?= esc($row['client_name'] ?? '-') ?></td>
                    <?php if (!in_array($work['status'], ['finished', 'completed'], true)): ?>
                    <td><a href="<?= base_url('assembly-work/'.$work['id'].'/remove-order/'.$row['id']) ?>" class="btn btn-sm btn-outline-danger" data-ajax-delete="order" data-link-id="<?= (int)$row['id'] ?>" onclick="return confirm('Remove linked order?')">Del</a></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (!$linkedOrders): ?><tr><td colspan="<?= !in_array($work['status'], ['finished', 'completed'], true) ? 4 : 3 ?>" class="text-center text-muted">No linked orders</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Order-linked Issue Guidance</strong>
        <button class="btn btn-sm btn-outline-secondary" type="button" onclick="toggleBlock('requirementDetails')">View detailed requirement table</button>
    </div>
    <div class="card-body">
        <div class="text-muted small">
            Select a part batch in <strong>Issue Parts</strong>. The system will use linked order <strong>pending pcs</strong> and the <strong>selected batch weight / pc</strong> to suggest how much weight to issue now.
        </div>
    </div>
    <div id="requirementDetails" style="display:none">
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Part</th>
                        <th>Dept Group</th>
                        <th>Required Pcs</th>
                        <th>Approx Weight</th>
                        <th>Issued Pcs</th>
                        <th>Issued Weight</th>
                        <th>Pending Pcs</th>
                        <th>Approx Pending Wt</th>
                        <th>Stock</th>
                        <th>Suggested</th>
                        <th>Shortage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requirements as $row): ?>
                    <tr>
                        <td><?= esc($row['part_name']) ?><?php if (!empty($row['tamil_name'])): ?><br><small class="text-muted"><?= esc($row['tamil_name']) ?></small><?php endif; ?></td>
                        <td><?= esc($row['department_group_name'] ?? '-') ?></td>
                        <td class="text-end"><?= number_format((float)$row['required_pcs'], 4) ?></td>
                        <td class="text-end"><?= number_format((float)($row['required_weight_g_approx'] ?? $row['required_weight_g']), 4) ?></td>
                        <td class="text-end"><?= number_format((float)$row['issued_pcs'], 4) ?></td>
                        <td class="text-end"><?= number_format((float)$row['issued_weight_g'], 4) ?></td>
                        <td class="text-end <?= (float)$row['pending_pcs'] < 0 ? 'text-danger' : '' ?>"><?= number_format((float)$row['pending_pcs'], 4) ?></td>
                        <td class="text-end <?= (float)$row['pending_pcs'] < 0 ? 'text-danger' : '' ?>"><?= number_format((float)($row['pending_weight_g_approx'] ?? $row['pending_weight_g']), 4) ?></td>
                        <td class="text-end"><?= number_format((float)$row['stock_weight_g'], 4) ?></td>
                        <td class="text-end"><?= number_format((float)$row['suggested_issue_weight_g'], 4) ?></td>
                        <td class="text-end <?= (float)$row['shortage_weight_g'] > 0 ? 'text-danger fw-bold' : '' ?>"><?= number_format((float)$row['shortage_weight_g'], 4) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$requirements): ?><tr><td colspan="11" class="text-center text-muted">No order requirements found</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Issue Parts</strong>
        <?php if (!in_array($work['status'], ['finished', 'completed'], true)): ?>
        <button class="btn btn-outline-secondary btn-sm" onclick="toggleBlock('issueForm')">+ Add Issue</button>
        <?php endif; ?>
    </div>
    <?php if (!in_array($work['status'], ['finished', 'completed'], true)): ?>
    <div id="issueForm" style="display:none" class="card-body border-bottom bg-light">
        <div id="issueAjaxFeedback"></div>
        <form method="post" action="<?= base_url('assembly-work/'.$work['id'].'/add-issue') ?>" id="addIssueForm">
            <?= csrf_field() ?>
            <div class="row g-2">
                <div class="col-md-8">
                    <div class="row g-2 mb-2">
                        <div class="col-md-5">
                            <select id="issueStampFilter" class="form-select form-select-sm" onchange="applyIssueBatchFilters()">
                                <option value="">All Stamps</option>
                                <?php foreach ($stamps as $s): ?>
                                <option value="<?= esc($s['name']) ?>"><?= esc($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <input type="text" id="issueBatchSearch" class="form-control form-control-sm" placeholder="Search part / batch / stamp" oninput="applyIssueBatchFilters()">
                        </div>
                    </div>
                    <select name="part_batch_id" id="issueBatchSel" class="form-select form-select-sm" required onchange="fillIssueWeight(this)">
                        <option value="">-- Select part batch --</option>
                        <?php foreach ($partBatches as $pb): ?>
                        <option value="<?= $pb['id'] ?>"
                                data-part-id="<?= (int)$pb['part_id'] ?>"
                                data-weight="<?= (float)$pb['weight_in_stock_g'] ?>"
                                data-piece="<?= (float)$pb['piece_weight_g'] ?>"
                                data-part="<?= esc($pb['part_name']) ?>"
                                data-batch="<?= esc($pb['batch_number']) ?>"
                                data-stamp="<?= esc($pb['stamp_name'] ?? '') ?>">
                            <?= esc($pb['part_name']) ?> | Batch: <?= esc($pb['batch_number']) ?> | Stamp: <?= esc($pb['stamp_name'] ?? '-') ?> | Stock: <?= number_format((float)$pb['weight_in_stock_g'], 4) ?>g
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text" id="issueMeta">Part / batch / stamp will auto follow selected stock.</div>
                </div>
                <div class="col-md-2">
                    <input type="number" step="0.0001" name="weight_g" id="issueWeight" class="form-control form-control-sm" placeholder="Weight (g)" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Add</button>
                </div>
                <div class="col-12">
                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes (optional)">
                </div>
                <div class="col-12">
                    <div id="issueSuggestionBox" class="alert alert-info mb-0 d-none"></div>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>
    <div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0">
            <thead class="table-dark"><tr><th>Part</th><th>Batch</th><th>Stamp</th><th>Weight</th><th>Pcs</th><th>User</th><th>Issued At</th><?php if (!in_array($work['status'], ['finished', 'completed'], true)): ?><th></th><?php endif; ?></tr></thead>
            <tbody id="issueTableBody">
                <?php foreach ($issues as $row): ?>
                <tr>
                    <td><?= esc($row['part_name']) ?><?php if (!empty($row['part_tamil'])): ?><br><small class="text-muted"><?= esc($row['part_tamil']) ?></small><?php endif; ?></td>
                    <td><?= esc($row['batch_number']) ?></td>
                    <td><?= esc($row['stamp_name'] ?? '-') ?></td>
                    <td class="text-end"><?= number_format((float)$row['weight_g'], 4) ?></td>
                    <td class="text-end"><?= number_format((float)$row['pcs'], 4) ?></td>
                    <td><?= esc($row['created_by_username'] ?? '-') ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['issued_at'])) ?></td>
                    <?php if (!in_array($work['status'], ['finished', 'completed'], true)): ?>
                    <td class="text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-primary"
                                onclick="openIssueEditModal(<?= (int)$row['id'] ?>, '<?= esc($row['part_name'], 'js') ?>', '<?= esc($row['batch_number'], 'js') ?>', '<?= esc($row['stamp_name'] ?? '-', 'js') ?>', '<?= number_format((float)$row['weight_g'], 4, '.', '') ?>', '<?= esc($row['notes'] ?? '', 'js') ?>', '<?= number_format((float)($row['current_stock_g'] ?? 0), 4, '.', '') ?>')">Edit</button>
                        <a href="<?= base_url('assembly-work/issue/delete/'.$row['id']) ?>" class="btn btn-sm btn-outline-danger" data-ajax-delete="issue" data-issue-id="<?= (int)$row['id'] ?>" onclick="return confirm('Delete issue?')">Del</a>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (!$issues): ?><tr><td colspan="<?= !in_array($work['status'], ['finished', 'completed'], true) ? 8 : 7 ?>" class="text-center text-muted">No issues yet</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Receive Goods / Returns / By-products / Kacha</strong>
        <?php if ($work['status'] !== 'completed'): ?>
        <button class="btn btn-outline-secondary btn-sm" onclick="toggleBlock('receiveForm')">+ Add Receive</button>
        <?php endif; ?>
    </div>
    <?php if ($work['status'] !== 'completed'): ?>
    <div id="receiveForm" style="display:none" class="card-body border-bottom bg-light">
        <form method="post" action="<?= base_url('assembly-work/'.$work['id'].'/add-receive') ?>" id="addReceiveForm">
            <?= csrf_field() ?>
            <div class="row g-2">
                <div class="col-md-2">
                    <select name="receive_type" id="receiveType" class="form-select form-select-sm" onchange="toggleReceiveType()">
                        <option value="finished_good">Finished Good</option>
                        <option value="returned_part">Returned Part</option>
                        <option value="by_product">By-product</option>
                        <option value="kacha">Kacha</option>
                    </select>
                </div>
                <div class="col-md-3 d-none" id="finishedGoodsField">
                    <select name="finished_goods_id" class="form-select form-select-sm">
                        <option value="">-- Finished Good --</option>
                        <?php foreach ($finishedGoods as $fg): ?>
                        <option value="<?= $fg['id'] ?>"><?= esc($fg['name']) ?><?= !empty($fg['tamil_name']) ? ' - '.esc($fg['tamil_name']) : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3" id="partField">
                    <select name="part_id" class="form-select form-select-sm">
                        <option value="">-- Part --</option>
                        <?php foreach ($parts as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= esc($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-none" id="byproductField">
                    <select name="byproduct_type_id" class="form-select form-select-sm">
                        <option value="">-- By-product --</option>
                        <?php foreach ($byproducts as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= esc($b['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-none" id="kachaField"></div>
                <div class="col-md-2">
                    <input type="text" name="batch_number" class="form-control form-control-sm" placeholder="New batch no" required>
                </div>
                <div class="col-md-2">
                    <input type="number" step="0.0001" name="weight_g" id="recvWeight" class="form-control form-control-sm" placeholder="Weight (g)" required oninput="calcReceivePcs()">
                </div>
                <div class="col-md-2" id="pieceWeightField">
                    <input type="number" step="0.0001" name="piece_weight_g" id="recvPieceWeight" class="form-control form-control-sm" placeholder="Weight / pc" oninput="calcReceivePcs()">
                </div>
                <div class="col-md-1">
                    <input type="number" step="0.0001" name="pcs" id="recvPcs" class="form-control form-control-sm" placeholder="Pcs">
                </div>
                <div class="col-md-1" id="touchField">
                    <input type="number" step="0.0001" name="touch_pct" id="recvTouch" class="form-control form-control-sm" placeholder="Touch%">
                </div>
                <div class="col-md-2" id="stampField">
                    <select name="stamp_id" class="form-select form-select-sm">
                        <option value="">Stamp</option>
                        <?php foreach ($stamps as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-12">
                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes (optional)">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Add Receive</button>
                </div>
            </div>
        </form>
    </div>
    <?php endif; ?>
    <div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0">
            <thead class="table-dark"><tr><th>Type</th><th>Item</th><th>Batch</th><th>Stamp</th><th>Weight</th><th>Pcs</th><th>User</th><th>Received At</th><?php if ($work['status'] !== 'completed' && !in_array($work['status'], ['finished'], true)): ?><th></th><?php endif; ?></tr></thead>
            <tbody id="receiveTableBody">
                <?php foreach ($receives as $row): ?>
                <tr>
                    <td><?= ucwords(str_replace('_', ' ', $row['receive_type'])) ?></td>
                    <td>
                        <?php
                            if ($row['receive_type'] === 'by_product') {
                                echo esc($row['byproduct_name'] ?? '-');
                            } elseif ($row['receive_type'] === 'kacha') {
                                echo esc($row['kacha_name'] ?? '-');
                            } elseif ($row['receive_type'] === 'finished_good') {
                                echo esc($row['finished_goods_name'] ?? '-');
                                if (!empty($row['finished_goods_tamil'])) {
                                    echo '<br><small class="text-muted">' . esc($row['finished_goods_tamil']) . '</small>';
                                }
                            } else {
                                echo esc($row['part_name'] ?? '-');
                            }
                        ?>
                    </td>
                    <td><?= esc($row['batch_number']) ?></td>
                    <td><?= esc($row['stamp_name'] ?? '-') ?></td>
                    <td class="text-end"><?= number_format((float)$row['weight_g'], 4) ?></td>
                    <td class="text-end"><?= number_format((float)($row['pcs'] ?? 0), 4) ?></td>
                    <td><?= esc($row['created_by_username'] ?? '-') ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['received_at'])) ?></td>
                    <?php if ($work['status'] !== 'completed' && !in_array($work['status'], ['finished'], true)): ?>
                    <td class="text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-primary"
                                onclick="openReceiveEditModal(
                                    <?= (int)$row['id'] ?>,
                                    '<?= esc($row['receive_type'], 'js') ?>',
                                    '<?= esc($row['batch_number'], 'js') ?>',
                                    '<?= number_format((float)$row['weight_g'], 4, '.', '') ?>',
                                    '<?= number_format((float)($row['piece_weight_g'] ?? 0), 4, '.', '') ?>',
                                    '<?= number_format((float)($row['pcs'] ?? 0), 4, '.', '') ?>',
                                    '<?= number_format((float)($row['touch_pct'] ?? 0), 4, '.', '') ?>',
                                    '<?= esc($row['notes'] ?? '', 'js') ?>',
                                    '<?= (int)($row['part_id'] ?? 0) ?>',
                                    '<?= (int)($row['finished_goods_id'] ?? 0) ?>',
                                    '<?= (int)($row['byproduct_type_id'] ?? 0) ?>',
                                    '<?= (int)($row['stamp_id'] ?? 0) ?>'
                                )">Edit</button>
                        <a href="<?= base_url('assembly-work/receive/delete/'.$row['id']) ?>" class="btn btn-sm btn-outline-danger" data-ajax-delete="receive" data-recv-id="<?= (int)$row['id'] ?>" onclick="return confirm('Delete receive?')">Del</a>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (!$receives): ?><tr><td colspan="<?= $work['status'] !== 'completed' && !in_array($work['status'], ['finished'], true) ? 9 : 8 ?>" class="text-center text-muted">No receive rows yet</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3" id="summary">
    <div class="card-header"><strong>Department Group Summary</strong></div>
    <div class="card-body p-0">
        <form method="post" action="<?= base_url('assembly-work/'.$work['id'].'/save-summary') ?>" id="saveSummaryForm">
            <?= csrf_field() ?>
            <table class="table table-sm table-bordered mb-0">
                <thead class="table-dark"><tr><th>Department Group</th><th>Issue Weight</th><th>Issue Touch</th><th>Issue Fine</th><th>Receive Weight</th><th>Receive Touch</th><th>Receive Fine</th><th>Difference</th></tr></thead>
                <tbody>
                    <?php foreach ($departmentSummary as $index => $row): ?>
                    <tr>
                        <td>
                            <?= esc($row['department_group_name']) ?>
                            <input type="hidden" name="group_id[]" value="<?= $row['department_group_id'] ?>">
                        </td>
                        <td class="text-end"><?= number_format((float)$row['issue_weight_g'], 4) ?></td>
                        <td><input type="number" step="0.0001" name="issue_touch_pct[]" class="form-control form-control-sm text-end" value="<?= number_format((float)$row['issue_touch_pct'], 4, '.', '') ?>"></td>
                        <td class="text-end"><?= number_format((float)$row['issue_fine_g'], 4) ?></td>
                        <td class="text-end"><?= number_format((float)$row['receive_weight_g'], 4) ?></td>
                        <td><input type="number" step="0.0001" name="receive_touch_pct[]" class="form-control form-control-sm text-end" value="<?= number_format((float)$row['receive_touch_pct'], 4, '.', '') ?>"></td>
                        <td class="text-end"><?= number_format((float)$row['receive_fine_g'], 4) ?></td>
                        <td class="text-end fw-bold <?= (float)$row['difference_fine_g'] !== 0.0 ? 'text-danger' : '' ?>"><?= number_format((float)$row['difference_fine_g'], 4) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$departmentSummary): ?><tr><td colspan="8" class="text-center text-muted">No department-group totals yet</td></tr><?php endif; ?>
                </tbody>
            </table>
            <?php if ($departmentSummary): ?>
            <div class="p-3 border-top bg-light">
                <button type="submit" class="btn btn-primary btn-sm">Save Summary Touch</button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header"><strong>Making Charge</strong></div>
    <div class="card-body">
        <?php if ($work['status'] !== 'finished' && $work['status'] !== 'completed'): ?>
            <div class="alert alert-warning mb-0">Making charge can be entered only after the work is marked finished.</div>
        <?php else: ?>
            <form method="post" action="<?= base_url('assembly-work/'.$work['id'].'/save-making-charge') ?>" class="row g-3 align-items-end">
                <?= csrf_field() ?>
                <div class="col-md-3">
                    <label class="form-label">Finished Goods Weight</label>
                    <input type="text" class="form-control" value="<?= number_format($totalFinishedWeight, 4) ?> g" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Finished Goods Pcs</label>
                    <input type="text" class="form-control" value="<?= number_format($totalFinishedPcs, 4) ?>" readonly>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Making Charge Cash</label>
                    <input type="number" step="0.01" name="making_charge_cash" class="form-control" value="<?= number_format((float)($work['making_charge_cash'] ?? 0), 2, '.', '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Making Charge Fine</label>
                    <input type="number" step="0.0001" name="making_charge_fine" class="form-control" value="<?= number_format((float)($work['making_charge_fine'] ?? 0), 4, '.', '') ?>">
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Save Making Charge</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<div class="modal fade" id="editIssueModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" id="editIssueForm" class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title">Edit Issue</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Part</label><input type="text" id="editIssuePart" class="form-control" readonly></div>
        <div class="mb-2"><label class="form-label">Batch</label><input type="text" id="editIssueBatch" class="form-control" readonly></div>
        <div class="mb-2"><label class="form-label">Stamp</label><input type="text" id="editIssueStamp" class="form-control" readonly></div>
        <div class="mb-2"><label class="form-label">Available Stock</label><input type="text" id="editIssueAvailableStock" class="form-control" readonly></div>
        <div class="mb-2"><label class="form-label">Weight (g)</label><input type="number" step="0.0001" name="weight_g" id="editIssueWeight" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Notes</label><input type="text" name="notes" id="editIssueNotes" class="form-control"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="editReceiveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" id="editReceiveForm" class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title">Edit Receive</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="editReceiveType">
        <div class="mb-2"><label class="form-label">Batch / Lot No</label><input type="text" name="batch_number" id="editReceiveBatch" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Weight (g)</label><input type="number" step="0.0001" name="weight_g" id="editReceiveWeight" class="form-control" required></div>
        <div class="mb-2 d-none" id="editReceiveFinishedGoodsWrap"><label class="form-label">Finished Good</label><select name="finished_goods_id" id="editReceiveFinishedGoods" class="form-select"><?php foreach ($finishedGoods as $fg): ?><option value="<?= $fg['id'] ?>"><?= esc($fg['name']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-2 d-none" id="editReceivePartWrap"><label class="form-label">Part</label><select name="part_id" id="editReceivePart" class="form-select"><?php foreach ($parts as $p): ?><option value="<?= $p['id'] ?>"><?= esc($p['name']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-2 d-none" id="editReceiveByproductWrap"><label class="form-label">By-product</label><select name="byproduct_type_id" id="editReceiveByproduct" class="form-select"><?php foreach ($byproducts as $b): ?><option value="<?= $b['id'] ?>"><?= esc($b['name']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-2 d-none" id="editReceiveStampWrap"><label class="form-label">Stamp</label><select name="stamp_id" id="editReceiveStamp" class="form-select"><option value="">Stamp</option><?php foreach ($stamps as $s): ?><option value="<?= $s['id'] ?>"><?= esc($s['name']) ?></option><?php endforeach; ?></select></div>
        <div class="mb-2 d-none" id="editReceivePieceWeightWrap"><label class="form-label">Weight / pc</label><input type="number" step="0.0001" name="piece_weight_g" id="editReceivePieceWeight" class="form-control"></div>
        <div class="mb-2 d-none" id="editReceiveTouchWrap"><label class="form-label">Touch %</label><input type="number" step="0.0001" name="touch_pct" id="editReceiveTouch" class="form-control"></div>
        <div class="mb-2"><label class="form-label">Notes</label><input type="text" name="notes" id="editReceiveNotes" class="form-control"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>
<script>
var CSRF_NAME = '<?= csrf_token() ?>';
var CSRF_HASH = '<?= csrf_hash() ?>';
var BASE_URL  = '<?= rtrim(base_url(), "/") ?>/';
var WORK_ID   = <?= (int)$work['id'] ?>;
var assemblyRequirements = <?= json_encode(array_values(array_map(static function ($row) {
    return [
        'part_id' => (int)($row['part_id'] ?? 0),
        'part_name' => $row['part_name'] ?? '',
        'required_pcs' => (float)($row['required_pcs'] ?? 0),
        'required_weight_g' => (float)($row['required_weight_g'] ?? 0),
        'issued_pcs' => (float)($row['issued_pcs'] ?? 0),
        'issued_weight_g' => (float)($row['issued_weight_g'] ?? 0),
        'pending_pcs' => (float)($row['pending_pcs'] ?? 0),
        'pending_weight_g' => (float)($row['pending_weight_g'] ?? 0),
        'required_weight_g_approx' => (float)($row['required_weight_g_approx'] ?? 0),
        'pending_weight_g_approx' => (float)($row['pending_weight_g_approx'] ?? 0),
        'stock_weight_g' => (float)($row['stock_weight_g'] ?? 0),
        'suggested_issue_weight_g' => (float)($row['suggested_issue_weight_g'] ?? 0),
        'shortage_weight_g' => (float)($row['shortage_weight_g'] ?? 0),
    ];
}, $requirements)), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

/* ── Helpers ── */
function toggleBlock(id) {
    var el = document.getElementById(id);
    el.style.display = el.style.display === 'none' || el.style.display === '' ? 'block' : 'none';
}
function escHtml(str) { var d = document.createElement('div'); d.appendChild(document.createTextNode(str || '')); return d.innerHTML; }
function escAttr(str) { return (str || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"'); }
function formatNum(val, decimals) { return parseFloat(val || 0).toFixed(decimals || 4); }

function showFeedback(containerId, msg, type) {
    var c = document.getElementById(containerId);
    if (!c) return;
    c.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible fade show py-1 px-2 mb-2 small">' +
        escHtml(msg) + '<button type="button" class="btn-close btn-close-sm p-1" data-bs-dismiss="alert"></button></div>';
    if (type === 'success') setTimeout(function() { c.innerHTML = ''; }, 3000);
}
function showGlobalFeedback(msg, type) { showFeedback('globalFeedback', msg, type); }

function ajaxPost(url, fd) {
    fd.set(CSRF_NAME, CSRF_HASH);
    return fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(res) { if (res.csrf_hash) CSRF_HASH = res.csrf_hash; return res; });
}
function ajaxGet(url) {
    return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(res) { if (res.csrf_hash) CSRF_HASH = res.csrf_hash; return res; });
}

/* ── Issue Batch Filters ── */
function applyIssueBatchFilters() {
    var select = document.getElementById('issueBatchSel');
    var searchInput = document.getElementById('issueBatchSearch');
    var stampFilter = document.getElementById('issueStampFilter');
    var term = ((searchInput && searchInput.value) || '').toLowerCase().trim();
    var stamp = ((stampFilter && stampFilter.value) || '').toLowerCase().trim();
    Array.from(select.options).forEach(function(option, index) {
        if (index === 0) { option.hidden = false; return; }
        var text = (option.textContent || '').toLowerCase();
        var optionStamp = (option.dataset.stamp || '').toLowerCase().trim();
        option.hidden = !(( term === '' || text.indexOf(term) !== -1) && (stamp === '' || optionStamp === stamp));
    });
    if (select.selectedIndex > 0 && select.options[select.selectedIndex].hidden) {
        select.value = '';
        var sb = document.getElementById('issueSuggestionBox');
        if (sb) { sb.classList.add('d-none'); sb.innerHTML = ''; }
        document.getElementById('issueWeight').value = '';
        document.getElementById('issueMeta').textContent = 'Part / batch / stamp will auto follow selected stock.';
    }
}

/* ── Fill Issue Weight Suggestion ── */
function fillIssueWeight(select) {
    var option = select.options[select.selectedIndex];
    var suggestionBox = document.getElementById('issueSuggestionBox');
    if (!option || !option.value) { suggestionBox.classList.add('d-none'); suggestionBox.innerHTML = ''; return; }
    var partId = parseInt(option.dataset.partId || '0', 10);
    var stockWeight = parseFloat(option.dataset.weight || '0');
    var batchPieceWeight = parseFloat(option.dataset.piece || '0');
    var partName = option.dataset.part || '';
    var batch = option.dataset.batch || '';
    var stamp = option.dataset.stamp || '-';
    var suggested = 0;
    var requirement = assemblyRequirements.find(function(item) { return item.part_id === partId; });

    if (requirement) {
        var pendingPcs = requirement.pending_pcs;
        var availablePcsInBatch = 0, suggestedIssuePcs = 0, remainingPcsAfterThisBatch = 0;
        if (batchPieceWeight > 0) {
            availablePcsInBatch = stockWeight / batchPieceWeight;
            suggestedIssuePcs = Math.min(Math.max(pendingPcs, 0), availablePcsInBatch);
            suggested = suggestedIssuePcs * batchPieceWeight;
            remainingPcsAfterThisBatch = Math.max(0, pendingPcs - availablePcsInBatch);
        }
        if (batchPieceWeight <= 0) {
            suggestionBox.innerHTML = '<div><strong>' + partName + '</strong></div><div class="small text-danger mt-1">Selected batch has no weight / pc. System cannot suggest correct issue weight.</div><div class="mt-1 small text-muted">Pending pcs: ' + pendingPcs.toFixed(4) + ' | Batch: ' + batch + ' | Stamp: ' + stamp + '</div>';
            document.getElementById('issueWeight').value = '';
            document.getElementById('issueMeta').textContent = 'Part: ' + partName + ' | Batch: ' + batch + ' | Stamp: ' + stamp + ' | Available: ' + stockWeight.toFixed(4) + 'g';
            suggestionBox.classList.remove('d-none');
            return;
        }
        var shortageClass = remainingPcsAfterThisBatch > 0 ? 'text-danger fw-bold' : 'text-success fw-bold';
        var pendingClass = pendingPcs > 0 ? 'fw-bold' : 'text-success fw-bold';
        suggestionBox.innerHTML =
            '<div><strong>' + partName + '</strong> batch-wise issue suggestion</div>' +
            '<div class="row g-2 mt-1">' +
                '<div class="col-md-2"><small class="text-muted d-block">Required Pcs</small><strong>' + requirement.required_pcs.toFixed(4) + '</strong></div>' +
                '<div class="col-md-2"><small class="text-muted d-block">Issued Pcs</small><strong>' + requirement.issued_pcs.toFixed(4) + '</strong></div>' +
                '<div class="col-md-2"><small class="text-muted d-block">Pending Pcs</small><strong class="' + pendingClass + '">' + pendingPcs.toFixed(4) + '</strong></div>' +
                '<div class="col-md-2"><small class="text-muted d-block">Batch Wt / Pc</small><strong>' + batchPieceWeight.toFixed(4) + ' g</strong></div>' +
                '<div class="col-md-2"><small class="text-muted d-block">Batch Available Pcs</small><strong>' + availablePcsInBatch.toFixed(4) + '</strong></div>' +
                '<div class="col-md-2"><small class="text-muted d-block">Suggest Pcs</small><strong>' + suggestedIssuePcs.toFixed(4) + '</strong></div>' +
            '</div>' +
            '<div class="row g-2 mt-1">' +
                '<div class="col-md-3"><small class="text-muted d-block">Suggest Weight</small><strong>' + suggested.toFixed(4) + ' g</strong></div>' +
                '<div class="col-md-3"><small class="text-muted d-block">Remaining After This Batch</small><strong class="' + shortageClass + '">' + remainingPcsAfterThisBatch.toFixed(4) + ' pcs</strong></div>' +
                '<div class="col-md-3"><small class="text-muted d-block">Approx Ref Weight</small><strong>' + requirement.pending_weight_g_approx.toFixed(4) + ' g</strong></div>' +
                '<div class="col-md-3"><small class="text-muted d-block">Batch Available Weight</small><strong>' + stockWeight.toFixed(4) + ' g</strong></div>' +
            '</div>' +
            '<div class="mt-1 small text-muted">Batch: ' + batch + ' | Stamp: ' + stamp + ' | Suggestion is based on pending pcs and selected batch weight / pc.</div>';
        suggestionBox.classList.remove('d-none');
    } else {
        suggestionBox.innerHTML = '<div><strong>' + partName + '</strong></div><div class="small text-muted mt-1">No linked order requirement found for this part. You can still issue manually.</div><div class="mt-1 small text-muted">Batch: ' + batch + ' | Stamp: ' + stamp + ' | Available: ' + stockWeight.toFixed(4) + ' g</div>';
        suggestionBox.classList.remove('d-none');
    }
    document.getElementById('issueWeight').value = suggested > 0 ? suggested.toFixed(4) : '';
    document.getElementById('issueMeta').textContent = 'Part: ' + partName + ' | Batch: ' + batch + ' | Stamp: ' + stamp + ' | Available: ' + stockWeight.toFixed(4) + 'g';
}

/* ── Receive Type Toggle ── */
function toggleReceiveType() {
    var type = document.getElementById('receiveType').value;
    document.getElementById('finishedGoodsField').classList.toggle('d-none', type !== 'finished_good');
    document.getElementById('partField').classList.toggle('d-none', type !== 'returned_part');
    document.getElementById('pieceWeightField').classList.toggle('d-none', type !== 'returned_part');
    document.getElementById('recvPcs').parentElement.classList.toggle('d-none', type !== 'returned_part');
    document.getElementById('byproductField').classList.toggle('d-none', type !== 'by_product');
    document.getElementById('kachaField').classList.toggle('d-none', type !== 'kacha');
    document.getElementById('stampField').classList.toggle('d-none', !(type === 'finished_good' || type === 'returned_part'));
    document.getElementById('touchField').classList.toggle('d-none', type !== 'kacha');
    if (type !== 'returned_part') { document.getElementById('recvPieceWeight').value = ''; document.getElementById('recvPcs').value = ''; }
    if (type !== 'kacha') { document.getElementById('recvTouch').value = ''; }
}
function calcReceivePcs() {
    var w = parseFloat(document.getElementById('recvWeight').value || '0');
    var pw = parseFloat(document.getElementById('recvPieceWeight').value || '0');
    if (w > 0 && pw > 0) document.getElementById('recvPcs').value = (w / pw).toFixed(4);
}

/* ── Issue Table Helpers ── */
function updateBatchStock(partBatchId, newStockG) {
    var select = document.getElementById('issueBatchSel');
    if (!select) return;
    for (var i = 1; i < select.options.length; i++) {
        if (select.options[i].value == partBatchId) {
            select.options[i].dataset.weight = newStockG;
            select.options[i].textContent = select.options[i].textContent.replace(/Stock:\s*[\d,.]+g/, 'Stock: ' + formatNum(newStockG) + 'g');
            if (newStockG <= 0) { select.options[i].hidden = true; if (select.value == partBatchId) select.value = ''; }
            else { select.options[i].hidden = false; }
            break;
        }
    }
}
function updateIssueSummary(totalWeight, totalPcs) {
    var wEl = document.getElementById('summaryIssuedWeight');
    var pEl = document.getElementById('summaryIssuedPcs');
    if (wEl) wEl.textContent = formatNum(totalWeight) + ' g';
    if (pEl) pEl.innerHTML = '&asymp; ' + formatNum(totalPcs) + ' pcs';
}
function updateRequirement(req) {
    if (!req) return;
    for (var i = 0; i < assemblyRequirements.length; i++) {
        if (assemblyRequirements[i].part_id === req.part_id) { assemblyRequirements[i] = req; return; }
    }
}

function buildIssueRowHtml(issue, showActions) {
    var tamilHtml = issue.part_tamil ? '<br><small class="text-muted">' + escHtml(issue.part_tamil) + '</small>' : '';
    var html = '<td>' + escHtml(issue.part_name) + tamilHtml + '</td>' +
        '<td>' + escHtml(issue.batch_number) + '</td>' +
        '<td>' + escHtml(issue.stamp_name) + '</td>' +
        '<td class="text-end">' + formatNum(issue.weight_g) + '</td>' +
        '<td class="text-end">' + formatNum(issue.pcs) + '</td>' +
        '<td>' + escHtml(issue.created_by_username) + '</td>' +
        '<td>' + escHtml(issue.issued_at) + '</td>';
    if (showActions) {
        html += '<td class="text-nowrap">' +
            '<button type="button" class="btn btn-sm btn-outline-primary" onclick="openIssueEditModal(' +
                issue.id + ', \'' + escAttr(issue.part_name) + '\', \'' + escAttr(issue.batch_number) + '\', \'' +
                escAttr(issue.stamp_name) + '\', \'' + formatNum(issue.weight_g) + '\', \'' +
                escAttr(issue.notes) + '\', \'' + formatNum(issue.current_stock_g) + '\')">Edit</button> ' +
            '<a href="' + BASE_URL + 'assembly-work/issue/delete/' + issue.id + '" class="btn btn-sm btn-outline-danger" data-ajax-delete="issue" data-issue-id="' + issue.id + '" onclick="return confirm(\'Delete issue?\')">Del</a>' +
            '</td>';
    }
    return html;
}

function prependIssueRow(issue) {
    var tbody = document.getElementById('issueTableBody');
    if (!tbody) return;
    var placeholder = tbody.querySelector('tr td.text-center.text-muted');
    if (placeholder) placeholder.parentElement.remove();
    var tr = document.createElement('tr');
    tr.setAttribute('data-issue-id', issue.id);
    tr.innerHTML = buildIssueRowHtml(issue, true);
    tbody.insertBefore(tr, tbody.firstChild);
}

/* ── Edit Issue Modal ── */
function openIssueEditModal(id, part, batch, stamp, weight, notes, currentStock) {
    document.getElementById('editIssueForm').action = BASE_URL + 'assembly-work/issue/update/' + id;
    document.getElementById('editIssuePart').value = part;
    document.getElementById('editIssueBatch').value = batch;
    document.getElementById('editIssueStamp').value = stamp;
    var oldWeight = parseFloat(weight || '0');
    var availableStock = parseFloat(currentStock || '0') + oldWeight;
    document.getElementById('editIssueAvailableStock').value = availableStock.toFixed(4) + ' g';
    document.getElementById('editIssueWeight').value = weight;
    document.getElementById('editIssueNotes').value = notes;
    new bootstrap.Modal(document.getElementById('editIssueModal')).show();
}

/* ── Edit Receive Modal ── */
function openReceiveEditModal(id, type, batchNumber, weight, pieceWeight, pcs, touch, notes, partId, finishedGoodsId, byproductTypeId, stampId) {
    document.getElementById('editReceiveForm').action = BASE_URL + 'assembly-work/receive/update/' + id;
    document.getElementById('editReceiveType').value = type;
    document.getElementById('editReceiveBatch').value = batchNumber;
    document.getElementById('editReceiveWeight').value = weight;
    document.getElementById('editReceivePieceWeight').value = pieceWeight;
    document.getElementById('editReceiveTouch').value = touch;
    document.getElementById('editReceiveNotes').value = notes;
    document.getElementById('editReceivePart').value = partId || '';
    document.getElementById('editReceiveFinishedGoods').value = finishedGoodsId || '';
    document.getElementById('editReceiveByproduct').value = byproductTypeId || '';
    document.getElementById('editReceiveStamp').value = stampId || '';
    document.getElementById('editReceiveFinishedGoodsWrap').classList.toggle('d-none', type !== 'finished_good');
    document.getElementById('editReceivePartWrap').classList.toggle('d-none', type !== 'returned_part');
    document.getElementById('editReceiveByproductWrap').classList.toggle('d-none', type !== 'by_product');
    document.getElementById('editReceiveStampWrap').classList.toggle('d-none', !(type === 'finished_good' || type === 'returned_part'));
    document.getElementById('editReceivePieceWeightWrap').classList.toggle('d-none', type !== 'returned_part');
    document.getElementById('editReceiveTouchWrap').classList.toggle('d-none', type !== 'kacha');
    new bootstrap.Modal(document.getElementById('editReceiveModal')).show();
}

/* ── INIT ── */
toggleReceiveType();
(function initIssueStampFilter() {
    var storageKey = 'assembly_work_issue_stamp_filter';
    var stampFilter = document.getElementById('issueStampFilter');
    if (!stampFilter) return;
    var saved = window.localStorage ? localStorage.getItem(storageKey) : '';
    if (saved) stampFilter.value = saved;
    stampFilter.addEventListener('change', function() { if (window.localStorage) localStorage.setItem(storageKey, stampFilter.value || ''); });
    applyIssueBatchFilters();
})();

/* ══════════════════════════════════════════════
   AJAX: Add Issue (already existed, cleaned up)
   ══════════════════════════════════════════════ */
(function() {
    var form = document.getElementById('addIssueForm');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = form.querySelector('button[type="submit"]');
        btn.disabled = true; btn.textContent = 'Adding...';
        ajaxPost(form.action, new FormData(form)).then(function(res) {
            if (!res.success) { showFeedback('issueAjaxFeedback', res.error || 'Failed', 'danger'); btn.disabled = false; btn.textContent = 'Add'; return; }
            prependIssueRow(res.issue);
            updateBatchStock(res.part_batch_id, res.new_batch_stock_g);
            updateIssueSummary(res.totalIssuedWeight, res.totalIssuedPcs);
            if (res.updatedRequirement) updateRequirement(res.updatedRequirement);
            document.getElementById('issueWeight').value = '';
            var ni = form.querySelector('input[name="notes"]'); if (ni) ni.value = '';
            fillIssueWeight(document.getElementById('issueBatchSel'));
            showFeedback('issueAjaxFeedback', 'Issue added', 'success');
            btn.disabled = false; btn.textContent = 'Add';
            document.getElementById('issueBatchSel').focus();
        }).catch(function() { showFeedback('issueAjaxFeedback', 'Network error', 'danger'); btn.disabled = false; btn.textContent = 'Add'; });
    });
})();

/* ══════════════════════════════════════════════
   AJAX: Delete Issue / Delete Receive / Remove Order
   ══════════════════════════════════════════════ */
document.addEventListener('click', function(e) {
    var link = e.target.closest('[data-ajax-delete]');
    if (!link) return;
    e.preventDefault();
    var type = link.dataset.ajaxDelete;
    var fd = new FormData();

    if (type === 'issue') {
        ajaxPost(link.href, fd).then(function(res) {
            if (!res.success) { showGlobalFeedback(res.error || 'Failed', 'danger'); return; }
            var row = link.closest('tr'); if (row) row.remove();
            updateBatchStock(res.part_batch_id, res.new_batch_stock_g);
            updateIssueSummary(res.totalIssuedWeight, res.totalIssuedPcs);
            showGlobalFeedback('Issue deleted', 'success');
        }).catch(function() { showGlobalFeedback('Network error', 'danger'); });
    }
    else if (type === 'receive') {
        ajaxPost(link.href, fd).then(function(res) {
            if (!res.success) { showGlobalFeedback(res.error || 'Failed', 'danger'); return; }
            var row = link.closest('tr'); if (row) row.remove();
            showGlobalFeedback('Receive deleted', 'success');
        }).catch(function() { showGlobalFeedback('Network error', 'danger'); });
    }
    else if (type === 'order') {
        ajaxPost(link.href, fd).then(function(res) {
            if (!res.success) { showGlobalFeedback(res.error || 'Failed', 'danger'); return; }
            var row = link.closest('tr'); if (row) row.remove();
            showGlobalFeedback('Order removed', 'success');
        }).catch(function() { showGlobalFeedback('Network error', 'danger'); });
    }
});

/* ══════════════════════════════════════════════
   AJAX: Edit Issue (modal form)
   ══════════════════════════════════════════════ */
(function() {
    var form = document.getElementById('editIssueForm');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = form.querySelector('button[type="submit"]');
        btn.disabled = true; btn.textContent = 'Saving...';
        ajaxPost(form.action, new FormData(form)).then(function(res) {
            if (!res.success) { alert(res.error || 'Failed'); btn.disabled = false; btn.textContent = 'Save'; return; }
            updateBatchStock(res.part_batch_id, res.new_batch_stock_g);
            updateIssueSummary(res.totalIssuedWeight, res.totalIssuedPcs);
            // Update row in table
            var issue = res.issue;
            var tbody = document.getElementById('issueTableBody');
            if (tbody && issue) {
                var rows = tbody.querySelectorAll('tr[data-issue-id="' + issue.id + '"]');
                if (rows.length === 0) {
                    // Fallback: find row by matching, or just reload table section
                    rows = tbody.querySelectorAll('tr');
                }
                // For simplicity, refresh page data for this row - but page stays in position
            }
            bootstrap.Modal.getInstance(document.getElementById('editIssueModal')).hide();
            showGlobalFeedback('Issue updated', 'success');
            btn.disabled = false; btn.textContent = 'Save';
            // Reload page keeping scroll position
            var scrollY = window.scrollY;
            sessionStorage.setItem('aw_scroll', scrollY);
            location.reload();
        }).catch(function() { alert('Network error'); btn.disabled = false; btn.textContent = 'Save'; });
    });
})();

/* ══════════════════════════════════════════════
   AJAX: Edit Receive (modal form)
   ══════════════════════════════════════════════ */
(function() {
    var form = document.getElementById('editReceiveForm');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = form.querySelector('button[type="submit"]');
        btn.disabled = true; btn.textContent = 'Saving...';
        ajaxPost(form.action, new FormData(form)).then(function(res) {
            if (!res.success) { alert(res.error || 'Failed'); btn.disabled = false; btn.textContent = 'Save'; return; }
            bootstrap.Modal.getInstance(document.getElementById('editReceiveModal')).hide();
            showGlobalFeedback('Receive updated', 'success');
            btn.disabled = false; btn.textContent = 'Save';
            var scrollY = window.scrollY;
            sessionStorage.setItem('aw_scroll', scrollY);
            location.reload();
        }).catch(function() { alert('Network error'); btn.disabled = false; btn.textContent = 'Save'; });
    });
})();

/* ══════════════════════════════════════════════
   AJAX: Add Order
   ══════════════════════════════════════════════ */
(function() {
    var form = document.getElementById('addOrderForm');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = form.querySelector('button[type="submit"]');
        btn.disabled = true; btn.textContent = 'Adding...';
        ajaxPost(form.action, new FormData(form)).then(function(res) {
            if (!res.success) { showGlobalFeedback(res.error || 'Failed', 'danger'); btn.disabled = false; btn.textContent = 'Add'; return; }
            var o = res.order;
            var tbody = document.getElementById('orderTableBody');
            if (tbody && o) {
                var placeholder = tbody.querySelector('tr td.text-center.text-muted');
                if (placeholder) placeholder.parentElement.remove();
                var tr = document.createElement('tr');
                tr.innerHTML = '<td>' + escHtml(o.order_number || '') + '</td><td>' + escHtml(o.title || '-') + '</td><td>' + escHtml(o.client_name || '-') + '</td>' +
                    '<td><a href="' + BASE_URL + 'assembly-work/' + WORK_ID + '/remove-order/' + res.link_id + '" class="btn btn-sm btn-outline-danger" data-ajax-delete="order" data-link-id="' + res.link_id + '" onclick="return confirm(\'Remove linked order?\')">Del</a></td>';
                tbody.appendChild(tr);
            }
            // Remove from dropdown
            var sel = form.querySelector('select[name="order_id"]');
            if (sel) { var opt = sel.querySelector('option[value="' + o.id + '"]'); if (opt) opt.remove(); sel.value = ''; }
            showGlobalFeedback('Order linked', 'success');
            btn.disabled = false; btn.textContent = 'Add';
        }).catch(function() { showGlobalFeedback('Network error', 'danger'); btn.disabled = false; btn.textContent = 'Add'; });
    });
})();

/* ══════════════════════════════════════════════
   AJAX: Add Receive
   ══════════════════════════════════════════════ */
(function() {
    var form = document.getElementById('addReceiveForm');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = form.querySelector('button[type="submit"]');
        btn.disabled = true; btn.textContent = 'Adding...';
        ajaxPost(form.action, new FormData(form)).then(function(res) {
            if (!res.success) { showGlobalFeedback(res.error || 'Failed', 'danger'); btn.disabled = false; btn.textContent = 'Add Receive'; return; }
            showGlobalFeedback('Receive added', 'success');
            btn.disabled = false; btn.textContent = 'Add Receive';
            // Reload keeping scroll
            sessionStorage.setItem('aw_scroll', window.scrollY);
            location.reload();
        }).catch(function() { showGlobalFeedback('Network error', 'danger'); btn.disabled = false; btn.textContent = 'Add Receive'; });
    });
})();

/* ══════════════════════════════════════════════
   AJAX: Save Summary
   ══════════════════════════════════════════════ */
(function() {
    var form = document.getElementById('saveSummaryForm');
    if (!form) return;
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = form.querySelector('button[type="submit"]');
        btn.disabled = true; btn.textContent = 'Saving...';
        ajaxPost(form.action, new FormData(form)).then(function(res) {
            if (!res.success) { showGlobalFeedback(res.error || 'Failed', 'danger'); btn.disabled = false; btn.textContent = 'Save Summary Touch'; return; }
            showGlobalFeedback('Summary saved', 'success');
            btn.disabled = false; btn.textContent = 'Save Summary Touch';
            // Reload keeping scroll to refresh computed fine values
            sessionStorage.setItem('aw_scroll', window.scrollY);
            location.reload();
        }).catch(function() { showGlobalFeedback('Network error', 'danger'); btn.disabled = false; btn.textContent = 'Save Summary Touch'; });
    });
})();

/* ══════════════════════════════════════════════
   Scroll Position Restore
   ══════════════════════════════════════════════ */
(function() {
    var saved = sessionStorage.getItem('aw_scroll');
    if (saved !== null) {
        window.scrollTo(0, parseInt(saved, 10));
        sessionStorage.removeItem('aw_scroll');
    }
})();
</script>
<?= $this->endSection() ?>