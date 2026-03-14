<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<h5 class="mb-3">Scan Batch QR Code</h5>
<div class="card" style="max-width:450px">
<div class="card-body">
<div id="reader" style="width:100%;max-width:400px;margin:0 auto"></div>
<p class="text-muted mt-3 text-center">Point camera at a batch QR code to open batch record</p>
</div>
</div>
<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
var scanner = new Html5Qrcode("reader");
scanner.start(
    { facingMode: "environment" },
    { fps: 10, qrbox: { width: 250, height: 250 } },
    function(decodedText) {
        scanner.stop();
        window.location.href = decodedText;
    },
    function(err) {}
);
</script>
<?= $this->endSection() ?>
