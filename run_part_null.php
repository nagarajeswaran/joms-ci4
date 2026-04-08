<?php
$db = new mysqli('localhost','root','','psboffic1_psboffi1_joms');
if($db->connect_error){die('Connect: '.$db->connect_error."\n");}
$r = $db->query('ALTER TABLE part_batch MODIFY COLUMN part_id INT DEFAULT NULL');
echo $r ? "OK — part_id now nullable\n" : 'ERROR: '.$db->error."\n";
