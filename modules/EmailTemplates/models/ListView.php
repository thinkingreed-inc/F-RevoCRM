<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class EmailTemplates_ListView_Model extends Vtiger_ListView_Model {

	private $querySelectColumns = array('templatename, foldername, subject', 'systemtemplate', 'module', 'description');
	private $listViewColumns = array('templatename', 'subject', 'description', 'module');

	public function addColumnToSelectClause($columName) {
		if (!is_array($columName))
			$columNameList = array($columName);
		else
			$columNameList = $columName;

		$this->querySelectColumns = array_merge($this->querySelectColumns, $columNameList);
		return $this; 
	}

	/**
	 * Function to get the list of Mass actions for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associative array of Link type to List of  Vtiger_Link_Model instances for Mass Actions
	 */
	public function getListViewMassActions($linkParams) {
		$moduleModel = $this->getModule();
		$linkTypes = array('LISTVIEWMASSACTION');
		$links = array();

		$massActionLinks[] = array(
			'linktype' => 'LISTVIEWMASSACTION',
			'linklabel' => 'LBL_DELETE',
			'linkurl' => 'javascript:EmailTemplates_List_Js.massDeleteRecords("index.php?module='.$moduleModel->get('name').'&action=MassDelete");',
			'linkicon' => ''
		);

		foreach($massActionLinks as $massActionLink) {
			$links['LISTVIEWMASSACTION'][] = Vtiger_Link_Model::getInstanceFromValues($massActionLink);
		}

		return $links;
	}

	/**
	 * Static Function to get the Instance of Vtiger ListView model for a given module and custom view
	 * @param <String> $moduleName - Module Name
	 * @param <Number> $viewId - Custom View Id
	 * @return Vtiger_ListView_Model instance
	 */
	public static function getInstance($moduleName, $viewId='0', $listHeaders = array()) {
            list($moduleName) = func_get_args();
            $db = PearDatabase::getInstance();
            $modelClassName = Vtiger_Loader::getComponentClassName('Model', 'ListView', $moduleName);
            $instance = new $modelClassName();

            $moduleModel = Vtiger_Module_Model::getInstance($moduleName);
            return $instance->set('module', $moduleModel);
	}

	/**
	 * Function to get the list view header
	 * @return <Array> - List of Vtiger_Field_Model instances
	 */
	public function getListViewHeaders() {
		$fieldObjects = array();
		$listViewHeaders = array('Template Name' => 'templatename', 'Subject' => 'subject', 'Description' => 'description', 'Module Name' => 'module');
		foreach ($listViewHeaders as $key => $fieldName) {
			$fieldModel = new EmailTemplates_Field_Model();
			$fieldModel->set('name', $fieldName);
			$fieldModel->set('label', $key);
			$fieldModel->set('column', $fieldName);
			$fieldObjects[] = $fieldModel;
		}
		return $fieldObjects;
	}

	/**
	 * Function to get the list view entries
	 * @param Vtiger_Paging_Model $pagingModel
	 * @return <Array> - Associative array of record id mapped to Vtiger_Record_Model instance.
	 */

	public function getListViewEntries($pagingModel) {
		$db = PearDatabase::getInstance();
		$startIndex = $pagingModel->getStartIndex();
		$pageLimit = $pagingModel->getPageLimit();
		$orderBy = $this->getForSql('orderby');
		$sortOrder = $this->getForSql('sortorder');

		$listQuery = $this->getQuery();
		$sourceModule = $this->get('sourceModule');
		$searchKey = $this->getForSql('search_key');
		$searchValue = $this->get('search_value');

		$params = array();
 		if(!empty($searchKey) && !empty($searchValue)) {
			$whereQuery .= " WHERE $searchKey LIKE ? AND ";
			$params[] = "%".$searchValue."%";
 		} else {
 			$whereQuery .= " WHERE ";
 		}

		//module should be enabled or module should be empty then allow
		$moduleActiveCheck = '(vtiger_tab.presence IN (0,2) OR vtiger_emailtemplates.module IS NULL OR vtiger_emailtemplates.module = "")';
		$listQuery .= $whereQuery. $moduleActiveCheck;
		//To retrieve only selected module records
		if ($sourceModule) {
			$listQuery .= " AND vtiger_emailtemplates.module = ?";
			$params[] = $sourceModule;
		}

		if ($orderBy) {
			$listQuery .= " ORDER BY $orderBy $sortOrder";
		} else {
			$listQuery .= " ORDER BY templateid DESC";
		}
		$listQuery .= " LIMIT ?,?";
		$params[] = $startIndex;
		$params[] = $pageLimit + 1;
		$result = $db->pquery($listQuery, $params);
		$num_rows = $db->num_rows($result);

		$listViewRecordModels = array();
		for ($i = 0; $i < $num_rows; $i++) {
			$recordModel = new EmailTemplates_Record_Model();
			$recordModel->setModule('EmailTemplates');
			$row = $db->query_result_rowdata($result, $i);
			$recordModel->setRawData($row);
			foreach ($row as $key => $value) {
				if($key=="module"){
					$value = vtranslate($value,$value);
				}
				if(in_array($key,$this->listViewColumns)){
					$value = textlength_check($value);
				}
				$row[$key] = $value;
			}
			$listViewRecordModels[$row['templateid']] = $recordModel->setData($row);
		}

		$pagingModel->calculatePageRange($listViewRecordModels);

		if($num_rows > $pageLimit){
			array_pop($listViewRecordModels);
			$pagingModel->set('nextPageExists', true);
		}else{
			$pagingModel->set('nextPageExists', false);
		}

		return $listViewRecordModels;
	}

	/**
	 * Function to get the list of listview links for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associate array of Link Type to List of Vtiger_Link_Model instances
	 */
	public function getListViewLinks($linkParams) {
		$moduleModel = $this->getModule();

		$linkTypes = array('LISTVIEWBASIC', 'LISTVIEW', 'LISTVIEWSETTING');
		$links = Vtiger_Link_Model::getAllByType($moduleModel->getId(), $linkTypes, $linkParams);

		$basicLinks = array(
				array(
						'linktype' => 'LISTVIEWBASIC',
						'linklabel' => 'LBL_ADD_RECORD',
						'linkurl' => $moduleModel->getCreateRecordUrl(),
						'linkicon' => ''
				)
		);
		foreach($basicLinks as $basicLink) {
			$links['LISTVIEWBASIC'][] = Vtiger_Link_Model::getInstanceFromValues($basicLink);
		}

		return $links;
	}

	function getQuery() {
		$listQuery = 'SELECT templateid,'.implode(',',$this->querySelectColumns).' FROM vtiger_emailtemplates
						LEFT JOIN vtiger_tab ON vtiger_tab.name = vtiger_emailtemplates.module
						AND (vtiger_tab.isentitytype=1 or vtiger_tab.name = "Users") ';
		return $listQuery;
	}

	/**
	 * Function to get the list view entries
	 * @param Vtiger_Paging_Model $pagingModel
	 * @return <Array> - Associative array of record id mapped to Vtiger_Record_Model instance.
	 */
	public function getListViewCount() {
		$db = PearDatabase::getInstance();

		$listQuery = $this->getQuery();

		$position = stripos($listQuery, 'from');
		if ($position) {
			$split = preg_split('/from/i', $listQuery);
			$splitCount = count($split);
			$listQuery = 'SELECT count(*) AS count ';
			for ($i=1; $i<$splitCount; $i++) {
				$listQuery = $listQuery. ' FROM ' .$split[$i];
			}
		}
		$searchKey = $this->getForSql('search_key');
		$searchValue = $this->get('search_value');

		$whereQuery .= " WHERE ";
        $params = array();
		if(!empty($searchKey) && !empty($searchValue)) {
			$whereQuery .= "$searchKey LIKE ? AND ";
            array_push($params, "%$searchValue%");
		}

		//module should be enabled or module should be empty then allow
		$moduleActiveCheck = '(vtiger_tab.presence IN (0,2) OR vtiger_emailtemplates.module IS null OR vtiger_emailtemplates.module = "")';
		$listQuery .= $whereQuery. $moduleActiveCheck;

		$sourceModule = $this->get('sourceModule');
		if ($sourceModule) {
			$listQuery .= ' AND vtiger_emailtemplates.module= ?';
            array_push($params, $sourceModule);
		}

		$listResult = $db->pquery($listQuery, $params);
		return $db->query_result($listResult, 0, 'count');
	}

} 