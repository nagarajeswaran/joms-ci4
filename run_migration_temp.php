<?php
if (($_GET['token'] ?? '') !== 'xK9mZ2pQ7wL4') { http_response_code(403); die('Forbidden'); }
$env = [];
foreach (file(__DIR__.'/.env', FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $l) {
    $l = trim($l); if (!$l || $l[0]==='#') continue;
    if (strpos($l,'=')!==false) { [$k,$v]=array_map('trim',explode('=',$l,2)); $env[$k]=$v; }
}
$db = new mysqli($env['database.default.hostname']??'localhost',$env['database.default.username']??'',$env['database.default.password']??'',$env['database.default.database']??'');
if ($db->connect_error) die('DB error: '.$db->connect_error);
header('Content-Type: text/plain');

$sql = file_get_contents(__DIR__.'/add_audit_columns.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));
$ok = 0; $fail = 0;
foreach ($statements as $stmt) {
    if (!$stmt || stripos($stmt, 'SELECT') === 0) continue;
    if ($db->query($stmt)) { $ok++; } else { echo "FAIL: " . substr($stmt, 0, 80) . " - " . $db->error . "\n"; $fail++; }
}
$db->close();
echo "\nDONE: $ok OK, $fail failures\n";
