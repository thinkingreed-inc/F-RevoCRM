<?php
require_once 'include/utils/CommonUtils.php';

echo basename(__FILE__)." : Start\n";
$from_block = strtotime("now");

$db = PearDatabase::getInstance();
$sql = "
    CREATE TABLE IF NOT EXISTS vtiger_execute_lineitem (
        parent_id INT(19) NOT NULL,
        executed_at DATETIME DEFAULT NULL,
        PRIMARY KEY (parent_id)
    ) ENGINE=InnoDB;
";

$db->query($sql);

$to_block = strtotime("now");
echo basename(__FILE__)." : ".date_diff(new DateTime(date("H:i:s", $from_block)), new DateTime(date("H:i:s", $to_block)))->format('%H:%I:%S')."\n";