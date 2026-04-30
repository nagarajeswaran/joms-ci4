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
$db->query('SET FOREIGN_KEY_CHECKS=0');
$r1 = $db->query('TRUNCATE TABLE touch_entry') ? 'OK' : 'FAIL: '.$db->error;
$r2 = $db->query('UPDATE touch_serial_config SET last_number=0 WHERE id=1') ? 'OK' : 'FAIL: '.$db->error;
$db->query('SET FOREIGN_KEY_CHECKS=1');
echo "touch_entry: $r1\ntouch_serial_config reset: $r2\nDONE\n";
$db->close();
