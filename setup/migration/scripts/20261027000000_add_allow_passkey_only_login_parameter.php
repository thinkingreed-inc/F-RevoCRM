<?php

/**
 * マイグレーション: システム変数 ALLOW_PASSKEY_ONLY_LOGIN の追加
 * 生成日時: 20261027000000
 */
include_once 'vtlib/Vtiger/Module.php';

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20261027000000_AddAllowPasskeyOnlyLoginParameter extends FRMigrationClass
{
    /**
     * マイグレーションを実行する
     */
    public function process()
    {
        global $adb;

        $key = 'ALLOW_PASSKEY_ONLY_LOGIN';

        // 既に存在する場合は行を作らない（ユーザーが変更した値を上書きしない）
        $exists = $adb->pquery("SELECT 1 FROM vtiger_parameters WHERE `key` = ?", [$key]);
        if ($adb->num_rows($exists) === 0) {
            $record = Settings_Parameters_Record_Model::getInstanceByKey($key);
            $record->set("key", $key);
            $record->set("value", "false");
            $record->set("description", vtranslate('LBL_SETUP_PARAMETER_MESSAGE_ALLOW_PASSKEY_ONLY_LOGIN', 'Users'));
            $record->save();
        }

        // 型と秘匿の指定（値は変えない）
        $adb->pquery("UPDATE vtiger_parameters SET `type` = ?, `secret` = ? WHERE `key` = ?", ['boolean', 0, $key]);

        $this->log("システム変数 {$key} を追加しました");
    }
}
