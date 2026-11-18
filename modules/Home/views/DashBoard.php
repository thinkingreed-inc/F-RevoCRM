<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Home_DashBoard_View extends Vtiger_DashBoard_View {

	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		// ウィジェット「共有リンク」の枠の高さと「ウィジェットの追加」メニューの調整
		$jsScriptInstances = $this->checkAndConvertJsScripts(array('modules.Home.resources.SharedLinksWidget'));
		return array_merge($headerScriptInstances, $jsScriptInstances);
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		// ウィジェット「共有リンク」の見た目
		$cssInstances = $this->checkAndConvertCssStyles(array(
			'~layouts/'.Vtiger_Viewer::getDefaultLayoutName().'/modules/Home/resources/SharedLinksWidget.css',
		));
		return array_merge($headerCssInstances, $cssInstances);
	}
}