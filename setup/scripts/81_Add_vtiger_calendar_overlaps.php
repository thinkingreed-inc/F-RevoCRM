<?php
$Vtiger_Utils_Log = true;
include_once('include/utils/CommonUtils.php');

global $adb;

$adb->pquery('CREATE TABLE IF NOT EXISTS vtiger_calendar_overlaps (
                userid INT NOT NULL,
                overlap_userid INT NOT NULL,
                PRIMARY KEY (userid, overlap_userid)
                ) ENGINE=InnoDB DEFAULT CHARSET=UTF8;',array());
