<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

require_once 'includes/http/Response.php';

class MailManager_Response extends Vtiger_Response {

	/**
	 * Emit response wrapper as JSONString
	 */
	protected function emitJSON() {
		require_once 'include/Zend/Json/Encoder.php';
		echo Zend_Json_Encoder::encode($this->prepareResponse(), false);
	}

}