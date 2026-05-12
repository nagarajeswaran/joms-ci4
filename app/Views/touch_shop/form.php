<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="bi bi-flask"></i> <?= esc($title) ?></h5>
    <a href="<?= base_url('touch-shops') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger py-2"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="card" style="max-width:620px">
<div class="card-body">
<form method="post" action="<?= isset($isEdit) ? base_url('touch-shops/update/'.$entry['id']) : base_url('touch-shops/store') ?>" enctype="multipart/form-data" data-turbo="false">
<?= csrf_field() ?>

<div class="row g-3">
    <!-- Serial Number -->
    <div class="col-sm-4">
        <label class="form-label">Serial Number</label>
        <input type="text" class="form-control" value="<?= esc($nextSerial) ?>" readonly>
        <div class="form-text text-muted">Auto-assigned</div>
    </div>

    <!-- Issue Weight -->
    <div class="col-sm-4">
        <label class="form-label">Issue Weight (g) <span class="text-danger">*</span></label>
        <input type="number" step="0.0001" min="0.0001" name="issue_weight_g"
               class="form-control" required value="<?= old('issue_weight_g') ?>">
    </div>

    <!-- Stamp + Touch Shop Name row -->
    <div class="col-sm-4">
        <label class="form-label">Stamp</label>
        <select name="stamp_id" id="stampSelect" class="form-select">
            <option value="">— Select Stamp —</option>
            <?php foreach ($stamps as $s): ?>
            <option value="<?= $s['id'] ?>" <?= old('stamp_id', $prefill['stamp_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                <?= esc($s['name']) ?>
            </option>
            <?php endforeach; ?>
            <option value="__new__">+ Add New Stamp...</option>
        </select>
        <div id="newStampRow" class="mt-2 d-none">
            <div class="input-group input-group-sm">
                <input type="text" class="form-control" id="newStampName" placeholder="Enter new stamp name...">
                <button type="button" class="btn btn-success" onclick="addNewStamp()"><i class="bi bi-plus"></i> Add</button>
                <button type="button" class="btn btn-outline-secondary" onclick="cancelNewStamp()">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Touch Shop Name (combo: existing + add new) -->
    <div class="col-sm-8">
        <label class="form-label">Touch Shop Name <small class="text-muted">(who does the testing)</small></label>
        <select name="touch_shop_name" id="selTouchShop" class="form-select">
            <option value="">— Select Touch Shop —</option>
            <?php foreach ($shopNames as $sn): ?>
            <option value="<?= esc($sn['touch_shop_name']) ?>"><?= esc($sn['touch_shop_name']) ?></option>
            <?php endforeach; ?>
            <option value="__new__">＋ Add New Shop Name…</option>
        </select>
        <input type="text" name="touch_shop_name_new" id="inpNewShop" class="form-control mt-2"
               placeholder="Type new touch shop name…" style="display:none" maxlength="100">
    </div>

    <!-- Karigar -->
    <div class="col-sm-6">
        <label class="form-label">Karigar</label>
        <select name="karigar_id" class="form-select">
            <option value="">— Select Karigar —</option>
            <?php
            $currentDept = null;
            foreach ($karigars as $k):
                if ($k['dept'] !== $currentDept):
                    if ($currentDept !== null) echo '</optgroup>';
                    $currentDept = $k['dept'];
                    echo '<optgroup label="'.esc($currentDept ?? 'Other').'">';
                endif;
            ?>
            <option value="<?= $k['id'] ?>" <?= (old('karigar_id', $prefill['karigar_id'] ?? '')) == $k['id'] ? 'selected' : '' ?>>
                <?= esc($k['name']) ?>
            </option>
            <?php endforeach; ?>
            <?php if ($currentDept !== null) echo '</optgroup>'; ?>
        </select>
    </div>

    <!-- Gatti Batch -->
    <div class="col-sm-6">
        <label class="form-label">Link Gatti Batch <small class="text-muted">(optional)</small></label>
        <select name="gatti_stock_id" class="form-select">
            <option value="">— Not Linked —</option>
            <?php foreach ($gattis as $g): ?>
            <option value="<?= $g['id'] ?>" <?= old('gatti_stock_id', $prefill['gatti_stock_id'] ?? '') == $g['id'] ? 'selected' : '' ?>>
                <?= esc($g['batch_number'] ?: 'No batch') ?>
                <?= $g['job_number'] ? ' / Job '.$g['job_number'] : '' ?>
                — <?= number_format($g['weight_g'], 4) ?>g
                <?= $g['touch_pct'] ? ' (T:'.$g['touch_pct'].'%)' : '' ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Sample Image -->
    <div class="col-12">
        <label class="form-label">Sample Image <small class="text-muted">(jpg/png/webp)</small></label>
        <?php if (!empty($prefill['sample_image'])): ?>
        <div class="mb-2">
            <a href="<?= base_url($prefill['sample_image']) ?>" target="_blank">
                <img src="<?= base_url($prefill['sample_image']) ?>" alt="sample" style="height:60px;border-radius:4px;border:1px solid #dee2e6;">
            </a>
            <small class="text-muted ms-2">Current image — upload new to replace</small>
        </div>
        <?php endif; ?>
        <div class="d-flex gap-2 align-items-start">
            <input type="file" name="sample_image" id="fileInput" accept="image/*" class="form-control">
            <button type="button" class="btn btn-outline-primary btn-sm text-nowrap" onclick="openCamera()"><i class="bi bi-camera"></i> Take Photo</button>
        </div>
        <div id="cameraBox" class="d-none" style="position:fixed;inset:0;z-index:9999;background:#000;display:flex;flex-direction:column;align-items:center;justify-content:center;">
            <video id="cameraPreview" autoplay playsinline style="width:100%;height:calc(100% - 60px);object-fit:cover;"></video>
            <div style="position:absolute;bottom:12px;left:0;right:0;display:flex;justify-content:center;gap:12px;">
                <button type="button" class="btn btn-success btn-lg rounded-circle" onclick="capturePhoto()" style="width:56px;height:56px;"><i class="bi bi-camera-fill"></i></button>
                <button type="button" class="btn btn-light btn-sm rounded-pill align-self-center" onclick="closeCamera()"><i class="bi bi-x-lg"></i> Close</button>
            </div>
        </div>
        <canvas id="cameraCanvas" style="display:none;"></canvas>
        <div id="capturedPreview" class="mt-2 d-none">
            <img id="capturedImg" src="" style="height:80px;border-radius:4px;border:1px solid #dee2e6;">
            <button type="button" class="btn btn-outline-danger btn-sm ms-2" onclick="removeCaptured()"><i class="bi bi-x"></i> Remove</button>
        </div>
    </div>

    <!-- Notes -->
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2"><?= old('notes', $prefill['notes'] ?? '') ?></textarea>
    </div>
</div>

<div class="mt-3 d-flex gap-2">
    <?php if (isset($isEdit)): ?>
    <button type="submit" class="btn btn-warning"><i class="bi bi-pencil-square"></i> Save Changes</button>
    <?php else: ?>
    <button type="submit" class="btn btn-primary"><i class="bi bi-flask"></i> Create Entry</button>
    <?php endif; ?>
    <a href="<?= base_url('touch-shops') ?>" class="btn btn-outline-secondary">Cancel</a>
</div>
</form>
</div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.getElementById('stampSelect').addEventListener('change', function() {
    if (this.value === '__new__') {
        document.getElementById('newStampRow').classList.remove('d-none');
        document.getElementById('newStampName').focus();
        this.value = '';
    }
});
function addNewStamp() {
    var name = document.getElementById('newStampName').value.trim();
    if (!name) { alert('Enter a stamp name'); return; }
    var body = new URLSearchParams({name: name});
    fetch('<?= base_url("orders/createStamp") ?>', {method:'POST', body:body})
        .then(function(r){ return r.json(); })
        .then(function(d) {
            if (d.id) {
                var sel = document.getElementById('stampSelect');
                var opt = document.createElement('option');
                opt.value = d.id;
                opt.textContent = name;
                opt.selected = true;
                sel.insertBefore(opt, sel.querySelector('option[value="__new__"]'));
                cancelNewStamp();
            } else {
                alert(d.error || 'Failed to add stamp');
            }
        });
}
function cancelNewStamp() {
    document.getElementById('newStampRow').classList.add('d-none');
    document.getElementById('newStampName').value = '';
}
document.getElementById('newStampName').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') { e.preventDefault(); addNewStamp(); }
    if (e.key === 'Escape') cancelNewStamp();
});

var selShop  = document.getElementById('selTouchShop');
var inpNew   = document.getElementById('inpNewShop');
var LS_KEY   = 'last_touch_shop';

// Pre-select last used from localStorage
var lastShop = localStorage.getItem(LS_KEY) || '';
if (lastShop) {
    // try to find matching option
    for (var i=0; i < selShop.options.length; i++) {
        if (selShop.options[i].value === lastShop) { selShop.value = lastShop; break; }
    }
}

selShop.addEventListener('change', function() {
    inpNew.style.display = this.value === '__new__' ? '' : 'none';
    if (this.value === '__new__') { inpNew.focus(); }
});

// On submit, save resolved name to localStorage
document.querySelector('form').addEventListener('submit', function() {
    var name = selShop.value === '__new__' ? inpNew.value.trim() : selShop.value;
    if (name && name !== '__new__') localStorage.setItem(LS_KEY, name);
});

// ========== CAMERA ==========
var _stream = null;

function openCamera() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Camera not supported on this browser. Use HTTPS or a modern browser.');
        return;
    }
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment', width: { ideal: 1920 }, height: { ideal: 1080 } } })
        .then(function(stream) {
            _stream = stream;
            var video = document.getElementById('cameraPreview');
            video.srcObject = stream;
            var box = document.getElementById('cameraBox');
            box.classList.remove('d-none');
            box.style.display = 'flex';
        })
        .catch(function(err) {
            alert('Could not access camera: ' + err.message);
        });
}

function closeCamera() {
    if (_stream) {
        _stream.getTracks().forEach(function(t) { t.stop(); });
        _stream = null;
    }
    var video = document.getElementById('cameraPreview');
    video.srcObject = null;
    var box = document.getElementById('cameraBox');
    box.style.display = 'none';
    box.classList.add('d-none');
}

function capturePhoto() {
    var video = document.getElementById('cameraPreview');
    var canvas = document.getElementById('cameraCanvas');
    var srcW = video.videoWidth;
    var srcH = video.videoHeight;

    // Resize to max 1200px on longest side for compression + clarity
    var maxPx = 1200;
    var scale = 1;
    if (srcW > maxPx || srcH > maxPx) {
        scale = Math.min(maxPx / srcW, maxPx / srcH);
    }
    canvas.width = Math.round(srcW * scale);
    canvas.height = Math.round(srcH * scale);

    var ctx = canvas.getContext('2d');
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    closeCamera();

    canvas.toBlob(function(blob) {
        var file = new File([blob], 'camera_photo_' + Date.now() + '.jpg', { type: 'image/jpeg' });
        var dt = new DataTransfer();
        dt.items.add(file);
        document.getElementById('fileInput').files = dt.files;

        // Show preview
        document.getElementById('capturedImg').src = URL.createObjectURL(blob);
        document.getElementById('capturedPreview').classList.remove('d-none');
    }, 'image/jpeg', 0.82);
}

function removeCaptured() {
    document.getElementById('fileInput').value = '';
    document.getElementById('capturedPreview').classList.add('d-none');
    document.getElementById('capturedImg').src = '';
}
</script>
<?= $this->endSection() ?>
