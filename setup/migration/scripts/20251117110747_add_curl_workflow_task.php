<?php
/**
 * マイグレーション: add_curl_workflow_task
 * 生成日時: 20251117110747
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
require_once 'vtlib/Vtiger/Utils.php';
require_once 'modules/com_vtiger_workflow/VTTaskManager.inc';

class Migration20251117110747_AddCurlWorkflowTask extends FRMigrationClass {

    /**
     * マイグレーションを実行する
     * VTCurlTaskをワークフローシステムに登録
     */
    public function process() {
        $db = PearDatabase::getInstance();

        // 既に登録済みなら何もしない（再実行しても重複させない）
        $result = $db->pquery("SELECT id FROM com_vtiger_workflow_tasktypes WHERE tasktypename = ?", array('VTCurlTask'));

        if ($db->num_rows($result) > 0) {
            $this->log("VTCurlTaskは既に登録されています");
            $this->log("マイグレーション add_curl_workflow_task が正常に完了しました");
            return;
        }

        // IDの採番はフレームワーク側に任せる。
        // MAX(id)+1 を自前で計算すると同時実行時に衝突し、既存の採番方式ともずれる。
        // VTTaskType::registerTaskType() は内部で $adb->getUniqueID() を使う。
        // 呼び出し方は modules/Install/models/InitSchema.php の標準タスク型登録と同じ。
        VTTaskType::registerTaskType(array(
            'name' => 'VTCurlTask',
            'label' => 'Curl Request',
            'classname' => 'VTCurlTask',
            'classpath' => 'modules/com_vtiger_workflow/tasks/VTCurlTask.inc',
            'templatepath' => 'modules/Settings/Workflows/Tasks/VTCurlTask.tpl',
            'modules' => array('include' => array(), 'exclude' => array()),
            'sourcemodule' => '',
        ));

        $registered = $db->pquery("SELECT id FROM com_vtiger_workflow_tasktypes WHERE tasktypename = ?", array('VTCurlTask'));
        if ($db->num_rows($registered) === 0) {
            $this->log("VTCurlTaskの登録に失敗しました");
            return;
        }

        $taskTypeId = $db->query_result($registered, 0, 'id');
        $this->log("VTCurlTaskをワークフローシステムに登録しました (ID: {$taskTypeId})");
        $this->log("マイグレーション add_curl_workflow_task が正常に完了しました");
    }

}
