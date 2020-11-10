<?php 
require_once('setup/utils/FRPrint.php');

class FRMenuSetting {

    // 配列に入ったモジュール名のモジュールをメニューに表示する
    // それ以外のモジュールはメニューから外される
    public static function apply(array $moduleNames) {
        global $adb;

        if(count($moduleNames) == 0) {
            throw new Exception();
        }

        FRPrint::out("Menu setting to ".print_r($moduleNames, true));

        $adb->query("UPDATE vtiger_tab set tabsequence = -1");

        $cnt = 1;
        foreach($moduleNames as $module) {
            $adb->pquery("UPDATE vtiger_tab set tabsequence = $cnt where name = ?", array($module));
            $cnt++;
        }

        FRPrint::out("Menu setting completed.");
    }
}
