<?php $this->extend('layouts/main'); ?>
<?php $this->section('content'); ?>

<?php
$ready   = array_filter($rows, fn($r) => $r['status'] === 'ready');
$skipped = array_filter($rows, fn($r) => in_array($r['status'], ['duplicate', 'error']));
?>

<div class="container-fluid py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">Import Preview</h4>
    <a href="<?= site_url('kacha/import') ?>" class="btn btn-sm btn-outline-secondary">← Cancel</a>
  </div>

  <div class="d-flex gap-3 mb-3 flex-wrap">
    <span class="badge bg-success fs-6"><?= count($ready) ?> ready</span>
    <span class="badge bg-danger fs-6"><?= count($skipped) ?> skipped</span>
  </div>

  <?php if (count($ready) === 0): ?>
    <div class="alert alert-warning">No importable rows found. All rows are either duplicates or have errors.</div>
    <a href="<?= site_url('kacha/import') ?>" class="btn btn-secondary">Try Again</a>
  <?php else: ?>
    <form action="<?= site_url('kacha/import/confirm') ?>" method="post" data-turbo="false">
      <?= csrf_field() ?>
      <div class="card shadow-sm mb-3">
        <div class="card-header fw-semibold">Row Preview</div>
        <div class="table-responsive">
          <table class="table table-sm table-bordered table-hover mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th class="text-center"><input type="checkbox" id="chkAll" checked></th>
                <th>#</th>
                <th>Status</th>
                <th>Lot Number</th>
                <th class="text-end">Weight (g)</th>
                <th class="text-end">Touch %</th>
                <th class="text-end">Fine (g)</th>
                <th>Receipt Date</th>
                <th>Party</th>
                <th>Source</th>
                <th class="text-end">Test Touch</th>
                <th>Test No</th>
                <th>Notes</th>
                <th>Reason</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $i => $row): ?>
                <?php
                  $importable = $row['status'] === 'ready';
                  $rowClass = match($row['status']) {
                    'ready'   => 'table-success',
                    default   => 'table-danger',
                  };
                  $badge = match($row['status']) {
                    'ready'     => '<span class="badge bg-success">Ready</span>',
                    'duplicate' => '<span class="badge bg-danger">Duplicate</span>',
                    default     => '<span class="badge bg-danger">Error</span>',
                  };
                ?>
                <tr class="<?= $rowClass ?>">
                  <td class="text-center">
                    <?php if ($importable): ?>
                      <input type="checkbox" name="include[<?= $i ?>]" value="1" checked class="row-chk">
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td><?= $i + 1 ?></td>
                  <td><?= $badge ?></td>
                  <td class="fw-semibold"><?= esc($row['lotNumber']) ?></td>
                  <td class="text-end"><?= number_format((float)($row['weight'] ?? 0), 3) ?></td>
                  <td class="text-end"><?= number_format((float)($row['touchPct'] ?? 0), 2) ?>%</td>
                  <td class="text-end fw-semibold"><?= number_format((float)($row['fine'] ?? 0), 4) ?></td>
                  <td><?= esc($row['receiptDate'] ?? '') ?></td>
                  <td><?= esc($row['party'] ?? '') ?></td>
                  <td>
                    <?php $srcMap = ['purchase'=>'Purchase','internal'=>'Internal','part_order'=>'Part Order','melt_job'=>'Melt Job']; ?>
                    <span class="badge bg-secondary"><?= $srcMap[$row['sourceType']] ?? esc($row['sourceType']) ?></span>
                  </td>
                  <td class="text-end"><?= $row['testTouch'] !== '' ? number_format((float)$row['testTouch'], 2) . '%' : '—' ?></td>
                  <td><?= esc($row['testNumber'] ?? '') ?: '—' ?></td>
                  <td><small><?= esc($row['notes'] ?? '') ?: '—' ?></small></td>
                  <td><small class="text-muted"><?= esc($row['reason'] ?? '') ?></small></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
          <span class="text-muted small">
            <?= count($ready) ?> importable rows · <?= count($skipped) ?> auto-skipped
          </span>
          <button type="submit" class="btn btn-success">Confirm Import</button>
        </div>
      </div>
    </form>
  <?php endif; ?>
</div>

<script>
document.getElementById('chkAll').addEventListener('change', function () {
  document.querySelectorAll('.row-chk').forEach(c => c.checked = this.checked);
});
</script>

<?php $this->endSection(); ?>
