<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

include_once 'config.php';
require_once 'include/utils/utils.php';
include_once 'include/Webservices/Query.php';
require_once 'include/Webservices/QueryRelated.php';
require_once 'includes/runtime/Cache.php';
include_once 'include/Webservices/DescribeObject.php';
require_once 'modules/Vtiger/helpers/Util.php';
include_once 'modules/Settings/MailConverter/handlers/MailScannerAction.php';
include_once 'modules/Settings/MailConverter/handlers/MailAttachmentMIME.php';
include_once 'modules/MailManager/MailManager.php';

class MailManager_Relation_View extends MailManager_Abstract_View {

	/**
	 * Used to check the MailBox connection
	 * @var Boolean
	 */
	protected $skipConnection = false;

	/** To avoid working with mailbox */
	protected function getMailboxModel() {
		if ($this->skipConnection) return false;
		return parent::getMailboxModel();
	}

	/**
	 * List of modules used to match the Email address
	 * @var Array
	 */
	static $MODULES = array ( 'Contacts', 'Accounts', 'Leads', 'HelpDesk', 'Potentials');

	/**
	 * Process the request to perform relationship operations
	 * @global Users Instance $currentUserModel
	 * @global PearDataBase Instance $adb
	 * @global String $currentModule
	 * @param Vtiger_Request $request
	 * @return boolean
	 */
	public function process(Vtiger_Request $request) {
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$response = new MailManager_Response(true);
		$viewer = $this->getViewer($request);

		if ('find' == $this->getOperationArg($request)) {
			// Check if the message is already linked.
			$msgUid = $request->get('_msguid');
			if(!empty($msgUid)) {
				$linkedto = MailManager_Relate_Action::associatedLink($request->get('_msguid'));
			}
			// If the message was not linked, lookup for matching records, using FROM address
			if (empty($linkedto)) {
				$results = array();
				$allowedModules = $this->getCurrentUserMailManagerAllowedModules();
				foreach (self::$MODULES as $MODULE) {
					if(!in_array($MODULE, $allowedModules)) continue;

					//lookup will be from email other than sent mail folder 
					$lookupEmail = $request->get('_mfrom');
					$foldername = $request->get('_folder');
					$connector = $this->getConnector($foldername);
					$folder = $connector->folderInstance($foldername);
					$isSentFolder = $folder->isSentFolder();
					//if its sent folder, lookup email will be first TO email
					if($isSentFolder) {
						$toEmail = $request->get('_mto');
						$toEmail = explode(',', $toEmail);
						$lookupEmail = $toEmail[0];
					}
					if(empty($lookupEmail)) continue;

					$lookupResults = $this->lookupModuleRecordsWithEmail($MODULE, $lookupEmail);

					foreach ($lookupResults as $lookupResult) {
						if(array_key_exists('parent', $lookupResult)) {
							$results[getSalesEntityType($lookupResult['id'])][] = $lookupResult;
						}else{
							$results[$MODULE][] = $lookupResult;
						}
					}
				}
				$viewer->assign('LOOKUPS', $results);
			} else {
				$viewer->assign('LINKEDTO', $linkedto);
			}
			$jsFileNames = array("~libraries/jquery/instaFilta/instafilta.min.js");
			$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
			$viewer->assign('HEADER_SCRIPTS', $jsScriptInstances);
			$viewer->assign('LINK_TO_AVAILABLE_ACTIONS', $this->linkToAvailableActions());
			$viewer->assign('ALLOWED_MODULES', $allowedModules);
			$viewer->assign('MSGNO', $request->get('_msgno'));
			$viewer->assign('FOLDER', $request->get('_folder'));

			$response->setResult( array( 'ui' => $viewer->view( 'Relationship.tpl', 'MailManager', true ) ) );

		} else if ('link' == $this->getOperationArg($request)) {

			$linkto = $request->get('_mlinkto');
			$foldername = $request->get('_folder');
			$connector = $this->getConnector($foldername);

			// This is to handle larger uploads
			$memory_limit = MailManager_Config_Model::get('MEMORY_LIMIT');
			ini_set('memory_limit', $memory_limit);

			$mail = $connector->openMail($request->get('_msgno'), $foldername);
			$mail->attachments(); // Initialize attachments

			$linkedto = MailManager_Relate_Action::associate($mail, $linkto);

			$viewer->assign('LINK_TO_AVAILABLE_ACTIONS', $this->linkToAvailableActions());
			$viewer->assign('ALLOWED_MODULES', $this->getCurrentUserMailManagerAllowedModules());
			$viewer->assign('LINKEDTO', $linkedto);
			$viewer->assign('MSGNO', $request->get('_msgno'));
			$viewer->assign('FOLDER', $foldername);
			$response->setResult( array( 'ui' => $viewer->view( 'Relationship.tpl', 'MailManager', true ) ) );

		} else if ('create_wizard' == $this->getOperationArg($request)) {
			$moduleName = $request->get('_mlinktotype');
			if(!vtlib_isModuleActive($moduleName) && $moduleName != 'Events') {
				$response->setResult(array('error'=>vtranslate('LBL_OPERATION_NOT_PERMITTED', $moduleName)));
				return $response;
			}
			if($moduleName == 'Events' && !vtlib_isModuleActive('Calendar')) {
				$response->setResult(array('error'=>vtranslate('LBL_OPERATION_NOT_PERMITTED', $moduleName)));
				return $response;
			}
			$parent =  $request->get('_mlinkto');
			$foldername = $request->get('_folder');

			$connector = $this->getConnector($foldername);
			$mail = $connector->openMail($request->get('_msgno'), $foldername);
			$folder = $connector->folderInstance($foldername);
			$isSentFolder = $folder->isSentFolder();
			$formData = $this->processFormData($mail, $isSentFolder);
			foreach ($formData as $key => $value) {
				$request->set($key, $value);
			}

			$linkedto = MailManager_Relate_Action::getSalesEntityInfo($parent);
			switch ($moduleName) {
				case 'HelpDesk' :   $from = $mail->from();
									if ($parent) {
										if($linkedto['module'] == 'Contacts') {
											$referenceFieldName = 'contact_id';
										} elseif ($linkedto['module'] == 'Accounts') {
											$referenceFieldName = 'parent_id';
										}
										$request->set($referenceFieldName, $this->setParentForHelpDesk($parent, $from));
									}
									break;
									
				case 'Potentials' : if ($parent) {
										if($linkedto['module'] == 'Contacts') {
											$referenceFieldName = 'contact_id';
										} elseif ($linkedto['module'] == 'Accounts') {
											$referenceFieldName = 'related_to';
										}
										$request->set($referenceFieldName, $request->get('_mlinkto'));
									}
									break;
				case 'Events'	:
				case 'Calendar' :   if ($parent) {
										if($linkedto['module'] == 'Contacts') {
											$referenceFieldName = 'contact_id';
										} elseif ($linkedto['module'] == 'Accounts') {
											$referenceFieldName = 'parent_id';
										}
										$request->set($referenceFieldName, $parent);
									}
									break;
			}

			$request->set('module', $moduleName);

			// Delegate QuickCreate FormUI to the target view controller of module.
			$quickCreateviewClassName = $moduleName . '_QuickCreateAjax_View';
			if (!class_exists($quickCreateviewClassName)) {
				$quickCreateviewClassName = 'Vtiger_QuickCreateAjax_View';
			}
			$quickCreateViewController = new $quickCreateviewClassName();
			$quickCreateViewController->process($request);

			// UI already sent
			$response = false;

		} else if ('create' == $this->getOperationArg($request)) {
			$linkModule = $request->get('_mlinktotype');

			if(!vtlib_isModuleActive($linkModule)) {
				$response->setResult(array('ui'=>'', 'error'=>vtranslate('LBL_OPERATION_NOT_PERMITTED', $moduleName)));
				return $response;
			}

			$parent =  $request->get('_mlinkto');
			$foldername = $request->get('_folder');

			if(!empty($foldername)) {
				// This is to handle larger uploads
				$memory_limit = MailManager_Config_Model::get('MEMORY_LIMIT');
				ini_set('memory_limit', $memory_limit);

				$connector = $this->getConnector($foldername);
				$mail = $connector->openMail($request->get('_msgno'), $foldername);
				$attachments = $mail->attachments(); // Initialize attachments
			}

			$linkedto = MailManager_Relate_Action::getSalesEntityInfo($parent);
			$recordModel = Vtiger_Record_Model::getCleanInstance($linkModule);

			$fields = $recordModel->getModule()->getFields();
			foreach ($fields as $fieldName => $fieldModel) {
				if ($request->has($fieldName)) {
					$fieldValue = $request->get($fieldName);
					$fieldDataType = $fieldModel->getFieldDataType();
					if($fieldDataType == 'time') {
						$fieldValue = Vtiger_Time_UIType::getTimeValueWithSeconds($fieldValue);
					}
					$recordModel->set($fieldName, $fieldValue);
				}
			}

			// Newly added field for source of created record
			if($linkModule != "ModComments"){
				$recordModel->set('source','Mail Manager');
			}

			switch ($linkModule) {
				case 'Calendar' :   $activityType = $recordModel->get('activitytype');
									if (!$activityType) {
										$activityType = 'Task';
									}
									$recordModel->set('activitytype', $activityType);

									//Start Date and Time values
									$startTime = Vtiger_Time_UIType::getTimeValueWithSeconds($request->get('time_start'));
									$startDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($request->get('date_start')." ".$startTime);
									list($startDate, $startTime) = explode(' ', $startDateTime);

									$recordModel->set('date_start', $startDate);
									$recordModel->set('time_start', $startTime);

									//End Date and Time values
									$endDate = Vtiger_Date_UIType::getDBInsertedValue($request->get('due_date'));
									if ($activityType != 'Task') {
										$endTime = Vtiger_Time_UIType::getTimeValueWithSeconds($request->get('time_end'));
										$endDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($request->get('due_date')." ".$endTime);
										list($endDate, $endTime) = explode(' ', $endDateTime);
									} else {
										$endTime = '';
									}
									$recordModel->set('time_end', $endTime);
									$recordModel->set('due_date', $endDate);

									if($parent) {
										if($linkedto['module'] == 'Contacts') {
											$recordModel->set('contact_id', $parent);
										} else {
											$recordModel->set('parent_id', $parent);
										}
									}
									$recordModel->set('visibility', 'Public');
									break;

				case 'HelpDesk' :   $from = $mail->from();
									if ($parent) {
										if($linkedto['module'] == 'Contacts') {
											$referenceFieldName = 'contact_id';
										} elseif ($linkedto['module'] == 'Accounts') {
											$referenceFieldName = 'parent_id';
										}
									}
									if(!$request->has($referenceFieldName)) {
										$recordModel->set($referenceFieldName, $this->setParentForHelpDesk($parent, $from));
									}
									break;

				case 'ModComments': $recordModel->set('assigned_user_id', $currentUserModel->getId());
									$recordModel->set('commentcontent', $request->getRaw('commentcontent'));
									$recordModel->set('userid', $currentUserModel->getId());
									$recordModel->set('creator', $currentUserModel->getId());
									$recordModel->set('related_to', $parent);
									break;
			}

			try {
				$recordModel->save();

				// This condition is added so that emails are not created for Tickets and Todo without Parent,
				// as there is no way to relate them
				if(empty($parent) && $linkModule != 'HelpDesk' && $linkModule != 'Calendar') {
					$linkedto = MailManager_Relate_Action::associate($mail, $recordModel->getId());
				}

				if ($linkModule === 'Calendar') {
					// Handled to save follow up event
					$followupMode = $request->get('followup');

					//Start Date and Time values
					$startTime = Vtiger_Time_UIType::getTimeValueWithSeconds($request->get('followup_time_start'));
					$startDateTime = Vtiger_Datetime_UIType::getDBDateTimeValue($request->get('followup_date_start') . " " . $startTime);
					list($startDate, $startTime) = explode(' ', $startDateTime);

					$subject = $request->get('subject');
					if($followupMode == 'on' && $startTime != '' && $startDate != '') {
						$recordModel->set('eventstatus', 'Planned');
						$recordModel->set('subject', '[Followup] '.$subject);
						$recordModel->set('date_start', $startDate);
						$recordModel->set('time_start', $startTime);

						$currentUser = Users_Record_Model::getCurrentUserModel();
						$activityType = $recordModel->get('activitytype');
						if($activityType == 'Call') {
							$minutes = $currentUser->get('callduration');
						} else {
							$minutes = $currentUser->get('othereventduration');
						}
						$dueDateTime = date('Y-m-d H:i:s', strtotime("$startDateTime+$minutes minutes"));
						list($startDate, $startTime) = explode(' ', $dueDateTime);

						$recordModel->set('due_date', $startDate);
						$recordModel->set('time_end', $startTime);
						$recordModel->set('recurringtype', '');
						$recordModel->set('mode', 'create');
						$recordModel->save();
					}
				}

				// add attachments to the tickets as Documents
				if($linkModule == 'HelpDesk' && !empty($attachments)) {
					$relationController = new MailManager_Relate_Action();
					$relationController->__SaveAttachements($mail, $linkModule, $recordModel);
				}

				$viewer->assign('MSGNO', $request->get('_msgno'));
				$viewer->assign('LINKEDTO', $linkedto);
				$viewer->assign('ALLOWED_MODULES', $this->getCurrentUserMailManagerAllowedModules());
				$viewer->assign('LINK_TO_AVAILABLE_ACTIONS', $this->linkToAvailableActions());
				$viewer->assign('FOLDER', $foldername);

				$response->setResult( array( 'ui' => $viewer->view( 'Relationship.tpl', 'MailManager', true ) ) );
			} catch (DuplicateException $e) {
				$response->setResult(array('ui' => '', 'error' => $e, 'title' => $e->getMessage(), 'message' => $e->getDuplicationMessage()));
			} catch(Exception $e) {
				$response->setResult( array( 'ui' => '', 'error' => $e ));
			}

		} else if ('savedraft' == $this->getOperationArg($request)) {
			$connector = $this->getConnector('__vt_drafts');
			$draftResponse = $connector->saveDraft($request);
			$response->setResult($draftResponse);
		} else if ('saveattachment' == $this->getOperationArg($request)) {
			$connector = $this->getConnector('__vt_drafts');
			$uploadResponse = $connector->saveAttachment($request);
			$response->setResult($uploadResponse);
		} else if ('commentwidget' == $this->getOperationArg($request)) {
			$viewer->assign('LINKMODULE', $request->get('_mlinktotype'));
			$viewer->assign('PARENT', $request->get('_mlinkto'));
			$viewer->assign('MSGNO', $request->get('_msgno'));
			$viewer->assign('FOLDER', $request->get('_folder'));
			$viewer->assign('MODULE', $request->getModule());
			$viewer->view( 'MailManagerCommentWidget.tpl', 'MailManager' );
			$response = false;
		}
		return $response;
	}

	/**
	 * Returns the Parent for Tickets module
	 * @global Users Instance $currentUserModel
	 * @param Integer $parent - crmid of Parent
	 * @param Email Address $from - Email Address of the received mail
	 * @return Integer - Parent(crmid)
	 */
	public function setParentForHelpDesk($parent, $from) {
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		if(empty($parent)) {
			if(!empty($from)) {
				$parentInfo = MailManager::lookupMailInVtiger($from[0], $currentUserModel);
				if(!empty($parentInfo[0]['record'])) {
					$parentId = vtws_getIdComponents($parentInfo[0]['record']);
					return $parentId[1];
				}
			}
		} else {
			return $parent;
		}
	}


	/**
	 * Function used to set the record fields with the information from mail.
	 * @param Array $qcreate_array
	 * @param MailManager_Message_Model $mail
	 * @return Array
	 */
	public function processFormData($mail, $isSentFolder = false) {
		$subject = $mail->subject();
		$email = $mail->from();
		if($isSentFolder) {
			$email = $mail->to();
			if(!empty($email)) $mail_address = implode(',', $email);
		} else {
			if(!empty($email)) $mail_address = implode(',', $email);
		}

		if(!empty($mail_address)) $name = explode('@', $mail_address);
		if(!empty($name[1])) $companyName = explode('.', $name[1]);

		$defaultFieldValueMap =  array( 'lastname'	=>	$name[0],
				'email'			=> $email[0],
				'email1'		=> $email[0],
				'accountname'	=> $companyName[0],
				'company'		=> $companyName[0],
				'ticket_title'	=> $subject,
				'potentialname' => $subject,
				'subject'		=> $subject,
				'title'			=> $subject,
		);
		return $defaultFieldValueMap;
	}

	/**
	 * Returns the available List of accessible modules for Mail Manager
	 * @return Array
	 */
	public function getCurrentUserMailManagerAllowedModules() {
		$moduleListForCreateRecordFromMail = array('Contacts', 'Accounts', 'Leads', 'HelpDesk', 'Calendar', 'Potentials');

		foreach($moduleListForCreateRecordFromMail as $module) {
			if(MailManager::checkModuleWriteAccessForCurrentUser($module)) {
				$mailManagerAllowedModules[] = $module;
			}
		}
		return $mailManagerAllowedModules;
	}

	/**
	 * Returns the list of accessible modules on which Actions(Relationship) can be taken.
	 * @return string
	 */
	public function linkToAvailableActions() {
		$moduleListForLinkTo = array('Calendar','HelpDesk','ModComments','Emails','Potentials');

		foreach($moduleListForLinkTo as $module) {
			if(MailManager::checkModuleWriteAccessForCurrentUser($module)) {
				$mailManagerAllowedModules[] = $module;
			}
		}
		return $mailManagerAllowedModules;
	}

	/**
	 * Helper function to scan for relations
	 */
	protected $wsDescribeCache = array();
	public function ws_describe($module) {
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		if (!isset($this->wsDescribeCache[$module])) {
			$this->wsDescribeCache[$module] = vtws_describe( $module, $currentUserModel);
		}
		return $this->wsDescribeCache[$module];
	}

	/**
	 * Funtion used to build Web services query
	 * @param String $module - Name of the module
	 * @param String $text - Search String
	 * @param String $type - Tyoe of fields Phone, Email etc
	 * @return String
	 */
	public function buildSearchQuery($module, $text, $type) {
		$describe = $this->ws_describe($module);
		// to check whether fields are accessible to current_user or not
		$labelFields = explode(',',$describe['labelFields']);

		//overwrite labelfields with field names instead of column names
		$currentUserModel = vglobal('current_user');
		$handler = vtws_getModuleHandlerFromName($module, $currentUserModel);
		$meta = $handler->getMeta();
		$fieldColumnMapping = $meta->getFieldColumnMapping();
		$columnFieldMapping = array_flip($fieldColumnMapping);
		foreach ($labelFields as $i => $columnname) {
			$labelFields[$i] = $columnFieldMapping[$columnname];
		}

		foreach($labelFields as $fieldName){
			foreach($describe['fields'] as $describefield){
				if($describefield['name'] == $fieldName){
					$searchFields[] = $fieldName;
					break;
				}
			}
		}

		$whereClause = '';
		foreach($describe['fields'] as $field) {
			if (strcasecmp($type, $field['type']['name']) === 0) {
				$whereClause .= sprintf( " %s LIKE '%%%s%%' OR", $field['name'], $text );
			}
		}
		return sprintf( "SELECT %s FROM %s WHERE %s;", implode(',',$searchFields), $module, rtrim($whereClause, 'OR') );
	}

	/**
	 * Returns the List of Matching records with the Email Address
	 * @global Users Instance $currentUserModel
	 * @param String $module
	 * @param Email Address $email
	 * @return Array
	 */
	public function lookupModuleRecordsWithEmail($module, $email) {
		$currentUserModel = vglobal('current_user');
		$results = array();
		$activeEmailFields = null;

		$handler = vtws_getModuleHandlerFromName($module, $currentUserModel);
		$meta = $handler->getMeta();
		$emailFields = $meta->getEmailFields();
		$moduleFields = $meta->getModuleFields();
		foreach($emailFields as $emailFieldName){
			$emailFieldInstance = $moduleFields[$emailFieldName];
			if(!(((int)$emailFieldInstance->getPresence()) == 1)) {
				$activeEmailFields[] = $emailFieldName;
			}
		}
		//before calling vtws_query, need to check Active Email Fields are there or not
		if(count($activeEmailFields) > 0) {
			$query = $this->buildSearchQuery($module, $email, 'EMAIL');
			$qresults = vtws_query( $query, $currentUserModel );
			$describe = $this->ws_describe($module);
			$labelFields = explode(',', $describe['labelFields']);

			//overwrite labelfields with field names instead of column names
			$fieldColumnMapping = $meta->getFieldColumnMapping();
			$columnFieldMapping = array_flip($fieldColumnMapping);

			foreach ($labelFields as $i => $columnname) {
				$labelFields[$i] = $columnFieldMapping[$columnname];
			}

			foreach($qresults as $qresult) {
				$labelValues = array();
				foreach($labelFields as $fieldname) {
					if(isset($qresult[$fieldname])) $labelValues[] = $qresult[$fieldname];
				}
				$ids = vtws_getIdComponents($qresult['id']);
				$results[] = array( 'wsid' => $qresult['id'], 'id' => $ids[1], 'label' => implode(' ', $labelValues));
			}
		}


		if(!empty($results)) {
			foreach ($results as $result) {
				$relResults = $this->lookupRelModuleRecords($result['wsid']);
				$results = array_merge($results, $relResults);
			}
		}
		return $results;
	}

	/**
	 * Function to lookup rel records(which supports emails only) of records
	 * @param <string> $wsId
	 * @return <array> $results
	 */
	public function lookupRelModuleRecords($wsId) {
		$currentUser = vglobal('current_user');
		$results = array();
		/* Harcoded to fecth only project records. In future we should treat 
		 * below $relModules array as modules which support emails and related to 
		 * parent module.
		 */
		$relModules = array('Project');
		$db = PearDatabase::getInstance();
		$wsObject = VtigerWebserviceObject::fromId($db, $wsId);
		$entityName = $wsObject->getEntityName();

		foreach ($relModules as $relModule) {
			$relation = Vtiger_Relation_Model::getInstanceByModuleName($entityName, $relModule);
			if(!$relation) {
				continue;
			}
			$relDescribe = $this->ws_describe($relModule);
			$labelFields = explode(',', $relDescribe['labelFields']);
			$relHandler = vtws_getModuleHandlerFromName($relModule, $currentUser);
			$relMeta = $relHandler->getMeta();
			//overwrite labelfields with field names instead of column names
			$fieldColumnMapping = $relMeta->getFieldColumnMapping();
			$columnFieldMapping = array_flip($fieldColumnMapping);

			foreach ($labelFields as $i => $columnname) {
				$labelFields[$i] = $columnFieldMapping[$columnname];
			}

			$sql = sprintf("SELECT %s FROM %s",  implode(',', $labelFields),$relModule);
			$relQResults = vtws_query_related($sql, $wsId, $relation->get('label'), $currentUser);

			foreach($relQResults as $qresult) {
				$labelValues = array();
				foreach($labelFields as $fieldname) {
					if(isset($qresult[$fieldname])) $labelValues[] = $qresult[$fieldname];
				}
				$ids = vtws_getIdComponents($qresult['id']);
				$results[] = array( 'wsid' => $qresult['id'], 'id' => $ids[1], 'label' => implode(' ', $labelValues),'parent' => $wsId);
			}
		}
		return $results;
	}

	public function validateRequest(Vtiger_Request $request) {
		return $request->validateWriteAccess();
	}
}
?>