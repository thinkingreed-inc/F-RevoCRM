<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class MailManager_Request extends Vtiger_Request {

	public function get($key, $defvalue = '') {
		$value = parent::get($key, $defvalue);
		if (is_array($value)) {
			//For Review: http://stackoverflow.com/questions/8734626/how-to-urlencode-a-multidimensional-array#answer-8734910
			$str = urlencode(serialize($value));
			return unserialize(urldecode($str));
		}
       	return urldecode($value);
	}

	public static function getInstance($request) {
		return new MailManager_Request($request->getAll(), $request->getAll());
	}
}