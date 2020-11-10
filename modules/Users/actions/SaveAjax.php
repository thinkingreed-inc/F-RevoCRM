<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
vimport('~~/include/Webservices/Custom/ChangePassword.php');

class Users_SaveAjax_Action extends Vtiger_SaveAjax_Action {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('userExists');
		$this->exposeMethod('savePassword');
		$this->exposeMethod('restoreUser');
		$this->exposeMethod('transferOwner');
		$this->exposeMethod('changeUsername');
		$this->exposeMethod('changeAccessKey');
	}
    
    public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}

	public function checkPermission(Vtiger_Request $request) {
		$currentUserModel = Users_Record_Model::getCurrentUserModel();

		$userId = $request->get('userid');
		if(!$currentUserModel->isAdminUser()) {
			$mode = $request->getMode();
			if($mode == 'savePassword' && (isset($userId) && $currentUserModel->getId() != $userId)) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
			} else if(in_array($mode, array('userExists','restoreUser','transferOwner','changeUsername'))) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
			} else if($mode != 'savePassword' && ($currentUserModel->getId() != $request->get('record'))) {
				throw new AppException(vtranslate('LBL_PERMISSION_DENIED', 'Vtiger'));
			}
		}
	}

	public function process(Vtiger_Request $request) {

		$mode = $request->get('mode');
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}

		$recordModel = $this->saveRecord($request);

		$fieldModelList = $recordModel->getModule()->getFields();
		$result = array();
		foreach ($fieldModelList as $fieldName => $fieldModel) {
			if($fieldModel->isViewable()){
				$fieldValue = $displayValue = Vtiger_Util_Helper::toSafeHTML($recordModel->get($fieldName));
				if ($fieldModel->getFieldDataType() !== 'currency') {
					$displayValue = $fieldModel->getDisplayValue($fieldValue, $recordModel->getId());
				}
				if($fieldName == 'language') {
					$displayValue =  Vtiger_Language_Handler::getLanguageLabel($fieldValue);
				}
				if(($fieldName == 'currency_decimal_separator' || $fieldName == 'currency_grouping_separator') && ($displayValue == '&nbsp;')) {
					$displayValue = vtranslate('Space', 'Users');
				}
                if($fieldName == 'defaultlandingpage'){
                    $displayValue = vtranslate($fieldValue, $fieldValue);
                }
				$result[$fieldName] = array('value' => $fieldValue, 'display_value' => $displayValue);
			}
		}

		$result['_recordLabel'] = $recordModel->getName();
		$result['_recordId'] = $recordModel->getId();

		$response = new Vtiger_Response();
		$response->setEmitType(Vtiger_Response::$EMIT_JSON);
		$response->setResult($result);
		$response->emit();
	}

	/**
	 * Function to get the record model based on the request parameters
	 * @param Vtiger_Request $request
	 * @return Vtiger_Record_Model or Module specific Record Model instance
	 */
	public function getRecordModelFromRequest(Vtiger_Request $request) {
		$moduleName = $request->getModule();
		$recordId = $request->get('record');
		$currentUserModel = Users_Record_Model::getCurrentUserModel();

		if(!empty($recordId)) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
			$modelData = $recordModel->getData();
			$recordModel->set('id', $recordId);
			$sharedType = $request->get('sharedtype');
			if(!empty($sharedType))
				$recordModel->set('calendarsharedtype', $request->get('sharedtype'));
			$recordModel->set('mode', 'edit');
			
		} else {
			$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
			$modelData = $recordModel->getData();
			$recordModel->set('mode', '');
		}

		foreach ($modelData as $fieldName => $value) {
			$requestFieldExists = false;
			 if($request->has($fieldName)){
				$fieldValue = $request->get($fieldName,null);
				$requestFieldExists = true;
			}else if($fieldName === $request->get('field')){
				$fieldValue = $request->get('value');
				$requestFieldExists = true;
			}
			
			if(!$requestFieldExists){
				continue;
			}
			
			if ($fieldName === 'is_admin' && (!$currentUserModel->isAdminUser() || !$fieldValue)) {
				$fieldValue = 'off';
			}else if($fieldName === 'is_admin' && $currentUserModel->isAdminUser()) {
				$fieldValue = $fieldValue==1?"on":"off";
			}

			//to not update is_owner from ui
			 if ($fieldName == 'is_owner' || (in_array($fieldName, array('user_type', 'roleid')) && !$currentUserModel->isAdminUser())) {
				$fieldValue = null;
			}
			if($fieldName == 'signature'){
				$requestData = $request->getAll();
				$instyle = new InStyle();
				$signature = $instyle->convertStylesToInlineCss($requestData['signature']);
				//#4823970 - Added to remove any action tags like <form>, <input>, <button>..
				$fieldValue = vtlib_purify($signature);
				// Purify malicious html event attributes
	                        $fieldValue = purifyHtmlEventAttributes($fieldValue, true);
			}
			if($fieldValue !== null) {
				if(!is_array($fieldValue)) {
					$fieldValue = trim($fieldValue);
				}
				$recordModel->set($fieldName, $fieldValue);
			}
		}
                $homePageComponents = $recordModel->getHomePageComponents();
		$selectedHomePageComponents = $request->get('homepage_components', array());
		foreach ($homePageComponents as $key => $value) {
			if(in_array($key, $selectedHomePageComponents)) {
				$request->setGlobal($key, $key);
			} else {
				$request->setGlobal($key, '');
			}
		}
		if($request->has('tagcloudview')) {
			// Tag cloud save
			$tagCloud = $request->get('tagcloudview');
			if($tagCloud == "on") {
				$recordModel->set('tagcloud', 0);
			} else {
				$recordModel->set('tagcloud', 1);
			}
		}
		return $recordModel;
	}


        public function getOwnerRoleValue(Vtiger_Request $request, $fieldName) {
            $recordId = $request->get('record');
            $moduleName = $request->getModule();
            if(!empty($recordId)) {
                    $existingRecordModel =  Vtiger_Record_Model::getInstanceById($recordId, $moduleName);
                    return $existingRecordModel->get($fieldName);
            }
        }
        public function userExists(Vtiger_Request $request){
		$module = $request->getModule();
		$userName = $request->get('user_name');
		$status = Users_Record_Model::isUserExists($userName);
		$response = new Vtiger_Response();
		$response->setResult($status);
		$response->emit();
	}

	public function savePassword(Vtiger_Request $request) {
		$module = $request->getModule();
		$userModel = vglobal('current_user');
		$newPassword = $request->get('new_password');
		$oldPassword = $request->get('old_password');

		$wsUserId = vtws_getWebserviceEntityId($module, $request->get('userid'));
		$wsStatus = vtws_changePassword($wsUserId, $oldPassword, $newPassword, $newPassword, $userModel);

		$response = new Vtiger_Response();
		if ($wsStatus['message']) {
			$response->setResult($wsStatus);
		} else {
			$response->setError('JS_PASSWORD_INCORRECT_OLD', 'JS_PASSWORD_INCORRECT_OLD');
		}
		$response->emit();
	}

		/*
		 * To restore a user
		 * @param Vtiger_Request Object
		 */
		public function restoreUser(Vtiger_Request $request) {
			$moduleName = $request->getModule();
			$record = $request->get('userid');

			$recordModel = Users_Record_Model::getInstanceById($record, $moduleName);
				$recordModel->set('status', 'Active');
				$recordModel->set('id', $record);
				$recordModel->set('mode', 'edit');
				$recordModel->save();

				$db = PearDatabase::getInstance();
				$db->pquery("UPDATE vtiger_users SET deleted=? WHERE id=?", array(0,$record));

				$userModuleModel = Users_Module_Model::getInstance($moduleName);
				$listViewUrl = $userModuleModel->getListViewUrl();

			$response = new Vtiger_Response();
			$response->setResult(array('message'=>vtranslate('LBL_USER_RESTORED_SUCCESSFULLY', $moduleName), 'listViewUrl' => $listViewUrl));
			$response->emit();
		}

	/*
	 * Function to transfer CRM owner without deleting User
	 */
	public function transferOwner(Vtiger_Request $request) {
		$moduleName = $request->getModule(false);
		$record = $request->get('record');
		$usersInstance = CRMEntity::getInstance($moduleName);
		$status = $usersInstance->transferOwnership($record);
		$response = new Vtiger_Response();
		if($status) {
			$response->setResult(array('message' => vtranslate('LBL_OWNERSHIP_TRANSFERRED_SUCCESSFULLY', $moduleName)));
		} else {
			$response->setError(vtranslate('LBL_OWNERSHIP_TRANSFERRED_FAILED', $moduleName));
		}
		$response->emit();
	}

	/**
	 * Function to change username
	 */
	public function changeUsername(Vtiger_Request $request) {
		$response = new Vtiger_Response();
		$userId = $request->get('userid');

		$status = Users_Record_Model::changeUsername($request->get('newUsername'), $request->get('newPassword'), $request->get('oldPassword'), $userId);
		if($status['success']) {
			$response->setResult($status['message']);
		}else{
			$response->setError($status['message']);
		}
		$response->emit();
	}

	public function changeAccessKey(Vtiger_Request $request) {
		$recordId = $request->get('record');
		$moduleName = $request->getModule();

		$response = new Vtiger_Response();
		try {
			$recordModel = Users_Record_Model::getInstanceById($recordId, $moduleName);
			$oldAccessKey = $recordModel->get('accesskey');

			$entity = $recordModel->getEntity();
			$entity->createAccessKey();

			require_once('modules/Users/CreateUserPrivilegeFile.php');
			createUserPrivilegesfile($recordId);
			Vtiger_AccessControl::clearUserPrivileges($recordId);

			$recordModel = Users_Record_Model::getInstanceFromPreferenceFile($recordId);
			$newAccessKey = $recordModel->get('accesskey');

			if ($newAccessKey != $oldAccessKey) {
				$response->setResult(array('success' => true, 'message' => vtranslate('LBL_ACCESS_KEY_UPDATED_SUCCESSFULLY', $moduleName), 'accessKey' => $newAccessKey));
			} else {
				$response->setError(vtranslate('LBL_FAILED_TO_UPDATE_ACCESS_KEY', $moduleName));
			}
		} catch (Exception $ex) {
			$response->setError($ex->getMessage());
		}
		$response->emit();
	}
}
