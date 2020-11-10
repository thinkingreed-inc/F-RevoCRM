<?php 
require_once('setup/utils/FRPrint.php');

class FRModuleSetting {
    // モジュールを非表示にする（有効/無効は変わらない）
    public static function hide(array $moduleNames) {
        global $adb;

        if(count($moduleNames) == 0) {
            throw new Exception();
        }

        FRPrint::out("Modules hide ".print_r($moduleNames, true));

        foreach($moduleNames as $module) {
            $adb->pquery("UPDATE vtiger_tab set parent = NULL where name = ?", array($module));
        }

        FRPrint::out("Modules hide completed.");
    }

    // モジュールを表示にする（有効/無効は変わらない）
    public static function show(array $moduleNames, $category = 'Sales') {
        global $adb;

        if(count($moduleNames) == 0) {
            throw new Exception();
        }

        FRPrint::out("Modules show ".print_r($moduleNames, true));

        foreach($moduleNames as $module) {
            $adb->pquery("UPDATE vtiger_tab set parent = ? where name = ?", array($category, $module));
        }

        FRPrint::out("Modules show completed.");
    }
    
    //モジュールを有効にする（表示/非表示は変わらない）
    public static function enable(array $moduleNames) {
        global $adb;

        if(count($moduleNames) == 0) {
            throw new Exception();
        }

        FRPrint::out("Module enable ".print_r($moduleNames, true));

        foreach($moduleNames as $module) {
            $adb->pquery("UPDATE vtiger_tab set presence = 0 where name = ?", array($module));
        }

        FRPrint::out("Module enable completed.");
    }

    //モジュールを無効にする（表示/非表示は変わらない）
    public static function disable(array $moduleNames) {
        global $adb;

        if(count($moduleNames) == 0) {
            throw new Exception();
        }

        FRPrint::out("Module disable ".print_r($moduleNames, true));

        foreach($moduleNames as $module) {
            $adb->pquery("UPDATE vtiger_tab set presence = 1 where name = ?", array($module));
        }

        FRPrint::out("Module disable completed.");
    }
}
