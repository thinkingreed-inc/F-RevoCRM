<?php

/**
 * マイグレーション: add_lead_convert_assign_current_user_parameter
 * 生成日時: 20260929112019
 *
 * リード昇格画面の担当の初期値をログインユーザーにするかのフラグを追加する — #1864
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
require_once 'modules/Settings/Parameters/models/Record.php';
require_once 'modules/Leads/models/ConvertLeadAssignee.php';

class Migration20260929112019_AddLeadConvertAssignCurrentUserParameter extends FRMigrationClass
{
    public function process(): void
    {
        $key = Leads_ConvertLeadAssignee_Model::PARAMETER_KEY;

        // 既に登録済み（手で追加した場合など）なら値を上書きしない
        $existing = Settings_Parameters_Record_Model::getInstanceByKey($key);
        if ($existing->getId()) {
            $this->log("パラメータ {$key} は登録済みのため追加しません");
            return;
        }

        $record = new Settings_Parameters_Record_Model();
        $record->set('key', $key);
        $record->set('value', 'false');
        $record->set('description', vtranslate('LBL_SETUP_PARAMETER_MESSAGE_LEAD_CONVERT_ASSIGN_CURRENT_USER', 'Leads'));
        $record->save();

        $this->log("パラメータ {$key} を追加しました");
    }
}
