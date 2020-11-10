<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Mobile_WS_TaxByType extends Mobile_WS_Controller{
    
    function process(Mobile_API_Request $request) {
		global $current_user;
		$response = new Mobile_API_Response();
		$current_user = $this->getActiveUser();

		$taxType = $request->get('taxType');
        $recordId = $request->get('record');
        
        if ($taxType == "charges" && $recordId){
            $result = $this->getCharges($recordId);
            $response->setResult($result);
            return $response;
        } else {
        	$result = $this->getTaxDetails($taxType);
			$response->setResult($result);
			return $response;
		}
	}
    
    protected function getTaxDetails($taxType){
       global $adb;
       $tableName = $this->getTableName($taxType);
       $result = $adb->pquery("SELECT * FROM $tableName WHERE deleted = 0", array());
       $rowCount =  $adb->num_rows($result);
        if($rowCount){
            for($i = 0; $i < $rowCount; $i++){
                $row = $adb->query_result_rowdata($result, $i);
                $recordDetails[] = $row;
            }
        }
        return $recordDetails;
    }
    
    protected function getCharges($recordId) {
		global $adb;
		$chargesAndItsTaxes = array();
                
		if ($recordId) {
			$result = $adb->pquery('SELECT * FROM vtiger_inventorychargesrel WHERE recordid = ?', array($recordId));
			while ($rowData = $adb->fetch_array($result)) {
				$chargesAndItsTaxes = Zend_Json::decode(html_entity_decode($rowData['charges']));
			}
		}
		if ($chargesAndItsTaxes) {
			return $chargesAndItsTaxes;
		} else {
			return False;
		}
    }
    
    protected function getTableName($taxType){
        switch($taxType){
            case 'shipping':
                return 'vtiger_shippingtaxinfo';
                break;
            case 'inventory':
                return 'vtiger_inventorytaxinfo';
                break;
            case 'charges':
                return 'vtiger_inventorychargesrel';
                break;
        }
    }
}
