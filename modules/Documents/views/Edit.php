<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

require_once 'modules/Documents/utils/FolderPermission.php';

Class Documents_Edit_View extends Vtiger_Edit_View {

	/**
	 * フォルダ権限を確認する（標準の権限判定はフォルダを見ない）
	 *
	 * 編集画面なので、参照だけでなく変更できることまで求める。
	 */
	public function checkPermission(Vtiger_Request $request) {
		parent::checkPermission($request);
		Documents_FolderPermission::checkRequestEdit($request);
		return true;
	}

	/**
	 * Function to get the list of Script models to be included
	 * @param Vtiger_Request $request
	 * @return <Array> - List of Vtiger_JsScript_Model instances
	 */
	function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);

		$moduleName = $request->getModule();

		$jsFileNames = array(
				"libraries.jodit.jodit.fat.min",
				'modules.Vtiger.resources.JoditEditor',
		);
		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

}
?>
