<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
include_once 'include/Webservices/DescribeObject.php';

class Mobile_WS_Describe extends Mobile_WS_Controller {
	
	function process(Mobile_API_Request $request) {
		$current_user = $this->getActiveUser();
		$module = $request->get('module');
		$describeInfo = vtws_describe($module, $current_user);

		$fields = $describeInfo['fields'];
		
		$moduleModel = Vtiger_Module_Model::getInstance($module);
		$fieldModels = $moduleModel->getFields();
		foreach($fields as $index=>$field) {
			$fieldModel = $fieldModels[$field['name']];
			if($fieldModel) {
				$field['headerfield'] = $fieldModel->get('headerfield');
				$field['summaryfield'] = $fieldModel->get('summaryfield');
			}
			if($fieldModel && $fieldModel->getFieldDataType() == 'owner') {
				$currentUser = Users_Record_Model::getCurrentUserModel();
                $users = $currentUser->getAccessibleUsers();
                $usersWSId = Mobile_WS_Utils::getEntityModuleWSId('Users');
                foreach ($users as $id => $name) {
                    unset($users[$id]);
                    $users[$usersWSId.'x'.$id] = $name; 
                }
                
                $groups = $currentUser->getAccessibleGroups();
                $groupsWSId = Mobile_WS_Utils::getEntityModuleWSId('Groups');
                foreach ($groups as $id => $name) {
                    unset($groups[$id]);
                    $groups[$groupsWSId.'x'.$id] = $name; 
                }
				$field['type']['picklistValues']['users'] = $users; 
				$field['type']['picklistValues']['groups'] = $groups;

				//Special treatment to set default mandatory owner field
				if (!$field['default']) {
					$field['default'] = $usersWSId.'x'.$current_user->id;
				}
			}
			if($fieldModel && $fieldModel->get('name') == 'salutationtype') {
				$values = $fieldModel->getPicklistValues();
				$picklistValues = array();
				foreach($values as $value => $label) {
					$picklistValues[] = array('value'=>$value, 'label'=>$label);
				}
				$field['type']['picklistValues'] = $picklistValues;
			}
			$newFields[] = $field;
		}
		$fields=null;
		$describeInfo['fields'] = $newFields;
		
		$response = new Mobile_API_Response();
		$response->setResult(array('describe' => $describeInfo));
		
		return $response;
	}
}