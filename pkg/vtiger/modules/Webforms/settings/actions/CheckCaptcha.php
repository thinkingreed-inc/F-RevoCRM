<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

// Switch the working directory to base
chdir(dirname(__FILE__) . '/../../../..');

include_once 'includes/http/Response.php';
include_once 'vtlib/Vtiger/Module.php';
include_once 'include/utils/VtlibUtils.php';
include_once 'include/recaptcha/recaptchalib.php';

class Webform_CheckCaptcha {

	function checkCaptchaNow($request) {
        // reCAPTCHA public and private keys to validate
		$publickey = "6Lchg-wSAAAAAIkV51_LSksz6fFdD2vgy59jwa38";
        $privatekey = "6Lchg-wSAAAAABUvZ57ogylowuv8SK0Hq4h2Yghs";

        // to store the response from reCAPTCHA
        $resp = null;

        if ($request["recaptcha_response_field"]) {
                $resp = recaptcha_check_answer ($privatekey,
                                                $_SERVER["REMOTE_ADDR"],
                                                $request["recaptcha_challenge_field"],
                                                $request["recaptcha_response_field"]);

                if ($resp->is_valid) {
                        $this->sendResponse(true, $request['callId']);
                } else {
                        $this->sendResponse(false, $request['callId']);
                }
        } else {
			$this->sendResponse(false, $request['callId']);
		}
	}

	protected function sendResponse($success, $callId) {
        $response = new Vtiger_Response();
        if ($success)
            $response->setResult(array('success' => true, 'callId' => $callId));
        else
            $response->setResult(array('success' => false, 'callId' => $callId));

        // Support JSONP
        if (!empty($_REQUEST['callback'])) {
            $callback = vtlib_purify($_REQUEST['callback']);
            $response->setEmitType('4');
            $response->setEmitJSONP($callback);
            $response->emit();
        } else {
            $response->emit();
        }
	}
}

$webformCheckCaptcha = new Webform_CheckCaptcha;
$webformCheckCaptcha->checkCaptchaNow(vtlib_purify($_REQUEST));
?>