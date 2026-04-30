<?php
// Temporary cleanup script - DELETE AFTER USE
if (($_GET['token'] ?? '') !== 'xK9mZ2pQ7wL4') {
    http_response_code(403);
    die('Forbidden');
}

// Read DB credentials from .env
$envFile = __DIR__ . '/.env';
$env = [];
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            [$key, $val] = array_map('trim', explode('=', $line, 2));
            $env[$key] = $val;
        }
    }
}

$host = $env['database.default.hostname'] ?? 'localhost';
$user = $env['database.default.username'] ?? '';
$pass = $env['database.default.password'] ?? '';
$name = $env['database.default.database'] ?? '';

header('Content-Type: text/plain');

if (!$user || !$name) {
    echo "DB config:\n";
    foreach ($env as $k => $v) {
        if (stripos($k, 'database') !== false) {
            echo "  $k = $v\n";
        }
    }
    die("\nCould not find DB credentials in .env");
}

$db = new mysqli($host, $user, $pass, $name);
if ($db->connect_error) {
    die('DB Connect Error: ' . $db->connect_error);
}

echo "Connected to: $name@$host\n\n";

$tables = [
    'karigar_ledger_conversion',
    'karigar_ledger',
    'assembly_work_summary',
    'assembly_work_receive',
    'assembly_work_issue',
    'assembly_work_order',
    'assembly_work',
    'part_batch_stock_log',
    'part_order_receive',
    'part_order_issue',
    'part_batch',
    'part_batch_sequence',
    'part_order',
    'gatti_stock_log',
    'melt_job_receive',
    'melt_job_input',
    'gatti_stock',
    'melt_job',
    'raw_material_batch_log',
    'raw_material_batch',
    'byproduct_stock',
    'kacha_lot',
    'order_touch',
    'order_item_qty',
    'order_items',
    'orders',
    'client',
    'stamp',
    'stock_transfer_item',
    'stock_transfer',
    'stock_transaction',
    'product_stock',
];

$db->query('SET FOREIGN_KEY_CHECKS = 0');

$ok = 0;
$fail = 0;

foreach ($tables as $t) {
    if ($db->query("TRUNCATE TABLE `$t`")) {
        echo "OK: $t\n";
        $ok++;
    } else {
        echo "FAIL: $t - " . $db->error . "\n";
        $fail++;
    }
}

$db->query('SET FOREIGN_KEY_CHECKS = 1');
$db->close();

echo "\n--- DONE: $ok/" . count($tables) . " tables truncated, $fail failures ---\n";
