<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Rss_GetHtml_Action extends Vtiger_Action_Controller {

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView');
		return $permissions;
	} 
	
	public function process(Vtiger_Request $request) {
		$module = $request->get('module');
        $url = $request->get('url');
        $recordModel = Rss_Record_Model::getCleanInstance($module);
        $html = $recordModel->getHtmlFromUrl($url);

		$response = new Vtiger_Response();
		$response->setResult(array('html'=>$html));
		$response->emit();
	}
}
