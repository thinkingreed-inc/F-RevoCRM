<?php

/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class EmailTemplates_Module_Model extends Vtiger_Module_Model {

	public static $BROWSER_MERGE_TAG = '$custom-viewinbrowser$';
	/**
	 * Function to get Alphabet Search Field 
	 */
	public function getAlphabetSearchField() {
		return 'templatename';
	}

	/**
	 * Function to get the url for the Create Record view of the module
	 * @return <String> - url
	 */
	public function getCreateRecordUrl() {
		return 'index.php?module=' . $this->get('name') . '&view=' . $this->getEditViewName();
	}

	/**
	* Function to save a given record model of the current module
	* @param EmailtTemplates_Record_Model $recordModel
	* @return <integer> template id
	*/
	public function saveRecord(Vtiger_Record_Model $recordModel) {
		$db = PearDatabase::getInstance();
		$recordId = $templateid = $recordModel->getId();
		$systemtemplate = $recordModel->get('systemtemplate');
		if (empty($systemtemplate)) {
			$systemtemplate = '0';
		}
		if(empty($templateid)){
			$templateid = $db->getUniqueID('vtiger_emailtemplates');
			$sql = "INSERT INTO vtiger_emailtemplates(templatename, subject, description, module, body, deleted, systemtemplate, templateid) VALUES (?,?,?,?,?,?,?,?)";
		}else{
			if($systemtemplate) {
				$sql = "UPDATE vtiger_emailtemplates SET templatename=?, description=?, module=?, body=?, deleted=?, systemtemplate=? WHERE templateid = ?";
			} else {
				$sql = "UPDATE vtiger_emailtemplates SET templatename=?, subject=?, description=?, module=?, body=?, deleted=?, systemtemplate=? WHERE templateid = ?";
			}
		}
		if(!empty($recordId) && $systemtemplate) {
			$params = array(decode_html($recordModel->get('templatename')), decode_html($recordModel->get('description')),
				$recordModel->get('module'),$recordModel->get('body'), 0, $systemtemplate, $templateid);
		} else {
			$params = array(decode_html($recordModel->get('templatename')), decode_html($recordModel->get('subject')),
				decode_html($recordModel->get('description')), $recordModel->get('module'),$recordModel->get('body'), 0, 
				$systemtemplate, $templateid);
		}
		$db->pquery($sql, $params);
		return $recordModel->setId($templateid);
	}

	/**
	 * Function to delete the email template
	 * @param type $recordIds
	 */
	public function deleteRecord(Vtiger_Record_Model $recordModel) {
		$recordId = $recordModel->getId();
		$db = PearDatabase::getInstance();
		$db->pquery('DELETE FROM vtiger_emailtemplates WHERE templateid = ? AND systemtemplate = ? ', array($recordId, '0'));
	}

	/**
	 * Function to delete all the email templates
	 * @param type $recordIds
	 */
	public function deleteAllRecords() {
		$db = PearDatabase::getInstance();
		$db->pquery('DELETE FROM vtiger_emailtemplates WHERE systemtemplate = ?', array('0'));
	}

	/**
	 * Function to get Email template fields from modules
	 * @return <array> template fields
	 */
	public function getAllModuleEmailTemplateFields() {
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$allModuleList = $this->getAllModuleList();
		$allRelFields = array();
		foreach ($allModuleList as $index => $module) {
			if($module == 'Users'){
				$fieldList = $this->getRelatedModuleFieldList($module, $currentUserModel);
				//Added for sending credentials through a system email from an email template
				$fieldList[] = array(
					'module' => $module,
					'fieldname' => 'user_password_custom',
					'columnname' => 'user_password_custom',
					'fieldlabel' => 'Password'
				);
			}else{
				$fieldList = $this->getRelatedFields($module, $currentUserModel);
			}
			$allFields = array();
			foreach ($fieldList as $key => $field) {
				$option = array(vtranslate($field['module'], $field['module']) . ':' . vtranslate($field['fieldlabel'], $field['module']), "$" . strtolower($field['module']) . "-" . $field['columnname'] . "$");
				$allFields[] = $option;
				if (!empty($field['referencelist'])) {
					foreach ($field['referencelist'] as $referenceList) {
						foreach($referenceList as $key => $relField) {
						$relOption = array(vtranslate($field['fieldlabel'], $field['module']) . ':' . '(' . vtranslate($relField['module'], $relField['module']) . ')' . vtranslate($relField['fieldlabel'],$relField['module']), "$" . strtolower($field['module']) . "-" . $field['columnname'] . ":" . $relField['columnname'] . "$");
						$allRelFields[] = $relOption;
					}
				}
			}
			}
			if(is_array($allFields) && is_array($allRelFields)){
				$allFields = array_merge($allFields, $allRelFields);
				$allRelFields= array();
			}
			$allOptions[$module] = $allFields;
			$allFields = array();
		}
		return $allOptions;
	}

	/** 
	* Function retrives all company details merge tags and add to field array 
	 * @return string 
	 */ 
	function getCompanyMergeTagsInfo(){ 
		global $site_URL; 
		$companyModuleModel = Settings_Vtiger_CompanyDetails_Model::getInstance(); 
		$basicFields = $companyModuleModel->companyBasicFields; 
		$socialFields = $companyModuleModel->companySocialLinks; 
		$qualifiedModule = "Settings:Vtiger"; 
		$moduleName = vtranslate("LBL_COMPANY_DETAILS", $qualifiedModule); 
		$allFields = array(); 
		$logoPath = $site_URL . '/' . $companyModuleModel->getLogoPath(); 
		foreach ($basicFields as $columnName => $value) { 
			//For column logo we need place logo in content 
			if($columnName == 'logo'){
				$allFields[] = array($moduleName.':'. vtranslate($columnName, $qualifiedModule),"$$columnName$");
			} else {
				$allFields[] = array($moduleName.':'. vtranslate($columnName, $qualifiedModule),"$".strtolower("companydetails")."-".$columnName."$");
			}
		} 
		// Social links will be having hyperlink redirected to URL mentioned 
		foreach($socialFields as $columnName => $value){ 
			$url = $companyModuleModel->get($columnName); 
			if($columnName == 'website'){ 
				$websiteURL = $url;
				if(empty($url)){
					$websiteURL = $columnName;
				}
				$allFields[] = array($moduleName.':'. vtranslate($columnName, $qualifiedModule),"<a target='_blank' href='".$url."'>$websiteURL</a>"); 
			} else { 
				$allFields[] = array($moduleName.':'. vtranslate($columnName, $qualifiedModule),"<a target='_blank' href='".$url."'>$columnName</a>"); 
			} 
		} 
		return $allFields; 
	} 

	/**
	 * Function returns custom merge tags	
	 * @return array
	 */
	function getCustomMergeTags(){
		$customMergeTags = array(
			array('Current Date', '$custom-currentdate$'),
			array('Current Time', '$custom-currenttime$'),
			array('View in browser',"<a target='_blank' href='".self::$BROWSER_MERGE_TAG."'>View in browser</a>"),
			array('CRM Detail View Url','$custom-crmdetailviewurl$'),
			array('Portal Detail View Url','$custom-portaldetailviewurl$'),
			array('Site Url','$custom-siteurl$'),
			array('Portal Url','$custom-portalurl$'));
		return $customMergeTags;
	}

	/**
	 * Function to get module fields
	 * @param type $module
	 * @param type $currentUserModel
	 * @return <arrau>
	 */
	function getRelatedFields($module, $currentUserModel) {
		$handler = vtws_getModuleHandlerFromName($module, $currentUserModel);
		$meta = $handler->getMeta();
		$moduleFields = $meta->getModuleFields();
		$db = PearDatabase::getInstance();
		//adding record id merge tag option 
		$fieldInfo = array('columnname' => 'id','fieldname' => 'id','fieldlabel' =>vtranslate('LBL_RECORD_ID', $this->getName()));
		$recordIdField = WebserviceField::fromArray($db, $fieldInfo);
		$moduleFields[$recordIdField->getFieldName()] = $recordIdField;

		$returnData = array();
		foreach ($moduleFields as $key => $field) {
			if(!in_array($field->getPresence(), array(0,2))){
				continue;
			}
			$referencelist = array();
			$relatedField = $field->getReferenceList();
			if ($field->getFieldName() == 'assigned_user_id') {
				$relModule = 'Users';
				$referencelist[] = $this->getRelatedModuleFieldList($relModule, $currentUserModel);
			}
			if (!empty($relatedField)) {
				foreach ($relatedField as $ind => $relModule) {
					$referencelist[] = $this->getRelatedModuleFieldList($relModule, $currentUserModel);
				}
			}
			$returnData[] = array('module' => $module, 'fieldname' => $field->getFieldName(), 'columnname' => $field->getColumnName(), 'fieldlabel' => $field->getFieldLabelKey(), 'referencelist' => $referencelist);
		}
		return $returnData;
	}

	/**
	 * Function to get related module fields
	 * @param type $relModule
	 * @param type $user
	 * @return null
	 */

	function getRelatedModuleFieldList($relModule, $user) {
		$handler = vtws_getModuleHandlerFromName($relModule, $user);
		$relMeta = $handler->getMeta();
		if (!$relMeta->isModuleEntity()) {
			return array();
		}
		$relModuleFields = $relMeta->getModuleFields();
		$relModuleFieldList = array();
		foreach ($relModuleFields as $relind => $relModuleField) {
			if(!in_array($relModuleField->getPresence(), array(0,2))){
				continue;
			}
			if($relModule == 'Users') {
				if(in_array($relModuleField->getFieldDataType(),array('string','phone','email','text'))) {
					$skipFields = array(98,115,116,31,32);
					if(!in_array($relModuleField->getUIType(), $skipFields) && $relModuleField->getFieldName() != 'asterisk_extension'){
						$relModuleFieldList[] = array('module' => $relModule, 'fieldname' => $relModuleField->getFieldName(), 'columnname' => $relModuleField->getColumnName(), 'fieldlabel' => $relModuleField->getFieldLabelKey());
					}
				}
			} else {
				$relModuleFieldList[] = array('module' => $relModule, 'fieldname' => $relModuleField->getFieldName(), 'columnname' => $relModuleField->getColumnName(), 'fieldlabel' => $relModuleField->getFieldLabelKey());
			}
		}
		return $relModuleFieldList;
	}

	/**
	 * Function to get module list which has the email field.
	 * @return type
	 */
	public function getAllModuleList(){
		$db = PearDatabase::getInstance();
		// Get modules names only those are active
		$query = 'SELECT DISTINCT(name) AS modulename FROM vtiger_tab 
					LEFT JOIN vtiger_field ON vtiger_field.tabid = vtiger_tab.tabid
					WHERE (vtiger_field.uitype = ? AND vtiger_tab.presence = ?) ';
		$params = array('13',0);
		// Check whether calendar module is active or not.
		if(vtlib_isModuleActive("Calendar")){
			$eventsTabid = getTabid('Events');
			$query .= ' OR vtiger_tab.tabid IN (?, ?)';
			array_push($params, $eventsTabid, getTabid('Calendar'));
		}
		$result = $db->pquery($query, $params);
		$num_rows = $db->num_rows($result);
		$moduleList = array();
		for($i=0; $i<$num_rows; $i++){
			$moduleList[] = $db->query_result($result, $i, 'modulename');
		}
		return $moduleList;
	}

	/**
	 * Function to get the Quick Links for the module
	 * @param <Array> $linkParams
	 * @return <Array> List of Vtiger_Link_Model instances
	 */
	public function getSideBarLinks($linkParams) {
		$linkTypes = array('SIDEBARLINK', 'SIDEBARWIDGET');
		$links = Vtiger_Link_Model::getAllByType($this->getId(), $linkTypes, $linkParams);

		$quickLinks = array(
			array(
				'linktype' => 'SIDEBARLINK',
				'linklabel' => 'LBL_RECORDS_LIST',
				'linkurl' => $this->getDefaultUrl(),
				'linkicon' => '',
			),
		);
		foreach($quickLinks as $quickLink) {
			$links['SIDEBARLINK'][] = Vtiger_Link_Model::getInstanceFromValues($quickLink);
		}
		return $links;
	}

	public function getRecordIds($skipRecords){
		$db = PearDatabase::getInstance();

		$query = 'SELECT templateid FROM vtiger_emailtemplates WHERE templateid NOT IN ('.generateQuestionMarks($skipRecords).')';
		$result = $db->pquery($query, $skipRecords);
		$num_rows = $db->num_rows($result);
		$recordIds = array();
		for($i; $i<$num_rows; $i++){
			$recordIds[] = $db->query_result($result, $i, 'templateid');
		}
		return $recordIds;
	}

	/**
	 * Funxtion to identify if the module supports quick search or not
	 */
	public function isQuickSearchEnabled() {
		return false;
	}

	public function getPopupUrl() {
		return 'module=EmailTemplates&view=Popup';
	}

	public function getBlocks() {
		if(empty($this->blocks)) {
			$blockLabelKeys = array('SINGLE_EmailTemplates', 'LBL_EMAIL_TEMPLATE');
			$blocks = array();
			foreach($blockLabelKeys as $blockIndex => $blockLabel){
				$blockInstance = new EmailTemplates_Block_Model();
				$blockInstance->set('blocklabel',$blockLabel)->set('sequence',$blockIndex+1)->set('module',$this)->set('id',$blockIndex+1);
				$blocks[$blockLabel] = $blockInstance;
			}
			$this->blocks = $blocks;
		}
		return parent::getBlocks();
	}

	/*
	 * Function to get supported utility actions for a module
	 */
	function getUtilityActionsNames() {
		return array();
	}

	/**
	 * Function to get Module Header Links (for Vtiger7)
	 * @return array
	 */
	public function getModuleBasicLinks(){
		$createPermission = Users_Privileges_Model::isPermitted($this->getName(), 'CreateView');
		$moduleName = $this->getName();
		if($createPermission) {
			$basicLinks[] = array(
				'linktype' => 'BASIC',
				'linklabel' => 'LBL_ADD_RECORD',
				'linkurl' => $this->getCreateRecordUrl(),
				'linkicon' => 'fa-plus'
			);
		}

		return $basicLinks;
	}

	function isFilterColumnEnabled() {
		return false;
	}
}
