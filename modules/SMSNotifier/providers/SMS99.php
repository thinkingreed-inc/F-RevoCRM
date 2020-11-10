<?php
/*********************************************************************************
 ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ********************************************************************************/

class SMSNotifier_SMS99_Provider implements SMSNotifier_ISMSProvider_Model {

	private $_username;
	private $_password;
	private $_parameters = array();

	const SERVICE_URI = 'http://labs.sms99.in/';

	private static $REQUIRED_PARAMETERS = array('from');

	function __construct() {
		
	}

	public function getName() {
		return 'SMS99';
	}

	public function setAuthParameters($username, $password) {
		$this->_username = $username;
		$this->_password = $password;
	}

	public function setParameter($key, $value) {
		$this->_parameters[$key] = $value;
	}

	public function getParameter($key, $defvalue = false) {
		if (isset($this->_parameters[$key])) {
			return $this->_parameters[$key];
		}
		return $defvalue;
	}

	public function getRequiredParams() {
		return self::$REQUIRED_PARAMETERS;
	}

	public function getServiceURL($type = false) {
		if ($type) {
			switch (strtoupper($type)) {
				case self::SERVICE_AUTH : return self::SERVICE_URI . 'spanelv2/api.php';
				case self::SERVICE_SEND : return self::SERVICE_URI . 'spanelv2/api.php';
				case self::SERVICE_QUERY : return self::SERVICE_URI . 'spanelv2/api.php';
			}
		}
		return false;
	}

	protected function prepareParameters() {
		$params = array('username' => $this->_username, 'password' => $this->_password);
		foreach (self::$REQUIRED_PARAMETERS as $key) {
			$params[$key] = $this->getParameter($key);
		}
		return $params;
	}

	public function send($message, $tonumbers) {
		if (!is_array($tonumbers)) {
			$tonumbers = array($tonumbers);
		}
		foreach ($tonumbers as $i => $tonumber) {
			$tonumbers[$i] = str_replace(array('(', ')', ' ', '+', '-'), '', $tonumber);
		}

		$params = $this->prepareParameters();
		$params['message'] = $message;
		$params['to'] = implode(',', $tonumbers);

		$serviceURL = $this->getServiceURL(self::SERVICE_SEND);
		$httpClient = new Vtiger_Net_Client($serviceURL);
		$messageId = $httpClient->doGet($params);
		$queryResult = $this->query($messageId);
		$result = array(
			'id' => $messageId,
			'status' => $queryResult['status'],
			'error' => false
		);
		if ($queryResult['status'] == self::MSG_STATUS_FAILED) {
			$result['error'] = true;
		}
		$results = array();
		foreach ($tonumbers as $i => $tonumber) {
			$results[$i] = $result;
			$results[$i]['to'] = $tonumber;
		}

		return $results;
	}

	public function query($messageid) {
		$params = array('username' => $this->_username, 'password' => $this->_password, 'msgid' => $messageid);
		$serviceURL = $this->getServiceURL(self::SERVICE_QUERY);
		$httpClient = new Vtiger_Net_Client($serviceURL);
		$response = $httpClient->doGet($params);
		$result = array();
		if (stripos($response, 'Delivered') !== false) {
			$result['status'] = self::MSG_STATUS_DISPATCHED;
			$result['needlookup'] = 0;
			$result['statusmessage'] = 'Message delivered';
		} else if (stripos($response, 'Submitted') !== false) {
			$result['status'] = self::MSG_STATUS_PROCESSING;
			$result['needlookup'] = 1;
			$result['statusmessage'] = 'Message submitted for processing';
		} else {
			$result['status'] = self::MSG_STATUS_FAILED;
			$result['needlookup'] = 0;
			$result['statusmessage'] = 'Message delivery failed.';
		}

		return $result;
	}

}

?>