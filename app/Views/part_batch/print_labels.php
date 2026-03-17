<?php
$paperWmm = ($paper === 'A5') ? 148 : 210;
$paperHmm = ($paper === 'A5') ? 210 : 297;
$cellW    = round($paperWmm / $cols, 3);
$cellH    = round($paperHmm / $rows, 3);
$qrSize   = max(20, (int)round(min($cellW, $cellH) * 0.55));
$fontSize = max(6, (int)round($cellH * 0.14));
$batchFs  = max(7, (int)round($cellH * 0.18));
?><!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Batch Labels</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
@media print {
    @page { margin: 0; size: <?= $paperWmm ?>mm <?= $paperHmm ?>mm portrait; }
    body  { margin: 0; }
    .no-print { display: none; }
}
body { font-family: Arial, sans-serif; background: #f5f5f5; }
.no-print { padding: 10px 15px; background: #fff; border-bottom: 1px solid #ccc; }
.no-print button { padding: 6px 18px; font-size: 13px; margin-right: 8px; cursor: pointer; }
.label-page {
    width: <?= $paperWmm ?>mm;
    height: <?= $paperHmm ?>mm;
    display: grid;
    grid-template-columns: repeat(<?= (int)$cols ?>, <?= $cellW ?>mm);
    grid-template-rows: repeat(<?= (int)$rows ?>, <?= $cellH ?>mm);
    gap: 0;
    overflow: hidden;
    background: #fff;
}
.label-cell {
    width: <?= $cellW ?>mm;
    height: <?= $cellH ?>mm;
    border: 0.4pt solid #aaa;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    padding: 1mm;
    text-align: center;
}
.label-cell .part-name {
    font-size: <?= $fontSize ?>pt;
    font-weight: bold;
    line-height: 1.1;
    max-width: 100%;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
.label-cell .batch-no {
    font-size: <?= $batchFs ?>pt;
    font-weight: bold;
    color: #111;
    line-height: 1.1;
}
.label-cell img {
    display: block;
    width: <?= $qrSize ?>mm;
    height: <?= $qrSize ?>mm;
    flex-shrink: 0;
}
.label-cell .pc-wt {
    font-size: <?= $fontSize ?>pt;
    border-top: 0.5pt dashed #aaa;
    padding-top: 0.5mm;
    width: 100%;
    text-align: center;
    line-height: 1.1;
}
</style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()">&#128438; Print</button>
    <button onclick="window.close()">Close</button>
    <span style="font-size:12px;color:#666;margin-left:10px;">
        Paper: <?= esc($paper) ?> &nbsp;|&nbsp;
        Cols: <?= (int)$cols ?> &nbsp;|&nbsp;
        Rows: <?= (int)$rows ?> &nbsp;|&nbsp;
        Cell: <?= $cellW ?>mm &times; <?= $cellH ?>mm &nbsp;|&nbsp;
        Labels: <?= count($batches) ?>
    </span>
</div>
<div class="label-page">
<?php foreach ($batches as $b): ?>
<div class="label-cell">
    <div class="part-name"><?= esc($b['part_name']) ?></div>
    <div class="batch-no"><?= esc($b['batch_number']) ?></div>
    <?php if (!empty($b['qr_base64']) && strpos($b['qr_base64'], 'ERROR') === false): ?>
        <img src="<?= $b['qr_base64'] ?>" alt="QR">
    <?php else: ?>
        <div style="width:<?= $qrSize ?>mm;height:<?= $qrSize ?>mm;border:0.5pt solid #ccc;display:flex;align-items:center;justify-content:center;font-size:6pt;color:#999;">QR?</div>
    <?php endif; ?>
    <div class="pc-wt">Pc Wt: ________ g</div>
</div>
<?php endforeach; ?>
</div>
</body>
</html>
