<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="mb-0"><?= esc($work['work_number']) ?></h5>
        <small class="text-muted">Karigar: <strong><?= esc($work['karigar_name']) ?></strong><?= !empty($work['dept_name']) ? ' ('.esc($work['dept_name']).')' : '' ?></small>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <span class="badge <?= $work['status'] === 'completed' ? 'bg-success' : ($work['status'] === 'finished' ? 'bg-info text-dark' : ($work['status'] === 'in_progress' ? 'bg-primary' : 'bg-warning text-dark')) ?> fs-6">
            <?= ucwords(str_replace('_', ' ', $work['status'])) ?>
        </span>
        <?php if (!in_array($work['status'], ['finished', 'completed'], true)): ?>
            <a href="<?= base_url('assembly-work/finish/'.$work['id']) ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Mark this work as finished?')">Mark Finished</a>
        <?php endif; ?>
        <?php if ($work['status'] === 'finished'): ?>
            <a href="<?= base_url('assembly-work/complete/'.$work['id']) ?>" class="btn btn-sm btn-success" onclick="return confirm('Complete this work?')">Complete</a>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Issued Weight</div>
                <div class="fs-5 fw-bold"><?= number_format($totalIssuedWeight, 4) ?> g</div>
                <div class="small text-muted">≈ <?= number_format($totalIssuedPcs, 4) ?> pcs</div>
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
        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addOrderForm">+ Link Order</button>
        <?php endif; ?>
    </div>
    <?php if (!in_array($work['status'], ['finished', 'completed'], true)): ?>
    <div id="addOrderForm" class="collapse border-bottom">
        <div class="card-body bg-light">
            <form method="post" action="<?= base_url('assembly-work/'.$work['id'].'/add-order') ?>" class="row g-2">
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
            <tbody>
                <?php foreach ($linkedOrders as $row): ?>
                <tr>
                    <td><?= esc($row['order_number']) ?></td>
                    <td><?= esc($row['title'] ?? '-') ?></td>
                    <td><?= esc($row['client_name'] ?? '-') ?></td>
                    <?php if (!in_array($work['status'], ['finished', 'completed'], true)): ?>
                    <td><a href="<?= base_url('assembly-work/'.$work['id'].'/remove-order/'.$row['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove linked order?')">Del</a></td>
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
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#requirementDetails">View detailed requirement table</button>
    </div>
    <div class="card-body">
        <div class="text-muted small">
            Select a part batch in <strong>Issue Parts</strong>. The system will use linked order <strong>pending pcs</strong> and the <strong>selected batch weight / pc</strong> to suggest how much weight to issue now.
        </div>
    </div>
    <div id="requirementDetails" class="collapse">
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
        <form method="post" action="<?= base_url('assembly-work/'.$work['id'].'/add-issue') ?>">
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
            <tbody>
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
                        <a href="<?= base_url('assembly-work/issue/delete/'.$row['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete issue?')">Del</a>
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
        <form method="post" action="<?= base_url('assembly-work/'.$work['id'].'/add-receive') ?>">
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
            <tbody>
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
                        <a href="<?= base_url('assembly-work/receive/delete/'.$row['id']) ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete receive?')">Del</a>
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
        <form method="post" action="<?= base_url('assembly-work/'.$work['id'].'/save-summary') ?>">
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

function toggleBlock(id) {
    var element = document.getElementById(id);
    element.style.display = element.style.display === 'none' || element.style.display === '' ? 'block' : 'none';
}

function openIssueEditModal(id, part, batch, stamp, weight, notes, currentStock) {
    document.getElementById('editIssueForm').action = '<?= base_url('assembly-work/issue/update') ?>/' + id;
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

function openReceiveEditModal(id, type, batchNumber, weight, pieceWeight, pcs, touch, notes, partId, finishedGoodsId, byproductTypeId, stampId) {
    document.getElementById('editReceiveForm').action = '<?= base_url('assembly-work/receive/update') ?>/' + id;
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

function applyIssueBatchFilters() {
    var select = document.getElementById('issueBatchSel');
    var searchInput = document.getElementById('issueBatchSearch');
    var stampFilter = document.getElementById('issueStampFilter');
    var term = ((searchInput && searchInput.value) || '').toLowerCase().trim();
    var stamp = ((stampFilter && stampFilter.value) || '').toLowerCase().trim();

    Array.from(select.options).forEach(function(option, index) {
        if (index === 0) {
            option.hidden = false;
            return;
        }

        var text = (option.textContent || '').toLowerCase();
        var optionStamp = (option.dataset.stamp || '').toLowerCase().trim();
        var matchesSearch = term === '' || text.indexOf(term) !== -1;
        var matchesStamp = stamp === '' || optionStamp === stamp;
        option.hidden = !(matchesSearch && matchesStamp);
    });

    if (select.selectedIndex > 0 && select.options[select.selectedIndex].hidden) {
        select.value = '';
        var suggestionBox = document.getElementById('issueSuggestionBox');
        if (suggestionBox) {
            suggestionBox.classList.add('d-none');
            suggestionBox.innerHTML = '';
        }
        document.getElementById('issueWeight').value = '';
        document.getElementById('issueMeta').textContent = 'Part / batch / stamp will auto follow selected stock.';
    }
}

function fillIssueWeight(select) {
    var option = select.options[select.selectedIndex];
    var suggestionBox = document.getElementById('issueSuggestionBox');
    if (!option || !option.value) {
        suggestionBox.classList.add('d-none');
        suggestionBox.innerHTML = '';
        return;
    }

    var partId = parseInt(option.dataset.partId || '0', 10);
    var stockWeight = parseFloat(option.dataset.weight || '0');
    var batchPieceWeight = parseFloat(option.dataset.piece || '0');
    var partName = option.dataset.part || '';
    var batch = option.dataset.batch || '';
    var stamp = option.dataset.stamp || '-';
    var suggested = 0;
    var requirement = assemblyRequirements.find(function(item) {
        return item.part_id === partId;
    });

    if (requirement) {
        var pendingPcs = requirement.pending_pcs;
        var availablePcsInBatch = 0;
        var suggestedIssuePcs = 0;
        var remainingPcsAfterThisBatch = 0;

        if (batchPieceWeight > 0) {
            availablePcsInBatch = stockWeight / batchPieceWeight;
            suggestedIssuePcs = Math.min(Math.max(pendingPcs, 0), availablePcsInBatch);
            suggested = suggestedIssuePcs * batchPieceWeight;
            remainingPcsAfterThisBatch = Math.max(0, pendingPcs - availablePcsInBatch);
        }

        if (batchPieceWeight <= 0) {
            suggestionBox.innerHTML =
                '<div><strong>' + partName + '</strong></div>' +
                '<div class="small text-danger mt-1">Selected batch has no weight / pc. System cannot suggest correct issue weight.</div>' +
                '<div class="mt-1 small text-muted">Pending pcs: ' + pendingPcs.toFixed(4) + ' | Batch: ' + batch + ' | Stamp: ' + stamp + '</div>';
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
        suggestionBox.innerHTML =
            '<div><strong>' + partName + '</strong></div>' +
            '<div class="small text-muted mt-1">No linked order requirement found for this part. You can still issue manually.</div>' +
            '<div class="mt-1 small text-muted">Batch: ' + batch + ' | Stamp: ' + stamp + ' | Available: ' + stockWeight.toFixed(4) + ' g</div>';
        suggestionBox.classList.remove('d-none');
    }

    document.getElementById('issueWeight').value = suggested > 0 ? suggested.toFixed(4) : '';
    document.getElementById('issueMeta').textContent = 'Part: ' + partName + ' | Batch: ' + batch + ' | Stamp: ' + stamp + ' | Available: ' + stockWeight.toFixed(4) + 'g';
}

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

    if (type !== 'returned_part') {
        document.getElementById('recvPieceWeight').value = '';
        document.getElementById('recvPcs').value = '';
    }
    if (type !== 'kacha') {
        document.getElementById('recvTouch').value = '';
    }
}

function calcReceivePcs() {
    var weight = parseFloat(document.getElementById('recvWeight').value || '0');
    var pieceWeight = parseFloat(document.getElementById('recvPieceWeight').value || '0');
    if (weight > 0 && pieceWeight > 0) {
        document.getElementById('recvPcs').value = (weight / pieceWeight).toFixed(4);
    }
}

toggleReceiveType();
(function initIssueStampFilter() {
    var storageKey = 'assembly_work_issue_stamp_filter';
    var stampFilter = document.getElementById('issueStampFilter');
    if (!stampFilter) {
        return;
    }

    var saved = window.localStorage ? localStorage.getItem(storageKey) : '';
    if (saved) {
        stampFilter.value = saved;
    }

    stampFilter.addEventListener('change', function() {
        if (!window.localStorage) {
            return;
        }
        localStorage.setItem(storageKey, stampFilter.value || '');
    });

    applyIssueBatchFilters();
})();
</script>
<?= $this->endSection() ?>