<?php
/* +***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * *********************************************************************************** */

class RecycleBin_Module_Model extends Vtiger_Module_Model {


	/**
	 * Function to get the url for list view of the module
	 * @return <string> - url
	 */
	public function getDefaultUrl() {
		return 'index.php?module='.$this->get('name').'&view='.$this->getListViewName();
	}

	/**
	 * Function to get the list of listview links for the module
	 * @return <Array> - Associate array of Link Type to List of Vtiger_Link_Model instances
	 */
	public function getListViewLinks() {
		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		$privileges = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$basicLinks = array();
		if($currentUserModel->isAdminUser()) {
			$basicLinks = array(
					array(
						'linktype' => 'LISTVIEWBASIC',
						'linklabel' => 'LBL_EMPTY_RECYCLEBIN',
						'linkurl' => 'javascript:RecycleBin_List_Js.emptyRecycleBin("index.php?module='.$this->get('name').'&action=RecycleBinAjax")',
						'linkicon' => ''
					)
				);
		} 

		foreach($basicLinks as $basicLink) {
			$links['LISTVIEWBASIC'][] = Vtiger_Link_Model::getInstanceFromValues($basicLink);
		}

		return $links;
	}

	/**
	 * Function to get the list of Mass actions for the module
	 * @param <Array> $linkParams
	 * @return <Array> - Associative array of Link type to List of  Vtiger_Link_Model instances for Mass Actions
	 */
	public function getListViewMassActions() {
		$currentUserModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();

		$massActionLinks = array();
		if($currentUserModel->isAdminUser()) {
			$massActionLinks[] = array(
					'linktype' => 'LISTVIEWMASSACTION',
					'linklabel' => 'LBL_DELETE',
					'linkurl' => 'javascript:RecycleBin_List_Js.deleteRecords("index.php?module='.$this->get('name').'&action=RecycleBinAjax")',
					'linkicon' => ''
			);
		}

			$massActionLinks[] = array(
					'linktype' => 'LISTVIEWMASSACTION',
					'linklabel' => 'LBL_RESTORE',
					'linkurl' => 'javascript:RecycleBin_List_Js.restoreRecords("index.php?module='.$this->get('name').'&action=RecycleBinAjax")',
					'linkicon' => ''
			);


		foreach($massActionLinks as $massActionLink) {
			$links[] = Vtiger_Link_Model::getInstanceFromValues($massActionLink);
		}

		return $links;
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

	/**
	 * Function to get all entity modules
	 * @return <array>
	 */
	public function getAllModuleList(){
		$moduleModels = parent::getEntityModules();
		$restrictedModules = array('Emails', 'ProjectMilestone', 'ModComments', 'Rss', 'Portal', 'Integration', 'PBXManager', 'Dashboard', 'Home', 'Events');
		foreach($moduleModels as $key => $moduleModel){
			if(in_array($moduleModel->getName(),$restrictedModules) || $moduleModel->get('isentitytype') != 1){
				unset($moduleModels[$key]);
			}
		}
		return $moduleModels;
	}

	/**
	 * Function to delete the reccords perminently in vitger CRM database
	 */
	public function emptyRecycleBin(){
		$db = PearDatabase::getInstance();

		$db->pquery('DELETE vtiger_modtracker_basic.* FROM vtiger_modtracker_basic INNER JOIN vtiger_crmentity on 
					vtiger_crmentity.crmid = vtiger_modtracker_basic.crmid AND vtiger_crmentity.deleted = 1', array());

		$getIdsQuery='SELECT crmid from vtiger_crmentity WHERE deleted=?';
		$resultIds=$db->pquery($getIdsQuery,array(1));
		$recordIds=array();
		if($db->num_rows($resultIds)){
			for($i=0;$i<$db->num_rows($resultIds);$i++){
				$recordIds[$i]=$db->query_result($resultIds,$i,'crmid');
			}
		}
		$this->deleteFiles($recordIds);
		$db->pquery('DELETE FROM vtiger_crmentity WHERE deleted = 1', array());
		$db->pquery('DELETE FROM vtiger_relatedlists_rb', array());

		return true;
	}

	/**
	 * Function to deleted the records perminently in CRM
	 * @param type $reocrdIds
	 */
	public function deleteRecords($recordIds){
		$db = PearDatabase::getInstance();

		$query = 'DELETE FROM vtiger_relatedlists_rb WHERE entityid in('.generateQuestionMarks($recordIds).')';
		$db->pquery($query, array($recordIds));
        
        // Delete related mod comments
		$this->deleteRelatedComments($recordIds);
        $this->deleteRelatedEmails($recordIds);

		// TODO - Remove records from module tables and other related stores.
		$query = 'DELETE FROM vtiger_modtracker_basic WHERE crmid in(' . generateQuestionMarks($recordIds) . ')';
		$db->pquery($query, array($recordIds));
		// Delete entries of attachments from vtiger_attachments and vtiger_seattachmentsrel
		$this->deleteFiles($recordIds);

		//Delete the records in vtiger crmentity and relatedlists.
		$query = 'DELETE FROM vtiger_crmentity WHERE deleted = ? and crmid in('.generateQuestionMarks($recordIds).')';
		$db->pquery($query, array(1, $recordIds));
	}

	/**Function to delete files from CRM.
	 *@param type $recordIds
	 */
	public function deleteFiles($recordIds){
		global $db;
		$db=PearDatabase::getInstance();
		$getAttachmentsIdQuery='SELECT * FROM vtiger_seattachmentsrel WHERE crmid in('.generateQuestionMarks($recordIds).')';
		$result=$db->pquery($getAttachmentsIdQuery,array($recordIds));
		$attachmentsIds=array();
		if($db->num_rows($result)){
			for($i=0;$i<($db->num_rows($result));$i++){
				$attachmentsIds[$i]=$db->query_result($result,$i,'attachmentsid');
			}
		}
		if(!empty($attachmentsIds)){
			$deleteRelQuery='DELETE FROM vtiger_seattachmentsrel WHERE crmid in('.generateQuestionMarks($recordIds).')';
			$db->pquery($deleteRelQuery,array($recordIds));
			$attachmentsLocation=array();
			$getPathQuery='SELECT * FROM vtiger_attachments WHERE attachmentsid in ('.generateQuestionMarks($attachmentsIds).')';
			$pathResult=$db->pquery($getPathQuery,array($attachmentsIds));
			if($db->num_rows($pathResult)){
				for($i=0;$i<($db->num_rows($pathResult));$i++){
					$attachmentsLocation[$i]=$db->query_result($pathResult,$i,'path');
					$attachmentName=$db->query_result($pathResult,$i,'name');
					$attachmentId=$db->query_result($pathResult,$i,'attachmentsid');
					$fileName=$attachmentsLocation[$i].$attachmentId.'_'.decode_html($attachmentName);
					if(file_exists($fileName)){
						chmod($fileName,0750);
						unlink($fileName);
					}
				}
			}
			$deleteAttachmentQuery='DELETE FROM vtiger_attachments WHERE attachmentsid in ('.generateQuestionMarks($attachmentsIds).')';
			$db->pquery($deleteAttachmentQuery,array($attachmentsIds));
		}
	}

	/**
	 * Function to restore the deleted records.
	 * @param type $sourceModule
	 * @param type $recordIds
	 */
	public function restore($sourceModule, $recordIds){
		$focus = CRMEntity::getInstance($sourceModule);
		for($i=0;$i<php7_count($recordIds);$i++) {
			if(!empty($recordIds[$i])) {
				$focus->restore($sourceModule, $recordIds[$i]);
			}
		}
	}

	public function getDeletedRecordsTotalCount() {  
		$db = PearDatabase::getInstance();  
		$totalCount = $db->pquery('select count(*) as count from vtiger_crmentity where deleted=1',array());  
		return $db->query_result($totalCount, 0, 'count');  
	}

	/**
	 * Function to check deleted records exists in recyclebin
	 * @return <Boolean> true/false
	 */
	public function isRecordsDeleted() {
		$db = PearDatabase::getInstance(); 
		$totalCount = $db->pquery('SELECT 1 FROM vtiger_crmentity WHERE deleted=1 LIMIT 1',array());
		if($db->num_rows($totalCount) > 0) {
			return true;
		}
		return false;
	}

	/*
	 * Function to get supported utility actions for a module
	 */
	function getUtilityActionsNames() {
		return array();
	}

	/**
	 * Funxtion to identify if the module supports quick search or not
	 */
	public function isQuickSearchEnabled() {
		return true;
	}
    
    public function deleteRelatedComments($recordIds) {
		$db = PearDatabase::getInstance();
		$query = 'DELETE vtiger_crmentity.* FROM vtiger_crmentity '
				. 'INNER JOIN vtiger_modcomments ON vtiger_modcomments.modcommentsid = vtiger_crmentity.crmid '
				. 'WHERE vtiger_modcomments.related_to in(' . generateQuestionMarks($recordIds) . ')';

		$db->pquery($query, array($recordIds));
	}
    
    /**
	 * Function to remove emails related to given records
	 * @param type $recordIds
	 */
	public function deleteRelatedEmails($recordIds) {
		$db = PearDatabase::getInstance();
		/**
		 *  we have to delete emails if email is related to any $recordIds and same email is 
		 *  not related to another record
		 */
		$query = "DELETE vtiger_crmentity.* FROM vtiger_crmentity INNER JOIN "
				. "(SELECT vtiger_crmentity.crmid AS actid,vtiger_seactivityrel.crmid AS relid "
				. "FROM vtiger_crmentity INNER JOIN vtiger_seactivityrel ON "
				. "vtiger_seactivityrel.activityid=vtiger_crmentity.crmid "
				. "GROUP BY vtiger_seactivityrel.activityid HAVING count(vtiger_seactivityrel.activityid) = 1)"
				. " AS relationdata ON relationdata.actid=vtiger_crmentity.crmid "
				. "WHERE relationdata.relid IN (" . generateQuestionMarks($recordIds) . ")";

		$db->pquery($query, array($recordIds));
	}
}
