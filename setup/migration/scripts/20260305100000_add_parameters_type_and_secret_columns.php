<?php
/**
 * マイグレーション: add_parameters_type_and_secret_columns
 * 生成日時: 20260305100000
 * 
 * システム変数テーブルに type と secret カラムを追加
 * - type: 値の型（boolean, integer, string）
 * - secret: シークレットフラグ（1の場合、値は画面でマスク表示される）
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260305100000_AddParametersTypeAndSecretColumns extends FRMigrationClass {
    
    public function process() {
        global $adb;
        
        // type カラムの追加
        $this->addTypeColumn($adb);
        
        // secret カラムの追加
        $this->addSecretColumn($adb);
        
        // 既存データの型を設定
        $this->updateExistingDataTypes($adb);

        $this->log("マイグレーション add_parameters_type_and_secret_columns が正常に完了しました");
    }
    
    /**
     * type カラムを追加
     */
    private function addTypeColumn($adb) {
        // カラムが存在するかチェック
        $result = $adb->pquery("SHOW COLUMNS FROM vtiger_parameters LIKE 'type'", array());
        if ($adb->num_rows($result) > 0) {
            $this->log("type カラムは既に存在します");
            return;
        }
        
        // value カラムの後に type カラムを追加
        $adb->pquery("ALTER TABLE vtiger_parameters ADD COLUMN `type` VARCHAR(20) NOT NULL DEFAULT 'string' AFTER `value`", array());
        $this->log("type カラムを追加しました");
    }
    
    /**
     * secret カラムを追加
     */
    private function addSecretColumn($adb) {
        // カラムが存在するかチェック
        $result = $adb->pquery("SHOW COLUMNS FROM vtiger_parameters LIKE 'secret'", array());
        if ($adb->num_rows($result) > 0) {
            $this->log("secret カラムは既に存在します");
            return;
        }
        
        // type カラムの後に secret カラムを追加
        $adb->pquery("ALTER TABLE vtiger_parameters ADD COLUMN `secret` TINYINT(1) NOT NULL DEFAULT 0 AFTER `type`", array());
        $this->log("secret カラムを追加しました");
    }
    
    /**
     * 既存データの型を設定
     */
    private function updateExistingDataTypes($adb) {
        // boolean型のパラメータ
        $booleanKeys = array(
            'FORCE_MULTI_FACTOR_AUTH',
            'SHOW_SCHEDULE_CONFIRM_FLAG',
            'CALENDAR_REMEMBER_FEED_SELECTION'
        );
        
        // integer型のパラメータ
        $integerKeys = array(
            'USER_LOCK_COUNT',
            'USER_LOCK_TIME',
            'IMPORT_MAX_HISTORY_COUNT'
        );
        
        // boolean型に更新
        foreach ($booleanKeys as $key) {
            $adb->pquery("UPDATE vtiger_parameters SET `type` = 'boolean' WHERE `key` = ?", array($key));
        }
        $this->log("boolean型のパラメータを設定しました: " . implode(', ', $booleanKeys));
        
        // integer型に更新
        foreach ($integerKeys as $key) {
            $adb->pquery("UPDATE vtiger_parameters SET `type` = 'integer' WHERE `key` = ?", array($key));
        }
        $this->log("integer型のパラメータを設定しました: " . implode(', ', $integerKeys));
    }
}

$migration = new Migration20260305100000_AddParametersTypeAndSecretColumns();
$migration->process();
