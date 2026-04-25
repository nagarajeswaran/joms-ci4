<?php $this->extend('layouts/main') ?>
<?php $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-images text-primary"></i> Image Gallery</h5>
    <a href="<?= base_url('products') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<!-- Type filter -->
<div class="card mb-3">
    <div class="card-body py-2 px-3">
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <label class="form-label mb-0 fw-bold" style="font-size:13px;">Filter:</label>
            <select id="filterType" class="form-select form-select-sm" style="width:220px;">
                <option value="">All Product Types</option>
                <?php foreach ($productTypes as $pt): ?>
                <option value="<?= $pt['id'] ?>"><?= esc($pt['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="filterSearch" class="form-control form-control-sm" style="width:200px;" placeholder="Search product name...">
            <span id="galleryCount" class="text-muted ms-2" style="font-size:13px;"><?= count($products) ?> products</span>
        </div>
    </div>
</div>

<div id="galleryGrid">
<?php foreach ($products as $product): ?>
<?php $ptns = $patternsByProduct[$product['id']] ?? []; ?>
<div class="card mb-3 gallery-product-card"
     data-type="<?= $product['product_type_id'] ?? '' ?>"
     data-name="<?= strtolower(esc($product['name'])) ?>">
    <div class="card-header d-flex align-items-center gap-2 py-2">
        <strong style="font-size:13px;"><?= esc($product['name']) ?></strong>
        <?php if ($product['sku']): ?>
        <span class="badge bg-secondary" style="font-size:11px;"><?= esc($product['sku']) ?></span>
        <?php endif; ?>
        <?php if ($product['product_type_name']): ?>
        <span class="badge bg-light text-dark border" style="font-size:11px;"><?= esc($product['product_type_name']) ?></span>
        <?php endif; ?>
    </div>
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-3 align-items-start">

            <!-- Product image -->
            <div class="text-center" style="min-width:110px;">
                <div class="mb-1 position-relative">
                    <?php if (!empty($product['image'])): ?>
                    <img src="<?= upload_url('products/' . $product['image']) ?>"
                         class="gallery-thumb preview-img"
                         data-name="<?= esc($product['name']) ?>"
                         onerror="this.style.display='none';var p=this.parentElement.querySelector('.gallery-placeholder');if(!p){this.insertAdjacentHTML('afterend','<div class=\'gallery-placeholder d-flex align-items-center justify-content-center\' style=\'width:90px;height:90px;background:#f0f0f0;border-radius:6px;border:2px dashed #ccc;\'><i class=\'bi bi-image text-muted\' style=\'font-size:24px;\'></i></div>')}"
                         style="width:90px;height:90px;object-fit:cover;border-radius:6px;border:2px solid #ddd;cursor:pointer;">
                    <?php else: ?>
                    <div class="gallery-placeholder d-flex align-items-center justify-content-center"
                         style="width:90px;height:90px;background:#f0f0f0;border-radius:6px;border:2px dashed #ccc;cursor:pointer;"
                         onclick="document.getElementById('prod-img-<?= $product['id'] ?>').click()">
                        <i class="bi bi-image text-muted" style="font-size:24px;"></i>
                    </div>
                    <?php endif; ?>
                    <div class="upload-spinner" id="prod-spin-<?= $product['id'] ?>" style="display:none;position:absolute;inset:0;background:rgba(255,255,255,0.8);border-radius:6px;align-items:center;justify-content:center;">
                        <div class="spinner-border spinner-border-sm text-primary"></div>
                    </div>
                </div>
                <div style="font-size:11px;font-weight:600;color:#555;margin-bottom:4px;">Product Image</div>
                <input type="file" id="prod-img-<?= $product['id'] ?>" accept="image/*" style="display:none;"
                       data-product-id="<?= $product['id'] ?>" class="product-img-input">
                <button type="button" class="btn btn-outline-primary btn-sm"
                        style="font-size:11px;padding:2px 8px;"
                        onclick="document.getElementById('prod-img-<?= $product['id'] ?>').click()">
                    <i class="bi bi-upload"></i> Upload
                </button>
                <div class="upload-error text-danger" id="prod-err-<?= $product['id'] ?>" style="font-size:11px;display:none;"></div>
            </div>

            <!-- Divider -->
            <div style="border-left:1px solid #dee2e6;margin:0 4px;"></div>

            <!-- Patterns -->
            <div class="d-flex flex-wrap gap-3">
                <?php foreach ($ptns as $pat): ?>
                <div class="text-center" style="min-width:90px;">
                    <div class="mb-1 position-relative">
                        <?php if (!empty($pat['image'])): ?>
                        <img src="<?= upload_url('patterns/' . $pat['image']) ?>"
                             class="gallery-thumb preview-img"
                             data-name="<?= esc($product['name'] . ' — ' . $pat['pattern_name']) ?>"
                             onerror="this.style.display='none';this.insertAdjacentHTML('afterend','<div class=\'gallery-placeholder d-flex align-items-center justify-content-center\' style=\'width:75px;height:75px;background:#f8f8f8;border-radius:5px;border:2px dashed #ccc;\'><i class=\'bi bi-image text-muted\' style=\'font-size:18px;\'></i></div>')"
                             style="width:75px;height:75px;object-fit:cover;border-radius:5px;border:2px solid #ccc;cursor:pointer;">
                        <?php else: ?>
                        <div class="gallery-placeholder d-flex align-items-center justify-content-center"
                             style="width:75px;height:75px;background:#f8f8f8;border-radius:5px;border:2px dashed #ccc;cursor:pointer;"
                             onclick="document.getElementById('pat-img-<?= $pat['id'] ?>').click()">
                            <i class="bi bi-image text-muted" style="font-size:18px;"></i>
                        </div>
                        <?php endif; ?>
                        <div class="upload-spinner" id="pat-spin-<?= $pat['id'] ?>" style="display:none;position:absolute;inset:0;background:rgba(255,255,255,0.8);border-radius:5px;align-items:center;justify-content:center;">
                            <div class="spinner-border spinner-border-sm text-primary" style="width:18px;height:18px;"></div>
                        </div>
                    </div>
                    <div style="font-size:10px;font-weight:600;color:#555;margin-bottom:3px;max-width:80px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= esc($pat['pattern_name']) ?>">
                        <?= esc($pat['pattern_name']) ?>
                        <?php if ($pat['is_default']): ?><span class="text-primary">*</span><?php endif; ?>
                    </div>
                    <input type="file" id="pat-img-<?= $pat['id'] ?>" accept="image/*" style="display:none;"
                           data-pattern-id="<?= $pat['id'] ?>" class="pattern-img-input">
                    <button type="button" class="btn btn-outline-secondary btn-sm"
                            style="font-size:10px;padding:1px 6px;"
                            onclick="document.getElementById('pat-img-<?= $pat['id'] ?>').click()">
                        <i class="bi bi-upload"></i>
                    </button>
                    <div class="upload-error text-danger" id="pat-err-<?= $pat['id'] ?>" style="font-size:10px;display:none;"></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($ptns)): ?>
                <span class="text-muted" style="font-size:12px;">No patterns</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<div id="noGalleryResults" class="text-center text-muted py-5" style="display:none;">
    <i class="bi bi-search" style="font-size:32px;"></i><br>No products match the filter.
</div>

<!-- Image preview lightbox -->
<div id="lightbox" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.65);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:8px;padding:14px;max-width:420px;width:92%;text-align:center;position:relative;">
        <button onclick="document.getElementById('lightbox').style.display='none'"
                style="position:absolute;top:6px;right:10px;background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
        <p id="lightboxName" style="font-weight:600;margin-bottom:8px;font-size:13px;"></p>
        <img id="lightboxImg" src="" style="max-width:100%;max-height:380px;object-fit:contain;border-radius:4px;">
    </div>
</div>

<script>
(function() {
    var BASE = '<?= base_url() ?>';
    var cards = Array.from(document.querySelectorAll('.gallery-product-card'));
    var countEl = document.getElementById('galleryCount');
    var noResults = document.getElementById('noGalleryResults');

    // ── filter ──────────────────────────────────────────────
    function applyFilter() {
        var type = document.getElementById('filterType').value;
        var q    = document.getElementById('filterSearch').value.toLowerCase().trim();
        var visible = 0;
        cards.forEach(function(card) {
            var match =
                (!type || card.dataset.type === type) &&
                (!q    || card.dataset.name.includes(q));
            card.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        countEl.textContent = visible + ' products';
        noResults.style.display = (visible === 0 && cards.length > 0) ? 'block' : 'none';
    }
    document.getElementById('filterType').addEventListener('change', applyFilter);
    var dt;
    document.getElementById('filterSearch').addEventListener('input', function() {
        clearTimeout(dt); dt = setTimeout(applyFilter, 250);
    });

    // ── product image upload ──────────────────────────────────
    document.querySelectorAll('.product-img-input').forEach(function(input) {
        input.addEventListener('change', function() {
            if (!this.files[0]) return;
            var pid     = this.dataset.productId;
            var spinner = document.getElementById('prod-spin-' + pid);
            var errEl   = document.getElementById('prod-err-' + pid);
            var fd      = new FormData();
            fd.append('product_image', this.files[0]);
            fd.append('<?= csrf_token() ?>', document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').content : '');

            spinner.style.display = 'flex';
            errEl.style.display   = 'none';

            fetch(BASE + 'products/ajaxUploadProductImage/' + pid, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    spinner.style.display = 'none';
                    if (data.success) {
                        var container = spinner.parentElement;
                        var existing  = container.querySelector('img.gallery-thumb');
                        var placeholder = container.querySelector('.gallery-placeholder');
                        if (existing) {
                            existing.src = data.url + '?t=' + Date.now();
                        } else {
                            if (placeholder) placeholder.remove();
                            var img = document.createElement('img');
                            img.src = data.url;
                            img.className = 'gallery-thumb preview-img';
                            img.style.cssText = 'width:90px;height:90px;object-fit:cover;border-radius:6px;border:2px solid #ddd;cursor:pointer;';
                            img.dataset.name = '';
                            container.insertBefore(img, spinner);
                            bindPreview(img);
                        }
                    } else {
                        errEl.textContent = data.error || 'Upload failed';
                        errEl.style.display = 'block';
                    }
                }).catch(function() {
                    spinner.style.display = 'none';
                    errEl.textContent = 'Network error';
                    errEl.style.display = 'block';
                });
        });
    });

    // ── pattern image upload ──────────────────────────────────
    document.querySelectorAll('.pattern-img-input').forEach(function(input) {
        input.addEventListener('change', function() {
            if (!this.files[0]) return;
            var ptid    = this.dataset.patternId;
            var spinner = document.getElementById('pat-spin-' + ptid);
            var errEl   = document.getElementById('pat-err-' + ptid);
            var fd      = new FormData();
            fd.append('pattern_image', this.files[0]);

            spinner.style.display = 'flex';
            errEl.style.display   = 'none';

            fetch(BASE + 'products/ajaxUploadPatternImage/' + ptid, { method: 'POST', body: fd })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    spinner.style.display = 'none';
                    if (data.success) {
                        var container = spinner.parentElement;
                        var existing  = container.querySelector('img.gallery-thumb');
                        var placeholder = container.querySelector('.gallery-placeholder');
                        if (existing) {
                            existing.src = data.url + '?t=' + Date.now();
                        } else {
                            if (placeholder) placeholder.remove();
                            var img = document.createElement('img');
                            img.src = data.url;
                            img.className = 'gallery-thumb preview-img';
                            img.style.cssText = 'width:75px;height:75px;object-fit:cover;border-radius:5px;border:2px solid #ccc;cursor:pointer;';
                            img.dataset.name = '';
                            container.insertBefore(img, spinner);
                            bindPreview(img);
                        }
                    } else {
                        errEl.textContent = data.error || 'Upload failed';
                        errEl.style.display = 'block';
                    }
                }).catch(function() {
                    spinner.style.display = 'none';
                    errEl.textContent = 'Network error';
                    errEl.style.display = 'block';
                });
        });
    });

    // ── lightbox ──────────────────────────────────────────────
    function bindPreview(img) {
        img.addEventListener('click', function() {
            document.getElementById('lightboxImg').src = this.src.split('?')[0];
            document.getElementById('lightboxName').textContent = this.dataset.name || '';
            document.getElementById('lightbox').style.display = 'flex';
        });
    }
    document.querySelectorAll('.preview-img').forEach(bindPreview);
    document.getElementById('lightbox').addEventListener('click', function(e) {
        if (e.target === this) this.style.display = 'none';
    });
})();
</script>
<?php $this->endSection() ?>
