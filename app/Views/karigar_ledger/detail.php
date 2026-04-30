<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0"><?= esc($karigar['name']) ?></h5>
        <small class="text-muted"><?= esc($karigar['dept_name'] ?? '') ?></small>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= base_url('karigar-ledger/'.$karigar['id'].'/convert') ?>" class="btn btn-outline-secondary btn-sm">Fine &harr; Cash</a>
        <a href="<?= base_url('karigar-ledger') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<div class="row mb-3">
    <div class="col-auto"><div class="card border-danger"><div class="card-body py-2 px-3">
        <div class="text-muted" style="font-size:12px">Fine Balance (Karigar Owes)</div>
        <div class="fs-5 fw-bold text-danger"><?= number_format($fineBalance,4) ?> g</div>
    </div></div></div>
    <div class="col-auto"><div class="card border-success"><div class="card-body py-2 px-3">
        <div class="text-muted" style="font-size:12px">Cash Balance (Company Owes)</div>
        <div class="fs-5 fw-bold text-success">Rs <?= number_format($cashBalance,2) ?></div>
    </div></div></div>
</div>

<div class="table-responsive">
<table class="table table-sm table-bordered">
<thead class="table-dark"><tr>
    <th>Date</th><th>Source</th><th>Account</th><th>Direction</th><th>Amount</th><th>Fine Running (g)</th><th>Cash Running (Rs)</th><th>Narration</th>
</tr></thead>
<tbody>
<?php foreach ($ledger as $row): ?>
<tr>
    <td><?= date('d/m/Y', strtotime($row['posted_at'])) ?></td>
    <td>
        <?php if ($row['source_type'] === 'melt_job'): ?>
        <a href="<?= base_url('melt-jobs/view/'.$row['source_id']) ?>">MELT</a>
        <?php elseif ($row['source_id'] > 0): ?>
        <a href="<?= base_url('part-orders/view/'.$row['source_id']) ?>">PARTORD</a>
        <?php else: ?>Conversion<?php endif; ?>
    </td>
    <td><span class="badge <?= $row['account_type']==='fine'?'bg-warning text-dark':'bg-info text-dark' ?>"><?= ucfirst($row['account_type']) ?></span></td>
    <td><span class="badge <?= $row['direction']==='debit'?'bg-danger':'bg-success' ?>"><?= ucfirst($row['direction']) ?></span></td>
    <td><?= number_format($row['amount'],4) ?></td>
    <td class="<?= $row['fine_running']>0?'text-danger':'' ?>"><?= number_format($row['fine_running'],4) ?></td>
    <td class="<?= $row['cash_running']>0?'text-success':'' ?>"><?= number_format($row['cash_running'],2) ?></td>
    <td style="font-size:12px"><?= esc($row['narration']) ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$ledger): ?><tr><td colspan="8" class="text-center text-muted">No transactions yet</td></tr><?php endif; ?>
</tbody>
<tfoot class="table-light"><tr>
    <td colspan="5"><strong>Current Balance</strong></td>
    <td class="<?= $fineBalance>0?'text-danger':'' ?>"><strong><?= number_format($fineBalance,4) ?>g</strong></td>
    <td class="<?= $cashBalance>0?'text-success':'' ?>"><strong>Rs <?= number_format($cashBalance,2) ?></strong></td>
    <td></td>
</tr></tfoot>
</table>
</div>
<?= $this->endSection() ?>
