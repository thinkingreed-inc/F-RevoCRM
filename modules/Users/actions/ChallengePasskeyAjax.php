<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class Users_ChallengePasskeyAjax_Action extends Vtiger_Action_Controller {

	function loginRequired() {
		return false;
	}

    public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}

	function checkPermission(Vtiger_Request $request) {
		return true;
	} 

	public function process(Vtiger_Request $request) {
        // チャレンジトークンを生成
        $challenge = base64_encode(random_bytes(32));
        $_SESSION['challenge'] = $challenge;

        $result = json_encode([
            'success' => true,
            'challenge' => $challenge
        ]);

        $response = new Vtiger_Response();
        $response->setResult($challenge);
        $response->emit();
	}

}
