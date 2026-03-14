!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Batch Labels</title>
<style>
@media print { @page { margin: 10mm; } body { margin: 0; } .no-print { display: none; } }
body { font-family: Arial, sans-serif; font-size: 11px; }
.no-print { margin: 15px; }
.label-grid { display: grid; gap: 4px; }
.label-cell { border: 1px solid #333; padding: 6px; text-align: center; break-inside: avoid; }
.label-cell .part-name { font-weight: bold; font-size: 12px; margin-bottom: 4px; }
.label-cell .batch-no { font-size: 13px; font-weight: bold; color: #222; margin-bottom: 4px; }
.label-cell img { display: block; margin: 4px auto; }
.label-cell .pc-wt { margin-top: 6px; font-size: 11px; border-top: 1px dashed #aaa; padding-top: 4px; }
</style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()" style="padding:8px 20px;font-size:14px;">Print</button>
    <button onclick="window.close()" style="margin-left:10px;padding:8px 20px;">Close</button>
</div>
<div class="label-grid" style="grid-template-columns: repeat(<?= (int)$cols ?>, 1fr);">
<?php foreach ($batches as $b): ?>
<div class="label-cell">
    <div class="part-name"><?= esc($b['part_name']) ?></div>
    <div class="batch-no"><?= esc($b['batch_number']) ?></div>
    <img src="<?= base_url('part-stock/qr/'.$b['id']) ?>" width="90" height="90" alt="QR">
    <div class="pc-wt">Pc Wt: ____________ g</div>
</div>
<?php endforeach; ?>
</div>
</body>
</html>
