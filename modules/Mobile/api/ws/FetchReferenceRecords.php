<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

include_once 'include/Webservices/DescribeObject.php';
include_once 'include/Webservices/Query.php';

class Mobile_WS_FetchReferenceRecords extends Mobile_WS_Controller {
    
    function process(Mobile_API_Request $request) {
        
        $response = new Mobile_API_Response();
		$current_user = $this->getActiveUser();
        
        //fetch reference records request params
        $referenceModule = $request->get('module');
        $searchKey = $request->get('searchValue');
        
        if($referenceModule=='Documents') {
            $labelFields = 'notes_title';
        } else if($referenceModule=='HelpDesk') {
            $labelFields = 'ticket_title';
        } else {
            $describe = vtws_describe($referenceModule, $current_user);
            $labelFields = $describe['labelFields'];
        }
        
        $labelFieldsArray = explode(',', $labelFields);
        
        $sql = sprintf("SELECT %s FROM %s WHERE ",$labelFields,$referenceModule);
        
        foreach($labelFieldsArray as $labelField) {
            
            $sql .= $labelField . " LIKE '%" . $searchKey . "%' OR ";
        }
        $sql = rtrim($sql,' OR ') . ';';

        $wsresult = vtws_query($sql,$current_user);
        
        $referenceRecords = array();
        foreach($wsresult as $result) {
            $record = array();
            foreach($labelFieldsArray as $labelField) {
                $record['label'] .= $result[$labelField] . ' ';
            }
            $record['label'] = trim($record['label']);
            $record['value'] = decode_html($result['id']);
            $referenceRecords[] = $record;
        }
        
        $response->setResult($referenceRecords);
        return $response;
    }
    
}
?>