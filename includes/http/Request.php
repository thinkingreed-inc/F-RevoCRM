<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Vtiger_Request implements ArrayAccess {

	// Datastore
	private $valuemap;
	private $rawvaluemap;
	private $defaultmap = array();

	// ArrayAccess Start
	public function offsetExists($key) {
		return $this->has($key);
	}
	public function offsetSet($key, $value) {
		$this->set($key, $value);
	}
	public function offsetGet($key) {
		return $this->get($key);
	}
	public function offsetUnset($key) {
		// Ignore
	}
	// ArrayAccess End

	/**
	 * Default constructor
	 */
	function __construct($values, $rawvalues = array(), $stripifgpc=true) {
        	Vtiger_Functions::validateRequestParameters($values);
		$this->valuemap = $values;
		$this->rawvaluemap = $rawvalues;
		if ($stripifgpc && !empty($this->valuemap) && get_magic_quotes_gpc()) {
			$this->valuemap = $this->stripslashes_recursive($this->valuemap);
            $this->rawvaluemap = $this->stripslashes_recursive($this->rawvaluemap);
		}
	}

	/**
	 * Strip the slashes recursively on the values.
	 */
	function stripslashes_recursive($value) {
		$value = is_array($value) ? array_map(array($this, 'stripslashes_recursive'), $value) : stripslashes($value);
		return $value;
	}

	/**
	 * Get key value (otherwise default value)
	 */
	function get($key, $defvalue = '') {
		$value = $defvalue;
		if(isset($this->valuemap[$key])) {
			$value = $this->valuemap[$key];
		}
		if($value === '' && isset($this->defaultmap[$key])) {
			$value = $this->defaultmap[$key];
		}

		$isJSON = false;
		if (is_string($value)) {
			// NOTE: Zend_Json or json_decode gets confused with big-integers (when passed as string)
			// and convert them to ugly exponential format - to overcome this we are performin a pre-check
			if (strpos($value, "[") === 0 || strpos($value, "{") === 0) {
				$isJSON = true;
			}
		}
		if($isJSON) {
			$oldValue = Zend_Json::$useBuiltinEncoderDecoder;
			Zend_Json::$useBuiltinEncoderDecoder = false;
			$decodeValue = Zend_Json::decode($value);
			if(isset($decodeValue)) {
				$value = $decodeValue;
			}
			Zend_Json::$useBuiltinEncoderDecoder  = $oldValue;
		}

        //Handled for null because vtlib_purify returns empty string
        if(!empty($value)){
            $value = vtlib_purify($value);
        }
		return $value;
	}

	/**
	 * Get value for key as boolean
	 */
	function getBoolean($key, $defvalue = '') {
		return strcasecmp('true', $this->get($key, $defvalue).'') === 0;
	}

	/**
	 * Function to get the value if its safe to use for SQL Query (column).
	 * @param <String> $key
	 * @param <Boolean> $skipEmpty - Skip the check if string is empty
	 * @return Value for the given key
	 */
	public function getForSql($key, $skipEmtpy=true) {
		return Vtiger_Util_Helper::validateStringForSql($this->get($key), $skipEmtpy);
	}

	/**
	 * Get data map
	 */
	function getAll() {
		return $this->valuemap;
	}
	
	/**
	 * Check for existence of key
	 */
	function has($key) {
		return isset($this->valuemap[$key]);
	}

	/**
	 * Is the value (linked to key) empty?
	 */
	function isEmpty($key) {
		$value = $this->get($key);
		return empty($value);
	}

	/**
	 * Get the raw value (if present) ignoring primary value.
	 */
	function getRaw($key, $defvalue = '') {
		if (isset($this->rawvaluemap[$key])) {
			return $this->rawvaluemap[$key];
		}
		return $this->get($key, $defvalue);
	}

	/**
	 * Set the value for key
	 */
	function set($key, $newvalue) {
		$this->valuemap[$key]= $newvalue;
	}

	/**
	 * Set the value for key, both in the object as well as global $_REQUEST variable
	 */
	function setGlobal($key, $newvalue) {
		$this->set($key, $newvalue);
		// TODO - This needs to be cleaned up once core apis are made independent of REQUEST variable.
		// This is added just for backward compatibility
		$_REQUEST[$key] = $newvalue;
	}

	/**
	 * Set default value for key
	 */
	function setDefault($key, $defvalue) {
		$this->defaultmap[$key] = $defvalue;
	}

	/**
	 * Shorthand function to get value for (key=_operation|operation)
	 */
	function getOperation() {
		return $this->get('_operation', $this->get('operation'));
	}

	/**
	 * Shorthand function to get value for (key=_session)
	 */
	function getSession() {
		return $this->get('_session', $this->get('session'));
	}

	/**
	 * Shorthand function to get value for (key=mode)
	 */
	function getMode() {
		return $this->get('mode');
	}

	function getModule($raw=true) {
		$moduleName = $this->get('module');
		if(!$raw) {
			$parentModule = $this->get('parent');
			if(!empty($parentModule)) {
				$moduleName = $parentModule.':'.$moduleName;
			}
		}
		return $moduleName;
	}

	function isAjax() {
		if(!empty($_SERVER['HTTP_X_PJAX']) && $_SERVER['HTTP_X_PJAX'] == true) {
			return true;
		} elseif(!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
			return true;
		}
		return false;
	}

	/**
	 * Validating incoming request.
	 */	
	function validateReadAccess() {
		$this->validateReferer();
		// TODO validateIP restriction?
		return true;
	}
	
	function validateWriteAccess($skipRequestTypeCheck = false) {
        if(!$skipRequestTypeCheck) {
            if ($_SERVER['REQUEST_METHOD'] != 'POST') throw new Exception('Invalid request');
        }
		$this->validateReadAccess();
		$this->validateCSRF();
		return true;
	}

	protected function validateReferer() {
        $user=  vglobal('current_user');
		// Referer check if present - to over come 
		if (isset($_SERVER['HTTP_REFERER']) && $user) {//Check for user post authentication.
			global $site_URL;
			if ((stripos($_SERVER['HTTP_REFERER'], $site_URL) !== 0) && ($this->get('module') != 'Install')) {
				throw new Exception('Illegal request');
			}
		}
		return true;
	}
	
	protected function validateCSRF() {
		if (!csrf_check(false)) {
			throw new Exception('Unsupported request');
		}
	}

	/**
	 * Get purified data map
	 */
	function getAllPurified() {
		foreach ($this->valuemap as $key => $value) {
			$sanitizedMap[$key] = $this->get($key);
		}
		return $sanitizedMap;
	}

	/**
	* Function gives the return url for a request
	* @return <String> - return url
	*/
	function getReturnURL() {
		$data = $this->getAll();
		$returnURL = array();
		foreach($data as $key => $value) {
			if(stripos($key, 'return') === 0 && !empty($value) && $value != '/') {
				if($key == 'returnsearch_params' && $value == '""') continue;
				$newKey = str_replace('return','',$key);
				$returnURL[$newKey] = $value;
			}
		}
		return http_build_query($returnURL);
	}

	/**
	* Function sets the viewer with the return url parameters
	* @param $viewer <Vtiger_Viewer> - template object 
	*/
	function setViewerReturnValues($viewer) {
		$viewer->assign('RETURN_MODULE', $this->get('returnmodule'));
		$viewer->assign('RETURN_VIEW', $this->get('returnview'));
		$viewer->assign('RETURN_PAGE', $this->get('returnpage'));
		$viewer->assign('RETURN_VIEW_NAME', $this->get('returnviewname'));
		$viewer->assign('RETURN_SEARCH_PARAMS', $this->get('returnsearch_params'));
		$viewer->assign('RETURN_SEARCH_KEY', $this->get('returnsearch_key'));
		$viewer->assign('RETURN_SEARCH_VALUE', $this->get('returnsearch_value'));
		$viewer->assign('RETURN_SEARCH_OPERATOR', $this->get('returnoperator'));
		$viewer->assign('RETURN_SORTBY', $this->get('returnsortorder'));
		$viewer->assign('RETURN_ORDERBY', $this->get('returnorderby'));
		
		$viewer->assign('RETURN_RECORD', $this->get('returnrecord'));
		$viewer->assign('RETURN_RELATED_TAB', $this->get('returntab_label'));
		$viewer->assign('RETURN_RELATED_MODULE', $this->get('returnrelatedModuleName'));
		$viewer->assign('RETURN_MODE', $this->get('returnmode'));
        $viewer->assign('RETURN_RELATION_ID', $this->get('returnrelationId'));
        $viewer->assign('RETURN_PARENT_MODULE', $this->get('returnparent'));
	}
}
