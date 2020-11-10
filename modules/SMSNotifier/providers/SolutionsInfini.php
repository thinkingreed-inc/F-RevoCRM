<?php
/*********************************************************************************
 ** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ********************************************************************************/

class SMSNotifier_SolutionsInfini_Provider implements SMSNotifier_ISMSProvider_Model {

	private $username;
	private $password;
	private $parameters = array();

	const SERVICE_URI = 'http://global.sinfini.com/api/v3';

	private static $REQUIRED_PARAMETERS = array(
		array('name' => 'api_key', 'label' => 'API Key', 'type' => 'text'),
		array('name' => 'sender', 'label' => 'Sender ID', 'type' => 'text'),
		array('name' => 'unicode', 'label' => 'Character Set', 'type' => 'picklist', 'picklistvalues' => array('1' => 'Unicode', '0' => 'GSM', 'auto' => 'Auto Detect'))
	);

	public function getName() {
		return 'SolutionsInfini';
	}

	public function setAuthParameters($username, $password) {
		$this->username = $username;
		$this->password = $password;
	}

	public function setParameter($key, $value) {
		$this->parameters[$key] = $value;
	}

	public function getParameter($key, $defaultvalue = false) {
		if (isset($this->parameters[$key])) {
			return $this->parameters[$key];
		}
		return $defaultvalue;
	}

	public function getRequiredParams() {
		return self::$REQUIRED_PARAMETERS;
	}

	public function getServiceURL($type = false) {
		if ($type) {
			switch (strtoupper($type)) {
				case self::SERVICE_SEND : return self::SERVICE_URI . '/index.php?method=sms';
				case self::SERVICE_QUERY : return self::SERVICE_URI . '/index.php?method=sms.status';
			}
		}
		return false;
	}

	protected function prepareParameters() {
		foreach (self::$REQUIRED_PARAMETERS as $requiredParam) {
			$paramName = $requiredParam['name'];
			$params[$paramName] = $this->getParameter($paramName);
		}
		$params['output'] = 'json';
		return $params;
	}

	public function send($message, $tonumbers) {
		if (!is_array($tonumbers)) {
			$tonumbers = array($tonumbers);
		}
		foreach ($tonumbers as $i => $tonumber) {
			$tonumbers[$i] = str_replace(array('(', ')', ' ', '-'), '', $tonumber);
		}

		$params = $this->prepareParameters();
		$params['message'] = $message;
		$params['to'] = implode(',', $tonumbers);

		$serviceURL = $this->getServiceURL(self::SERVICE_SEND);
		$httpClient = new Vtiger_Net_Client($serviceURL);
		$response = $httpClient->doGet($params);
		$rows = json_decode($response, true);

		$numbers = explode(',', $params['to']);
		$results = array();
		$i = 0;

		if ($rows['status'] != 'OK') {
			foreach ($numbers as $number) {
				$result = array();
				$result['to'] = $number;
				$result['error'] = true;
				$result['statusmessage'] = $rows['message'];
				$result['id'] = $rows['data'][$i++]['id'];
				$result['status'] = self::MSG_STATUS_ERROR;
				$results[] = $result;
			}
		} else {
			foreach ($rows['data'] as $value) {
				if (is_array($value)) {
					$result = array();
					$result['error'] = false;
					$result['to'] = $value['mobile'];
					$result['id'] = $value['id'];
					$result['statusmessage'] = $rows['message'];
					$result['status'] = $this->checkstatus($value['status']);
					$results[] = $result;
				}
			}
		}
		return $results;
	}

	public function checkstatus($status) {
		if ($status == 'AWAITED-DLR') {
			$result = self::MSG_STATUS_PROCESSING;
		} elseif ($status == 'DELIVRD') {
			$result = self::MSG_STATUS_DELIVERED;
		} else {
			$result = self::MSG_STATUS_FAILED;
		}
		return $result;
	}

	public function query($messageid) {
		$params = $this->prepareParameters();
		$params['id'] = $messageid;
		$serviceURL = $this->getServiceURL(self::SERVICE_QUERY);
		$httpClient = new Vtiger_Net_Client($serviceURL);
		$response = $httpClient->doGet($params);
		$rows = json_decode($response, true);
		$result = array();
		if ($rows['status'] != 'OK') {
			$result['error'] = true;
			$result['status'] = self::MSG_STATUS_ERROR;
			$result['needlookup'] = 1;
			$result['statusmessage'] = $rows['message'];
		} else {
			$result['error'] = false;
			$result['status'] = $this->checkstatus($rows['data']['0']['status']);
			$result['needlookup'] = 0;
			$result['statusmessage'] = $rows['message'];
		}
		return $result;
	}

	function getProviderEditFieldTemplateName() {
		return 'BaseProviderEditFields.tpl';
	}

}
