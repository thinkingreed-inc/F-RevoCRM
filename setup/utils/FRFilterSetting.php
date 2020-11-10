<?php

require_once('setup/utils/FRPrint.php');

class FRFilterSetting {

    // 対象モジュールの全てのフィルターを削除する
    public static function deleteAll(Vtiger_Module $module) {
        FRPrint::out("Filter delete to ".$module->name);

        $filter = new Vtiger_Filter();
        $filter->deleteForModule($module);//全てのフィルターが対象

        FRPrint::out("Filter delete complated.");
    }

    // 対象モジュールにフィルタを追加する
    public static function add(Vtiger_Module $module, $name, array $fieldNames, $isDefault = false) {

        FRPrint::out("Filter setting to ".$module->name." ".$name."(default=$isDefault) ".print_r($fieldNames, true));

        $filter = new Vtiger_Filter();
        $filter->name = $name;
        $filter->isdefault = true;
        $filter->save($module);

        $cnt = 1;
        $field_list = array();
        foreach($fieldNames as $name) {
            $field = Vtiger_Field::getInstance($name, $module);
            $filter->addField($field, $cnt);
            $cnt++;
        }

        FRPrint::out("Filter setting complated.");
    }
}
