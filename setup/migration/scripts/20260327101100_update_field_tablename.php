<?php
/**
 * マイグレーション: update_field_tablename
 * 生成日時: 20260327101100
 *
 * 75_Update_CRMEntity.php で CRMEntity->id（レコードID）を tabid として
 * 使用していたため、vtiger_field.tablename の更新が実行されなかった問題を修正する。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260327101100_UpdateFieldTablename extends FRMigrationClass {

    public function process() {
        $db = PearDatabase::getInstance();

        // エンティティモジュール一覧を取得（name, tabid）
        $ignoreModules = array('SMSNotifier', 'PBXManager', 'Webmails');
        $result = $db->pquery(
            'SELECT name, tabid FROM vtiger_tab WHERE isentitytype = ? AND name NOT IN (' . generateQuestionMarks($ignoreModules) . ')',
            array(1, $ignoreModules)
        );
        if ($result === false) {
            throw new Exception('vtiger_tab の取得に失敗しました');
        }

        $modules = array();
        while ($row = $db->fetchByAssoc($result)) {
            $modules[] = $row;
        }

        $totalUpdated = 0;

        foreach ($modules as $moduleInfo) {
            $moduleName = $moduleInfo['name'];
            $tabid = $moduleInfo['tabid'];

            // CRMEntityからモジュールのベーステーブル名を取得
            $entity = CRMEntity::getInstance($moduleName);
            $baseTable = $entity->table_name;
            if (empty($baseTable)) continue;

            // vtiger_crmentityからbaseTableへtablenameを付け替える
            // baseTableを共有するモジュール（Calendar/Events/Emails等）でもtabidが異なるため個別に更新が必要
            $updateResult = $db->pquery(
                'UPDATE vtiger_field SET tablename = ? WHERE tabid = ? AND tablename = ?',
                array($baseTable, $tabid, 'vtiger_crmentity')
            );
            if ($updateResult === false) {
                throw new Exception("vtiger_field の更新に失敗しました: モジュール=$moduleName, tabid=$tabid");
            }

            $affectedRows = $db->getAffectedRowCount($updateResult);
            $this->log("モジュール: $moduleName (tabid=$tabid), テーブル: $baseTable, 更新件数: $affectedRows");

            $totalUpdated += $affectedRows;
        }

        $this->log("合計 $totalUpdated 件の vtiger_field レコードを更新しました");
    }
}