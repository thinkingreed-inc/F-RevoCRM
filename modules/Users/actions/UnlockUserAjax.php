<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Users_UnlockUserAjax_Action extends Vtiger_Action_Controller {

	public function checkPermission(Vtiger_Request $request) {
		$currentUser = Users_Record_Model::getCurrentUserModel();

		// Only administrators can unlock users
		if (!$currentUser->isAdminUser()) {
			throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
		}
	}

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		$recordId = $request->get('record');

		try {
			if (empty($recordId)) {
				throw new Exception('Invalid record ID');
			}

			// Get the user record model
			$userModel = Users_Record_Model::getInstanceById($recordId, 'Users');

			if (!$userModel) {
				throw new Exception('User not found');
			}

			// Delete the lock record from database
			global $adb;
			$query = "DELETE FROM `vtiger_user_lock` WHERE `userid` = ?";
			$params = array($recordId);
			$adb->pquery($query, $params);

			// Log the unlock action
			global $log;
			$currentUser = Users_Record_Model::getCurrentUserModel();
			$log->info("User lock removed: userid=$recordId by admin userid=" . $currentUser->getId());

			$response->setResult([
				'success' => true,
				'message' => vtranslate('LBL_UNLOCK_SUCCESS', 'Users')
			]);
		} catch (Exception $e) {
			global $log;
			$log->error("Failed to unlock user: " . $e->getMessage());

			$response->setResult([
				'success' => false,
				'message' => vtranslate('LBL_UNLOCK_FAILED', 'Users')
			]);
		}

		$response->emit();
	}
}
