<?php
/* ***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

require_once 'include/Webservices/Create.php';
require_once 'include/Webservices/Update.php';
require_once 'include/Webservices/Delete.php';
require_once 'include/Webservices/Revise.php';
require_once 'include/Webservices/Retrieve.php';
require_once 'include/Webservices/DataTransform.php';
require_once 'vtlib/Vtiger/Utils.php';
require_once 'modules/Vtiger/CRMEntity.php';
require_once 'include/QueryGenerator/QueryGenerator.php';
require_once 'vtlib/Vtiger/Mailer.php';
require_once 'include/events/include.inc';
vimport('includes.runtime.EntryPoint');

class Import_Data_Action extends Vtiger_Action_Controller {

	var $id;
	var $user;
	var $module;
	var $fieldMapping;
	var $mergeType;
	var $mergeFields;
	var $defaultValues;
	var $lineitem_currency_id;
	var $paging;
	var $importedRecordInfo = array();
	protected $allPicklistValues = array();
	var $batchImport = true;
	public $entitydata = array();
	var $recordSource = 'IMPORT';

	static $IMPORT_RECORD_NONE = 0;
	static $IMPORT_RECORD_CREATED = 1;
	static $IMPORT_RECORD_SKIPPED = 2;
	static $IMPORT_RECORD_UPDATED = 3;
	static $IMPORT_RECORD_MERGED = 4;
	static $IMPORT_RECORD_FAILED = 5;

	public function __construct($importInfo, $user) {
		$this->id = $importInfo['id'];
		$this->module = $importInfo['module'];
		$this->fieldMapping = $importInfo['field_mapping'];
		$this->mergeType = $importInfo['merge_type'];
		if (!$importInfo['merge_fields']) {
			$this->mergeFields = array();
		} else {
			$this->mergeFields = $importInfo['merge_fields'];
		}
		$this->defaultValues = $importInfo['default_values'];
		$this->lineitem_currency_id = $importInfo['lineitem_currency_id'];
		$this->user = $user;
		$this->paging = $importInfo['paging'];
	}

	public function process(Vtiger_Request $request) {
		return;
	}

	public function getDefaultFieldValues($moduleMeta) {
		static $cachedDefaultValues = array();

		if (isset($cachedDefaultValues[$this->module])) {
			return $cachedDefaultValues[$this->module];
		}

		$defaultValues = array();
		if (!empty($this->defaultValues)) {
			if (!is_array($this->defaultValues)) {
				$this->defaultValues = Zend_Json::decode($this->defaultValues);
			}
			if ($this->defaultValues != null) {
				$defaultValues = $this->defaultValues;
			}
		}
		$moduleFields = $moduleMeta->getModuleFields();
		$moduleMandatoryFields = $moduleMeta->getMandatoryFields();
		foreach ($moduleMandatoryFields as $mandatoryFieldName) {
			if (empty($defaultValues[$mandatoryFieldName])) {
				$fieldInstance = $moduleFields[$mandatoryFieldName];
				if ($fieldInstance->getFieldDataType() == 'owner') {
					$defaultValues[$mandatoryFieldName] = $this->user->id;
				} elseif ($fieldInstance->getFieldDataType() != 'datetime' && $fieldInstance->getFieldDataType() != 'date' && $fieldInstance->getFieldDataType() != 'time' && $fieldInstance->getFieldDataType() != 'reference') {
					$defaultValues[$mandatoryFieldName] = '????';
				}
			}
		}
		foreach ($moduleFields as $fieldName => $fieldInstance) {
			$fieldDefaultValue = $fieldInstance->getDefault();
			if (empty($defaultValues[$fieldName])) {
				if ($fieldInstance->getUIType() == '52') {
					$defaultValues[$fieldName] = $this->user->id;
				} elseif (($fieldInstance->getFieldDataType() == 'picklist') && !empty($fieldDefaultValue)) {
					$defaultValues[$fieldName] = trim($fieldDefaultValue);
				} elseif (!empty($fieldDefaultValue)) {
					$defaultValues[$fieldName] = $fieldDefaultValue;
				}
			}
		}
		$className = get_class($moduleMeta);
		if ($className != 'VtigerLineItemMeta') {
			$cachedDefaultValues[$this->module] = $defaultValues;
		}
		return $defaultValues;
	}

	public function import() {
		if(!$this->initializeImport()) return false;
		$this->importData();
		$this->finishImport();
	}

	public function importData() {
		$focus = CRMEntity::getInstance($this->module);
		$moduleModel = Vtiger_Module_Model::getInstance($this->module);
		// pre fetch the fields and premmisions of module
		Vtiger_Field_Model::getAllForModule($moduleModel);
		if ($this->user->is_admin == 'off') {
			Vtiger_Field_Model::preFetchModuleFieldPermission($moduleModel->getId());
		}
		if (method_exists($focus, 'createRecords')) {
			$focus->createRecords($this);
		} else {
			$this->createRecords();
		}
	}

	public function initializeImport() {
		$lockInfo = Import_Lock_Action::isLockedForModule($this->module);
		if ($lockInfo != null) {
			if ($lockInfo['userid'] != $this->user->id) {
				Import_Utils_Helper::showImportLockedError($lockInfo);
				return false;
			} else {
				return true;
			}
		} else {
			Import_Lock_Action::lock($this->id, $this->module, $this->user);
			return true;
		}
	}

	public function finishImport() {
		Import_Lock_Action::unLock($this->user, $this->module);
		Import_Queue_Action::remove($this->id);
	}

	public function updateModuleSequenceNumber() {
		$moduleName = $this->module;
		$focus = CRMEntity::getInstance($moduleName);
		$focus->updateMissingSeqNumber($moduleName);
	}

	public function updateImportStatus($entryId, $entityInfo) {
		$adb = PearDatabase::getInstance();
		$recordId = null;
		if (!empty($entityInfo['id'])) {
			$entityIdComponents = vtws_getIdComponents($entityInfo['id']);
			$recordId = $entityIdComponents[1];
		}
		$adb->pquery('UPDATE '.Import_Utils_Helper::getDbTableName($this->user).' SET status=?, recordid=? WHERE id=?', array($entityInfo['status'], $recordId, $entryId));
	}

	public function createRecords() {
		$adb = PearDatabase::getInstance();
		$moduleName = $this->module;
		$tabId = getTabid($moduleName);

		$focus = CRMEntity::getInstance($moduleName);
		$moduleHandler = vtws_getModuleHandlerFromName($moduleName, $this->user);
		$moduleMeta = $moduleHandler->getMeta();
		$moduleObjectId = $moduleMeta->getEntityId();

		$moduleFields = $moduleMeta->getModuleFields();
		if ($moduleName === 'Calendar') {
			$eventModuleHandler = vtws_getModuleHandlerFromName('Events', $this->user);
			$eventModuleFields = $eventModuleHandler->getMeta()->getModuleFields();
			foreach ($eventModuleFields as $fieldName => $fieldModel) {
				if (stripos($fieldName, 'cf_') !== false) {
					$moduleFields[$fieldName] = $fieldModel;
				}
			}
		}

		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		$moduleImportableFields = $moduleModel->getAdditionalImportFields();
		$moduleFields = array_merge($moduleFields, $moduleImportableFields);

		$createdRecords = array();
		$entityData = array();
		$tableName = Import_Utils_Helper::getDbTableName($this->user);
        $params = array();
		$sql = 'SELECT * FROM '.$tableName.' WHERE status = ?';
        array_push($params, Import_Data_Action::$IMPORT_RECORD_NONE);

		$configReader = new Import_Config_Model();
		if ($this->batchImport) {
			$importBatchLimit = $configReader->get('importBatchLimit');
			$sql .= ' LIMIT '.$importBatchLimit;
		} else if ($this->paging) {
			$pagingLimit = $configReader->get('importPagingLimit');
			$sql .= ' LIMIT '. $pagingLimit;
		}

		$result = $adb->pquery($sql, $params);
		$numberOfRecords = $adb->num_rows($result);

		if ($numberOfRecords <= 0) {
			return;
		}

		$fieldMapping = $this->fieldMapping;
		$fieldColumnMapping = $moduleMeta->getFieldColumnMapping();

		$createRecordExists = method_exists($focus, 'importRecord');
		if (!$createRecordExists) {
			$queryGenerator = new QueryGenerator($moduleName, $this->user);
			$customView = new CustomView($moduleName);
			$viewId = $customView->getViewIdByName('All', $moduleName);
			if (!empty($viewId)) {
				$queryGenerator->initForCustomViewById($viewId);
			} else {
				$queryGenerator->initForDefaultCustomView();
			}

			$fieldsList = array('id');
			$queryGenerator->setFields($fieldsList);

			$mergeFields = $this->mergeFields;
			if ($queryGenerator->getWhereFields() && $mergeFields) {
				$queryGenerator->addConditionGlue(QueryGenerator::$AND);
			}
		}

		$mergedRecords = array();
		for ($i = 0; $i < $numberOfRecords; ++$i) {
			$row = $adb->raw_query_result_rowdata($result, $i);
			$rowId = $row['id'];
			$entityInfo = null;
			$fieldData = array();
			foreach ($fieldMapping as $fieldName => $index) {
				$fieldData[$fieldName] = trim($row[$fieldName]);
			}

			$mergeType = $this->mergeType;
			$createRecord = false;

			if ($createRecordExists) {
				$entityInfo = $focus->importRecord($this, $fieldData);
				if ($entityInfo) {
					$entityIdComponents = vtws_getIdComponents($entityInfo['id']);
					$createdRecords[] = $entityIdComponents[1];
				}
			} else {
				if (!empty($mergeType) && $mergeType != Import_Utils_Helper::$AUTO_MERGE_NONE) {
					if (count($this->mergeFields) == 0) {
						$mergeType = Import_Utils_Helper::$AUTO_MERGE_IGNORE;
					}
					$index = 0;
					foreach ($mergeFields as $mergeFieldName => $mergeFieldLabel) {
						if ($index != 0) {
							$queryGenerator->addConditionGlue(QueryGenerator::$AND);
						}
						$comparisonValue = $fieldData[$mergeFieldName];
						$fieldInstance = $moduleFields[$mergeFieldName];
						$fieldDataType = $fieldInstance->getFieldDataType();
						switch ($fieldDataType) {
							case 'owner'	:	$userId = getUserId_Ol($comparisonValue);
												$comparisonValue = getUserFullName($userId);
												break;
							case 'reference':	if (strpos($comparisonValue, '::::') > 0) {
													$referenceFileValueComponents = explode('::::', $comparisonValue);
												} else {
													$referenceFileValueComponents = explode(':::', $comparisonValue);
												}
												if (count($referenceFileValueComponents) > 1) {
													$comparisonValue = trim($referenceFileValueComponents[1]);
												}
												break;
							case 'currency'	:	if (!empty($comparisonValue)) {
													$comparisonValue = CurrencyField::convertToUserFormat($comparisonValue, $this->user, TRUE, FALSE);
												}
												break;
						}
						$queryGenerator->addCondition($mergeFieldName, $comparisonValue, 'e', '', '', '', true);
						$index++;
					}
					$query = $queryGenerator->getQuery();
					// to eliminate clash of next record values
					$queryGenerator->clearConditionals();
					$duplicatesResult = $adb->pquery($query, array());
					$noOfDuplicates = $adb->num_rows($duplicatesResult);

					if ($noOfDuplicates > 0) {
						if ($mergeType == Import_Utils_Helper::$AUTO_MERGE_IGNORE) {
							$entityInfo['status'] = self::$IMPORT_RECORD_SKIPPED;
						} elseif ($mergeType == Import_Utils_Helper::$AUTO_MERGE_OVERWRITE || $mergeType == Import_Utils_Helper::$AUTO_MERGE_MERGEFIELDS) {
							$userPriviligesModel = Users_Privileges_Model::getInstanceById($this->user->id);
							$baseRecordId = $adb->query_result($duplicatesResult, $noOfDuplicates - 1, $fieldColumnMapping['id']);
							$baseEntityId = vtws_getId($moduleObjectId, $baseRecordId);
							$baseRecordModel = Vtiger_Record_Model::getInstanceById($baseRecordId);

							for ($index = 0; $index < $noOfDuplicates - 1; ++$index) {
								$duplicateRecordId = $adb->query_result($duplicatesResult, $index, $fieldColumnMapping['id']);
								$entityId = vtws_getId($moduleObjectId, $duplicateRecordId);
								if ($userPriviligesModel->hasModuleActionPermission($tabId, 'Delete')) {
									$baseRecordModel->transferRelationInfoOfRecords(array($duplicateRecordId));
									if ($moduleName == 'Calendar') {
										$recordModel = Vtiger_Record_Model::getInstanceById($duplicateRecordId);
										$recordModel->delete();
									} else {
										vtws_delete($entityId, $this->user);
									}
								}
							}

							if ($mergeType == Import_Utils_Helper::$AUTO_MERGE_OVERWRITE) {
								$fieldData = $this->transformForImport($fieldData, $moduleMeta);
								$fieldData['id'] = $baseEntityId;
								$entityInfo = $this->importRecord($fieldData, 'update');
								if ($entityInfo) {
									$entityIdComponents = vtws_getIdComponents($entityInfo['id']);
									$createdRecords[] = $entityIdComponents[1];
									$mergedRecords[] = $entityIdComponents[1];
								}
							}

							if ($mergeType == Import_Utils_Helper::$AUTO_MERGE_MERGEFIELDS) {
								$filteredFieldData = array();
								foreach ($fieldData as $fieldName => $fieldValue) {
									// empty will give false for value = 0
									if (!empty($fieldValue) || $fieldValue != "") {
										$filteredFieldData[$fieldName] = $fieldValue;
									}
								}

								// Custom handling for default values & mandatory fields
								// need to be taken care than normal import as we merge
								// existing record values with newer values.
								$fillDefault = false;
								$mandatoryValueChecks = false;
								if ($userPriviligesModel->hasModuleActionPermission($tabId, 'DetailView')) {
									$existingFieldValues = $baseRecordModel->getData();
									if ($moduleName != 'Calendar') {
										$existingFieldValues = vtws_retrieve($baseEntityId, $this->user);
									}
									$defaultFieldValues = $this->getDefaultFieldValues($moduleMeta);

									foreach ($existingFieldValues as $fieldName => $fieldValue) {
										if (empty($fieldValue) && empty($filteredFieldData[$fieldName]) && !empty($defaultFieldValues[$fieldName])) {
											$filteredFieldData[$fieldName] = $defaultFieldValues[$fieldName];
										}
									}
								}

								$filteredFieldData = $this->transformForImport($filteredFieldData, $moduleMeta, $fillDefault, $mandatoryValueChecks);
								$filteredFieldData['id'] = $baseEntityId;
								if ($userPriviligesModel->hasModuleActionPermission($tabId, 'EditView')) {
									$entityInfo = $this->importRecord($filteredFieldData, 'revise');
									if ($entityInfo) {
										$entityIdComponents = vtws_getIdComponents($entityInfo['id']);
										$createdRecords[] = $entityIdComponents[1];
										$mergedRecords[] = $entityIdComponents[1];
									}
								} else {
									$entityInfo['status'] = self::$IMPORT_RECORD_SKIPPED;
								}
								$fieldData = $filteredFieldData;
							}
						} else {
							$createRecord = true;
						}
					} else {
						$createRecord = true;
					}
				} else {
					$createRecord = true;
				}
				if ($createRecord) {
					$fieldData = $this->transformForImport($fieldData, $moduleMeta);
					if ($fieldData == null) {
						$entityInfo = null;
					} else {
						try {
							// to save Source of Record while Creating
							$fieldData['source'] = $this->recordSource;
							$entityInfo = $this->importRecord($fieldData, 'create');
							if ($entityInfo) {
								$entityIdComponents = vtws_getIdComponents($entityInfo['id']);
								$createdRecords[] = $entityIdComponents[1];
							}
						} catch (Exception $e) {

						}
					}
				}
			}
			if ($entityInfo == null) {
				$entityInfo = array('id' => null, 'status' => self::$IMPORT_RECORD_FAILED);
			} else if ($createRecord) {
				$entityInfo['status'] = self::$IMPORT_RECORD_CREATED;
			}
			if ($createRecord || $mergeType == Import_Utils_Helper::$AUTO_MERGE_MERGEFIELDS || $mergeType == Import_Utils_Helper::$AUTO_MERGE_OVERWRITE) {
				$entityIdComponents = vtws_getIdComponents($entityInfo['id']);
				$recordId = $entityIdComponents[1];
				if (!empty($recordId)) {
					$entityfields = getEntityFieldNames($this->module);
					switch ($this->module) {
						case 'HelpDesk'	: $entityfields['fieldname'] = array('ticket_title');	break;
						case 'Documents': $entityfields['fieldname'] = array('notes_title');	break;
					}
					$label = '';
					if (is_array($entityfields['fieldname'])) {
						foreach ($entityfields['fieldname'] as $field) {
							$label .= $fieldData[$field]." ";
						}
					} else {
						$label = $fieldData[$entityfields['fieldname']];
					}

					$adb->pquery('UPDATE vtiger_crmentity SET label=? WHERE crmid=?', array(trim($label), $recordId));
					//updating solr while import records
					$recordModel = Vtiger_Record_Model::getCleanInstance($this->module);
					$focus = $recordModel->getEntity();
					$focus->id = $recordId;
					$focus->column_fields = $fieldData;
					$this->entityData[] = VTEntityData::fromCRMEntity($focus);
				}

				$label = trim($label);
				$adb->pquery('UPDATE vtiger_crmentity SET label=? WHERE crmid=?', array($label, $recordId));
				//Creating entity data of updated records for post save events
				if (in_array($entityInfo['status'], array(self::$IMPORT_RECORD_MERGED, self::$IMPORT_RECORD_UPDATED))) {
					$recordModel = Vtiger_Record_Model::getCleanInstance($this->module);
					$focus = $recordModel->getEntity();
					$focus->id = $recordId;
					$focus->column_fields = $entityInfo;
					$this->entitydata[] = VTEntityData::fromCRMEntity($focus);
				}
			}

			$this->updateImportStatus($rowId, $entityInfo);
		}

		//Update missing seq numbers
		$focus = CRMEntity::getInstance($moduleName);
		$focus->updateMissingSeqNumber($moduleName);

		//Creating entity data of created records for post save events 
		if (!empty($createdRecords)) {
			$recordModels = Vtiger_Record_Model::getInstancesFromIds($createdRecords, $this->module);
			$entityInfos = array();
			foreach ($recordModels as $recordModel) {
				$focus = $recordModel->getEntity();
				$entityInfos[] = VTEntityData::fromCRMEntity($focus);
			}
			$this->entitydata = array_merge($this->entitydata, $entityInfos);
		}

		//Triggering post save events
		if ($this->entitydata) {
			$entity = new VTEventsManager($adb);
			$entity->triggerEvent('vtiger.batchevent.save', $this->entitydata);
		}
		$this->entitydata = null;
		$result = null;
		return true;
	}

	public function transformForImport($fieldData, $moduleMeta, $fillDefault = true, $checkMandatoryFieldValues = true) {
		global $current_user;
		$moduleImportableFields = array();
		$moduleFields = $moduleMeta->getModuleFields();
		$moduleName = $moduleMeta->getEntityName();

		if ($moduleName === 'Calendar') {
			$eventModuleHandler = vtws_getModuleHandlerFromName('Events', $this->user);
			$eventModuleFields = $eventModuleHandler->getMeta()->getModuleFields();
			if(!array_key_exists('visibility', $fieldData)){
				$fieldData['visibility'] = $current_user->calendarsharedtype;
			}
			foreach ($eventModuleFields as $fieldName => $fieldModel) {
				if (stripos($fieldName, 'cf_') !== false) {
					$moduleFields[$fieldName] = $fieldModel;
				}
			}
		}

		$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
		// for Inventory Module Import, LineItem will come as a module
		if ($moduleModel) {
			$moduleImportableFields = $moduleModel->getAdditionalImportFields();
		}
		$moduleFields = array_merge($moduleFields, $moduleImportableFields);

		$defaultFieldValues = $this->getDefaultFieldValues($moduleMeta);
		foreach ($defaultFieldValues as $defaultFieldName => $defaultFieldValue) {
			$fieldInstance = $moduleFields[$defaultFieldName];
			if ($fieldInstance) {
				$fieldDataType = $fieldInstance->getFieldDataType();
				if ($fieldDataType == 'datetime' && !empty($defaultFieldValues[$defaultFieldName])) {
					$defaultFieldValues[$defaultFieldName] = getValidDBInsertDateTimeValue($defaultFieldValues[$defaultFieldName]);
				}
			}
		}
		foreach ($fieldData as $fieldName => $fieldValue) {
			$fieldInstance = $moduleFields[$fieldName];
			$fieldDataType = $fieldInstance->getFieldDataType();
			if ($fieldDataType == 'owner') {
				$ownerId = getUserId_Ol(trim($fieldValue));
				if (empty($ownerId)) {
					$ownerId = getGrpId($fieldValue);
				}
				if (empty($ownerId) && isset($defaultFieldValues[$fieldName])) {
					$ownerId = $defaultFieldValues[$fieldName];
				}
				if (empty($ownerId) ||
						!Import_Utils_Helper::hasAssignPrivilege($moduleName, $ownerId)) {
					$ownerId = $this->user->id;
				}
				$fieldData[$fieldName] = $ownerId;
			} elseif ($fieldDataType == 'multipicklist') {
				$trimmedValue = trim($fieldValue);

				if (!$trimmedValue && isset($defaultFieldValues[$fieldName])) {
					$explodedValue = explode(',', $defaultFieldValues[$fieldName]);
				} else {
					$explodedValue = explode(' |##| ', $trimmedValue);
				}
				if($trimmedValue && strpos($trimmedValue, ' |##| ') === false) {
					$explodedValue = explode(',', $trimmedValue);
				}

				foreach ($explodedValue as $key => $value) {
					$explodedValue[$key] = trim($value);
				}

				$implodeValue = implode(' |##| ', $explodedValue);
				$fieldData[$fieldName] = $implodeValue;
			} elseif ($fieldDataType == 'reference') {
				$entityId = false;
				if (!empty($fieldValue)) {
					if (strpos($fieldValue, '::::') > 0) {
						$fieldValueDetails = explode('::::', $fieldValue);
					} else if (strpos($fieldValue, ':::') > 0) {
						$fieldValueDetails = explode(':::', $fieldValue);
					} else {
						$fieldValueDetails = $fieldValue;
					}
					if (count($fieldValueDetails) > 1) {
						$referenceModuleName = trim($fieldValueDetails[0]);
						if (count($fieldValueDetails) == 2) {
							$entityLabel = trim($fieldValueDetails[1]);
							$entityId = getEntityId($referenceModuleName, decode_html($entityLabel));
						} else {//multi reference field
							$entityIdsList = $this->getEntityIdsList($referenceModuleName, $fieldValueDetails);
							if ($entityIdsList) {
								$entityId = implode(', ', $entityIdsList);
							}
						}
					} else {
						$referencedModules = $fieldInstance->getReferenceList();
						$entityLabel = $fieldValue;
						foreach ($referencedModules as $referenceModule) {
							$referenceModuleName = $referenceModule;
							if ($referenceModule == 'Users') {
								$referenceEntityId = getUserId_Ol($entityLabel);
								if (empty($referenceEntityId) ||
										!Import_Utils_Helper::hasAssignPrivilege($moduleName, $referenceEntityId)) {
									$referenceEntityId = $this->user->id;
								}
							} elseif ($referenceModule == 'Currency') {
								$referenceEntityId = getCurrencyId($entityLabel);
							} else {
								$referenceEntityId = getEntityId($referenceModule, decode_html($entityLabel));
							}
							if ($referenceEntityId != 0) {
								$entityId = $referenceEntityId;
								break;
							}
						}
					}
					if ((empty($entityId) || $entityId == 0) && !empty($referenceModuleName)) {
						if (isPermitted($referenceModuleName, 'CreateView') == 'yes') {
							try {
								$wsEntityIdInfo = $this->createEntityRecord($referenceModuleName, $entityLabel);
								$wsEntityId = $wsEntityIdInfo['id'];
								$entityIdComponents = vtws_getIdComponents($wsEntityId);
								$entityId = $entityIdComponents[1];
							} catch (Exception $e) {
								$entityId = false;
							}
						}
					}
					$fieldData[$fieldName] = $entityId;
				} else {
					$referencedModules = $fieldInstance->getReferenceList();
					if ($referencedModules[0] == 'Users') {
						if (isset($defaultFieldValues[$fieldName])) {
							$fieldData[$fieldName] = $defaultFieldValues[$fieldName];
						}
						if (empty($fieldData[$fieldName]) ||
								!Import_Utils_Helper::hasAssignPrivilege($moduleName, $fieldData[$fieldName])) {
							$fieldData[$fieldName] = $this->user->id;
						}
					} else {
						$fieldData[$fieldName] = '';
					}
				}
			} elseif ($fieldDataType == 'picklist' || $fieldName == 'salutationtype') {
				$fieldValue = trim(strip_tags(decode_html($fieldValue)));
				global $default_charset;
				if (empty($fieldValue) && isset($defaultFieldValues[$fieldName])) {
					$fieldData[$fieldName] = $fieldValue = $defaultFieldValues[$fieldName];
				}
				if (!isset($this->allPicklistValues[$fieldName])) {
					$this->allPicklistValues[$fieldName] = $fieldInstance->getPicklistDetails();
				}
				$allPicklistDetails = $this->allPicklistValues[$fieldName];

				$allPicklistValues = array();
				foreach ($allPicklistDetails as $picklistDetails) {
					$allPicklistValues[] = $picklistDetails['value'];
				}

				$picklistValueInLowerCase = strtolower($fieldValue);
				$allPicklistValuesInLowerCase = array_map('strtolower', $allPicklistValues);
				if (sizeof($allPicklistValuesInLowerCase) > 0 && sizeof($allPicklistValues) > 0) {
					$picklistDetails = array_combine($allPicklistValuesInLowerCase, $allPicklistValues);
				}

				if (!in_array($picklistValueInLowerCase, $allPicklistValuesInLowerCase) && !empty($picklistValueInLowerCase)) {
					if ($moduleName != 'Calendar') {
						// Required to update runtime cache.
						$wsFieldDetails = $fieldInstance->getPicklistDetails();

						$moduleObject = Vtiger_Module::getInstance($moduleName);
						$fieldObject = Vtiger_Field::getInstance($fieldName, $moduleObject);
						$fieldObject->setPicklistValues(array($fieldValue));

						// Update cache state with new value added.
						$wsFieldDetails[] = array('label' => $fieldValue, 'value' => $fieldValue);
						Vtiger_Cache::getInstance()->setPicklistDetails($moduleObject->getId(), $fieldName, $wsFieldDetails);

						unset($this->allPicklistValues[$fieldName]);
					}
				} else {
					$fieldData[$fieldName] = $picklistDetails[$picklistValueInLowerCase];
				}
			} else if ($fieldDataType == 'currency') {
				// While exporting we are exporting as user format, we should import as db format while importing
				if ($fieldInstance->getUIType() == 72 || $fieldName == 'listprice') {
					// if It is line item field we should not convert the value just map that as selected currency
					$fieldData[$fieldName] = $fieldValue;
				} else {
					if (!empty($fieldValue)) {
						$fieldValue = CurrencyField::convertToUserFormat($fieldValue, $current_user, true);
						$fieldData[$fieldName] = CurrencyField::convertToDBFormat($fieldValue, $current_user, false);
					}
				}
			} else if($fieldDataType == 'boolean') {
				$fieldValue = strtolower($fieldValue);
				if($fieldValue == 'yes' || $fieldValue == 1) {
					$fieldData[$fieldName] = 1;
				} else {
					$fieldData[$fieldName] = 0;
				}
			} else if($fieldDataType == 'ownergroup') {
				$groupId = getGrpId(trim($fieldValue));
				if (empty($groupId) && isset($defaultFieldValues[$fieldName])) {
					$groupId = $defaultFieldValues[$fieldName];
				}
				$fieldData[$fieldName] = $groupId;
			} else {
				if ($fieldDataType == 'datetime' && !empty($fieldValue)) {
					if ($fieldValue == null || $fieldValue == '0000-00-00 00:00:00') {
						$fieldValue = '';
					} 
					$valuesList = explode(' ', $fieldValue);
					if(count($valuesList) == 1) $fieldValue = '';
					$fieldValue = getValidDBInsertDateTimeValue($fieldValue);
					if (preg_match("/^[0-9]{2,4}[-][0-1]{1,2}?[0-9]{1,2}[-][0-3]{1,2}?[0-9]{1,2} ([0-1][0-9]|[2][0-3])([:][0-5][0-9]){1,2}$/",
							$fieldValue) == 0) {
						$fieldValue = '';
					}
					$fieldData[$fieldName] = $fieldValue;
				}
				if ($fieldDataType == 'time' && !empty($fieldValue)) {
					if($fieldValue == null || $fieldValue == '00:00:00') {
						$fieldValue = '';
						$fieldData[$fieldName] = $fieldValue;
					}
				}
				if ($fieldDataType == 'date' && !empty($fieldValue)) {
					if ($fieldValue == null || $fieldValue == '0000-00-00') {
						$fieldValue = '';
					}

					$valuesList = explode(' ', $fieldValue);
					if (count($valuesList) > 1) {
						$fieldValue = $valuesList[0];
					}

					$userDateFormat = $current_user->column_fields['date_format'];
					if ('dd.mm.yyyy' === $userDateFormat) {
						$dateFormat = 'd.m.Y';
					} else if ('mm.dd.yyyy' === $userDateFormat) {
						$dateFormat = 'm.d.Y';
					} else if ('yyyy.mm.dd' === $userDateFormat) {
						$dateFormat = 'Y.m.d';
					} else if ('dd/mm/yyyy' === $userDateFormat) {
						$dateFormat = 'd/m/Y';
					} else if ('mm/dd/yyyy' === $userDateFormat) {
						$dateFormat = 'm/d/Y';
					} else if ('yyyy/mm/dd' === $userDateFormat) {
						$dateFormat = 'Y/m/d';
					} else if ('dd-mm-yyyy' === $userDateFormat) {
						$dateFormat = 'd-m-Y';
					} else if ('mm-dd-yyyy' === $userDateFormat) {
						$dateFormat = 'm-d-Y';
					} else {
						$dateFormat = 'Y-m-d';
					}

					if (preg_match('/\d{4}-\d{2}-\d{2}/', $fieldValue)) {
						$fieldValue = date($dateFormat, strtotime($fieldValue));
					}
					$fieldValue = getValidDBInsertDateValue($fieldValue);
					if (preg_match("/^[0-9]{2,4}[-][0-1]{1,2}?[0-9]{1,2}[-][0-3]{1,2}?[0-9]{1,2}$/", $fieldValue) == 0) {
						$fieldValue = '';
					}
					$fieldData[$fieldName] = $fieldValue;
				}

				if (($fieldValue == NULL || $fieldValue == "") && isset($defaultFieldValues[$fieldName])) {
					$fieldData[$fieldName] = $fieldValue = $defaultFieldValues[$fieldName];
				}
			}
		}
		if ($fillDefault) {
			foreach ($defaultFieldValues as $fieldName => $fieldValue) {
				if (!isset($fieldData[$fieldName])) {
					$fieldData[$fieldName] = $defaultFieldValues[$fieldName];
				}
			}
		}

		// We should sanitizeData before doing final mandatory check below.
		$fieldData = DataTransform::sanitizeData($fieldData, $moduleMeta);
		if (($this->lineitem_currency_id)) {
			/**
			 * for unit_price field we are getting values from $_REQUEST params in
			 * insertPriceInformation() of Products/Services
			 */
			if ($fieldData['unit_price']) {
				$_REQUEST['unit_price'] = $fieldData['unit_price'];
				$_REQUEST['curname'.$this->lineitem_currency_id] = $fieldData['unit_price'];
				$_REQUEST['base_currency'] = 'curname'.$this->lineitem_currency_id;
				$_REQUEST['cur_'.$this->lineitem_currency_id.'_check'] = 1;
			}
			$fieldData['currency_id'] = $this->lineitem_currency_id;
		}
		if ($fieldData != null && $checkMandatoryFieldValues) {
			foreach ($moduleFields as $fieldName => $fieldInstance) {
				if ((($fieldData[$fieldName] == '') || ($fieldData[$fieldName] == null)) && $fieldInstance->isMandatory()) {
					return null;
				}
			}
		}

		return $fieldData;
	}

	public function createEntityRecord($moduleName, $entityLabel) {
		$moduleHandler = vtws_getModuleHandlerFromName($moduleName, $this->user);
		$moduleMeta = $moduleHandler->getMeta();
		$moduleFields = $moduleMeta->getModuleFields();
		$mandatoryFields = $moduleMeta->getMandatoryFields();
		$entityNameFieldsString = $moduleMeta->getNameFields();
		$entityNameFields = explode(',', $entityNameFieldsString);
		$fieldData = array();
		foreach ($entityNameFields as $entityNameField) {
			$entityNameField = trim($entityNameField);
			if (in_array($entityNameField, $mandatoryFields)) {
				$fieldData[$entityNameField] = $entityLabel;
			}
		}
		foreach ($mandatoryFields as $mandatoryField) {
			if (empty($fieldData[$mandatoryField])) {
				$fieldInstance = $moduleFields[$mandatoryField];
				if ($fieldInstance->getFieldDataType() == 'owner') {
					$fieldData[$mandatoryField] = $this->user->id;
				} else if (!in_array($mandatoryField, $entityNameFields) && $fieldInstance->getFieldDataType() != 'reference') {
					$fieldData[$mandatoryField] = '????';
				}
			}
		}

		$fieldData = DataTransform::sanitizeData($fieldData, $moduleMeta);
		$entityIdInfo = vtws_create($moduleName, $fieldData, $this->user);
		$adb = PearDatabase::getInstance();
		$entityIdComponents = vtws_getIdComponents($entityIdInfo['id']);
		$recordId = $entityIdComponents[1];
		$entityfields = getEntityFieldNames($moduleName);
		switch ($moduleName) {
			case 'HelpDesk'	: $entityfields['fieldname'] = array('ticket_title');	break;
			case 'Documents': $entityfields['fieldname'] = array('notes_title');	break;
		}
		$label = '';
		if (is_array($entityfields['fieldname'])) {
			foreach ($entityfields['fieldname'] as $field) {
				$label .= $fieldData[$field]." ";
			}
		} else {
			$label = $fieldData[$entityfields['fieldname']];
		}

		$label = trim($label);
		$adb->pquery('UPDATE vtiger_crmentity SET label=? WHERE crmid=?', array($label, $recordId));

		$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
		$focus = $recordModel->getEntity();
		$focus->id = $recordId;
		$focus->column_fields = $fieldData;
		$this->entitydata[] = VTEntityData::fromCRMEntity($focus);
		$focus->updateMissingSeqNumber($moduleName);
		return $entityIdInfo;
	}

	public function getImportStatusCount() {
		$adb = PearDatabase::getInstance();
		$tableName = Import_Utils_Helper::getDbTableName($this->user);

		$focus = CRMEntity::getInstance($this->module);
		if ($focus && method_exists($focus, 'getGroupQuery')) {
			$query = $focus->getGroupQuery($tableName);
		} else {
			$query = 'SELECT status FROM '.$tableName;
		}
		$result = $adb->pquery($query, array());

		$statusCount = array('TOTAL' => 0, 'IMPORTED' => 0, 'FAILED' => 0, 'PENDING' => 0, 'CREATED' => 0, 'SKIPPED' => 0, 'UPDATED' => 0, 'MERGED' => 0);

		if ($result) {
			$noOfRows = $adb->num_rows($result);
			$statusCount['TOTAL'] = $noOfRows;
			for ($i = 0; $i < $noOfRows; ++$i) {
				$status = $adb->query_result($result, $i, 'status');
				if (self::$IMPORT_RECORD_NONE == $status) {
					$statusCount['PENDING'] ++;
				} elseif (self::$IMPORT_RECORD_FAILED == $status) {
					$statusCount['FAILED'] ++;
				} else {
					$statusCount['IMPORTED'] ++;
					switch ($status) {
						case self::$IMPORT_RECORD_CREATED	: $statusCount['CREATED']++;	break;
						case self::$IMPORT_RECORD_SKIPPED	: $statusCount['SKIPPED']++;	break;
						case self::$IMPORT_RECORD_UPDATED	: $statusCount['UPDATED']++;	break;
						case self::$IMPORT_RECORD_MERGED	: $statusCount['MERGED']++;		break;
					}
				}
			}
		}
		return $statusCount;
	}

	public static function runScheduledImport() {
		global $current_user, $adb;
		$scheduledImports = self::getScheduledImport();
		$vtigerMailer = new Vtiger_Mailer();
		$vtigerMailer->IsHTML(true);
		foreach ($scheduledImports as $scheduledId => $importDataController) {
			$current_user = $importDataController->user;
			$importDataController->batchImport = false;

			if(!$importDataController->initializeImport()) { continue; }
			$importDataController->importData();
			$importStatusCount = $importDataController->getImportStatusCount();
			$recordsToImport = $importDataController->getNumberOfRecordsToImport($importDataController->user);
			$emailSubject = getTranslatedString('LBL_SCHEDULE_IMPORT_SUBJECT', 'Import').' '.$importDataController->module;
			$viewer = new Vtiger_Viewer();
			$viewer->assign('FOR_MODULE', $importDataController->module);
			$viewer->assign('INVENTORY_MODULES', getInventoryModules());
			$viewer->assign('IMPORT_RESULT', $importStatusCount);
			$viewer->assign('MODULE', 'Import');
			$importResult = $viewer->view('Import_Result_Details.tpl','Import',true);
			$importResult = str_replace('align="center"', '', $importResult);

                        $emailData = getTranslatedString('LBL_IMPORT_COMPLETED', 'Import').' '.$importResult.getTranslatedString('LBL_CHECK_IMPORT_STATUS', 'Import');

			$userName = getFullNameFromArray('Users', $importDataController->user->column_fields);
			$userEmail = $importDataController->user->email1;
			$vtigerMailer->AddAddress($userEmail, $userName);
			$vtigerMailer->Subject = $emailSubject;
			$vtigerMailer->Body    = $emailData;
			$vtigerMailer->Send(true);

			$importDataController->finishImport();
		}
	}

	public function getNumberOfRecordsToImport($user){
		$db = PearDatabase::getInstance();
		$table = Import_Utils_Helper::getDbTableName($user);
		$query = "SELECT count(*) AS count FROM $table WHERE status = ?";
		$result = $db->pquery($query,array(Import_Data_Action::$IMPORT_RECORD_NONE));
		$rows = $db->num_rows($result);
		$count = 0;
		if($rows) {
			$count = $db->query_result($result,0,'count');
		}
		return $count;
	}

	public static function getScheduledImport() {
		$scheduledImports = array();
		$importQueue = Import_Queue_Action::getAll(Import_Queue_Action::$IMPORT_STATUS_SCHEDULED);
		foreach ($importQueue as $importId => $importInfo) {
			$userId = $importInfo['user_id'];
			$user = new Users();
			$user->id = $userId;
			$user->retrieve_entity_info($userId, 'Users');

			$scheduledImports[$importId] = new Import_Data_Action($importInfo, $user);
		}
		return $scheduledImports;
	}

	public static function getScheduledImportCount() {
		$adb = PearDatabase::getInstance();
		$result = $adb->pquery("SELECT count(*) AS count FROM vtiger_import_queue WHERE status = ?", array(Import_Queue_Action::$IMPORT_STATUS_SCHEDULED));
		$count = $adb->query_result($result, 0, 'count');
		return $count;
	}

	/*
	 * Function to get Record details of import
	 * @parms $user <User Record Model> Current Users
	 * @returns <Array> Import Records with the list of skipped records and failed records
	 */
	public static function getImportDetails($user, $moduleName) {
		$adb = PearDatabase::getInstance();
		$tableName = Import_Utils_Helper::getDbTableName($user);
		$result = $adb->pquery("SELECT * FROM $tableName where status IN (?,?)", array(self::$IMPORT_RECORD_SKIPPED, self::$IMPORT_RECORD_FAILED));
		$importRecords = array();
		if ($result) {
			$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
			$headers = array_slice($adb->getColumnNames($tableName), 3);
			foreach($headers as $fieldName) {
				$fieldModel = $moduleModel->getField($fieldName);
				if($fieldModel) {
					$importRecords['headers'][] = $fieldModel;
				}
			}
			$noOfRows = $adb->num_rows($result);
			for ($i = 0; $i < $noOfRows; ++$i) {
				$row = $adb->fetchByAssoc($result, $i);
				$record = new Vtiger_Base_Model();
				foreach ($importRecords['headers'] as $header) {
					$record->set($header->getName(), $row[$header->getName()]);
				}
				if ($row['status'] == self::$IMPORT_RECORD_SKIPPED) {
					$importRecords['skipped'][] = $record;
				} else {
					$importRecords['failed'][] = $record;
				}
			}
			return $importRecords;
		}
	}

	public function getImportRecordStatus($value) {
		$status = '';
		switch ($value) {
			case 'created'	: $status = self::$IMPORT_RECORD_CREATED;	break;
			case 'skipped'	: $status = self::$IMPORT_RECORD_SKIPPED;	break;
			case 'updated'	: $status = self::$IMPORT_RECORD_UPDATED;	break;
			case 'merged'	: $status = self::$IMPORT_RECORD_MERGED;	break;
			case 'failed'	: $status = self::$IMPORT_RECORD_FAILED;	break;
			case 'none'		: $status = self::$IMPORT_RECORD_NONE;		break;
		}
		return $status;
	}

	public function importRecord($recordData, $operation) {
		$db = PearDatabase::getInstance();
		$entityInfo = null;
		$user = $this->user;
		$moduleName = $this->module;

		if ($moduleName === 'Calendar') {
			if ($recordData['activitytype']) {
				$moduleModel = Vtiger_Module_Model::getInstance($moduleName);
				$moduleFields = $moduleModel->getFields();

				foreach ($recordData as $fieldName => $fieldValue) {
					if ($fieldValue) {
						$fieldModel = $moduleFields[$fieldName];
						$fieldDataType = ($fieldModel) ? $fieldModel->getFieldDataType() : '';
						if (in_array($fieldDataType, array('reference', 'owner'))) {
							$valueComponents = vtws_getIdComponents($fieldValue);
							$recordData[$fieldName] = $valueComponents[1];
						} else if($fieldDataType == 'time') {
							$recordData[$fieldName] = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
						} else if($fieldDataType == 'boolean') {
							if($fieldValue == 1 || strtolower($fieldValue) == 'on' || strtolower($fieldValue) == 'yes') {
								$recordData[$fieldName] = true;
							} else {
								$recordData[$fieldName] = false;
							}
						} else if (!in_array($fieldName, array('date_start', 'due_date'))) {
							if ($fieldModel) {
								$recordData[$fieldName] = $fieldModel->getDisplayValue($fieldValue);
							}
						}
					}
				}

				foreach ($recordData as $fieldName => $fieldValue) {
					if (in_array($fieldName, array('date_start', 'due_date'))) {
						$timeField = 'time_start';
						if ($fieldName === 'due_date') {
							$timeField = 'time_end';
						}

						$dateParts = explode(' ', $fieldValue);
						$dateValue = $dateParts[0];
						if ($dateValue == null || $dateValue == '0000-00-00') {
							return $entityInfo;
						}
						$dateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($dateParts[0].' '.$recordData[$timeField]);

						list($fieldValue, $timeValue) = explode(' ', $dateTime);
						$recordData[$fieldName] = $fieldValue;
						if ($recordData[$timeField]) {
							$recordData[$timeField] = $timeValue;
						}
					}
				}

				unset($_REQUEST['contactidlist']);
				if ($recordData['contact_id']) {
					$contactIdsList = explode(', ', $recordData['contact_id']);
					if (count($contactIdsList) > 1) {
						$_REQUEST['contactidlist'] = implode(';', $contactIdsList);
					}
				}

				if ($recordData['time_end']) {
					$moduleName = 'Events';
					$recordData['eventstatus'] = $recordData['taskstatus'];
					unset($recordData['taskstatus']);
				} else {
					$recordData['activitytype'] = 'Task';
				}

				$eventModuleModel = Vtiger_Module_Model::getInstance('Events');
				$eventFields = $eventModuleModel->getFields();

				foreach ($recordData as $fieldName => $fieldValue) {
					$fieldModel = $moduleFields[$fieldName];
					if ($recordData['activitytype'] != 'Task') {
						$fieldModel = $eventFields[$fieldName];
					}

					$fieldDataType = ($fieldModel) ? $fieldModel->getFieldDataType() : '';
					if ($fieldDataType == 'picklist') {
						$fieldValue = trim($recordData[$fieldName]);
						$picklistValues = $fieldModel->getPicklistValues();

						$fieldValueInLowerCase = strtolower($fieldValue);
						$picklistValuesInLowerCase = array_map('strtolower', $picklistValues);
						if (sizeof($picklistValuesInLowerCase)&& sizeof($picklistValues)) {
							$picklistDetails = array_combine($picklistValuesInLowerCase, $picklistValues);
						}

						if (!in_array($fieldValueInLowerCase, $picklistValuesInLowerCase)
								&& $fieldName !== 'visibility'
								&& !($fieldName == 'activitytype' && $fieldValue == 'Task')) {
							$fieldModel->setPicklistValues(array($fieldValue));
						}
					}
				}

				if(!empty($recordData['createdtime'])) {
					$recordData['createdtime'] = Vtiger_Datetime_UIType::getDBDateTimeValue($recordData['createdtime']);
				}
				if ($operation != 'create') {
					$valueComponents = vtws_getIdComponents($recordData['id']);
					$recordData['id'] = $valueComponents[1];
					$recordData['mode'] = 'edit';
				}

				try {
					if ($recordData['id']) {
						$recordModel = Vtiger_Record_Model::getInstanceById($recordData['id'], $moduleName);
					} else {
						$recordModel = Vtiger_Record_Model::getCleanInstance($moduleName);
					}
					$recordModel->setData($recordData);
					$recordModel->save();

					$webServiceObj = VtigerWebserviceObject::fromName($db, $moduleName);
					$entityInfo['id'] = vtws_getId($webServiceObj->getEntityId(), $recordModel->getId());
					if ($entityInfo['id']) {
						switch($operation) {
							case 'create' : $entityInfo['status'] = self::$IMPORT_RECORD_CREATED;	break;
							case 'update' : $entityInfo['status'] = self::$IMPORT_RECORD_UPDATED;	break;
							case 'revise' : $entityInfo['status'] = self::$IMPORT_RECORD_MERGED;	break;
						}
					}
				} catch (Exception $e) {
					if ($operation != 'create') {
						$entityInfo['status'] = self::$IMPORT_RECORD_SKIPPED;
					}
				}
				unset($_REQUEST['contactidlist']);
			}
			return $entityInfo;
		}

		try {
			if ($recordData) {
				switch($operation) {
					case 'create' : $entityInfo = vtws_create($moduleName, $recordData, $user);
									$entityInfo['status'] = self::$IMPORT_RECORD_CREATED;
									break;

					case 'update' : $entityInfo = vtws_update($recordData, $user);
									$entityInfo['status'] = self::$IMPORT_RECORD_UPDATED;
									break;

					case 'revise' : $entityInfo = vtws_revise($recordData, $user);
									$entityInfo['status'] = self::$IMPORT_RECORD_MERGED;
									break;
				}
			}
		} catch (Exception $e) {
			if ($operation != 'create') {
				$entityInfo['status'] = self::$IMPORT_RECORD_SKIPPED;
			}
		}

		return $entityInfo;
	}

	public function getEntityIdsList($referenceModuleName, $fieldValueDetails) {
		$entityIdsList = array();
		if ($referenceModuleName && $fieldValueDetails) {
			foreach ($fieldValueDetails as $value) {
				$entityLabel = str_replace($referenceModuleName, '', $value);
				$entityLabel = trim(trim($entityLabel), ',');
				$entityId = getEntityId($referenceModuleName, decode_html($entityLabel));
				if (!$entityId) {
					if (isPermitted($referenceModuleName, 'CreateView') == 'yes') {
						try {
							$wsEntityIdInfo = $this->createEntityRecord($referenceModuleName, $entityLabel);
							$wsEntityId = $wsEntityIdInfo['id'];
							$entityIdComponents = vtws_getIdComponents($wsEntityId);
							$entityId = $entityIdComponents[1];
						} catch (Exception $e) {
						}
					}
				}
				if ($entityId) {
					$entityIdsList[] = $entityId;
				}
			}
		}
		return $entityIdsList;
	}
}
?>
