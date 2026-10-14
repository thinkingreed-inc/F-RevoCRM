<?php
/**
 * マイグレーション: set_parameters_type
 * 生成日時: 20261014043627
 *
 * 既存のシステム変数に型（type）を設定する。
 * システム変数を追加するマイグレーションより後に実行する必要があるため、
 * カラム追加（20251028000000_add_parameters_type_and_secret_columns）とは分けている。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20261014043627_SetParametersType extends FRMigrationClass {

    public function process() {
        if (!$this->checkColumnExists('vtiger_parameters', 'type')) {
            throw new Exception(
                "vtiger_parameters.type カラムがありません。" .
                "先に 20251028000000_add_parameters_type_and_secret_columns を実行してください。"
            );
        }

        $booleanKeys = array(
            'FORCE_MULTI_FACTOR_AUTH',
            'SHOW_SCHEDULE_CONFIRM_FLAG',
            'CALENDAR_REMEMBER_FEED_SELECTION',
        );

        $integerKeys = array(
            'USER_LOCK_COUNT',
            'USER_LOCK_TIME',
            'IMPORT_MAX_HISTORY_COUNT',
        );

        foreach ($booleanKeys as $key) {
            $this->db->pquery("UPDATE vtiger_parameters SET `type` = 'boolean' WHERE `key` = ?", array($key));
        }
        $this->log("boolean型のパラメータを設定しました: " . implode(', ', $booleanKeys));

        foreach ($integerKeys as $key) {
            $this->db->pquery("UPDATE vtiger_parameters SET `type` = 'integer' WHERE `key` = ?", array($key));
        }
        $this->log("integer型のパラメータを設定しました: " . implode(', ', $integerKeys));

        // boolean型は値が true / false の2択しかなく、マスクしても値を推測できるため
        // シークレットを設定しない
        $this->db->pquery("UPDATE vtiger_parameters SET `secret` = 0 WHERE `type` = 'boolean'", array());
        $this->log("boolean型のパラメータのシークレットを解除しました");

        $this->log("マイグレーション set_parameters_type が正常に完了しました");
    }
}
