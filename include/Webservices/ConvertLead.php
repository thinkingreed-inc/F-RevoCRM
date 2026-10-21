<?php
/* +*******************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ******************************************************************************** */

require_once 'include/Webservices/Retrieve.php';
require_once 'include/Webservices/Create.php';
require_once 'include/Webservices/Delete.php';
require_once 'include/Webservices/Revise.php';
require_once 'include/Webservices/DescribeObject.php';
require_once 'includes/Loader.php';
vimport ('includes.runtime.Globals');
vimport ('includes.runtime.BaseModel');

/**
 * リードを昇格する
 *
 * 任意パラメータ（Web Components 版の昇格画面から使う。省略時は従来どおり）:
 *   $element['entities']['Accounts'|'Contacts']['existingId']
 *       既存レコードの Webservice ID。指定するとそのレコードに紐づけ、新規作成もリードの値での上書きもしない。
 *       既存担当者の所属企業（account_id）が空のときだけ、昇格で決まった企業をセットする。
 *   $element['strict'] = true
 *       同じ会社名の企業を黙って再利用しない。entities に渡した値は空でもそのまま使い、
 *       必須項目が空なら '????' で埋めずに例外を投げる。途中で失敗したら作成済みのレコードを消す。
 */
function vtws_convertlead($element, $leadId, $assignedTo, $accountName, $avoidPotential, $potential, $user) {

	global $adb, $log;
	$strict = !empty($element['strict']);
	if (empty($element['assignedTo'])) {
		$element['assignedTo'] = vtws_getWebserviceEntityId('Users', $user->id);
	}
	if (empty($element['transferRelatedRecordsTo'])) {
		$element['transferRelatedRecordsTo'] = 'Contacts';
	}
	$activeAdminUser = Users::getActiveAdminUser();

	$leadObject = VtigerWebserviceObject::fromName($adb, 'Leads');
	$handlerPath = $leadObject->getHandlerPath();
	$handlerClass = $leadObject->getHandlerClass();

	require_once $handlerPath;

	$leadHandler = new $handlerClass($leadObject, $activeAdminUser, $adb, $log);


	$leadInfo = vtws_retrieve($element['leadId'], $activeAdminUser);
	$sql = "select converted from vtiger_leaddetails where converted = 1 and leadid=?";
	$leadIdComponents = vtws_getIdComponents($element['leadId']);
	$result = $adb->pquery($sql, array($leadIdComponents[1]));
	if ($result === false) {
		throw new WebServiceException(WebServiceErrorCode::$DATABASEQUERYERROR,
				vtws_getWebserviceTranslatedString('LBL_' .
						WebServiceErrorCode::$DATABASEQUERYERROR));
	}
	$rowCount = $adb->num_rows($result);
	if ($rowCount > 0) {
		throw new WebServiceException(WebServiceErrorCode::$LEAD_ALREADY_CONVERTED,
				"Lead is already converted");
	}

	$leadHasImage = false;
	if($leadInfo['imagename'] && $element['imageAttachmentId']) {
		$leadHasImage = true;
		$imageAttachmentId = $element['imageAttachmentId'];
	}
	$entityIds = array();
	// 今回の昇格で新しく作ったレコード（失敗時に消してよいのはこれだけ）
	$createdEntityIds = array();
	// 所属企業が空の既存担当者（企業が決まった後、すべての作成に成功してからセットする）
	$contactToLinkAccount = null;

	$availableModules = array('Accounts', 'Contacts', 'Potentials');

	if (!(($element['entities']['Accounts']['create']) || ($element['entities']['Contacts']['create']))) {
		return null;
	}

	$quoteIds = array();
	$leadId = explode('x', $leadInfo['id']);
	$getQuote = 'SELECT quoteid from vtiger_quotes where contactid=?';
	$results = $adb->pquery($getQuote, array($leadId[1]));
	$count = $adb->num_rows($results);
	if($count > 0){
		for($i=0; $i < $count; $i++){
			$quoteIds[] = $adb->query_result($results, $i, 'quoteid');
		}
	}

	foreach ($availableModules as $entityName) {
		if ($element['entities'][$entityName]['create']) {
			$entityvalue = $element['entities'][$entityName];
			if (!empty($entityvalue['existingId']) && in_array($entityName, array('Accounts', 'Contacts'))) {
				try {
					$existing = vtws_convertLeadRetrieveExisting($entityName, $entityvalue['existingId'], $user);
					$entityIds[$entityName] = $existing['id'];
					if ($entityName == 'Contacts' && empty($existing['account_id'])) {
						$contactToLinkAccount = $existing['id'];
					}
				} catch (Exception $e) {
					vtws_convertLeadDeleteCreated($createdEntityIds, $activeAdminUser);
					throw $e;
				}
				continue;
			}
			$entityObject = VtigerWebserviceObject::fromName($adb, $entityName);
			$handlerPath = $entityObject->getHandlerPath();
			$handlerClass = $entityObject->getHandlerClass();

			require_once $handlerPath;

			$entityHandler = new $handlerClass($entityObject, $activeAdminUser, $adb, $log);

			$entityObjectValues = array();
			$entityObjectValues['assigned_user_id'] = $element['assignedTo'];
			try {
				$entityObjectValues = vtws_populateConvertLeadEntities($entityvalue, $entityObjectValues, $entityHandler, $leadHandler, $leadInfo, $entityName, $strict);
			} catch (Exception $e) {
				vtws_convertLeadDeleteCreated($createdEntityIds, $activeAdminUser);
				throw $e;
			}

			//update potential related to property
			if ($entityName == 'Potentials') {
				if (!empty($entityIds['Accounts'])) {
					$entityObjectValues['related_to'] = $entityIds['Accounts'];
				}
				if (!empty($entityIds['Contacts'])) {
					$entityObjectValues['contact_id'] = $entityIds['Contacts'];
				}
			}

			//update the contacts relation
			if ($entityName == 'Contacts') {
				if (!empty($entityIds['Accounts'])) {
					$entityObjectValues['account_id'] = $entityIds['Accounts'];
				}
			}

			if ($strict) {
				// 関連づけ（account_id / related_to / contact_id）を入れた後の値で必須を確かめる
				try {
					vtws_assertConvertEntityMandatoryValues($entityObjectValues, $entityHandler, $entityName);
				} catch (Exception $e) {
					vtws_convertLeadDeleteCreated($createdEntityIds, $activeAdminUser);
					throw $e;
				}
			}

			try {
				$create = true;
				if ($entityName == 'Accounts' && !$strict) {
					$sql = "SELECT vtiger_account.accountid FROM vtiger_account,vtiger_crmentity WHERE vtiger_crmentity.crmid=vtiger_account.accountid AND vtiger_account.accountname=? AND vtiger_crmentity.deleted=0";
					$result = $adb->pquery($sql, array($entityvalue['accountname']));
					if ($adb->num_rows($result) > 0) {
						$entityIds[$entityName] = vtws_getWebserviceEntityId('Accounts', $adb->query_result($result, 0, 'accountid'));
						$create = false;
					}
				}
				if ($create) {
					$entityObjectValues['imagename'] = '';
					if(($leadHasImage) && ((($entityName == 'Contacts') || ($entityName == 'Accounts' && !$element['entities']['Contacts']['create'])))) {
						$imageName = $adb->query_result($adb->pquery('SELECT name FROM vtiger_attachments 
							WHERE attachmentsid = ?',array($imageAttachmentId)),0,'name');
						$entityObjectValues['imagename'] = $imageName;
					}
					$entityObjectValues['isconvertedfromlead'] = 1;
					$entityRecord = vtws_create($entityName, $entityObjectValues, $activeAdminUser);
					$entityIds[$entityName] = $entityRecord['id'];
					$createdEntityIds[$entityName] = $entityRecord['id'];
					if($leadHasImage && in_array($entityName,array('Accounts','Contacts'))) {
						$idComponents = explode('x',$entityIds[$entityName]);
						$crmId = $idComponents[1];
						$adb->pquery('UPDATE vtiger_seattachmentsrel SET crmid = ? WHERE attachmentsid = ?',array($crmId,$imageAttachmentId));
						$adb->pquery('UPDATE vtiger_crmentity SET setype = ? WHERE crmid = ?',array($entityName.' Image',$imageAttachmentId));
					}
				}
			} catch (DuplicateException $e) {
				if ($strict) {
					vtws_convertLeadDeleteCreated($createdEntityIds, $activeAdminUser);
				}
				throw $e;
			} catch (Exception $e) {
				if ($strict) {
					vtws_convertLeadDeleteCreated($createdEntityIds, $activeAdminUser);
				}
				throw new WebServiceException(WebServiceErrorCode::$UNKNOWNOPERATION,
						$e->getMessage().' : '.$entityName);
			}
		}
	}

	if ($contactToLinkAccount !== null && !empty($entityIds['Accounts'])) {
		try {
			vtws_revise(array('id' => $contactToLinkAccount, 'account_id' => $entityIds['Accounts']), $activeAdminUser);
		} catch (Exception $e) {
			vtws_convertLeadDeleteCreated($createdEntityIds, $activeAdminUser);
			throw $e;
		}
	}


	try {
		$accountIdComponents = vtws_getIdComponents($entityIds['Accounts']);
		$accountId = $accountIdComponents[1];

		$contactIdComponents = vtws_getIdComponents($entityIds['Contacts']);
		$contactId = $contactIdComponents[1];

		if(!empty($entityIds['Potentials'])){
			$potentialIdComponents = vtws_getIdComponents($entityIds['Potentials']);
			$potentialId = $potentialIdComponents[1];
		}

		if (!empty($accountId) && !empty($contactId) && !empty($potentialId)) {
			$sql = "insert into vtiger_contpotentialrel values(?,?)";
			$result = $adb->pquery($sql, array($contactId, $potentialId));
			if ($result === false) {
				throw new WebServiceException(WebServiceErrorCode::$FAILED_TO_CREATE_RELATION,
						"Failed to related Contact with the Potential");
			}
		}
		if($quoteIds){
			$queryUpdate = 'UPDATE vtiger_quotes SET contactid=?, potentialid=? WHERE quoteid IN('. generateQuestionMarks($quoteIds).') ';
			$adb->pquery($queryUpdate, array($contactId, $potentialId, $quoteIds));

			if($accountId){
				$queryUpdate = 'UPDATE vtiger_quotes SET accountid=? WHERE quoteid IN('. generateQuestionMarks($quoteIds).')';
				$adb->pquery($queryUpdate, array($accountId, $quoteIds));
			}
		}
		$transfered = vtws_convertLeadTransferHandler($leadIdComponents, $entityIds, $element);

		$relatedIdComponents = vtws_getIdComponents($entityIds[$element['transferRelatedRecordsTo']]);
		vtws_getRelatedActivities($leadIdComponents[1], $accountId, $contactId, $relatedIdComponents[1]);
		vtws_updateConvertLeadStatus($entityIds, $element['leadId'], $user);
	} catch (Exception $e) {
		// 既存レコード（暗黙に再利用した企業・指定した既存レコード）は消さない
		vtws_convertLeadDeleteCreated($createdEntityIds, $activeAdminUser);
		return null;
	}

	$leadId = explode("x",$element['leadId']);
	if($leadId[1]) {
		$em = new VTEventsManager($adb);
		$em->initTriggerCache();

		$entityData = VTEntityData::fromEntityId($adb, $leadId[1], 'Leads');
		$entityData->entityIds = $entityIds;
		$entityData->transferRelatedRecordsTo = $element['transferRelatedRecordsTo'];

		$em->triggerEvent('vtiger.lead.convertlead', $entityData);
	}

	return $entityIds;
}

/*
 * populate the entity fields with the lead info.
 * if mandatory field is not provided populate with '????'
 * returns the entity array.
 */

function vtws_populateConvertLeadEntities($entityvalue, $entity, $entityHandler, $leadHandler, $leadinfo, $entityName, $strict = false) {
	global $adb, $log;
	$column = "";
	$sql = "SELECT * FROM vtiger_convertleadmapping";
	$result = $adb->pquery($sql, array());
	if ($adb->num_rows($result)) {
		switch ($entityName) {
			case 'Accounts':$column = 'accountfid';
				break;
			case 'Contacts':$column = 'contactfid';
				break;
			case 'Potentials':$column = 'potentialfid';
				break;
			default:$column = 'leadfid';
				break;
		}

		$leadFields = $leadHandler->getMeta()->getModuleFields();
		$entityFields = $entityHandler->getMeta()->getModuleFields();
		$row = $adb->fetch_array($result);
		$count = 1;
		do {
			$entityField = vtws_getFieldfromFieldId($row[$column], $entityFields);
			if ($entityField == null) {
				//user doesn't have access so continue.TODO update even if user doesn't have access
				continue;
			}
			$leadField = vtws_getFieldfromFieldId($row['leadfid'], $leadFields);
			if ($leadField == null) {
				//user doesn't have access so continue.TODO update even if user doesn't have access
				continue;
			}
			$leadFieldName = $leadField->getFieldName();
			$entityFieldName = $entityField->getFieldName();
			$entity[$entityFieldName] = $leadinfo[$leadFieldName];
			$count++;
		} while ($row = $adb->fetch_array($result));

		foreach ($entityFields as $fieldName=>$fieldModel) {
			if(!empty($entityFields[$fieldName]) && $fieldModel->getDefault() && $fieldName !='isconvertedfromlead') {
				if(!isset($entityvalue[$fieldName]) && empty($entity[$fieldName])) {
					$entityvalue[$fieldName] = $fieldModel->getDefault();
				}
			}
		}

		foreach ($entityvalue as $fieldname => $fieldvalue) {
			// strict では呼び出し側が決めた最終的な値をそのまま使う（空にした値を復活させない）
			if ($strict || !empty($fieldvalue)) {
				$entity[$fieldname] = $fieldvalue;
			}
		}

		// strict の必須チェックは関連づけの値を入れた後に vtws_convertlead 側で行う
		if (!$strict) {
			$entity = vtws_validateConvertEntityMandatoryValues($entity, $entityHandler, $entityName);
		}
	}
	return $entity;
}

/**
 * strict モード用: 必須項目が空なら '????' で埋めずに例外を投げる
 */
function vtws_assertConvertEntityMandatoryValues($entity, $entityHandler, $module) {
	$missing = array();
	foreach ($entityHandler->getMeta()->getMandatoryFields() as $field) {
		if ($field === 'assigned_user_id') {
			continue;
		}
		if (!isset($entity[$field]) || trim((string) $entity[$field]) === '') {
			$missing[] = $field;
		}
	}
	if ($missing) {
		throw new WebServiceException(WebServiceErrorCode::$MANDFIELDSMISSING,
				'Mandatory fields are empty : ' . $module . ' (' . implode(', ', $missing) . ')');
	}
}

/**
 * 紐づける既存レコードを、昇格を実行するユーザーの権限で読む（閲覧できなければ例外）
 *
 * 既存レコードの値は書き換えない（担当者の所属企業だけは、空のとき昇格の最後にセットする）。
 * @return array 既存レコード
 */
function vtws_convertLeadRetrieveExisting($entityName, $existingId, $user) {
	$idComponents = vtws_getIdComponents($existingId);
	if (getSalesEntityType($idComponents[1]) !== $entityName) {
		throw new WebServiceException(WebServiceErrorCode::$INVALIDID, "Id specified is not $entityName");
	}
	return vtws_retrieve($existingId, $user);
}

/**
 * 今回の昇格で作ったレコードだけを消す（後始末の失敗で元の例外を隠さない）
 * 作成と同じく管理者として消す（実行ユーザーに削除権限が無くても孤立レコードを残さない）
 */
function vtws_convertLeadDeleteCreated($createdEntityIds, $user) {
	foreach ($createdEntityIds as $id) {
		try {
			vtws_delete($id, $user);
		} catch (Exception $e) {
			global $log;
			$log->error('ConvertLead rollback failed for ' . $id . ': ' . $e->getMessage());
		}
	}
}

//function to handle the transferring of related records for lead
function vtws_convertLeadTransferHandler($leadIdComponents, $entityIds, $element) {

	try {
		$entityidComponents = vtws_getIdComponents($entityIds[$element['transferRelatedRecordsTo']]);
		vtws_transferLeadRelatedRecords($leadIdComponents[1], $entityidComponents[1], $element['transferRelatedRecordsTo']);
	} catch (Exception $e) {
		return false;
	}

	return true;
}

function vtws_updateConvertLeadStatus($entityIds, $leadId, $user) {
	global $adb, $log;
	$leadIdComponents = vtws_getIdComponents($leadId);
	if ($entityIds['Accounts'] != '' || $entityIds['Contacts'] != '') {
		$sql = "UPDATE vtiger_leaddetails SET converted = 1 where leadid=?";
		$result = $adb->pquery($sql, array($leadIdComponents[1]));
		if ($result === false) {
			throw new WebServiceException(WebServiceErrorCode::$FAILED_TO_MARK_CONVERTED,
					"Failed mark lead converted");
		}
		//updating the campaign-lead relation --Minnie
		$sql = "DELETE FROM vtiger_campaignleadrel WHERE leadid=?";
		$adb->pquery($sql, array($leadIdComponents[1]));

		$sql = "DELETE FROM vtiger_tracker WHERE item_id=?";
		$adb->pquery($sql, array($leadIdComponents[1]));

		//update the modifiedtime and modified by information for the record
		$leadModifiedTime = $adb->formatDate(date('Y-m-d H:i:s'), true);
		$crmentityUpdateSql = "UPDATE vtiger_crmentity SET modifiedtime=?, modifiedby=? WHERE crmid=?";
		$adb->pquery($crmentityUpdateSql, array($leadModifiedTime, $user->id, $leadIdComponents[1]));
	}

}

?>
