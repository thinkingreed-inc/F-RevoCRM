<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class ExtensionStore_Promotion_Action extends Vtiger_Index_View {

	public function __construct() {
		parent::__construct();
		$this->exposeMethod('maxCreatedOn');
	}

    public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}
    
	public function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		if (!empty($mode)) {
			$this->invokeExposedMethod($mode, $request);
			return;
		}
	}

    protected function maxCreatedOn(Vtiger_Request $request){
		$modelInstance = Settings_ExtensionStore_Extension_Model::getInstance();
		$promotions = $modelInstance->getMaxCreatedOn('Promotion', 'max', 'createdon');
		$response = new Vtiger_Response();
		if ($promotions['success'] != 'true') {
			$response->setError('', $promotions['error']);
		} else {
			$response->setResult($promotions['response']);
		}
		$response->emit();
	}
}
