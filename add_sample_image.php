<?php
$db = new mysqli('localhost', 'root', '', 'psboffic1_psboffi1_joms');
$r = $db->query('ALTER TABLE touch_entry ADD COLUMN sample_image VARCHAR(255) NULL AFTER touch_shop_name');
echo $r ? 'OK - sample_image column added' : 'Error: ' . $db->error;
