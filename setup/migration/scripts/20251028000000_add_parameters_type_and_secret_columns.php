<?php
/**
 * マイグレーション: add_parameters_type_and_secret_columns
 * 生成日時: 20251028000000
 *
 * システム変数テーブルに type と secret カラムを追加
 * - type: 値の型（boolean, integer, string）
 * - secret: シークレットフラグ（1の場合、値は画面でマスク表示される）
 *
 * Settings_Parameters_Record_Model::save() は type / secret へ書き込むため、
 * save() でシステム変数を追加する最初のマイグレーション（20251028162441）より前に実行する。
 * 既存パラメータへの型の設定は、全パラメータの追加後に 20261014043627_set_parameters_type で行う。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20251028000000_AddParametersTypeAndSecretColumns extends FRMigrationClass {

    public function process() {
        global $adb;

        // type カラムの追加
        $this->addTypeColumn($adb);

        // secret カラムの追加
        $this->addSecretColumn($adb);

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
}
