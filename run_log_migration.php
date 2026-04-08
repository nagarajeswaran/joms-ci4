<?php
$db = new mysqli('localhost','root','','psboffic1_psboffi1_joms');
if($db->connect_error){die('Connect: '.$db->connect_error."\n");}
$sql = "CREATE TABLE IF NOT EXISTS part_batch_stock_log (
  id             INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  part_batch_id  INT NOT NULL,
  entry_type     ENUM('in','out') NOT NULL,
  reason         VARCHAR(50) NOT NULL,
  weight_g       DECIMAL(10,4) NOT NULL DEFAULT 0,
  qty            INT NOT NULL DEFAULT 0,
  piece_weight_g DECIMAL(10,4) DEFAULT NULL,
  touch_pct      DECIMAL(8,4) DEFAULT 0,
  stamp_id       INT DEFAULT NULL,
  notes          TEXT DEFAULT NULL,
  created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (part_batch_id) REFERENCES part_batch(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$r = $db->query($sql);
echo $r ? "TABLE OK\n" : 'ERROR: '.$db->error."\n";
