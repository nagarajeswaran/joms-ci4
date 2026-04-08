<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<!-- Filter bar -->
<form method="get" action="<?= base_url('stock/qr-registry') ?>" class="card mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label mb-1 small">Search (product name, SKU or QR number)</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       placeholder="e.g. TG1 or 10277"
                       value="<?= esc($q ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1 small">Filter by Product</label>
                <select name="product_id" class="form-select form-select-sm">
                    <option value="">— All Products —</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= (int)($productId ?? 0) == $p['id'] ? 'selected' : '' ?>>
                            <?= esc(($p['sku'] ? $p['sku'] . ' — ' : '') . $p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Search</button>
                <a href="<?= base_url('stock/qr-registry') ?>" class="btn btn-outline-secondary btn-sm ms-1"><i class="bi bi-x"></i> Reset</a>
            </div>
        </div>
    </div>
</form>

<!-- Results -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center py-2">
        <span class="fw-semibold"><i class="bi bi-upc-scan"></i> QR Code Registry</span>
        <div class="d-flex align-items-center gap-2">
            <small class="text-muted"><?= number_format(count($rows)) ?> record<?= count($rows) != 1 ? 's' : '' ?></small>
            <button type="button" id="btnBulkGenQr" class="btn btn-success btn-sm">
                <i class="bi bi-lightning-charge-fill"></i> Bulk Generate All QR Codes
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0" style="font-size:13px;">
            <thead class="table-light">
                <tr>
                    <th style="width:100px;">QR Number</th>
                    <th style="width:80px;">SKU</th>
                    <th>Product</th>
                    <th>Pattern</th>
                    <th style="width:80px;">Size</th>
                    <th style="width:120px;">Generated</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        <i class="bi bi-inbox"></i> No QR codes found.
                        <?= ($q || $productId) ? '<a href="' . base_url('stock/qr-registry') . '">Clear filters</a>' : 'Generate labels to create QR codes.' ?>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <td>
                        <span class="badge bg-dark font-monospace fs-6"><?= esc($row['qr_number']) ?></span>
                    </td>
                    <td><small class="text-muted"><?= esc($row['sku'] ?? '—') ?></small></td>
                    <td><?= esc($row['product_name']) ?></td>
                    <td>
                        <?php if ($row['is_default']): ?>
                            <span class="text-muted fst-italic">Default</span>
                        <?php else: ?>
                            <?= esc($row['pattern_name']) ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['size']): ?>
                            <strong><?= esc($row['size']) ?>"</strong>
                        <?php else: ?>
                            <span class="text-muted"><?= esc($row['variation_name']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><small class="text-muted"><?= $row['generated_at'] ? date('d M Y', strtotime($row['generated_at'])) : '—' ?></small></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="bulkGenAlert" class="alert mt-3 d-none"></div>

<script>
document.getElementById('btnBulkGenQr').addEventListener('click', function () {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Generating...';
    document.getElementById('bulkGenAlert').className = 'alert mt-3 d-none';

    const productId = new URLSearchParams(window.location.search).get('product_id');
    const body = new URLSearchParams();
    if (productId) body.append('product_ids[]', productId);

    fetch('<?= base_url('stock/bulk-generate-qr') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: body.toString()
    })
    .then(r => r.json())
    .then(data => {
        const el = document.getElementById('bulkGenAlert');
        el.className = 'alert mt-3 ' + (data.success ? 'alert-success' : 'alert-danger');
        el.textContent = data.message || 'Error occurred.';
        if (data.created > 0) setTimeout(() => location.reload(), 1500);
    })
    .catch(() => {
        const el = document.getElementById('bulkGenAlert');
        el.className = 'alert mt-3 alert-danger';
        el.textContent = 'Request failed.';
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-lightning-charge-fill"></i> Bulk Generate All QR Codes';
    });
});
</script>

<?= $this->endSection() ?>
