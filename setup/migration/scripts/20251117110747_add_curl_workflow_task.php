<?php
/**
 * マイグレーション: add_curl_workflow_task
 * 生成日時: 20251117110747
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20251117110747_AddCurlWorkflowTask extends FRMigrationClass {
    
    /**
     * マイグレーションを実行する
     * VTCurlTaskをワークフローシステムに登録
     */
    public function process() {
        $db = PearDatabase::getInstance();

        // Check if VTCurlTask already exists
        $result = $db->pquery("SELECT id FROM com_vtiger_workflow_tasktypes WHERE tasktypename = ?", array('VTCurlTask'));

        if ($db->num_rows($result) == 0) {
            // Get next available task type id
            $taskTypeResult = $db->pquery("SELECT MAX(id) as max_id FROM com_vtiger_workflow_tasktypes", array());
            $maxId = $taskTypeResult->fields['max_id'];
            $newTaskTypeId = $maxId + 1;

            // Define task type data
            $defaultModules = array('include' => array(), 'exclude' => array());
            $modulesJson = json_encode($defaultModules);

            // Insert VTCurlTask into workflow task types
            $db->pquery(
                "INSERT INTO com_vtiger_workflow_tasktypes (id, tasktypename, label, classname, classpath, templatepath, modules, sourcemodule)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                array(
                    $newTaskTypeId,
                    'VTCurlTask',
                    'Curl Request',
                    'VTCurlTask',
                    'modules/com_vtiger_workflow/tasks/VTCurlTask.inc',
                    'modules/Settings/Workflows/Tasks/VTCurlTask.tpl',
                    $modulesJson,
                    ''
                )
            );

            $this->log("VTCurlTaskをワークフローシステムに登録しました (ID: {$newTaskTypeId})");
        } else {
            $this->log("VTCurlTaskは既に登録されています");
        }

        $this->log("マイグレーション add_curl_workflow_task が正常に完了しました");
    }

}