<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h5 class="mb-3">Scan Batch Barcode</h5>
<div class="card" style="max-width:450px">
<div class="card-body">
<div id="reader" style="width:100%;max-width:400px;margin:0 auto"></div>
<p id="scanStatus" class="text-muted mt-3 text-center">Point camera at a batch barcode to open batch record</p>
</div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/@zxing/browser@0.1.4/umd/index.min.js"></script>
<script>
(function() {
    var codeReader = new ZXingBrowser.BrowserMultiFormatReader();
    var baseUrl = '<?= base_url() ?>';

    codeReader.decodeFromVideoDevice(null, 'reader', function(result, err) {
        if (!result) return;
        var batchNo = result.getText();
        document.getElementById('scanStatus').textContent = 'Found: ' + batchNo + ' — looking up...';
        codeReader.reset();

        fetch(baseUrl + 'part-stock/lookup-batch?q=' + encodeURIComponent(batchNo))
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (data.id) {
                window.location.href = baseUrl + 'part-stock/batch/' + data.id;
            } else {
                document.getElementById('scanStatus').textContent = 'Not found: ' + batchNo;
            }
        });
    });
})();
</script>
<?= $this->endSection() ?>
