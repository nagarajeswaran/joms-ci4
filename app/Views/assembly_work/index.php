<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Assembly Work</h5>
    <a href="<?= base_url('assembly-work/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus"></i> New Assembly Work</a>
</div>
<form method="get" class="row g-2 mb-3">
    <div class="col-auto">
        <select name="karigar" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Karigar</option>
            <?php foreach ($karigars as $k): ?>
            <option value="<?= $k['id'] ?>" <?= $karigarFilter == $k['id'] ? 'selected' : '' ?>><?= esc($k['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="draft" <?= $statusFilter === 'draft' ? 'selected' : '' ?>>Draft</option>
            <option value="in_progress" <?= $statusFilter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
            <option value="finished" <?= $statusFilter === 'finished' ? 'selected' : '' ?>>Finished</option>
            <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
        </select>
    </div>
</form>
<div class="table-responsive">
    <table class="table table-sm table-bordered table-hover">
        <thead class="table-dark">
            <tr>
                <th>Work No</th>
                <th>Karigar</th>
                <th>Status</th>
                <th>Making Charge Cash</th>
                <th>Making Charge Fine</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $row): ?>
            <tr>
                <td><strong><?= esc($row['work_number']) ?></strong></td>
                <td><?= esc($row['karigar_name']) ?></td>
                <td>
                    <span class="badge <?= $row['status'] === 'completed' ? 'bg-success' : ($row['status'] === 'finished' ? 'bg-info text-dark' : ($row['status'] === 'in_progress' ? 'bg-primary' : 'bg-warning text-dark')) ?>">
                        <?= ucwords(str_replace('_', ' ', $row['status'])) ?>
                    </span>
                </td>
                <td><?= number_format((float)($row['making_charge_cash'] ?? 0), 2) ?></td>
                <td><?= number_format((float)($row['making_charge_fine'] ?? 0), 4) ?></td>
                <td><?= date('d/m/Y', strtotime($row['created_at'])) ?></td>
                <td><a href="<?= base_url('assembly-work/view/'.$row['id']) ?>" class="btn btn-sm btn-outline-primary">View</a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$items): ?><tr><td colspan="7" class="text-center text-muted">No assembly work found</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?= $this->endSection() ?>