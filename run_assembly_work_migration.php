<?php
$db = new mysqli('localhost', 'root', '', 'psboffic1_psboffi1_joms');
if ($db->connect_error) {
    die('Connect: ' . $db->connect_error . "\n");
}

$sql = file_get_contents(__DIR__ . '/app/Database/Migrations/assembly_work_module.sql');
if ($sql === false) {
    die("Could not read assembly_work_module.sql\n");
}

$db->query('SET FOREIGN_KEY_CHECKS=0');
$db->query('DROP TABLE IF EXISTS assembly_work_summary');
$db->query('DROP TABLE IF EXISTS assembly_work_receive');
$db->query('DROP TABLE IF EXISTS assembly_work_issue');
$db->query('DROP TABLE IF EXISTS assembly_work_order');
$db->query('DROP TABLE IF EXISTS assembly_work');
$db->query('DROP TABLE IF EXISTS finished_goods_master');
$db->query('SET FOREIGN_KEY_CHECKS=1');

$statements = array_filter(array_map('trim', preg_split('/;\s*(?:\r?\n|$)/', $sql)));

foreach ($statements as $index => $statement) {
    if ($statement === '') {
        continue;
    }
    if (!$db->query($statement)) {
        die('ERROR in statement '.($index + 1).': '.$db->error."\nSQL: ".$statement."\n");
    }
}

echo "ASSEMBLY WORK TABLES OK\n";