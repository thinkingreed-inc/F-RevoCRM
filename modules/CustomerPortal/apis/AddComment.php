<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

include_once dirname(__FILE__).'/SaveRecord.php';

class CustomerPortal_AddComment extends CustomerPortal_SaveRecord {

	function process(CustomerPortal_API_Request $request) {
		$response = new CustomerPortal_API_Response();
		global $adb;
		$current_user = $this->getActiveUser();

		if ($current_user) {
			$valuesJSONString = $request->get('values');
			$element = null;

			if (!empty($valuesJSONString) && is_string($valuesJSONString)) {
				$element = Zend_Json::decode($valuesJSONString);
			} else {
				$element = $valuesJSONString; // Either empty or already decoded.
			}

			$element['assigned_user_id'] = vtws_getWebserviceEntityId('Users', $current_user->id);
			$parentId = $request->get('parentId');

			$relatedRecordId = $element['related_to'];
			$relatedModule = VtigerWebserviceObject::fromId($adb, $relatedRecordId)->getEntityName();

			if (!CustomerPortal_Utils::isModuleActive($relatedModule)) {
				throw new Exception("Module not accessible.", 1412);
				exit;
			}

			if (!empty($parentId)) {
				if (!$this->isRecordAccessible($parentId)) {
					throw new Exception("Parent record not accessible.", 1412);
					exit;
				}
				$relatedRecordIds = $this->relatedRecordIds($relatedModule, CustomerPortal_Utils::getRelatedModuleLabel($relatedModule), $parentId);

				if (!in_array($relatedRecordId, $relatedRecordIds)) {
					throw new Exception("Record not Accessible", 1412);
					exit;
				}
			} else {
				//If module is Faq by pass this check as we Faq's are not related to Contacts module.
				if ($relatedModule == 'Faq') {
					if (!($this->isFaqPublished($relatedRecordId))) {
						throw new Exception("This Faq is not published", 1412);
						exit;
					}
				} else if (!$this->isRecordAccessible($relatedRecordId)) {
					throw new Exception("Record not accessible.", 1412);
					exit;
				}
			}
			// Always set the customer to Portal user when comment is added from portal 
			$customerId = vtws_getWebserviceEntityId('Contacts', $this->getActiveCustomer()->id);
			$element['customer'] = $customerId;
			$element['from_portal'] = true;
			$element['commentcontent'] = nl2br($element['commentcontent']);
			//comment_added_from_portal added to check workflow condition "is added from portal" for comments.
			//Cannot use from_portal as Mailroom also sets to TRUE.
			$element['comment_added_from_portal'] = true;
			$result = vtws_create('ModComments', $element, $current_user);
			$result = CustomerPortal_Utils::resolveRecordValues($result, $current_user);
			$response->setResult($result);
			return $response;
		}
	}

}
