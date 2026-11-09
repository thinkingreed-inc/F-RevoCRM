<?php

/**
 * 詳細画面の「改版」「版履歴」ボタンを出してよいかの判定（vtiger_links の handler から呼ぶ）
 */
class RecordVersion_LinkVisibility_Helper
{
    /**
     * 「改版」：版管理「はい」のモジュールで、レコードの編集権限がある利用者だけ
     */
    public static function canRevise(Vtiger_LinkData $linkData): bool
    {
        return self::isAllowed($linkData, 'EditView');
    }

    /**
     * 「版履歴」：版管理「はい」のモジュールで、レコードの参照権限がある利用者
     */
    public static function canViewHistory(Vtiger_LinkData $linkData): bool
    {
        return self::isAllowed($linkData, 'DetailView');
    }

    private static function isAllowed(Vtiger_LinkData $linkData, string $action): bool
    {
        $recordId = RecordVersion_Util_Helper::toInt($linkData->getInputParameter('record'));
        if ($recordId <= 0) {
            return false;
        }
        $moduleName = RecordVersion_Util_Helper::toStr(getSalesEntityType($recordId));
        if ($moduleName === '' || !RecordVersion_Config_Model::isEnabled($moduleName)) {
            return false;
        }
        return (bool) Users_Privileges_Model::isPermitted($moduleName, $action, $recordId);
    }
}
