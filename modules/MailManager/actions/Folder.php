<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/

class MailManager_Folder_Action extends Vtiger_Action_Controller {

	function __construct() {
		parent::__construct();
		$this->exposeMethod('showMailContent');
	}

	public function process(Vtiger_Request $request) {
		$mode = $request->getMode();
		if (!empty($mode)) {
			echo $this->invokeExposedMethod($mode, $request);
			return;
		}
	}

	/**
	 * Function to show body of all the mails in a folder
	 * @param Vtiger_Request $request
	 */
	public function showMailContent(Vtiger_Request $request) {
		$mailIds = $request->get("mailids");
		$folderName = $request->get("folderName");

		$model = MailManager_Mailbox_Model::activeInstance();
		$connector = MailManager_Connector_Connector::connectorWithModel($model, $folderName);

		$mailContents = array();
		foreach ($mailIds as $msgNo) {
			$message = $connector->openMail($msgNo, $folderName);
			$mailContents[$msgNo] = $message->getInlineBody();
		}
		$response = new Vtiger_Response();
		$response->setResult($mailContents);
		$response->emit();
	}

}
