<?php

/**
 * マイグレーション: add_converted_field_to_leads
 * 生成日時: 20260925193541
 *
 * リードの昇格済みフラグ（vtiger_leaddetails.converted）を vtiger_field に登録する。
 * 値は昇格処理（vtws_convertlead）だけが書き換えるため、displaytype=2 の読み取り専用項目とする。
 */
include_once 'vtlib/Vtiger/Module.php';

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260925193541_AddConvertedFieldToLeads extends FRMigrationClass
{
    /**
     * マイグレーションを実行する
     */
    public function process(): void
    {
        $module = Vtiger_Module::getInstance('Leads');
        if (Vtiger_Field::getInstance('converted', $module)) {
            $this->log("項目 converted は登録済みのためスキップしました");
            return;
        }

        $block = Vtiger_Block::getInstance('LBL_LEAD_INFORMATION', $module);

        $field = new Vtiger_Field();
        $field->name = 'converted';
        $field->label = 'Converted';
        $field->table = 'vtiger_leaddetails';
        $field->column = 'converted';
        // カラムは既存（int DEFAULT 0）。addField 内の AddColumn は既存カラムならスキップされる
        $field->columntype = 'INT(1) DEFAULT 0';
        $field->uitype = 56;
        $field->typeofdata = 'C~O';
        $field->displaytype = 2;
        $field->presence = 2;
        $field->masseditable = 0;
        $field->quickcreate = 1;
        $block->addField($field);

        $this->log("マイグレーション add_converted_field_to_leads が正常に完了しました");
    }
}
