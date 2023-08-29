<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

require_once 'include/events/VTEventHandler.inc';
class FollowRecordHandler extends VTEventHandler {

	function handleEvent($eventName, $entityData) {
		if ($eventName == 'vtiger.entity.aftersave') {
			global $site_URL;
			$db = PearDatabase::getInstance();

			//current user details
			$currentUserModel = Users_Record_Model::getCurrentUserModel();
			$currentUserId = $currentUserModel->getId();

			//record details
			$recordId = $entityData->getId();
			$moduleName = $entityData->getModuleName();

			$restrictedModules = array('CustomerPortal', 'Dashboard', 'Emails', 'EmailTemplates', 'ExtensionStore', 'Google', 'Home',
										'Import', 'MailManager', 'Mobile', 'ModComments', 'ModTracker', 'PBXManager', 'Portal',
										'RecycleBin', 'Reports', 'Rss', 'SMSNotifier', 'Users', 'Webforms', 'Webmails', 'WSAPP', 'PDFTemplates');

			if (!in_array($moduleName, $restrictedModules)) {
				$tableName = Vtiger_Functions::getUserSpecificTableName($moduleName);

				//following users
				$userIdsList = array();
				$result = $db->pquery("SELECT userid FROM $tableName WHERE recordid = ? AND starred = ? AND userid != ?", array($recordId, '1', $currentUserId));
				if ($result && $db->num_rows($result)) {
					while ($rowData = $db->fetch_row($result)) {
						$userIdsList[] = $rowData['userid'];
					}
				}

				if ($userIdsList) {
					//changed fields data
					$vtEntityDelta = new VTEntityDelta();
					$delta = $vtEntityDelta->getEntityDelta($moduleName, $recordId, true);

					if ($delta) {
						$newEntity = $vtEntityDelta->getNewEntity($moduleName, $recordId);
						$label = decode_html(trim($newEntity->get('label')));

						$fieldModels = array();
						$changedValues = array();
						$skipFields = array('modifiedtime', 'modifiedby', 'label');
						$moduleModel = Vtiger_Module_Model::getInstance($moduleName);

						foreach ($delta as $fieldName => $fieldInfo) {
							if (!in_array($fieldName, $skipFields)) {
								$fieldModel = Vtiger_Field_Model::getInstance($fieldName, $moduleModel);
								if ($fieldModel) {
									$fieldModels[$fieldName] = $fieldModel;
									$changedValues[$fieldName] = $fieldInfo;
								}
							}
						}

						if ($fieldModels) {
							$companyDetails = getCompanyDetails();
							$userModuleModel = Users_Module_Model::getInstance('Users');

							foreach ($userIdsList as $userId) {
								$userRecordModel = Users_Record_Model::getInstanceById($userId, $userModuleModel);
								if ($userRecordModel && $userRecordModel->get('status') == 'Active') {

									$changedFieldString = $this->getChangedFieldString($fieldModels, $changedValues, $userRecordModel);
									$detailViewLink = "$site_URL/index.php?module=$moduleName&view=Detail&record=$recordId";
									$recordDetailViewLink = '<a style="text-decoration:none;" target="_blank" href="'.$detailViewLink.'">'.$label.'</a>';

									$data = vtranslate('LBL_STARRED_RECORD_UPDATED', $moduleName, $currentUserModel->getName(), $recordDetailViewLink)."<br/><br/>".$changedFieldString;
									$body = '<table><tbody><tr><td style="padding:10px">'.nl2br(decode_html($data)).'</td></tr></tbody></table>';

									$notificationMessage = ucwords($companyDetails['companyname']).' '.vtranslate('LBL_NOTIFICATION', $moduleName).' - '.$currentUserModel->getName();
									$subject = vtranslate('LBL_STARRED_RECORD_UPDATED', $moduleName, $notificationMessage, $label);

									$this->sendEmail($userRecordModel->get('email1'), $subject, $body, $recordId);
								}
							}
						}
					}
				}
			}
		}
	}

	public function getChangedFieldString($fieldModels, $changedValues, $userRecordModel) {
		$userEntity = $userRecordModel->entity;

		$changedFieldString = '';
		foreach ($fieldModels as $fieldName => $fieldModel) {
			$moduleName = $fieldModel->getModule()->getName();
			$fieldOldValue = $changedValues[$fieldName]['oldValue'];
			$fieldCurrentValue = $changedValues[$fieldName]['currentValue'];

			$fieldDisplayOldValue = '';
			$fieldDisplayValue = '';
			if ($fieldModel->isReferenceField()) {
				$referenceModules = $fieldModel->getReferenceList();
				$OldrecordModel = Vtiger_Record_Model::getInstanceById($fieldOldValue, $referenceModules[0]);
				$CurrentrecordModel = Vtiger_Record_Model::getInstanceById($fieldCurrentValue, $referenceModules[0]);

				if($OldrecordModel && $CurrentrecordModel){
					$fieldDisplayOldValue = $OldrecordModel->getDisplayName();
					$fieldDisplayValue = $CurrentrecordModel->getDisplayName();
				}
			} else if ($fieldModel->isOwnerField()) {
				$fieldDisplayOldValue = getOwnerName($fieldOldValue);
				$fieldDisplayValue = getOwnerName($fieldCurrentValue);
			} else if ($fieldModel->get('uitype') == 117 && $fieldCurrentValue) {
				$fieldDisplayOldValue = getCurrencyName($fieldOldValue, FALSE);
				$fieldDisplayValue = getCurrencyName($fieldCurrentValue, FALSE);
			} else {
				$fieldDataType = $fieldModel->getFieldDataType();
				switch ($fieldDataType) {
					case 'boolean'		:
					case 'multipicklist':	$fieldDisplayOldValue = $fieldModel->getDisplayValue($fieldOldValue);
											$fieldDisplayValue = $fieldModel->getDisplayValue($fieldCurrentValue);
											break;
					case 'date'			:	$fieldDisplayOldValue = DateTimeField::convertToUserFormat($fieldOldValue, $userEntity);	
											$fieldDisplayValue = DateTimeField::convertToUserFormat($fieldCurrentValue, $userEntity);
											break;
					case 'double'		:	$fieldDisplayOldValue = CurrencyField::convertToUserFormat(decimalFormat($fieldOldValue), $userEntity, true);
											$fieldDisplayValue = CurrencyField::convertToUserFormat(decimalFormat($fieldCurrentValue), $userEntity, true);
											break;
					case 'time'			:	if ($userRecordModel->get('hour_format') == '12') {
												$fieldDisplayOldValue = Vtiger_Time_UIType::getTimeValueInAMorPM($fieldOldValue);
												$fieldDisplayValue = Vtiger_Time_UIType::getTimeValueInAMorPM($fieldCurrentValue);
											} else {
												$fieldDisplayOldValue = $fieldModel->getEditViewDisplayValue($fieldOldValue);
												$fieldDisplayValue = $fieldModel->getEditViewDisplayValue($fieldCurrentValue);
											}
											break;
					case 'currency'		:	$skipConversion = false;
											if ($fieldModel->get('uitype') == 72) {
												$skipConversion = true;
											}
											$fieldDisplayOldValue = CurrencyField::convertToUserFormat($fieldOldValue, $userEntity, $skipConversion);
											$fieldDisplayValue = CurrencyField::convertToUserFormat($fieldCurrentValue, $userEntity, $skipConversion);
											break;

					default				:	$fieldDisplayOldValue = $fieldModel->getEditViewDisplayValue($fieldOldValue);
											$fieldDisplayValue = $fieldModel->getEditViewDisplayValue($fieldCurrentValue);
											break;
				}
			}
			$changedFieldString .= '['.vtranslate('LBL_ITEM_NAME',$moduleName)." : ".vtranslate($fieldModel->get('label'),$moduleName)."]<br/>";
			$changedFieldString .= vtranslate('LBL_STARRED_RECORD_TO', $moduleName, $fieldDisplayOldValue, $fieldDisplayValue).'<br/><br/>';
		}
		return $changedFieldString;
	}

	public function sendEmail($toEmailId, $subject, $body, $recordId) {
		//It will not show in CRM
		$generatedMessageId = Emails_Mailer_Model::generateMessageID();
		Emails_Mailer_Model::updateMessageIdByCrmId($generatedMessageId, $recordId);

		$mailer = new Emails_Mailer_Model();
		$mailer->reinitialize();
		$mailer->Body = $body;
		$mailer->Subject = decode_html($subject);

		$activeUserModel = $this->getActiveUserModel();
		$replyTo = decode_html($activeUserModel->email1);
		$replyToName = decode_html($activeUserModel->last_name.' '.$activeUserModel->first_name);
		$fromEmail = decode_html($activeUserModel->email1);

		$mailer->ConfigSenderInfo($fromEmail, $replyTo, $replyToName);
		$mailer->IsHTML();
		$mailer->AddCustomHeader("In-Reply-To", $generatedMessageId);
		$mailer->AddAddress($toEmailId);

		$response = $mailer->Send(true);
	}

	var $activeAdmin = '';
	public function getActiveUserModel() {
		if (!$this->activeAdmin) {
			$activeUserModel = new Users();
			$activeUserModel->retrieveCurrentUserInfoFromFile(Users::getActiveAdminId());
			$this->activeAdmin = $activeUserModel;
		}
		return $this->activeAdmin;
	}
}
