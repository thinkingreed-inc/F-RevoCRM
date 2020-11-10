<?php

require_once('setup/utils/FRPrint.php');

class FRFieldSetting {

    // 対象モジュールのフィールドを非表示にする
    public static function hide(Vtiger_Module $module, array $fieldnames) {
        FRPrint::out("Field hide ".print_r($fieldnames, true));
        
        global $adb;

        foreach($fieldnames as $name) {
            $sql = "UPDATE vtiger_field SET presence = 1 where tabid = ? AND fieldname = ?";
            $adb->pquery($sql, array($module->id, $name));
        }

        FRPrint::out("Field hide complated.");
    }

    // 対象モジュールのフィールドを並び変える
    public static function sort(Vtiger_Module $module, array $fieldnames) {
        FRPrint::out("Field sort ".print_r($fieldnames, true));

        global $adb;

        $cnt = 1;
        foreach($fieldnames as $name) {
            $sql = "UPDATE vtiger_field SET sequence = $cnt where tabid = ? AND fieldname = ?";
            $adb->pquery($sql, array($module->id, $name));
            $cnt++;
        }

        FRPrint::out("Field sort complated.");
    }

    public static function quickCreateEnable(Vtiger_Module $module, array $fieldnames) {
        FRPrint::out("Field quickcreate enable ".print_r($fieldnames, true));

        global $adb;

        $adb->pquery("UPDATE vtiger_field SET quickcreate = 1 where tabid = ?", array($module->id));

        foreach($fieldnames as $name) {
            $sql = "UPDATE vtiger_field SET quickcreate = 0 where tabid = ? AND fieldname = ?";
            $adb->pquery($sql, array($module->id, $name));
        }

        FRPrint::out("Field quickcreate enable complated.");
    }

    public static function summaryEnable(Vtiger_Module $module, array $fieldnames) {
        FRPrint::out("Field summaryfield enable ".print_r($fieldnames, true));

        global $adb;

        $adb->pquery("UPDATE vtiger_field SET summaryfield = 0 where tabid = ?", array($module->id));

        foreach($fieldnames as $name) {
            $sql = "UPDATE vtiger_field SET summaryfield = 1 where tabid = ? AND fieldname = ?";
            $adb->pquery($sql, array($module->id, $name));
        }

        FRPrint::out("Field summaryfield enable complated.");
    }

    public static function massEditEnable(Vtiger_Module $module, array $fieldnames) {
        FRPrint::out("Field masseditable enable ".print_r($fieldnames, true));

        global $adb;

        $adb->pquery("UPDATE vtiger_field SET masseditable = 2 where tabid = ?", array($module->id));

        foreach($fieldnames as $name) {
            $sql = "UPDATE vtiger_field SET masseditable = 1 where tabid = ? AND fieldname = ?";
            $adb->pquery($sql, array($module->id, $name));
        }

        FRPrint::out("Field masseditable enable complated.");
    }
}
