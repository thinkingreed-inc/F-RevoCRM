<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Google_SaveSyncSettings_Action extends Vtiger_BasicAjax_Action {

    public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}
    
	public function process(Vtiger_Request $request) {
		$contactsSettings = $request->get('Contacts');
		$calendarSettings = $request->get('Calendar');
		$sourceModule = $request->get('sourceModule');

		$contactRequest = new Vtiger_Request($contactsSettings);
		$contactRequest->set('sourcemodule', 'Contacts');
		Google_Utils_Helper::saveSyncSettings($contactRequest);

		$calendarRequest = new Vtiger_Request($calendarSettings);
		$calendarRequest->set('sourcemodule', 'Calendar');
		Google_Utils_Helper::saveSyncSettings($calendarRequest);
		$googleModuleModel = Vtiger_Module_Model::getInstance('Google');

		$returnUrl = $googleModuleModel->getBaseExtensionUrl($sourceModule);

		if($request->has('parent') && $request->get('parent') === 'Settings') {
			$returnUrl = 'index.php?module=' . $sourceModule . '&parent=Settings&view=Extension&extensionModule=Google&extensionView=Index&mode=settings';
		}

		header('Location: '.$returnUrl);
	}

}

?>