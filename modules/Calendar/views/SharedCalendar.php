<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Calendar_SharedCalendar_View extends Calendar_Calendar_View {
	
	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$flag = 1;
		$record = Settings_Parameters_Record_Model::getInstanceByKey('CALENDAR_REMEMBER_FEED_SELECTION');
		if ($record) {
			$value = strtolower(trim((string)$record->get('value')));
			$flag = ($value === 'true' || $value === '1') ? 1 : 0;
		}
		$viewer->assign('CURRENT_USER', $currentUserModel);
		$viewer->assign('IS_CREATE_PERMITTED', isPermitted('Calendar', 'CreateView'));
		// 共有カレンダー：組織/役割切替時に「無効化したフィード(disablefeeds)」の記憶を反映するかどうか
		$viewer->assign('CALENDAR_REMEMBER_FEED_SELECTION', $flag);
		$viewer->view('SharedCalendarView.tpl', $request->getModule());
	}
	
	public function getHeaderScripts(Vtiger_Request $request) {
		$headerScriptInstances = parent::getHeaderScripts($request);
		$jsFileNames = array(
			'modules.Calendar.resources.SharedCalendar',
			'~/libraries/jquery/bootstrapswitch/js/bootstrap-switch.min.js',
		);

		$jsScriptInstances = $this->checkAndConvertJsScripts($jsFileNames);
		$headerScriptInstances = array_merge($headerScriptInstances, $jsScriptInstances);
		return $headerScriptInstances;
	}

	public function getHeaderCss(Vtiger_Request $request) {
		$headerCssInstances = parent::getHeaderCss($request);
		$cssFileNames = array(
			'~/libraries/jquery/bootstrapswitch/css/bootstrap2/bootstrap-switch.min.css',
		);
		$cssInstances = $this->checkAndConvertCssStyles($cssFileNames);
		$headerCssInstances = array_merge($headerCssInstances, $cssInstances);
		return $headerCssInstances;
	}	
}