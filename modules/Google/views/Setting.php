<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class Google_Setting_View extends Vtiger_PopupAjax_View {

	public function __construct() {
		$this->exposeMethod('emitContactSyncSettingUI');
	}

    public function requiresPermission(\Vtiger_Request $request) {
		return array();
	}
    
	public function process(Vtiger_Request $request) {
		switch ($request->get('sourcemodule')) {
			case "Contacts" : $this->emitContactsSyncSettingUI($request);
				break;
			case "Calendar" : $this->emitCalendarSyncSettingUI($request);
				break;
		}
	}

	public function emitCalendarSyncSettingUI(Vtiger_Request $request) {
		$user = Users_Record_Model::getCurrentUserModel();
		$connector = new Google_Contacts_Connector(FALSE);
		$oauth2 = new Google_Oauth2_Connector($request->get('sourcemodule'));
		$isSyncReady = 'no';
		$selectedGoogleCalendar = Google_Utils_Helper::getSelectedCalendarForUser($user);
		if($oauth2->hasStoredToken()) {
			$controller = new Google_Calendar_Controller($user);
			$connector = $controller->getTargetConnector();
			try {
				$calendars = $connector->pullCalendars();
				$validCalendarSelected = false;
				foreach($calendars as $calendarsDetails) {
					if($calendarsDetails['id'] == $selectedGoogleCalendar || $selectedGoogleCalendar == 'primary')
						$validCalendarSelected = true;
				}
				if(!$validCalendarSelected) $selectedGoogleCalendar = 'primary';
				$isSyncReady = 'yes';
			} catch(Exception $e) {
				$calendars = array();
				$selectedGoogleCalendar = 'primary';
			}
		}
		$viewer = $this->getViewer($request);
		$viewer->assign('MODULENAME', $request->getModule());
		$viewer->assign('SOURCE_MODULE', $request->get('sourcemodule'));
		$viewer->assign('GOOGLE_CALENDARS', $calendars);
		$viewer->assign('SELECTED_GOOGLE_CALENDAR', $selectedGoogleCalendar);
		$viewer->assign('SYNC_DIRECTION',Google_Utils_Helper::getSyncDirectionForUser($user, $request->get('sourcemodule')));
		$viewer->assign('IS_SYNC_READY',$isSyncReady);

		echo $viewer->view('CalendarSyncSettings.tpl', $request->getModule(), true);
	}

	public function emitContactsSyncSettingUI(Vtiger_Request $request) {
	   echo $this->intializeContactsSyncSettingParameters($request);
	}

	public function intializeContactsSyncSettingParameters(Vtiger_Request $request) {
		$user = Users_Record_Model::getCurrentUserModel();
		$connector = new Google_Contacts_Connector(FALSE);
		$fieldMappping = Google_Utils_Helper::getFieldMappingForUser();
		$oauth2 = new Google_Oauth2_Connector($request->get('sourcemodule'));
		$isSyncReady = 'no';
		if($oauth2->hasStoredToken()) {
			$controller = new Google_Contacts_Controller($user);
			$connector = $controller->getTargetConnector();
			$groups = $connector->pullGroups();
			$isSyncReady = 'yes';
		}
		$targetFields = $connector->getFields();
		$selectedGroup = Google_Utils_Helper::getSelectedContactGroupForUser();
		$syncDirection = Google_Utils_Helper::getSyncDirectionForUser($user);
		$contactsModuleModel = Vtiger_Module_Model::getInstance($request->get('sourcemodule'));
		$mandatoryMapFields = array('salutationtype','firstname','lastname','title','account_id','birthday',
			'email','secondaryemail','mobile','phone','homephone','mailingstreet','otherstreet','mailingpobox',
			'otherpobox','mailingcity','othercity','mailingstate','otherstate','mailingzip','otherzip','mailingcountry',
			'othercountry','otheraddress','description','mailingaddress','otheraddress');
		$customFieldMapping = array();
		$contactsFields = $contactsModuleModel->getFields();
		foreach($fieldMappping as $vtFieldName => $googleFieldDetails) {
			if(!in_array($vtFieldName, $mandatoryMapFields) && ($contactsFields[$vtFieldName] && $contactsFields[$vtFieldName]->isViewable()))
				$customFieldMapping[$vtFieldName] = $googleFieldDetails;
		}
		$skipFields = array('reference','contact_id','leadsource','assigned_user_id','donotcall','notify_owner',
			'emailoptout','createdtime','modifiedtime','contact_no','modifiedby','isconvertedfromlead','created_user_id',
			'portal','support_start_date','support_end_date','imagename');
		$emailFields = $phoneFields = $urlFields = $otherFields = array();
		$disAllowedFieldTypes = array('reference','picklist','multipicklist');
		$sourceModule = $request->get('sourcemodule');
		foreach($contactsFields as $contactFieldModel) {
			if($contactFieldModel->isEditable() && !in_array($contactFieldModel->getFieldName(),array_merge($mandatoryMapFields,$skipFields))) {
				if($contactFieldModel->getFieldDataType() == 'email')
					$emailFields[$contactFieldModel->getFieldName()] = decode_html(vtranslate($contactFieldModel->get('label'), $sourceModule));
				else if($contactFieldModel->getFieldDataType() == 'phone') 
					$phoneFields[$contactFieldModel->getFieldName()] = decode_html(vtranslate($contactFieldModel->get('label'), $sourceModule));
				else if($contactFieldModel->getFieldDataType() == 'url')
					$urlFields[$contactFieldModel->getFieldName()] = decode_html(vtranslate($contactFieldModel->get('label'), $sourceModule));
				else if(!in_array ($contactFieldModel->getFieldDataType(), $disAllowedFieldTypes))
					$otherFields[$contactFieldModel->getFieldName()] = decode_html(vtranslate($contactFieldModel->get('label'), $sourceModule));
			}
		}

		$viewer = $this->getViewer($request);
		if($request->get('onlyGoogleToVtiger')) {
			$viewer->assign('ONLY_GOOGLE_TO_VTIGER', true);
		}
		$viewer->assign('MODULENAME', 'Google');
		$viewer->assign('SOURCE_MODULE', $request->get('sourcemodule'));
		$viewer->assign('SELECTED_GROUP', $selectedGroup);
		$viewer->assign('SYNC_DIRECTION', $syncDirection);
		$viewer->assign('GOOGLE_GROUPS', $groups);
		$viewer->assign('GOOGLE_FIELDS',$targetFields);
		$viewer->assign('FIELD_MAPPING',$fieldMappping);
		$viewer->assign('CUSTOM_FIELD_MAPPING',$customFieldMapping);
		$viewer->assign('VTIGER_EMAIL_FIELDS',$emailFields);
		$viewer->assign('VTIGER_PHONE_FIELDS',$phoneFields);
		$viewer->assign('VTIGER_URL_FIELDS',$urlFields);
		$viewer->assign('VTIGER_OTHER_FIELDS',$otherFields);
		$viewer->assign('IS_SYNC_READY',$isSyncReady);
		$onlyContents = $request->get('onlyContents');
		if($request->get('mode') == 'googleImport'){
			return $viewer->view('GoogleImportContents.tpl','Google',true);
		}
		if($onlyContents){
			return $viewer->view('ContactSyncSettingsContents.tpl', 'Google', true);
		}else{
			return $viewer->view('ContactsSyncSettings.tpl', 'Google', true);
		}
	}

}

?>