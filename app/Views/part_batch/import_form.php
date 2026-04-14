<?php $this->extend('layouts/main'); ?>
<?php $this->section('content'); ?>

<div class="container-fluid py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0">Import Part Stock</h4>
    <a href="<?= site_url('part-stock') ?>" class="btn btn-sm btn-outline-secondary">← Back to Part Stock</a>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-md-7">
      <div class="card shadow-sm">
        <div class="card-header fw-semibold">Upload File</div>
        <div class="card-body">
          <form action="<?= site_url('part-stock/import/preview') ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="form-label">Select CSV or Excel file <span class="text-danger">*</span></label>
              <input type="file" name="import_file" class="form-control" accept=".csv,.xlsx" required>
              <div class="form-text">Accepted formats: <strong>.csv</strong>, <strong>.xlsx</strong></div>
            </div>
            <button type="submit" class="btn btn-primary">Preview Import</button>
            <a href="<?= site_url('part-stock/import/sample') ?>" class="btn btn-outline-secondary ms-2">Download Sample CSV</a>
          </form>
        </div>
      </div>
      <div class="alert alert-warning mt-3">
        <strong>Note:</strong> If a part name is not found in the database, it will be <strong>auto-created with name only</strong>.
        Fill in other details (Tamil name, Gatti/kg, etc.) later from the
        <a href="<?= site_url('parts') ?>">Parts master page</a>.
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
              <tr><td>1</td><td>Part Name</td><td><span class="badge bg-danger">Yes</span></td><td>Case-insensitive; auto-created if missing</td></tr>
              <tr><td>2</td><td>Batch Number</td><td><span class="badge bg-danger">Yes</span></td><td>Must be unique</td></tr>
              <tr><td>3</td><td>Weight (g)</td><td><span class="badge bg-danger">Yes</span></td><td>Decimal grams</td></tr>
              <tr><td>4</td><td>Weight/pc (g)</td><td><span class="badge bg-secondary">No</span></td><td>Decimal grams per piece, default null</td></tr>
              <tr><td>5</td><td>Touch %</td><td><span class="badge bg-secondary">No</span></td><td>Decimal, default 0</td></tr>
              <tr><td>6</td><td>Stamp</td><td><span class="badge bg-secondary">No</span></td><td>Stamp name or leave blank</td></tr>
              <tr><td>7</td><td>Date</td><td><span class="badge bg-secondary">No</span></td><td>YYYY-MM-DD, defaults to today</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php $this->endSection(); ?>
