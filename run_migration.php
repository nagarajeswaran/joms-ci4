<?php
$db = new mysqli('localhost','root','','psboffic1_psboffi1_joms');
if($db->connect_error){die('Connect: '.$db->connect_error."\n");}
$r1 = $db->query('ALTER TABLE part_batch ADD COLUMN weight_in_stock_g DECIMAL(10,4) NOT NULL DEFAULT 0.0000 AFTER qty_in_stock');
if(!$r1) echo 'ALTER: '.$db->error."\n"; else echo "ALTER OK\n";
$r2 = $db->query('UPDATE part_batch SET weight_in_stock_g = qty_in_stock * COALESCE(piece_weight_g,0) WHERE piece_weight_g IS NOT NULL AND piece_weight_g > 0');
if(!$r2) echo 'BACKFILL: '.$db->error."\n"; else echo 'BACKFILL OK rows='.$db->affected_rows."\n";
