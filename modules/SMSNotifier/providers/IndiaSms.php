<?php
/*********************************************************************************
 ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ********************************************************************************/

class SMSNotifier_IndiaSms_Provider implements SMSNotifier_ISMSProvider_Model {

	private $_username;
	private $_password;
	private $_parameters = array();

	const SERVICE_URI = 'http://49.50.69.90';

	private static $REQUIRED_PARAMETERS = array(
		array('name' => 'from', 'label' => 'Sender Id', 'type' => 'text'),
		array('name' => 'CharacterSet', 'label' => 'Character Set', 'type' => 'picklist', 'picklistvalues' => array('unicode' => 'Unicode', 'gsm' => 'GSM'))
	);

	function __construct() {
		
	}

	public function getName() {
		return 'IndiaSms';
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
				case self::SERVICE_AUTH: return self::SERVICE_URI . '/api/smsapi.aspx';
				case self::SERVICE_SEND: return self::SERVICE_URI . '/api/smsapi.aspx';
				case self::SERVICE_QUERY: return self::SERVICE_URI . '/api/smsstatus.aspx';
			}
		}
		return false;
	}

	protected function prepareParameters() {
		$params = array('username' => $this->_username, 'password' => $this->_password);
		foreach (self::$REQUIRED_PARAMETERS as $requiredParam) {
			$paramName = $requiredParam['name'];
			if ($paramName != 'CharacterSet') {
				$params[$paramName] = $this->getParameter($paramName);
			}
		}
		$CharacterSet = $this->getParameter('CharacterSet', '');
		if ($CharacterSet == 'unicode') {
			$params['code'] = 2;
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

		$response = $httpClient->doGet($params);
		$responseLines = split("\n", $response);

		$results = array();
		foreach ($responseLines as $responseLine) {

			$responseLine = trim($responseLine);
			if (empty($responseLine))
				continue;

			$result = array('error' => false, 'statusmessage' => '');
			$matches = null;
			if (preg_match("/ERR:(.*)/", trim($responseLine), $matches)) {
				$result['error'] = true;
				$result['to'] = $tonumbers[$i++];
				$result['statusmessage'] = $matches[0]; // Complete error message
			} else if (preg_match("/ID: ([^ ]+)TO:(.*)/", $responseLine, $matches)) {
				$result['id'] = trim($matches[1]);
				$result['to'] = trim($matches[2]);
				$result['status'] = self::MSG_STATUS_PROCESSING;
			} else if (preg_match("/ID: (.*)/", $responseLine, $matches)) {
				$result['id'] = trim($matches[1]);
				$result['to'] = $tonumbers[0];
				$result['status'] = self::MSG_STATUS_PROCESSING;
			} else if (preg_match("/(^[0-9a-z-]+)/", $responseLine, $matches)) {
				$result['id'] = trim($matches[1]);
				$result['to'] = $tonumbers[0];
				$result['status'] = self::MSG_STATUS_PROCESSING;
			}
			if ($matches)
				$results[] = $result;
		}

		return $results;
	}

	public function query($messageid) {
		$params = array('username' => $this->_username, 'password' => $this->_password, 'messageid' => $messageid);

		$serviceURL = $this->getServiceURL(self::SERVICE_QUERY);
		$httpClient = new Vtiger_Net_Client($serviceURL);
		$response = $httpClient->doGet($params);
		$response = str_replace('<br/>', "\n", $response);
		$response = strip_tags($response);
		$responseLines = explode("\n", $response);
		$responseData = explode(",", $responseLines[1]);

		$response = array();
		$response['phone'] = trim(str_replace('"', '', $responseData[0]));
		$response['status'] = trim(str_replace('"', '', $responseData[1]));
		$response['date'] = trim(str_replace('"', '', $responseData[2]));

		$result = array('error' => false, 'needlookup' => 1);

		if ($response['status'] == 'Delivered') {
			$result['status'] = self::MSG_STATUS_DISPATCHED;
			$result['needlookup'] = 0;
			$result['statusmessage'] = 'Message delivered to ' . $response['phone'] . ' on ' . $response['date'];
		} else if ($response['status'] = 'Submit') {
			$result['status'] = self::MSG_STATUS_PROCESSING;
			$result['statusmessage'] = 'Message to ' . $response['phone'] . ' is submitted for processing';
		} else if ($response['status'] == 'Failed') {
			$result['status'] = self::MSG_STATUS_FAILED;
			$result['needlookup'] = 0;
			$result['statusmessage'] = 'Message delivery failed.';
		} else {
			$result['status'] = self::MSG_STATUS_PROCESSING;
			$result['statusmessage'] = 'Message is under process.';
		}

		return $result;
	}

}

?>
