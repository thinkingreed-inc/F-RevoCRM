<?php
/**
 * マイグレーション: add_displayname_to_lineitems
 * 生成日時: 20260807131934
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260807131934_AddDisplaynameToLineitems extends FRMigrationClass {
    
    /**
     * マイグレーションを実行する
     * 見積・受注・発注・請求の品目(LBL_ITEM_DETAILS)に「表示名称」列を追加する
     */
    public function process() {
        $moduleNames = array('Quotes', 'PurchaseOrder', 'SalesOrder', 'Invoice');
        foreach ($moduleNames as $moduleName) {
            $moduleInstance = Vtiger_Module::getInstance($moduleName);
            $blockInstance = Vtiger_Block::getInstance('LBL_ITEM_DETAILS', $moduleInstance);

            $field = new Vtiger_Field();
            $field->name        = 'displayname';
            $field->label       = 'LBL_DISPLAY_NAME';
            $field->table       = 'vtiger_inventoryproductrel';
            $field->column      = 'displayname';
            $field->columntype  = 'VARCHAR(255)';
            $field->uitype      = 19;
            $field->typeofdata  = 'V~O';
            $field->readonly    = 0;
            $field->displaytype = 5;
            $field->masseditable = 1;
            $blockInstance->addField($field);

            $this->log("{$moduleName} に displayname フィールドを追加しました");
        }

        $this->log("マイグレーション add_displayname_to_lineitems が正常に完了しました");
    }
}