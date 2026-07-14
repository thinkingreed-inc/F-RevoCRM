<?php
/**
 * マイグレーション: deleting_field_reference_from_Contacts
 * 生成日時: 20260427040814
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260427040814_DeletingFieldReferenceFromContacts extends FRMigrationClass {
    
    /**
     * マイグレーションを実行する
     * 顧客担当者モジュールの「参照」フィールドを削除し、DBカラムも削除
     */
    public function process() {
        $adb = PearDatabase::getInstance();
        $moduleName = 'Contacts';
        $fieldName = 'reference';
        $tableName = 'vtiger_contactdetails';
        
        $this->log("顧客担当者モジュールから '$fieldName' フィールドの削除を開始します");
        
        $moduleInstance = Vtiger_Module::getInstance($moduleName);
        if ($moduleInstance) {
            $fieldInstance = Vtiger_Field::getInstance($fieldName, $moduleInstance);
            if ($fieldInstance) {
                $fieldInstance->delete();
                $this->log("vtlibを使用してフィールド '$fieldName' を削除しました");
            } else {
                $this->log("フィールド '$fieldName' が $moduleName モジュールに見つかりませんでした（既に対象外の可能性があります）");
                
                // フォールバック: vtlibで見つからないが、DBに直接残っている場合のクリーンアップ
                $result = $adb->pquery("SELECT fieldid FROM vtiger_field WHERE tabid = ? AND fieldname = ?", array($moduleInstance->id, $fieldName));
                if ($adb->num_rows($result) > 0) {
                    $fieldId = $adb->query_result($result, 0, 'fieldid');
                    $adb->pquery("DELETE FROM vtiger_field WHERE fieldid = ?", array($fieldId));
                    $adb->pquery("DELETE FROM vtiger_profile2field WHERE fieldid = ?", array($fieldId));
                    $adb->pquery("DELETE FROM vtiger_def_org_field WHERE fieldid = ?", array($fieldId));
                    $this->log("DBから直接フィールド '$fieldName' (ID: $fieldId) 関連データを削除しました");
                }
            }
        }
        
        // データベースカラムの削除
        $result = $adb->pquery("SHOW COLUMNS FROM $tableName LIKE ?", array($fieldName));
        if ($adb->num_rows($result) > 0) {
            $adb->pquery("ALTER TABLE $tableName DROP COLUMN $fieldName", array());
            $this->log("テーブル $tableName からカラム '$fieldName' を削除しました");
        } else {
            $this->log("テーブル $tableName にカラム '$fieldName' は存在しませんでした");
        }
        
        $this->log("マイグレーション deleting_field_reference_from_Contacts が正常に完了しました");
    }
}
