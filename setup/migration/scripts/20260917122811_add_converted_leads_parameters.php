<?php

/**
 * マイグレーション: add_converted_leads_parameters
 * 生成日時: 20260917122811
 *
 * 昇格済みリードの表示／再昇格を制御するパラメーターを追加する。
 * いずれも既定値は 'false' で、従来どおりの挙動を維持する。
 */
include_once 'vtlib/Vtiger/Module.php';

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260917122811_AddConvertedLeadsParameters extends FRMigrationClass
{
    /**
     * マイグレーションを実行する
     */
    public function process(): void
    {
        $parameters = [
            Leads_ConvertSetting_Model::SHOW_CONVERTED_LEADS => 'LBL_SETUP_PARAMETER_MESSAGE_SHOW_CONVERTED_LEADS',
            Leads_ConvertSetting_Model::ALLOW_RECONVERT_LEAD => 'LBL_SETUP_PARAMETER_MESSAGE_ALLOW_RECONVERT_LEAD',
        ];

        foreach ($parameters as $key => $descriptionLabel) {
            $record = Settings_Parameters_Record_Model::getInstanceByKey($key);
            if (!empty($record->getId())) {
                // すでに登録済みの場合は利用者が設定した値を上書きしない
                $this->log("パラメーター {$key} は登録済みのためスキップしました");
                continue;
            }

            $record->set("key", $key);
            $record->set("value", "false");
            $record->set("description", vtranslate($descriptionLabel, 'Leads'));
            $record->save();
            $this->log("パラメーター {$key} を追加しました");
        }

        $this->log("マイグレーション add_converted_leads_parameters が正常に完了しました");
    }
}
