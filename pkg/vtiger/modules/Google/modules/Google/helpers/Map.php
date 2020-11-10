<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Google_Map_Helper {

     public function __construct() {
        self::initializeSchema();
    }
    
    /**
     *  Creates table if not exists
     */
    private static function initializeSchema() {
        if (!Vtiger_Utils::CheckTable('vtiger_google_map')) {
            // create table
            Vtiger_Utils::CreateTable('vtiger_google_map', '(module varchar(255), parameter_name varchar(255), parameter_field varchar(255))', true);
            // fill with defaults
            $db = PearDatabase::getInstance();
            $db->pquery("INSERT INTO `vtiger_google_map` (`module`, `parameter_name`, `parameter_field`) VALUES
                         ('Contacts', 'street', 'mailingstreet'),
                         ('Contacts', 'city', 'mailingcity'),
                         ('Contacts', 'state','mailingstate'),
                         ('Contacts', 'zip', 'mailingzip'),
                         ('Contacts', 'country', 'mailingcountry'),
                         ('Leads', 'street', 'lane'),
                         ('Leads', 'city', 'city'),
                         ('Leads', 'state', 'state'),
                         ('Leads', 'zip', 'code'),
                         ('Leads', 'country', 'country'),
                         ('Accounts', 'street', 'bill_street'),
                         ('Accounts', 'city', 'bill_city'),
                         ('Accounts', 'state', 'bill_state'),
                         ('Accounts', 'zip', 'bill_code'),
                         ('Accounts', 'country', 'bill_country');");
        }
    }

	/**
	 * get the location for the record based on the module type
	 * @param type $request
	 * @return type
	 */
	static function getLocation($request) {
		$result = array();
		$recordId = $request->get('recordid');
		$module = $request->get('source_module');
		$locationFields = self::getLocationFields($module);
		$address = array();
		if (!empty($locationFields)) {
			$recordModel = Vtiger_Record_Model::getInstanceById($recordId, $module);
			foreach ($locationFields as $key => $value) {
				$address[$key] = Vtiger_Util_Helper::getDecodedValue($recordModel->get($value));
			}
			$result['label'] = $recordModel->getName();
		}
		$result['address'] = implode(",", $address);

		return $result;
	}

	/**
	 * get location values for:
	 * street, city, country
	 * @param type $module
	 * @return type
	 */
    static function getLocationFields($module) {
        self::initializeSchema();
        $db = PearDatabase::getInstance();
        $result = $db->pquery("SELECT * FROM vtiger_google_map WHERE module='$module'");
        $number = $db->num_rows($result);
        $retArray = array();
        if ($number >= 1){
            // fill return array with db values
            for($i=0;$i<$number;$i++) {
                $row = $db->fetch_row($result);
                $retArray[$row['parameter_name']] = $row['parameter_field'];
            }
        } else {
            // in case nothing came from db
            switch ($module) {
                case 'Contacts': $retArray = array('street' => 'mailingstreet', 'city' => 'mailingcity', 'state' => 'mailingstate','zip' => 'mailingzip','country' => 'mailingcountry');
                break;
                case 'Leads' : $retArray = array('street' => 'lane', 'city' => 'city', 'state' => 'state', 'zip' => 'code', 'country' => 'country');
                break;
                case 'Accounts' : $retArray = array('street' => 'bill_street', 'city' => 'bill_city', 'state' => 'bill_state', 'zip' => 'bill_code','country' => 'bill_country');
                break;
                default : $retArray = array();
                break;
            }
        }
        return $retArray;
    }

}

?>
