<?php
/*+********************************************************************************
* The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is: vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*********************************************************************************/

if (defined('VTIGER_UPGRADE')) {
	global $current_user, $adb;
	$db = PearDatabase::getInstance();

    //Vtiger7.4
    $eventManager = new VTEventsManager($db);
    $className = 'Vtiger_RecordLabelUpdater_Handler';
    $eventManager->unregisterHandler($className);
    echo "Unregistered record label update handler.<br>";

    $moduleName = 'Users';
    $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
    $fieldName = 'userlabel';
    $blockModel = Vtiger_Block_Model::getInstance('LBL_MORE_INFORMATION', $moduleModel);
    if ($blockModel) {
        $fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
        if (!$fieldModel) {
            $fieldModel				= new Vtiger_Field();
            $fieldModel->name		= $fieldName;
            $fieldModel->label		= 'User Label';
            $fieldModel->table		= 'vtiger_users';
            $fieldModel->columntype = 'VARCHAR(255)';
            $fieldModel->typeofdata = 'V~O';
            $fieldModel->displaytype= 3;
            $blockModel->addField($fieldModel);
            echo "<br>Successfully added <b>$fieldName</b> field to <b>$moduleName</b><br>";
        }
    }
    
    $entityFields = Vtiger_Functions::getEntityModuleInfo($moduleName);
    $entityFieldNames  = explode(',', $entityFields['fieldname']);
    $sql = "UPDATE vtiger_users SET $fieldName = TRIM(CONCAT_WS(' ',".implode(',', $entityFieldNames)."))";
    $db->pquery($sql, array());
    
    Vtiger_Access::syncSharingAccess();

    //Vtiger7.5
    $db->pquery("ALTER TABLE vtiger_inventorychargesrel ADD KEY record_idx (recordid)", array());	

    //復活してしまうことがあるので再度削除
    include_once 'setup/scripts/10_Delete_Modules.php';

    // vtiger_crmentityテーブルの内容をモジュール毎のメインテーブルにコピーする
    require_once 'setup/scripts/75_Update_CRMEntity.php';

    // 親コメントが削除されている子コメントを削除する
    $result = $adb->pquery('SELECT crmid FROM vtiger_crmentity
                    INNER JOIN vtiger_modcomments ON vtiger_modcomments.modcommentsid = vtiger_crmentity.crmid
                    WHERE vtiger_crmentity.setype = "ModComments"
                    AND vtiger_crmentity.deleted = 1
                    AND vtiger_modcomments.parent_comments = 0', array());
    $rows = $adb->num_rows($result);
    for ($i = 0; $i < $rows; $i++) { // 削除済みの親コメントid
        $deletedparentId[] = $adb->query_result($result, $i, 'crmid');
    }

    if($rows > 0){
        $result = $adb->pquery('SELECT modcommentsid FROM vtiger_modcomments
                    WHERE parent_comments IN ('.generateQuestionMarks($deletedparentId).')', $deletedparentId);
        for ($i = 0; $i < $adb->num_rows($result); $i++) { // 子コメントを削除する
            $commentId = $adb->query_result($result, $i, 'modcommentsid');
            $recordModel = ModComments_Record_Model::getInstanceById($commentId, 'ModComments');
            if($recordModel) {
                $recordModel->delete();
            }
        }
    }
}