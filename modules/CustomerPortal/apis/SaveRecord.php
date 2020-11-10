<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class CustomerPortal_SaveRecord extends CustomerPortal_FetchRecord {

	protected $recordValues = false;
	protected $mode = 'edit';

	protected function isNewRecordRequest(CustomerPortal_API_Request $request) {
		$recordid = $request->get('recordId');
		return (preg_match("/([0-9]+)x0/", $recordid));
	}

	function process(CustomerPortal_API_Request $request) {
		$response = new CustomerPortal_API_Response();
		global $current_user;
		$current_user = $this->getActiveUser();

		if ($current_user) {
			$module = $request->get('module');

			if (!CustomerPortal_Utils::isModuleActive($module)) {
				throw new Exception("Module not accessible", 1412);
				exit;
			}



			if (in_array($module, array('HelpDesk', 'Documents', 'Assets', 'Quotes', 'Contacts', 'Accounts'))) {
				$recordId = $request->get('recordId');
				if (!empty($recordId)) {
					//Stop edit record if edit is disabled
					if (!CustomerPortal_Utils::isModuleRecordEditable($module)) {
						throw new Exception("Module record cannot be edited", 1412);
						exit;
					}
				} else {
					if (!CustomerPortal_Utils::isModuleRecordCreatable($module)) {
						throw new Exception("Module record cannot be created", 1412);
						exit;
					}
				}
				$valuesJSONString = $request->get('values', '', false);
				$values = "";

				if (!empty($valuesJSONString) && is_string($valuesJSONString)) {
					$values = Zend_Json::decode($valuesJSONString);
				} else {
					$values = $valuesJSONString; // Either empty or already decoded.
				}
				//Avoiding fetching fields from customerportal_fields for Accounts and Contacts
				if ($module !== 'Contacts' && $module !== 'Accounts') {
					//get active fieids with read , write permissions 
					$activeFields = CustomerPortal_Utils::getActiveFields($module, true);
					$editableFields = array();

					foreach ($activeFields as $key => $value) {
						if ($value == 1)
							$editableFields[] = $key;
					}
					if ($module == 'HelpDesk') {
						$editableFields[] = 'serviceid';
						$editableFields[] = 'ticketstatus';
						$editableFields[] = 'ticketpriorities';
					}
					if ($module == 'Quotes') {
						$editableFields[] = 'quotestage';
					}

					if (!empty($values)) {
						foreach ($values as $key => $value) {
							if (!in_array($key, $editableFields)) {
								throw new Exception("Specified fields not editable", 1412);
								exit;
							}
						}
					}
				}

				try {
					if (vtws_recordExists($recordId)) {
						// Retrieve or Initalize
						if (!empty($recordId) && !$this->isNewRecordRequest($request)) {
							$this->recordValues = vtws_retrieve($recordId, $current_user);
						} else {
							$this->recordValues = array();
							// set assigned user to default assignee
							$this->recordValues['assigned_user_id'] = CustomerPortal_Settings_Utils::getDefaultAssignee();
						}

						// Set the modified values
						if (!empty($values)) {
							foreach ($values as $name => $value) {
								$this->recordValues[$name] = $value;
							}
						}
						// set contact , Organization for Helpdesk record
						if ($module == 'HelpDesk') {
							$contactId = vtws_getWebserviceEntityId('Contacts', $this->getActiveCustomer()->id);
							$this->recordValues['contact_id'] = $contactId;
							$this->recordValues['from_portal'] = true;
							$accountId = $this->getParent($contactId);
							if (!empty($accountId))
								$this->recordValues['parent_id'] = $accountId;
						}

						if ($module == 'Documents' && count($_FILES)) {
							$file = $_FILES['file'];
							$this->recordValues['notes_title'] = $request->get('filename');
							$this->recordValues['filelocationtype'] = 'I'; // location type is internal
							$this->recordValues['filestatus'] = '1'; //status always active
							$this->recordValues['filename'] = $file['name'];
							$this->recordValues['filetype'] = $file['type'];
							$this->recordValues['filesize'] = $file['size'];
						}

						// Setting missing mandatory fields for record.
						$describe = vtws_describe($module, $current_user);
						$mandatoryFields = CustomerPortal_Utils:: getMandatoryFields($describe);
						foreach ($mandatoryFields as $fieldName => $type) {
							if (!isset($this->recordValues[$fieldName])) {
								if ($type['name'] == 'reference') {
									$crmId = Vtiger_Util_Helper::fillMandatoryFields($fieldName, $module);
									$wsId = vtws_getWebserviceEntityId($type['refersTo'][0], $crmId);
									$this->recordValues[$fieldName] = $wsId;
								} else {
									$this->recordValues[$fieldName] = Vtiger_Util_Helper::fillMandatoryFields($fieldName, $module);
								}
							}
						}
						// Update or Create
						if (isset($this->recordValues['id'])) {
							if ($module == 'Contacts' || $module == 'Accounts') {
								$updatedStatus = vtws_update($this->recordValues, $current_user);
								if ($updatedStatus['id'] == $recordId) {
									$response = new CustomerPortal_API_Response();
									$response->setResult($updatedStatus);
								} else {
									$response->setError("RECORD_NOT_FOUND", "Record does not exist");
								}
								return $response;
							}
							foreach ($mandatoryFields as $fieldName => $type) {
								if (!isset($this->recordValues[$fieldName]) || empty($this->recordValues[$fieldName])) {
									if ($type['name'] !== 'reference') {
										$this->recordValues[$fieldName] = Vtiger_Util_Helper::fillMandatoryFields($fieldName, $module);
									}
								}
							}
							$this->recordValues = vtws_update($this->recordValues, $current_user);
						} else {
							$this->mode = 'create';
							//Setting source to customer portal
							$this->recordValues['source'] = 'CUSTOMER PORTAL';
							$this->recordValues = vtws_create($module, $this->recordValues, $current_user);
						}

						// Update the record id
						$request->set('recordId', $this->recordValues['id']);
						$idComponents = explode('x', $this->recordValues['id']);
						$recordId = $idComponents[1];

						//Adding relation to Service Contracts

						if ($module == 'HelpDesk' && !empty($values['serviceid'])) {
							$contact = new Contacts();
							$serviceId = $values['serviceid'];
							$ids = explode('x', $serviceId);
							$crmId = explode('x', $this->recordValues['id']);
							$contact->save_related_module('HelpDesk', $crmId[1], 'ServiceContracts', array($ids[1]));
						}

						if ($module == 'Documents') {
							$contact = new Contacts();
							$contact->save_related_module('Contacts', $this->getActiveCustomer()->id, 'Documents', array($recordId));

							//relate Document with a Ticket OR Project
							$parentId = $request->get('parentId');

							if (!empty($parentId) && $this->isRecordAccessible($parentId)) {
								$focus = CRMEntity::getInstance('Documents');
								$parentIdComponents = explode('x', $parentId);
								$focus->insertintonotesrel($parentIdComponents[1], $recordId);
							}
						}

						if (count($_FILES)) {
							$_FILES = Vtiger_Util_Helper::transformUploadedFiles($_FILES, true);
							$attachmentType = $request->get('attachmentType');
							$focus = CRMEntity::getInstance($module);
							$focus->uploadAndSaveFile($recordId, $module, $_FILES['file'], $attachmentType);
						}

						// Gather response with full details
						$response = parent::process($request);
					} else {
						$response->setError("RECORD_NOT_FOUND", "Record does not exist");
					}
				} catch (Exception $e) {
					$response->setError($e->getCode(), $e->getMessage());
				}
			} else {
				$response->setError(1404, 'save operation not supported for this module');
			}
			return $response;
		}
	}

}
