<?php

/*
 * Copyright (C) www.vtiger.com. All rights reserved.
 * @license Proprietary
 */

class Settings_ExtensionStore_RestClient {

	protected static $name = 'ExtensionStoreRestClient';
	protected static $version = '1.0';
	protected $defaultHeaders = array();
	protected $defaultOptions = array();

	public function __construct() {
		global $site_URL, $current_vtiger_version;

		$this->defaultOptions[CURLOPT_REFERER] = $site_URL;
		$this->defaultOptions[CURLOPT_USERAGENT] = self::$name.'/'.self::$version.'(CRM '.$current_vtiger_version.')';
		$this->defaultOptions[CURLOPT_RETURNTRANSFER] = true;
		$this->defaultOptions[CURLOPT_FOLLOWLOCATION] = true;
		$this->defaultOptions[CURLOPT_MAXREDIRS] = 5;
		$this->defaultOptions[CURLOPT_SSL_VERIFYPEER] = 0;
		$this->defaultOptions[CURLOPT_SSL_VERIFYHOST] = 0;
		$this->defaultOptions[CURLOPT_TIMEOUT] = 30;

		$this->defaultHeaders['Content-Type'] = 'application/x-www-form-urlencoded';
		$this->defaultHeaders['Cache-Control'] = 'no-cache';
	}

	public function setDefaultOption($option, $value) {
		$this->defaultOptions[$option] = $value;
		return $this;
	}

	public function setDefaultHeader($header, $value) {
		$this->defaultHeaders[$header] = $value;
		return $this;
	}

	public function setBasicAuthentication($username, $password) {
		$this->defaultHeaders['Authorization'] = 'Basic '.base64_encode($username.':'.$password);
	}

	protected function exec($curlopts) {
		$curl = curl_init();
		foreach ($curlopts as $option => $value) {
			if ($option) {
				curl_setopt($curl, $option, $value);
			}
		}

		// To be secure - we don't want user to override this
		// and open doors for hackers.
		$cookiefile = tempnam(sys_get_temp_dir(), ".".uniqid()."co");
		$cookiefp = fopen($cookiefile, "w");

		curl_setopt($curl, CURLOPT_COOKIEJAR, $cookiefile);
		curl_setopt($curl, CURLOPT_COOKIEFILE, $cookiefile);

		// Now execute
		$response = curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		$responseData = array('response' => $response, 'status' => $status);
		if (curl_errno($curl)) {
			$errorMessage = curl_error($curl);
			$responseData['errorMessage'] = $errorMessage;
		}
		curl_close($curl);

		fclose($cookiefp);
		unlink($cookiefile);

		return $responseData;
	}

	protected function buildCurlOptions(array $headers, array $options) {
		foreach ($this->defaultOptions as $option => $value) {
			switch ($option) {
				// Stop overrides on some keys.
				case CURLOPT_REFERER:
				case CURLOPT_USERAGENT:
					$options[$option] = $value;
					break;
				default:
					// Pickup the overriding value
					if (!isset($options[$option])) {
						$options[$option] = $value;
					}
					break;
			}
		}

		$headeropts = array();
		foreach ($this->defaultHeaders as $key => $value) {
			// Respect the overriding value
			if ($headers && isset($headers[$key]))
				continue;
			$headeropts[] = ($key.': '.$value);
		}
		foreach ($headers as $key => $value)
			$headeropts[] = ($key.': '.$value);
		$options[CURLOPT_HTTPHEADER] = $headeropts;

		return $options;
	}

	public function get($url, $params = array(), $headers = array(), $options = array()) {
		$curlopts = $this->buildCurlOptions($headers, $options);


		$curlopts[CURLOPT_HTTPGET] = true;

		if (!empty($params)) {
			if (stripos($url, '?') === false)
				$url .= '?';
			else
				$url .= '&';
			$url .= http_build_query($params, '', '&');
		}

		$curlopts[CURLOPT_URL] = $url;
		return $this->exec($curlopts);
	}

	public function post($url, $params = array(), $headers = array(), $options = array()) {
		$curlopts = $this->buildCurlOptions($headers, $options);

		$curlopts[CURLOPT_POST] = true;
		if ($params) {
			$curlopts[CURLOPT_POSTFIELDS] = http_build_query($params, '', '&');
		}

		$curlopts[CURLOPT_URL] = $url;
		return $this->exec($curlopts);
	}

	public function put($url, $params = array(), $headers = array(), $options = array()) {
		$curlopts = $this->buildCurlOptions($headers, $options);

		$curlopts[CURLOPT_CUSTOMREQUEST] = 'PUT';
		if ($params) {
			$curlopts[CURLOPT_POSTFIELDS] = http_build_query($params, '', '&');
		}

		$curlopts[CURLOPT_URL] = $url;
		return $this->exec($curlopts);
	}

}
