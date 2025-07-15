<?php

$Vtiger_Utils_Log = true;
include_once('vtlib/Vtiger/Menu.php');
include_once('vtlib/Vtiger/Module.php');
include_once('modules/PickList/DependentPickListUtils.php');
include_once('modules/ModTracker/ModTracker.php');
include_once('include/utils/CommonUtils.php');

global $adb, $log;

$result = $adb->query("SHOW TABLES LIKE 'vtiger_user_credentials'");
if($adb->num_rows($result) == 0) {
    $adb->query("
        CREATE TABLE `vtiger_user_credentials` (
        `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
        `userid` int NOT NULL,
        `type` varchar(7) NOT NULL,
        `device_name` varchar(64) DEFAULT NULL,
        `totp_secret` varchar(32) DEFAULT NULL,
        `passkey_credential` json DEFAULT NULL,
        `signature_count` tinyint(1) NOT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
    ");
}

$result = $adb->query("SHOW TABLES LIKE 'vtiger_user_lock'");
if($adb->num_rows($result) == 0) {
    // ユーザーロックテーブルが存在しない場合は作成
    $adb->query("
        CREATE TABLE `vtiger_user_lock` (
        `userid` int NOT NULL,
        `locktime` datetime NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    ");
}
