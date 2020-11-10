<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************ */

class Settings_CustomerPortal_Module_Model extends Settings_Vtiger_Module_Model {

	var $name = 'CustomerPortal';
	var $max_sequence = '';

	/**
	 * Function to get Current portal user
	 * @return <Interger> userId
	 */
	public function getCurrentPortalUser() {
		$db = PearDatabase::getInstance();

		$result = $db->pquery("SELECT prefvalue FROM vtiger_customerportal_prefs WHERE prefkey = 'userid' AND tabid = 0", array());
		if ($db->num_rows($result)) {
			return $db->query_result($result, 0, 'prefvalue');
		}
		return false;
	}

	/**
	 * Function to get current default assignee from portal
	 * @return <Integer> userId
	 */
	public function getCurrentDefaultAssignee() {
		$db = PearDatabase::getInstance();

		$result = $db->pquery("SELECT default_assignee FROM vtiger_customerportal_settings", array());
		if ($db->num_rows($result)) {
			return $db->query_result($result, 0, 'default_assignee');
		}
		return false;
	}

	/**
	 * Function to get list of portal modules
	 * @return <Array> list of portal modules <Vtiger_Module_Model>
	 */
	public function getModulesList() {
		if (!$this->portalModules) {
			$db = PearDatabase::getInstance();

			$query = "SELECT vtiger_customerportal_tabs.*, vtiger_tab.name FROM vtiger_customerportal_tabs
					INNER JOIN vtiger_tab ON vtiger_customerportal_tabs.tabid = vtiger_tab.tabid AND vtiger_tab.presence = 0 ORDER BY vtiger_customerportal_tabs.sequence";

			$result = $db->pquery($query, array());
			$rows = $db->num_rows($result);
			for ($i = 0; $i < $rows; $i++) {
				$rowData = $db->query_result_rowdata($result, $i);
				$tabId = $rowData['tabid'];

				if ($rowData['sequence'] > $this->max_sequence)
					$this->max_sequence = $rowData['sequence'];

					$moduleModel = Vtiger_Module_Model::getInstance($tabId);
					foreach ($rowData as $key => $value) {
						$moduleModel->set($key, $value);
					}
					$portalModules[$tabId] = $moduleModel;
				}
			$this->portalModules = $portalModules;
		}
		return $this->portalModules;
	}

	/**
	 * Function to get list of Contact Related Modules LIst
	 * @return <Array> list of Contact Related Modules <Vtiger_Module_Model>
	 */
	public function getContactRelatedModulesList() {
		$contacModuleModel = Vtiger_Module_Model::getInstance('Contacts');
		$relationModules = Vtiger_Relation_Model::getAllRelations($contacModuleModel);
		$restrictedModules = array('ModComments', 'Calendar', 'Potentials', 'Emails', 'PurchaseOrder', 'SalesOrder', 'Campaigns', 'Vendors');
		$contactRelatedModules = array();
		foreach ($relationModules as $relationModuleModel) {
			$relatedModuleName = $relationModuleModel->get('relatedModuleName');
			if (!in_array($relatedModuleName, $restrictedModules)) {
				$relatedmoduleModel = Vtiger_Module_Model::getInstance($relatedModuleName);
				$contactRelatedModules[$relatedmoduleModel->getId()] = $relatedmoduleModel;
			}
		}
		return $contactRelatedModules;
	}

	/**
	 * Function to save the details of Portal modules
	 */
	public function save() {
		$db = PearDatabase::getInstance();
		$defaultAssignee = $this->get('defaultAssignee');
		$enableModules = $this->get('enableModules');
		$portalModulesInfo = $this->get('moduleSequence');
		$renewalPeriod = $this->get('support_notification');
		$announcement = $this->get('announcement');
		$shortcuts = $this->get('shortcuts');
		$moduleFieldsInfo = $this->get('moduleFieldsInfo');
		$relatedModuleList = $this->get('relatedModuleList');
		$charts = $this->get('charts');
		$widgets = $this->get('widgets');
		$recordsVisible = $this->get('recordsVisible');
		$recordPermissions = $this->get('recordPermissions');
		foreach ($enableModules as $moduleId => $visibility) {
			$disable = array(getTabid('Accounts'), getTabid('Contacts'));
			if (in_array($moduleId, $disable)) {
				throw new Exception("Trying to access restricted module");
				exit;
			}
			$tabid = getTabid($moduleId);
			$db->pquery('INSERT INTO vtiger_customerportal_tabs(tabid,visible) VALUES(?,?) ON DUPLICATE KEY UPDATE visible = ?', array($tabid, $visibility, $visibility));
		}

		$updateSequenceQuery = " UPDATE vtiger_customerportal_tabs SET sequence = ? WHERE tabid = ?";
		foreach ($portalModulesInfo as $tabId => $moduleDetails) {
			$db->pquery($updateSequenceQuery, array($moduleDetails['sequence'], $tabId));
		}

		//Update the dashboard widgets, charts, announcement and support_notification details.
		$activeWidgets['widgets'] = $widgets;
		$dashboardWidgets = json_encode($activeWidgets);
		if ($dashboardWidgets) {
			$db->pquery('UPDATE vtiger_customerportal_settings SET default_assignee=?, support_notification=?, announcement=?, widgets=?', array($defaultAssignee, $renewalPeriod, $announcement, $dashboardWidgets));
		}
		//Update module field info
		if (!empty($moduleFieldsInfo)) {
			foreach ($moduleFieldsInfo as $module => $fields) {
				$tabid = getTabid($module);
				$currentActiveFields = json_decode($fields, true);
				foreach ($currentActiveFields as $field => $status) {
					if (!isFieldActive($module, $field)) {
						$currentActiveFields[$field] = 0;
					}
				}
				self::updateFields($tabid, json_encode($currentActiveFields));
			}
		}

		//Update related module info

		if (!empty($relatedModuleList)) {
			foreach ($relatedModuleList as $module => $info) {
				$tabid = getTabid($module);
				$db->pquery('INSERT INTO vtiger_customerportal_relatedmoduleinfo(tabid, relatedmodules) VALUES(?,?) ON DUPLICATE KEY UPDATE relatedmodules = ?', array($tabid, $info, $info));
			}
		}

		//Update record visiblity status

		if (!empty($recordsVisible)) {
			foreach ($recordsVisible as $module => $info) {
				$tabid = getTabid($module);
				if ($info == 'all') {
					$db->pquery('UPDATE vtiger_customerportal_fields SET records_visible = ? WHERE tabid = ?', array(1, $tabid));
				} else if ($info == 'onlymine') {
					$db->pquery('UPDATE vtiger_customerportal_fields SET records_visible = ? WHERE tabid = ?', array(0, $tabid));
				}
			}
		}

		// clearing mem-cache for CustomerPortal
		Vtiger_Cache::delete('CustomerPortal', 'activeModules');
		Vtiger_Cache::delete('CustomerPortal', 'activeFields');

		//Update record permissions.
		if (!empty($recordPermissions)) {
			$updatedPermissions = array();
			foreach ($recordPermissions as $module => $permissionsArray) {
				$updatedPermissions['module'] = $module;
				foreach ($permissionsArray as $permissionKey => $permissionValues) {
					if (is_array($permissionValues)) {
						foreach ($permissionValues as $permissions => $value) {
							$updatedPermissions[$permissions] = $value;
						}
					}
				}
			}
			$tabId = getTabid($updatedPermissions['module']);
			$db->pquery('UPDATE vtiger_customerportal_tabs SET createrecord=?,editrecord=? WHERE tabid=?', array($updatedPermissions['create'], $updatedPermissions['edit'], getTabid($updatedPermissions['module'])));
		}
	}

	public function getRelatedModules($sourceModule) {
		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT relatedmodules FROM vtiger_customerportal_relatedmoduleinfo WHERE tabid = ? ', array(getTabid($sourceModule)));
		$relatedModules = array();
		if ($db->num_rows($result) > 0) {
			$row = $db->fetch_array($result);
			$relatedModules[$sourceModule] = json_decode(decode_html($row['relatedmodules']), true);
		}
		return $relatedModules;
	}

	public function getDashboardInfo() {
		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT * FROM vtiger_customerportal_settings', array());
		$noOfoRows = $db->num_rows($result);
		$dashboardInfo = array();
		if ($noOfoRows == 1) {
			while ($row = $db->fetch_array($result)) {
				$dashboardInfo['url'] = $row['url'];
				$dashboardInfo['default_assignee'] = $row['default_assignee'];
				$dashboardInfo['support_notification'] = $row['support_notification'];
				$dashboardInfo['announcement'] = $row['announcement'];
				$dashboardInfo['shortcuts'] = decode_html($row['shortcuts']);
				$dashboardInfo['widgets'] = decode_html($row['widgets']);
			}
			$currentWidgets = json_decode($dashboardInfo['widgets'], true);
			$dashboardInfo['widgets'] = json_encode($currentWidgets);
			$this->set('dashboardInfo', $dashboardInfo);
		}
		return $this;
	}

	public function getSelectedFields($tabId) {
		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT fieldinfo FROM vtiger_customerportal_fields WHERE tabid = ?', array($tabId));
		if ($db->num_rows($result) > 0) {
			$fieldInfo = $db->query_result($result, 0, 'fieldinfo');
		}
		return $fieldInfo;
	}

	public function updateFields($tabId, $fieldJson) {
		$db = PearDatabase::getInstance();
		$db->pquery('INSERT INTO vtiger_customerportal_fields(tabid, fieldinfo) VALUES(?,?) ON DUPLICATE KEY UPDATE fieldinfo = ?', array($tabId, $fieldJson, $fieldJson));
	}

	public function getRecordVisiblity($tabId) {
		$db = PearDatabase::getInstance();
		$result = $db->pquery('SELECT records_visible FROM vtiger_customerportal_fields WHERE tabid= ?', array($tabId));
		if ($db->num_rows($result)) {
			$visibilityResult = $db->query_result($result, 0, 'records_visible');
			$visibilityInfo = array();
			if ($visibilityResult == 0) {
				$visibilityInfo['onlymine'] = 1;
				$visibilityInfo['all'] = 0;
				$visibilityInfo['onlypublished'] = 0;
			} else if ($visibilityResult == 1) {
				$visibilityInfo['all'] = 1;
				$visibilityInfo['onlymine'] = 0;
				$visibilityInfo['onlypublished'] = 0;
			} else if ($visibilityResult == 2) {
				$visibilityInfo['all'] = 0;
				$visibilityInfo['onlymine'] = 0;
				$visibilityInfo['onlypublished'] = 1;
			}
		}
		return $visibilityInfo;
	}

	public function getRecordPermissions($tabid) {
		$db = PearDatabase::getInstance();
		$permissionResult = $db->pquery('SELECT createrecord,editrecord FROM vtiger_customerportal_tabs WHERE tabid=?', array($tabid));
		if ($db->num_rows($permissionResult)) {
			$createPermission = $db->query_result($permissionResult, 0, 'createrecord');
			$editPermission = $db->query_result($permissionResult, 0, 'editrecord');
			$permissionInfo = array();
			$permissionInfo['create'] = $createPermission;
			$permissionInfo['edit'] = $editPermission;
		}
		return $permissionInfo;
	}

	//Function to check if the field is editable on Portal depending on its
	//module field name and wether it is editable in CRM or No.,

	public function isFieldCustomerPortalEditable($crmStatus, $value, $module) {
		$isFieldEditable = 0;
		if ($crmStatus && $value->name !== 'assigned_user_id' && $value->name !== 'contact_id') {
			$isFieldEditable = 1;
			switch ($module) {
				case 'HelpDesk'	:	if (in_array($value->name, array('contact_id', 'parent_id'))) {
										$isFieldEditable = 0;
									}
									break;
				case 'Assets'	:	if (in_array($value->name, array('account', 'contact', 'datesold', 'serialnumber'))) {
										$isFieldEditable = 0;
									}
									break;
			}
		}
		return $isFieldEditable;
	}

}
