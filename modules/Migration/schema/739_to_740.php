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