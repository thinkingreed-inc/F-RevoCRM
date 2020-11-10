<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

function vtws_file_retrieve($file_id, $user) {

    global $log, $adb;

    $idComponents = vtws_getIdComponents($file_id);
    $attachmentId = $idComponents[1];
    
    $id = vtws_getAttachmentRecordId($attachmentId);
    if(!$id || !$attachmentId) {
        throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND, "Record you are trying to access is not found");
    } else {
        $id = vtws_getId($idComponents[0], $id);
    }
    
    $webserviceObject = VtigerWebserviceObject::fromId($adb, $id);
    $handlerPath = $webserviceObject->getHandlerPath();
    $handlerClass = $webserviceObject->getHandlerClass();
    
    require_once $handlerPath;
    $handler = new $handlerClass($webserviceObject, $user, $adb, $log);

    // If setype of the record is not equal to webservice entity
    $meta = $handler->getMeta();
    $elementType = $meta->getObjectEntityName($id);
    if ($elementType !== $webserviceObject->getEntityName()) {
        throw new WebServiceException(WebServiceErrorCode::$INVALIDID, "Id specified is incorrect");
    }

    // If User don't have access to the module (OR) View is not allowed
    $types = vtws_listtypes(null, $user);
    $viewPermission = Users_Privileges_Model::isPermitted($elementType, 'DetailView', $recordId);
    if (!$viewPermission || !in_array($elementType, $types['types'])) {
        throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED, "Permission to perform the operation is denied");
    }

    $response = $handler->file_retrieve($id, $elementType, $attachmentId);
    VTWS_PreserveGlobal::flush();

    return $response;
}

?>
