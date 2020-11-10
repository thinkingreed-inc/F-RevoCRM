<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class SMSNotifier_CheckStatus_View extends Vtiger_IndexAjax_View {

    function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$moduleName = $request->getModule();

		$notifierRecordModel = Vtiger_Record_Model::getInstanceById($request->get('record'), $moduleName);
		$notifierRecordModel->checkStatus();

		$response = new Vtiger_Response();
		$response->setResult(array(	'to'		=> $notifierRecordModel->get('tonumber'), 
									'status'	=> $notifierRecordModel->get('status'),
									'message'	=> $notifierRecordModel->get('statusmessage')
							));
		$response->emit();
	}
}