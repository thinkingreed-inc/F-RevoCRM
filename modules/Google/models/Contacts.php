<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */
vimport('~~/modules/WSAPP/synclib/models/SyncRecordModel.php');

class Google_Contacts_Model extends WSAPP_SyncRecordModel {
    
    /**
     * return id of Google Record
     * @return <string> id
     */
    public function getId() {
        return $this->data['entity']['id']['$t'];
    }

    /**
     * return modified time of Google Record
     * @return <date> modified time 
     */
    public function getModifiedTime() {
        return $this->vtigerFormat($this->data['entity']['updated']['$t']);
    }
    
    function getNamePrefix() {
        $namePrefix = $this->data['entity']['gd$name']['gd$namePrefix']['$t'];
        return $namePrefix;
    }

    /**
     * return first name of Google Record
     * @return <string> $first name
     */
    function getFirstName() {
        $fname = $this->data['entity']['gd$name']['gd$givenName']['$t'];
        return $fname;
    }

    /**
     * return Lastname of Google Record
     * @return <string> Last name
     */
    function getLastName() {
        $lname = $this->data['entity']['gd$name']['gd$familyName']['$t'];
        return $lname;
    }

    /**
     * return Emails of Google Record
     * @return <array> emails
     */
    function getEmails() {
        $arr = $this->data['entity']['gd$email'];
        $emails = array();
        if (is_array($arr)) {
            foreach ($arr as $email) {
                if(isset($email['rel']))
                    $labelEmail = parse_url($email['rel'], PHP_URL_FRAGMENT);
                else
                    $labelEmail = $email['label'];
                $emails[$labelEmail] = $email['address'];
            }
        }
        return $emails;
    }

    /**
     * return Phone number of Google Record
     * @return <array> phone numbers
     */
    function getPhones() {
        $arr = $this->data['entity']['gd$phoneNumber'];
        $phones = array();
        if(is_array($arr)) {
            foreach ($arr as $phone) {
                $phoneNo = $phone['$t'];
                if(isset($phone['rel']))
                    $labelPhone = parse_url($phone['rel'], PHP_URL_FRAGMENT);
                else
                    $labelPhone = $phone['label'];
                $phones[$labelPhone] = $phoneNo;
            }
        }
        return $phones;
    }

    /**
     * return Addresss of Google Record
     * @return <array> Addresses
     */
    function getAddresses() {
        $arr = $this->data['entity']['gd$structuredPostalAddress'];
        $addresses = array();
        if(is_array($arr)) {
            foreach ($arr as $address) {
                $structuredAddress = array(
                    'street' => $address['gd$street']['$t'],
                    'pobox' => $address['gd$pobox']['$t'],
                    'postcode' => $address['gd$postcode']['$t'],
                    'city' => $address['gd$city']['$t'],
                    'region' => $address['gd$region']['$t'],
                    'country' => $address['gd$country']['$t'],
                    'formattedAddress' => $address['gd$formattedAddress']['$t']
                );
                if(isset($address['rel']))
                    $labelAddress = parse_url($address['rel'], PHP_URL_FRAGMENT);
                else
                    $labelAddress = $address['label'];
                $addresses[$labelAddress] = $structuredAddress;
            }
        }
        return $addresses;
    }
    
    function getUserDefineFieldsValues() {
        $fieldValues = array();
        $userDefinedFields = $this->data['entity']['gContact$userDefinedField'];
        if(is_array($userDefinedFields) && count($userDefinedFields)) {
            foreach($userDefinedFields as $userDefinedField) {
                $fieldName = $userDefinedField['key'];
                $fieldValues[$fieldName] = $userDefinedField['value'];
            }
        }
        return $fieldValues;
    }
    
    function getUrlFields() {
        $websiteFields = $this->data['entity']['gContact$website'];
        $urls = array();
        if(is_array($websiteFields)) {
            foreach($websiteFields as $website) {
                $url = $website['href'];
                if(isset($website['rel'])) 
                    $fieldName = $website['rel'];
                else
                    $fieldName = $website['label'];
                $urls[$fieldName] = $url;
            }
        }
        return $urls;
    }
    
    function getBirthday() {
        return $this->data['entity']['gContact$birthday']['when'];
    }
    
    function getTitle() {
        return $this->data['entity']['gd$organization'][0]['gd$orgTitle']['$t'];
    }
    
    function getAccountName($userId) {
        $description = false;
        $orgName = $this->data['entity']['gd$organization'][0]['gd$orgName']['$t'];
        if(empty($orgName)) {
            $contactsModel = Vtiger_Module_Model::getInstance('Contacts');
            $accountFieldInstance = Vtiger_Field_Model::getInstance('account_id', $contactsModel);
            if($accountFieldInstance->isMandatory()) {
                $orgName = '????';
                $description = 'This Organization is created to support Google Contacts Synchronization. Since Organization Name is mandatory !';
            }
        }
        if(!empty($orgName)) {
            $db = PearDatabase::getInstance();
            $result = $db->pquery("SELECT crmid FROM vtiger_crmentity WHERE label = ? AND deleted = ? AND setype = ?", array($orgName, 0, 'Accounts'));
            if($db->num_rows($result) < 1) {
				try {
					$accountModel = Vtiger_Module_Model::getInstance('Accounts');
					$recordModel = Vtiger_Record_Model::getCleanInstance('Accounts');
				
					$fieldInstances = Vtiger_Field_Model::getAllForModule($accountModel);
					foreach($fieldInstances as $blockInstance) {
						foreach($blockInstance as $fieldInstance) {
							$fieldName = $fieldInstance->getName();
							$fieldValue = $recordModel->get($fieldName);
							if(empty($fieldValue)) {
								$defaultValue = $fieldInstance->getDefaultFieldValue();
								if($defaultValue) {
									$recordModel->set($fieldName, decode_html($defaultValue));
								}
								if($fieldInstance->isMandatory() && !$defaultValue) {
									$randomValue = Vtiger_Util_Helper::getDefaultMandatoryValue($fieldInstance->getFieldDataType());
									if($fieldInstance->getFieldDataType() == 'picklist' || $fieldInstance->getFieldDataType() == 'multipicklist') {
										$picklistValues = $fieldInstance->getPicklistValues();
										$randomValue = reset($picklistValues);
									}
									$recordModel->set($fieldName, $randomValue);
								}
							}
						}
					}
					$recordModel->set('mode', '');
					$recordModel->set('accountname', $orgName);
					$recordModel->set('assigned_user_id', $userId);
					$recordModel->set('source', 'GOOGLE');
					if($description) {
						$recordModel->set('description', $description);
					}
					$recordModel->save();
				} catch (Exception $e) {
					//TODO - Review
				}
            }
            return $orgName;
        }
        return false;
    }
    
    function getDescription() {
        return $this->data['entity']['content']['$t'];
    }

    /**
     * Returns the Google_Contacts_Model of Google Record
     * @param <array> $recordValues
     * @return Google_Contacts_Model
     */
    public static function getInstanceFromValues($recordValues) {
        $model = new Google_Contacts_Model($recordValues);
        return $model;
    }

    /**
     * converts the Google Format date to 
     * @param <date> $date Google Date
     * @return <date> Vtiger date Format
     */
    public function vtigerFormat($date) {
        list($date, $timestring) = explode('T', $date);
        list($time, $tz) = explode('.', $timestring);

        return $date . " " . $time;
    }

}

?>
