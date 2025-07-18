<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
vimport('~~/include/Webservices/Custom/DeleteUser.php');

class Users_DeleteAjax_Action extends Vtiger_Delete_Action {

    public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}
    
	public function checkPermission(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$ownerId = $request->get('userid');
		$mode = $request->get('mode');

		if(!$currentUser->isAdminUser() && $mode !== 'credential') {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
		} else if($currentUser->isAdminUser() && ($currentUser->getId() == $ownerId)) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
		} 
	}

    public function deleteUserCredential($userid, $credentialId) {
        $response = new Vtiger_Response();

        if (empty($credentialId)) {
            $response->setResult(['success' => false, 'message' => 'Invalid parameters.']);
            $response->emit();
            return;
        }
        $currentUser = Users_Record_Model::getInstanceById($userid, 'Users');

        try {
            $result = $currentUser->deleteMultiFactorAuthentication($credentialId);
            if ($result === true) {
                $response->setResult(['success' => true]);
            } else {
                $response->setResult([
                    'success' => false,
                    'message' => 'LBL_USER_CREDENTIAL_DELETE_FAILED_NOT_FOUND',
                ]);
            }
        } catch (Exception $e) {
            $response->setResult([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
        $response->emit();
    }

	public function process(Vtiger_Request $request) {
		$moduleName = $request->getModule();
        $ownerId = $request->get('userid');
		$recodeid = $request->get('recodeid');
        $newOwnerId = $request->get('transfer_user_id');
        
        $mode = $request->get('mode');
        $response = new Vtiger_Response();
        $result['message'] = vtranslate('LBL_USER_DELETED_SUCCESSFULLY', $moduleName);

		if($mode == 'permanent'){
            Users_Record_Model::deleteUserPermanently($ownerId, $newOwnerId);
        } elseif($mode == 'credential') {
            $credentialId = $request->get('credentialid');
            $this->deleteUserCredential($recodeid, $credentialId);
            exit;
        } else {
            $userId = vtws_getWebserviceEntityId($moduleName, $ownerId);
            $transformUserId = vtws_getWebserviceEntityId($moduleName, $newOwnerId);

            $userModel = Users_Record_Model::getCurrentUserModel();

            vtws_deleteUser($userId, $transformUserId, $userModel);

            if($request->get('permanent') == '1') {
                Users_Record_Model::deleteUserPermanently($ownerId, $newOwnerId);
            }    
        }
        
        if($request->get('mode') == 'deleteUserFromDetailView'){
            $usersModuleModel = Users_Module_Model::getInstance($moduleName);
            $listViewUrl = $usersModuleModel->getListViewUrl();
            $result['listViewUrl'] = $listViewUrl;
        }
		
		$response->setResult($result);
		$response->emit();
	}
}
