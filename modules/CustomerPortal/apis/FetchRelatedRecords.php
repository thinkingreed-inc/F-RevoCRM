<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class CustomerPortal_FetchRelatedRecords extends CustomerPortal_API_Abstract {

	function process(CustomerPortal_API_Request $request) {
		global $adb;
		$response = new CustomerPortal_API_Response();
		$current_user = $this->getActiveUser();

		if ($current_user) {
			$recordId = $request->get('recordId');
			$parentId = $request->get('parentId');
			$parentModule = $request->get('module');
			$module = $request->get('relatedModule');
			$moduleLabel = $request->get('relatedModuleLabel');
			$page = $request->get('page');
			$pageLimit = $request->get('pageLimit');
			$mode = CustomerPortal_Settings_Utils::getDefaultMode($moduleLabel);
			if (empty($pageLimit))
				$pageLimit = CustomerPortal_Config::$DEFAULT_PAGE_LIMIT;

			if (empty($page)) {
				$page = 0;
			}

			if ($module != 'ModComments' && !CustomerPortal_Utils::isModuleActive($module)) {
				throw new Exception("Module not Accessible", 1412);
				exit;
			}

			if (!empty($parentId)) {
				if (!$this->isRecordAccessible($parentId)) {
					throw new Exception("Parent record not accessible", 1412);
					exit;
				}
				$baseModule = VtigerWebserviceObject::fromId($adb, $recordId)->getEntityName();
				$relatedRecordIds = $this->relatedRecordIds($baseModule, CustomerPortal_Utils::getRelatedModuleLabel($baseModule), $parentId);

				if (!in_array($recordId, $relatedRecordIds)) {
					throw new Exception("Record not Accessible", 1412);
					exit;
				}
			} else if ($parentModule !== 'Faq') {
				if (!$this->isRecordAccessible($recordId) && $prentModule !== 'Faq') {
					throw new Exception("Record not accessible", 1412);
					exit;
				}
			} else {
				//If module is Faq by pass this check as we Faq's are not related to Contacts module.
				if (!$this->isFaqPublished($recordId)) {
					throw new Exception("This Faq is not published", 1412);
					exit;
				}
			}

			if ($module == 'ModComments' && !empty($recordId)) {
				global $adb;
				$relatedModule = VtigerWebserviceObject::fromId($adb, $recordId)->getEntityName();

				if (!CustomerPortal_Utils::isModuleActive($relatedModule)) {
					throw new Exception("Comments not accessible for this record", 1412);
					exit;
				}
				$result = vtws_query(sprintf("SELECT * FROM ModComments WHERE related_to = '%s' AND is_private='%s' ORDER BY %s DESC LIMIT %s,%s;", $recordId, 0, 'modifiedtime', ($page * $pageLimit), $pageLimit), $current_user);

				$fileIds = array();
				$$relatedEmailIds = array();
				if (is_array($result)) {
					foreach ($result as $index => $value) {
						$fileId = $value['filename'];
						$attachmentIds = explode(',', $fileId);
						//Fetching all attachments and its properties and appending to each comment.
						if (!empty($attachmentIds)) {
							$attachmentsResult = $adb->pquery('SELECT attachmentsid,name FROM vtiger_attachments WHERE attachmentsid IN ('.generateQuestionMarks($attachmentIds).')', $attachmentIds);
							$result[$index]['attachments'] = array();
							$noOfAttachments = $adb->num_rows($attachmentsResult);
							$attachments = array();
							for ($i = 0; $i < $noOfAttachments; $i++) {
								$attachments[$i]['filename'] = decode_html($adb->query_result($attachmentsResult, $i, 'name'));
								$attachments[$i]['attachmentid'] = $adb->query_result($attachmentsResult, $i, 'attachmentsid');
							}
						}
						$result[$index]['attachments'] = $attachments;
						$relatedEmailId = $value['related_email_id'];
						if (!empty($relatedEmailId)) {
							$relatedEmailIds[$value['id']] = $relatedEmailId;
						}
						if ($value['commentcontent']) {
							$result[$index]['commentcontent'] = trim(decode_html(strip_tags($value['commentcontent'])));
						}
					}
				}
				if (!empty($relatedEmailIds)) {
					foreach ($relatedEmailIds as $id => $emailId) {
						$attachmentsResult = $adb->pquery('SELECT * FROM vtiger_attachments
                            INNER JOIN vtiger_seattachmentsrel ON vtiger_attachments.attachmentsid = vtiger_seattachmentsrel.attachmentsid
                            WHERE vtiger_seattachmentsrel.crmid = ?', array($emailId));
						if ($row = $adb->fetch_row($attachmentsResult)) {
							foreach ($result as $index => $value) {
								if ($id == $value['id'])
									$result[$index]['attachmentName'] = decode_html($row['name']);
							}
						}
					}
				}
			} else {
				$activeFields = CustomerPortal_Utils::getActiveFields($module);
				$fields = implode(',', $activeFields);
				$limitCaluse = sprintf('ORDER BY modifiedtime DESC LIMIT %s,%s', ($page * $pageLimit), $pageLimit);
				if ($mode == 'all' && in_array($module, array('Products', 'Services'))) {
					$sql = sprintf("SELECT %s FROM %s", $fields, $module);
					$sql = $sql.' '.$limitCaluse;
					$result = vtws_query($sql, $current_user);
				} else {
					$result = vtws_query_related(sprintf("SELECT %s FROM %s", $fields, $module), $recordId, $moduleLabel, $current_user, $limitCaluse);
				}
			}

			foreach ($result as $key => $recordValues) {
				$result[$key] = CustomerPortal_Utils::resolveRecordValues($recordValues);
			}

			$response->setResult($result);
			return $response;
		}
	}

}
