<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class MailManager_ComposeEmail_View extends Vtiger_ComposeEmail_View {
    
    public function requiresPermission(Vtiger_Request $request){
		return array();
	}

	public function composeMailData($request) {
		$moduleName = 'Emails';
		$fieldModule = $request->get('fieldModule');
		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$userRecordModel = Users_Record_Model::getCurrentUserModel();
		$sourceModule = $request->getModule();
		$cvId = $request->get('viewname');
		$selectedIds = $request->get('selected_ids', array());
		$excludedIds = $request->get('excluded_ids', array());
		$selectedFields = $request->get('selectedFields');
		$relatedLoad = $request->get('relatedLoad');
		$documentIds = $request->get('documentIds');

		$viewer = $this->getViewer($request);
		$viewer->assign('MODULE', $moduleName);
		$viewer->assign('FIELD_MODULE', $fieldModule);
		$viewer->assign('VIEWNAME', $cvId);
		$viewer->assign('SELECTED_IDS', $selectedIds);
		$viewer->assign('EXCLUDED_IDS', $excludedIds);
		$viewer->assign('USER_MODEL', $userRecordModel);
		$viewer->assign('MAX_UPLOAD_SIZE', Vtiger_Util_Helper::getMaxUploadSizeInBytes());
		$viewer->assign('RELATED_MODULES', $moduleModel->getEmailRelatedModules());
		$viewer->assign('SOURCE_MODULE', $request->get('source_module'));

		if ($documentIds) {
			$attachements = array();
			foreach ($documentIds as $documentId) {
				$documentRecordModel = Vtiger_Record_Model::getInstanceById($documentId, $sourceModule);
				if ($documentRecordModel->get('filelocationtype') == 'I') {
					$fileDetails = $documentRecordModel->getFileDetails();
					if ($fileDetails) {
						$fileDetails['fileid'] = $fileDetails['attachmentsid'];
						$fileDetails['docid'] = $fileDetails['crmid'];
						$fileDetails['attachment'] = $fileDetails['name'];
						$fileDetails['size'] = filesize($fileDetails['path'] . $fileDetails['attachmentsid'] . "_" . $fileDetails['name']);
						$attachements[] = $fileDetails;
					}
				}
			}
			$viewer->assign('ATTACHMENTS', $attachements);
		}

		$searchKey = $request->get('search_key');
		$searchValue = $request->get('search_value');
		$operator = $request->get('operator');
		if (!empty($operator)) {
			$viewer->assign('OPERATOR', $operator);
			$viewer->assign('ALPHABET_VALUE', $searchValue);
			$viewer->assign('SEARCH_KEY', $searchKey);
		}

		$searchParams = $request->get('search_params');
		if (!empty($searchParams)) {
			$viewer->assign('SEARCH_PARAMS', $searchParams);
		}

		$to = array();
		$toMailInfo = array();
		$toMailNamesList = array();
		$selectIds = $this->getRecordsListFromRequest($request);

		$ccMailInfo = $request->get('ccemailinfo');
		if (empty($ccMailInfo)) {
			$ccMailInfo = array();
		}

		$bccMailInfo = $request->get('bccemailinfo');
		if (empty($bccMailInfo)) {
			$bccMailInfo = array();
		}

		$sourceRecordId = $request->get('record');
		if ($sourceRecordId) {
			$sourceRecordModel = Vtiger_Record_Model::getInstanceById($sourceRecordId);
			if ($sourceRecordModel->get('email_flag') === 'SAVED') {
				$selectIds = explode('|', $sourceRecordModel->get('parent_id'));
			}
		}

		$fallBack = false;
		if (!empty($selectedFields)) {
			if ($request->get('emailSource') == 'ListView') {
				foreach ($selectIds as $recordId) {
					$sourceModule = getSalesEntityType($recordId);
					$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $sourceModule);
					if ($recordModel) {
						if ($recordModel->get('emailoptout')) {
							continue;
						}
						foreach ($selectedFields as $selectedFieldJson) {
							$selectedFieldInfo = Zend_Json::decode($selectedFieldJson);
							if (!empty($selectedFieldInfo['basefield'])) {
								$refField = $selectedFieldInfo['basefield'];
								$refModule = getTabModuleName($selectedFieldInfo['module_id']);
								$fieldName = $selectedFieldInfo['field'];
								$refFieldValue = $recordModel->get($refField);
								if (!empty($refFieldValue)) {
									try {
										$refRecordModel = Vtiger_Record_Model::getInstanceById($refFieldValue, $refModule);
										$emailValue = $refRecordModel->get($fieldName);
										$moduleLabel = $refModule;
									} catch (Exception $e) {
										continue;
									}
								}
							} else {
								$fieldName = $selectedFieldInfo['field'];
								$emailValue = $recordModel->get($fieldName);
								$moduleLabel = $sourceModule;
							}
							if (!empty($emailValue)) {
								$to[] = $emailValue;
								$toMailInfo[$recordId][] = $emailValue;
								$toMailNamesList[$recordId][] = array('label' => decode_html($recordModel->get('label')) . ' : ' . vtranslate('SINGLE_' . $moduleLabel, $moduleLabel), 'value' => $emailValue);
							}
						}
					}
				}
			} else {
				foreach ($selectedFields as $selectedFieldJson) {
					$selectedFieldInfo = Zend_Json::decode($selectedFieldJson);
					if ($selectedFieldInfo) {
						$to[] = $selectedFieldInfo['field_value'];
						$toMailInfo[$selectedFieldInfo['record']][] = $selectedFieldInfo['field_value'];
						$toMailNamesList[$selectedFieldInfo['record']][] = array('label' => decode_html($selectedFieldInfo['record_label']), 'value' => $selectedFieldInfo['field_value']);
					} else {
						$fallBack = true;
					}
				}
			}
		}

		//fallback to old code
		if ($fallBack) {
			foreach ($selectIds as $id) {
				if ($id) {
					$parentIdComponents = explode('@', $id);
					if (count($parentIdComponents) > 1) {
						$id = $parentIdComponents[0];
						if ($parentIdComponents[1] === '-1') {
							$recordModel = Users_Record_Model::getInstanceById($id, 'Users');
						} else {
							$recordModel = Vtiger_Record_Model::getInstanceById($id);
						}
					} else if ($fieldModule) {
						$recordModel = Vtiger_Record_Model::getInstanceById($id, $fieldModule);
					} else {
						$recordModel = Vtiger_Record_Model::getInstanceById($id);
					}
					if ($selectedFields) {
						foreach ($selectedFields as $field) {
							$value = $recordModel->get($field);
							$emailOptOutValue = $recordModel->get('emailoptout');
							if (!empty($value) && (!$emailOptOutValue)) {
								$to[] = $value;
								$toMailInfo[$id][] = $value;
								$toMailNamesList[$id][] = array('label' => decode_html($recordModel->getName()), 'value' => decode_html($value));
							}
						}
					}
				}
			}
		}
		$requestTo = $request->get('to');
		if (!$to && is_array($requestTo)) {
			$to = $requestTo;
		}

		$documentsModel = Vtiger_Module_Model::getInstance('Documents');
		$documentsURL = $documentsModel->getInternalDocumentsURL();

		$emailTemplateModuleModel = Vtiger_Module_Model::getInstance('EmailTemplates');
		$emailTemplateListURL = $emailTemplateModuleModel->getPopupUrl();

		$viewer->assign('DOCUMENTS_URL', $documentsURL);
		$viewer->assign('EMAIL_TEMPLATE_URL', $emailTemplateListURL);
		$viewer->assign('TO', $to);
		$viewer->assign('TOMAIL_INFO', $toMailInfo);
		$viewer->assign('TOMAIL_NAMES_LIST', json_encode($toMailNamesList, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP));
		$viewer->assign('CC', $request->get('cc'));
		$viewer->assign('CCMAIL_INFO', $ccMailInfo);
		$viewer->assign('BCC', $request->get('bcc'));
		$viewer->assign('BCCMAIL_INFO', $bccMailInfo);

		//EmailTemplate module percission check
		$userPrevilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$viewer->assign('MODULE_IS_ACTIVE', $userPrevilegesModel->hasModulePermission(Vtiger_Module_Model::getInstance('EmailTemplates')->getId()));
		//

		if ($relatedLoad) {
			$viewer->assign('RELATED_LOAD', true);
		}
	}

}

?>
