<?php $this->extend('layouts/main'); ?>
<?php $this->section('content'); ?>

<?php
$ready      = array_filter($rows, fn($r) => $r['status'] === 'ready');
$newPart    = array_filter($rows, fn($r) => $r['status'] === 'new_part');
$skipped    = array_filter($rows, fn($r) => in_array($r['status'], ['duplicate', 'error']));
?>

<div class="container-fluid py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">Import Preview</h4>
    <a href="<?= site_url('part-stock/import') ?>" class="btn btn-sm btn-outline-secondary">← Cancel</a>
  </div>

  <!-- Summary -->
  <div class="d-flex gap-3 mb-3 flex-wrap">
    <span class="badge bg-success fs-6"><?= count($ready) ?> ready</span>
    <span class="badge bg-warning text-dark fs-6"><?= count($newPart) ?> unmatched (map below)</span>
    <span class="badge bg-danger fs-6"><?= count($skipped) ?> skipped</span>
  </div>

  <?php if (count($newPart) > 0): ?>
  <div class="alert alert-warning py-2">
    <strong>Action needed:</strong> Yellow rows did not match any part in the database.
    Use the dropdown to map each one to an existing part, or leave as <em>"— Create New —"</em> to auto-create.
  </div>
  <?php endif; ?>

  <?php if (count($ready) + count($newPart) === 0): ?>
    <div class="alert alert-warning">No importable rows found. All rows are either duplicates or have errors.</div>
    <a href="<?= site_url('part-stock/import') ?>" class="btn btn-secondary">Try Again</a>
  <?php else: ?>
    <form action="<?= site_url('part-stock/import/confirm') ?>" method="post">
      <?= csrf_field() ?>
      <div class="card shadow-sm mb-3">
        <div class="card-header fw-semibold">Row Preview</div>
        <div class="table-responsive">
          <table class="table table-sm table-bordered table-hover mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th class="text-center" title="Select all / none">
                  <input type="checkbox" id="chkAll" checked>
                </th>
                <th>#</th>
                <th>Status</th>
                <th>Part Name / Map To</th>
                <th>Batch Number</th>
                <th class="text-end">Weight (g)</th>
                <th class="text-end">Wt/pc (g)</th>
                <th class="text-end">Touch %</th>
                <th>Stamp</th>
                <th>Date</th>
                <th>Note</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $i => $row): ?>
                <?php
                  $importable = in_array($row['status'], ['ready', 'new_part']);
                  $rowClass = match($row['status']) {
                    'ready'    => 'table-success',
                    'new_part' => 'table-warning',
                    default    => 'table-danger',
                  };
                  $badge = match($row['status']) {
                    'ready'     => '<span class="badge bg-success">Ready</span>',
                    'new_part'  => '<span class="badge bg-warning text-dark">Map / New</span>',
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
                  <td>
                    <?php if ($row['status'] === 'new_part'): ?>
                      <div class="text-muted small mb-1"><?= esc($row['partName']) ?></div>
                      <select name="mapping[<?= $i ?>]" class="form-select form-select-sm">
                        <option value="">— Create New —</option>
                        <?php foreach ($allParts as $p): ?>
                          <option value="<?= $p['id'] ?>"><?= esc($p['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    <?php else: ?>
                      <?= esc($row['partName']) ?>
                    <?php endif; ?>
                  </td>
                  <td><?= esc($row['batchNo']) ?></td>
                  <td class="text-end"><?= number_format((float)($row['weightG'] ?? 0), 2) ?></td>
                  <td class="text-end"><?= $row['pieceWeightG'] ? number_format((float)$row['pieceWeightG'], 4) : '-' ?></td>
                  <td class="text-end"><?= number_format((float)($row['touchPct'] ?? 0), 2) ?></td>
                  <td><?= esc($row['stampName'] ?? '') ?></td>
                  <td><?= esc($row['date'] ?? '') ?></td>
                  <td><small class="text-muted"><?= esc($row['reason'] ?? '') ?></small></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
          <span class="text-muted small">
            <?= count($ready) + count($newPart) ?> importable rows
            <?php if (count($newPart) > 0): ?>
              · <?= count($newPart) ?> unmatched (select mapping or create new)
            <?php endif; ?>
            · <?= count($skipped) ?> auto-skipped
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
