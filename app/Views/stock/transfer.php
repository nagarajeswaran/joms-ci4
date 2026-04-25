<?= $this->extend('layouts/main') ?>
<?= $this->section('styles') ?>
<style>
.from-to-bar {
    display: flex; align-items: flex-end; gap: 12px;
    background: #f8f9fa; border-radius: 12px; padding: 16px; border: 1.5px solid #e0e0e0;
}
.from-to-bar .loc-block { flex: 1; }
.from-to-arrow {
    font-size: 26px; color: #0d6efd; padding-bottom: 6px; flex-shrink: 0;
}
.transfer-footer-bar {
    position: sticky; bottom: 0; background: #fff;
    border-top: 2px solid #0dcaf0; padding: 10px 16px;
    margin: 0 -20px -20px; display: flex; align-items: center; justify-content: space-between;
    box-shadow: 0 -2px 8px rgba(0,0,0,.07); z-index: 90;
}
.trf-thumb {
    width: 26px; height: 26px; border-radius: 5px; object-fit: cover;
    border: 1px solid #e0e0e0; flex-shrink: 0;
}
.trf-thumb-placeholder {
    width: 26px; height: 26px; border-radius: 5px;
    background: #f0f0f0; display: flex; align-items: center; justify-content: center;
    font-size: 12px; color: #aaa; flex-shrink: 0;
}
</style>
<?= $this->endSection() ?>
<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5><i class="bi bi-arrow-left-right text-info"></i> Stock Transfer</h5>
    <a href="<?= base_url('stock') ?>" class="btn btn-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<form method="post" action="<?= base_url('stock/save-transfer') ?>">
    <?= csrf_field() ?>

    <!-- From → To bar -->
    <div class="from-to-bar mb-3">
        <div class="loc-block">
            <label class="form-label fw-semibold">From Location <span class="text-danger">*</span></label>
            <select name="from_location_id" id="fromLocation" class="form-select" required>
                <option value="">-- Select From --</option>
                <?php foreach ($locations as $l): ?>
                    <option value="<?= $l['id'] ?>"><?= esc($l['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Select From first to see available stock</div>
        </div>
        <div class="from-to-arrow"><i class="bi bi-arrow-right-circle-fill"></i></div>
        <div class="loc-block">
            <label class="form-label fw-semibold">To Location <span class="text-danger">*</span></label>
            <select name="to_location_id" class="form-select" required>
                <option value="">-- Select To --</option>
                <?php foreach ($locations as $l): ?>
                    <option value="<?= $l['id'] ?>"><?= esc($l['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="loc-block" style="max-width:220px;">
            <label class="form-label fw-semibold">Note</label>
            <input type="text" name="note" class="form-control" placeholder="Transfer reason…">
        </div>
    </div>

    <!-- QR Scan -->
    <div class="card mb-3">
        <div class="card-header fw-semibold"><i class="bi bi-qr-code-scan"></i> Add via QR Scan</div>
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label">QR Number</label>
                    <input type="text" id="qrInput" class="form-control" placeholder="Type or scan…" style="width:200px;">
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-outline-primary btn-sm" id="btnLookupQr">
                        <i class="bi bi-search"></i> Lookup
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm" id="btnScanQr">
                        <i class="bi bi-camera"></i> Camera
                    </button>
                </div>
                <div class="col-12"><div id="qrScanMsg" class="text-muted small"></div></div>
            </div>
            <div id="cameraBox" class="mt-2" style="display:none;">
                <video id="qrVideo" style="width:260px;height:200px;border:1px solid #ccc;border-radius:6px;" autoplay playsinline muted></video>
                <button type="button" class="btn btn-sm btn-secondary ms-2 align-top" id="btnStopScan">Stop</button>
                <canvas id="qrCanvas" style="display:none;"></canvas>
            </div>
        </div>
    </div>

    <!-- Items table -->
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Items to Transfer
                <span id="itemCount" class="badge bg-info text-dark ms-1">0</span>
            </span>
            <button type="button" class="btn btn-sm btn-outline-primary" id="btnAddItem" disabled>
                <i class="bi bi-plus"></i> Add Item
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:32px;"></th>
                            <th>Product</th>
                            <th>Pattern</th>
                            <th>Variation</th>
                            <th style="width:130px;">Qty <small class="text-muted">(avail)</small></th>
                            <th style="width:36px;"></th>
                        </tr>
                    </thead>
                    <tbody id="transferItems">
                        <tr id="emptyRow">
                            <td colspan="6" class="text-center text-muted py-3">
                                <i class="bi bi-info-circle"></i> Select From Location first, then add items
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sticky footer -->
    <div class="transfer-footer-bar">
        <div class="text-muted small" id="trf-summary">No items added yet</div>
        <button type="submit" class="btn btn-info text-white px-4">
            <i class="bi bi-arrow-left-right"></i> Execute Transfer
        </button>
    </div>
</form>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script>
var BASE_URL  = '<?= base_url() ?>';
var CSRF_NAME = '<?= csrf_token() ?>';
var CSRF_HASH = '<?= csrf_hash() ?>';

function post(url, data) {
    var p = new URLSearchParams(data);
    p.append(CSRF_NAME, CSRF_HASH);
    return fetch(BASE_URL+url, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:p}).then(r=>r.json());
}

function getFromLoc() { return document.getElementById('fromLocation').value; }
function getFromLocName() {
    var s=document.getElementById('fromLocation');
    return s.options[s.selectedIndex]?s.options[s.selectedIndex].text:'';
}

function updateSummary() {
    var rows = document.querySelectorAll('#transferItems tr[data-item]');
    var c = rows.length;
    document.getElementById('itemCount').textContent = c;
    if (!c) { document.getElementById('trf-summary').textContent='No items added yet'; return; }
    var toSel = document.querySelector('[name="to_location_id"]');
    var toName = toSel&&toSel.options[toSel.selectedIndex]?toSel.options[toSel.selectedIndex].text:'?';
    document.getElementById('trf-summary').innerHTML =
        '<strong>'+c+' item(s)</strong> moving from <strong>'+escHtml(getFromLocName())+'</strong> → <strong>'+escHtml(toName)+'</strong>';
}

document.querySelector('[name="to_location_id"]').addEventListener('change', updateSummary);

function buildRow(prefill) {
    prefill = prefill || {};
    var tr = document.createElement('tr');
    tr.dataset.item = '1';

    var thumbHtml = prefill.product_image
        ? '<img src="'+BASE_URL+'uploads/products/'+escHtml(prefill.product_image)+'" class="trf-thumb">'
        : '<div class="trf-thumb-placeholder"><i class="bi bi-gem"></i></div>';

    tr.innerHTML =
        '<td>'+thumbHtml+'</td>'+
        '<td><select name="product_id[]" class="form-select form-select-sm trf-product" required>'+
            '<option value="">-- Product --</option>'+
            '<?php foreach ($products as $p): ?>'+
            '<option value="<?= $p['id'] ?>" data-img="<?= esc($p['image']??'') ?>"><?= esc(addslashes(($p['sku']?$p['sku'].' - ':'').$p['name'])) ?></option>'+
            '<?php endforeach; ?>'+
        '</select></td>'+
        '<td><select name="pattern_id[]" class="form-select form-select-sm trf-pattern" disabled required><option value="">-- Pattern --</option></select></td>'+
        '<td><select name="variation_id[]" class="form-select form-select-sm trf-variation" disabled required><option value="">-- Variation --</option></select></td>'+
        '<td><div class="input-group input-group-sm">'+
            '<input type="number" name="qty[]" class="form-control text-center trf-qty" value="1" min="1" required>'+
            '<span class="input-group-text trf-avail" style="font-size:11px;color:#888;min-width:46px;"></span>'+
        '</div></td>'+
        '<td class="text-center"><button type="button" class="btn btn-outline-danger btn-sm py-0 px-1 btnRemove"><i class="bi bi-x"></i></button></td>';

    var selProd = tr.querySelector('.trf-product');
    var selPat  = tr.querySelector('.trf-pattern');
    var selVar  = tr.querySelector('.trf-variation');
    var qtyInp  = tr.querySelector('.trf-qty');
    var availSp = tr.querySelector('.trf-avail');
    var thumbEl = tr.querySelector('td:first-child');

    selProd.addEventListener('change', function() {
        var opt = this.options[this.selectedIndex];
        var img = opt ? opt.dataset.img : '';
        thumbEl.innerHTML = img
            ? '<img src="'+BASE_URL+'uploads/products/'+img+'" class="trf-thumb">'
            : '<div class="trf-thumb-placeholder"><i class="bi bi-gem"></i></div>';
        if (!this.value||!getFromLoc()) return;
        post('stock/get-transfer-patterns',{product_id:this.value,from_location_id:getFromLoc()}).then(function(data){
            selPat.innerHTML='<option value="">-- Pattern --</option>';
            if (!data.length){selPat.innerHTML='<option value="">No stock here</option>';return;}
            data.forEach(function(p){
                var opt=document.createElement('option');
                opt.value=p.id; opt.textContent=p.is_default==1?'Default':p.name; selPat.appendChild(opt);
            });
            selPat.disabled=false;
            if (prefill.pattern_id) { selPat.value=prefill.pattern_id; selPat.dispatchEvent(new Event('change')); }
        });
    });

    selPat.addEventListener('change', function() {
        if(!this.value||!selProd.value||!getFromLoc())return;
        post('stock/get-transfer-stock',{product_id:selProd.value,pattern_id:this.value,from_location_id:getFromLoc()}).then(function(data){
            selVar.innerHTML='<option value="">-- Variation --</option>';
            if(!(data.variations||[]).length){selVar.innerHTML='<option value="">No stock</option>';return;}
            data.variations.forEach(function(v){
                var opt=document.createElement('option');
                opt.value=v.id; opt.dataset.avail=v.available_qty;
                opt.textContent=v.name+(v.size?' '+v.size+'"':'')+' ('+v.available_qty+' avail)';
                if(prefill.variation_id&&v.id==prefill.variation_id) opt.selected=true;
                selVar.appendChild(opt);
            });
            selVar.disabled=false;
            if(prefill.variation_id) selVar.dispatchEvent(new Event('change'));
        });
    });

    selVar.addEventListener('change', function(){
        var opt=this.options[this.selectedIndex];
        var avail=opt?opt.dataset.avail:'';
        availSp.textContent=avail?'/'+avail:'';
        if(avail) qtyInp.max=avail;
    });

    tr.querySelector('.btnRemove').addEventListener('click', function(){ tr.remove(); updateSummary(); });

    document.getElementById('transferItems').appendChild(tr);
    removeEmptyRow();
    updateSummary();

    if (prefill.product_id) {
        selProd.value = prefill.product_id;
        selProd.dispatchEvent(new Event('change'));
    }
    return tr;
}

function removeEmptyRow(){ var e=document.getElementById('emptyRow'); if(e)e.remove(); }

document.getElementById('fromLocation').addEventListener('change', function(){
    document.getElementById('transferItems').innerHTML = this.value
        ? '' : '<tr id="emptyRow"><td colspan="6" class="text-center text-muted py-3"><i class="bi bi-info-circle"></i> Select From Location first</td></tr>';
    document.getElementById('btnAddItem').disabled = !this.value;
    document.getElementById('qrScanMsg').textContent='';
    updateSummary();
});

document.getElementById('btnAddItem').addEventListener('click', function(){
    if(!getFromLoc()){alert('Select From Location first');return;}
    buildRow();
});

// QR lookup
document.getElementById('btnLookupQr').addEventListener('click', function(){ lookupQr(document.getElementById('qrInput').value.trim()); });
document.getElementById('qrInput').addEventListener('keydown', function(e){ if(e.key==='Enter'){e.preventDefault();lookupQr(this.value.trim());} });

function lookupQr(val){
    var msg=document.getElementById('qrScanMsg');
    if(!val){msg.textContent='Enter a QR number first.';return;}
    if(!getFromLoc()){msg.textContent='Select From Location first.';return;}
    msg.textContent='Looking up…';
    post('stock/get-stock-info',{qr_data:val}).then(function(d){
        if(d.error){msg.innerHTML='<span class="text-danger">'+d.error+'</span>';return;}
        post('stock/get-transfer-stock',{product_id:d.product.id,pattern_id:d.pattern.id,from_location_id:getFromLoc()}).then(function(sd){
            var found=(sd.variations||[]).find(function(v){return v.id==d.variation.id;});
            if(!found){msg.innerHTML='<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> No stock of <b>'+escHtml(d.product.name)+'</b> at From Location.</span>';return;}
            buildRow({product_id:d.product.id,product_image:d.product.image||'',pattern_id:d.pattern.id,variation_id:d.variation.id,available_qty:found.available_qty});
            msg.innerHTML='<span class="text-success"><i class="bi bi-check-circle"></i> Added: '+escHtml(d.product.name)+' — '+escHtml(d.variation.name)+'</span>';
            document.getElementById('qrInput').value='';
        });
    });
}

// Camera
var scanStream=null,scanInterval=null;
document.getElementById('btnScanQr').addEventListener('click', function(){
    if(!getFromLoc()){alert('Select From Location first');return;}
    document.getElementById('cameraBox').style.display='';
    var video=document.getElementById('qrVideo');
    navigator.mediaDevices.getUserMedia({video:{facingMode:'environment'}}).then(function(stream){
        scanStream=stream; video.srcObject=stream; video.play();
        scanInterval=setInterval(function(){
            if(video.readyState!==video.HAVE_ENOUGH_DATA)return;
            var canvas=document.getElementById('qrCanvas');
            canvas.width=video.videoWidth; canvas.height=video.videoHeight;
            var ctx=canvas.getContext('2d'); ctx.drawImage(video,0,0,canvas.width,canvas.height);
            var img=ctx.getImageData(0,0,canvas.width,canvas.height);
            var code=jsQR(img.data,img.width,img.height);
            if(code&&code.data){stopScan();document.getElementById('qrInput').value=code.data;lookupQr(code.data);}
        },300);
    }).catch(function(){alert('Camera not available.');stopScan();});
});
function stopScan(){
    clearInterval(scanInterval);
    if(scanStream){scanStream.getTracks().forEach(t=>t.stop());scanStream=null;}
    document.getElementById('cameraBox').style.display='none';
}
document.getElementById('btnStopScan').addEventListener('click', stopScan);

function escHtml(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
</script>
<?= $this->endSection() ?>
