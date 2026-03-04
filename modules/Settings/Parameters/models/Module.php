<?php

/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

class Settings_Parameters_Module_Model extends Settings_Vtiger_Module_Model{
	var $baseTable = 'vtiger_parameters';
	var $baseIndex = 'id';
	var $listFields = array('id' => 'ID', 'key' => 'Key', 'value' => 'Value', 'description' => 'Description', );
	var $nameFields = array('key');
	var $name = 'Parameters';

    /**
     * Function to get editable fields from this module
     * @return <Array> List of fieldNames
     */
    public function getEditableFieldsList() {
        return array();
    }

    public function isPagingSupported() {
        return false;
    }

    /*
     * Function to get Create view url 
     */
    public function getCreateRecordUrl() {
        return "javascript:Settings_Parameters_Js.triggerAdd(event)";
    }

}