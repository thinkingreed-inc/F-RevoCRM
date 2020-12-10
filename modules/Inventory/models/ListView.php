<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

/**
 * Inventory ListView Model Class
 */
class Inventory_ListView_Model extends Vtiger_ListView_Model {

	/*
	 * Function to give advance links of a module
	 *	@RETURN array of advanced links
	 */
	public function getAdvancedLinks(){
		return parent::getAdvancedLinks();
	}

	/**
	 * Function to get the list of listview links for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associate array of Link Type to List of Vtiger_Link_Model instances
	 */
	public function getListViewLinks($linkParams) {
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$moduleModel = $this->getModule();

		$linkTypes = array('LISTVIEWBASIC', 'LISTVIEW', 'LISTVIEWSETTING');
		$links = Vtiger_Link_Model::getAllByType($moduleModel->getId(), $linkTypes, $linkParams);

		$basicLinks = array();

		$createPermission = Users_Privileges_Model::isPermitted($moduleModel->getName(), 'CreateView');
		if($createPermission) {
			$basicLinks[] = array(
					'linktype' => 'LISTVIEWBASIC',
					'linklabel' => 'LBL_ADD_RECORD',
					'linkurl' => $moduleModel->getCreateRecordUrl(),
					'linkicon' => ''
			);
		}

		$exportPermission = Users_Privileges_Model::isPermitted($moduleModel->getName(), 'Export');
		if($exportPermission) {
			$advancedLinks[] = array(
					'linktype' => 'LISTVIEW',
					'linklabel' => 'LBL_EXPORT',
					'linkurl' => 'javascript:Vtiger_List_Js.triggerExportAction("'.$this->getModule()->getExportUrl().'")',
					'linkicon' => ''
				);
		}

		foreach($basicLinks as $basicLink) {
			$links['LISTVIEWBASIC'][] = Vtiger_Link_Model::getInstanceFromValues($basicLink);
		}

		$advancedLinks = $this->getAdvancedLinks();
		foreach($advancedLinks as $advancedLink) {
			$links['LISTVIEW'][] = Vtiger_Link_Model::getInstanceFromValues($advancedLink);
		}

		if($currentUserModel->isAdminUser()) {
			$settingsLinks = $this->getSettingLinks();
			foreach($settingsLinks as $settingsLink) {
				$links['LISTVIEWSETTING'][] = Vtiger_Link_Model::getInstanceFromValues($settingsLink);
			}
		}

		$exportPDFLinks = $this->getExportPDFLinks();
		foreach($exportPDFLinks as $exportPDFLink) {
			$links['LISTVIEW'][] = Vtiger_Link_Model::getInstanceFromValues($exportPDFLink);
		}

		return $links;
	}

	public function getExportPDFLinks()
	{
		global $adb;
		$moduleName = $this->getModule()->getName();
		$result = $adb->pquery("SELECT templateid, templatename FROM vtiger_pdftemplates WHERE module = ?",array($moduleName));
		$exportPDFLinks = array();
		for($i=0; $i<$adb->num_rows($result); $i++) {
			$templateId = $adb->query_result($result, $i, 'templateid');
			$templateName = $adb->query_result($result, $i, 'templatename');
			$exportPDFLink = array(
				'linklabel' => vtranslate('LBL_EXPORT_TO_PDF', $moduleName).'('.$templateName.')',
				'linkurl' => 'javascript:Vtiger_List_Js.triggerPDFExportAction("'.$this->getModule()->getPDFExportUrl().'&templateName='.$templateName.'&template='.$templateId.'")',
				'linkicon' => ''
			);
			$exportPDFLinks[] = $exportPDFLink;
		}
		return $exportPDFLinks;
	}
}