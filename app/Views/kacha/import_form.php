<?php $this->extend('layouts/main'); ?>
<?php $this->section('content'); ?>

<div class="container-fluid py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">Import Kacha Lots</h4>
    <a href="<?= site_url('kacha') ?>" class="btn btn-sm btn-outline-secondary">← Back to Kacha</a>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-md-7">
      <div class="card shadow-sm">
        <div class="card-header fw-semibold">Upload File</div>
        <div class="card-body">
          <form action="<?= site_url('kacha/import/preview') ?>" method="post" enctype="multipart/form-data" data-turbo="false">
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="form-label">Select CSV or Excel file <span class="text-danger">*</span></label>
              <input type="file" name="import_file" class="form-control" accept=".csv,.xlsx" required>
              <div class="form-text">Accepted formats: <strong>.csv</strong>, <strong>.xlsx</strong></div>
            </div>
            <button type="submit" class="btn btn-primary">Preview Import</button>
            <a href="<?= site_url('kacha/import/sample') ?>" class="btn btn-outline-secondary ms-2">Download Sample CSV</a>
          </form>
        </div>
      </div>
      <div class="alert alert-info mt-3">
        <strong>Note:</strong> Lots with duplicate <strong>Lot Number</strong> will be skipped in preview.
        Allowed Source Types: <code>purchase</code>, <code>internal</code>, <code>part_order</code>, <code>melt_job</code> (defaults to <code>purchase</code>).
      </div>
    </div>
    <div class="col-md-5">
      <div class="card shadow-sm">
        <div class="card-header fw-semibold">Expected Columns</div>
        <div class="card-body p-0">
          <table class="table table-sm table-bordered mb-0">
            <thead class="table-light">
              <tr><th>#</th><th>Column</th><th>Required</th><th>Notes</th></tr>
            </thead>
            <tbody>
              <tr><td>1</td><td>Lot Number</td><td><span class="badge bg-danger">Yes</span></td><td>Must be unique</td></tr>
              <tr><td>2</td><td>Weight (g)</td><td><span class="badge bg-danger">Yes</span></td><td>Decimal grams</td></tr>
              <tr><td>3</td><td>Touch %</td><td><span class="badge bg-danger">Yes</span></td><td>Decimal percentage</td></tr>
              <tr><td>4</td><td>Receipt Date</td><td><span class="badge bg-secondary">No</span></td><td>YYYY-MM-DD, defaults to today</td></tr>
              <tr><td>5</td><td>Party</td><td><span class="badge bg-secondary">No</span></td><td>Supplier / source name</td></tr>
              <tr><td>6</td><td>Source Type</td><td><span class="badge bg-secondary">No</span></td><td>purchase / internal / part_order / melt_job</td></tr>
              <tr><td>7</td><td>Test Touch</td><td><span class="badge bg-secondary">No</span></td><td>Decimal percentage</td></tr>
              <tr><td>8</td><td>Test Number</td><td><span class="badge bg-secondary">No</span></td><td>Reference number</td></tr>
              <tr><td>9</td><td>Notes</td><td><span class="badge bg-secondary">No</span></td><td>Free text</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php $this->endSection(); ?>
