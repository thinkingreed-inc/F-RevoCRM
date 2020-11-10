<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ************************************************************************************/

class Accounts_Module_Model extends Vtiger_Module_Model {

	/**
	 * Function to get the Quick Links for the module
	 * @param <Array> $linkParams
	 * @return <Array> List of Vtiger_Link_Model instances
	 */
	public function getSideBarLinks($linkParams) {
		$parentQuickLinks = parent::getSideBarLinks($linkParams);

		$quickLink = array(
			'linktype' => 'SIDEBARLINK',
			'linklabel' => 'LBL_DASHBOARD',
			'linkurl' => $this->getDashBoardUrl(),
			'linkicon' => '',
		);

		//Check profile permissions for Dashboards
		$moduleModel = Vtiger_Module_Model::getInstance('Dashboard');
		$userPrivilegesModel = Users_Privileges_Model::getCurrentUserPrivilegesModel();
		$permission = $userPrivilegesModel->hasModulePermission($moduleModel->getId());
		if($permission) {
			$parentQuickLinks['SIDEBARLINK'][] = Vtiger_Link_Model::getInstanceFromValues($quickLink);
		}
		
		return $parentQuickLinks;
	}

	/**
	 * Function to get list view query for popup window
	 * @param <String> $sourceModule Parent module
	 * @param <String> $field parent fieldname
	 * @param <Integer> $record parent id
	 * @param <String> $listQuery
	 * @return <String> Listview Query
	 */
	public function getQueryByModuleField($sourceModule, $field, $record, $listQuery) {
		if (($sourceModule == 'Accounts' && $field == 'account_id' && $record)
				|| in_array($sourceModule, array('Campaigns', 'Products', 'Services', 'Emails'))) {

		    	$db = PearDatabase::getInstance();
		    	$params = array($record);
			if ($sourceModule === 'Campaigns') {
				$condition = " vtiger_account.accountid NOT IN (SELECT accountid FROM vtiger_campaignaccountrel WHERE campaignid = ?)";
			} elseif ($sourceModule === 'Products') {
				$condition = " vtiger_account.accountid NOT IN (SELECT crmid FROM vtiger_seproductsrel WHERE productid = ?)";
			} elseif ($sourceModule === 'Services') {
				$condition = " vtiger_account.accountid NOT IN (SELECT relcrmid FROM vtiger_crmentityrel WHERE crmid = ? UNION SELECT crmid FROM vtiger_crmentityrel WHERE relcrmid = ?) ";
                		$params = array($record, $record);
            		} elseif ($sourceModule === 'Emails') {
				$condition = ' vtiger_account.emailoptout = 0';
                		$params = array();
			} else {
				$condition = " vtiger_account.accountid != ?";
			}
            		$condition = $db->convert2Sql($condition, $params);

			$position = stripos($listQuery, 'where');
			if($position) {
				$split = preg_split('/where/i', $listQuery);
				$overRideQuery = $split[0] . ' WHERE ' . $split[1] . ' AND ' . $condition;
			} else {
				$overRideQuery = $listQuery. ' WHERE ' . $condition;
			}
			return $overRideQuery;
		}
	}

	/**
	 * Function to get relation query for particular module with function name
	 * @param <record> $recordId
	 * @param <String> $functionName
	 * @param Vtiger_Module_Model $relatedModule
	 * @return <String>
	 */
	public function getRelationQuery($recordId, $functionName, $relatedModule, $relationId) {
		if ($functionName === 'get_activities') {
			$focus = CRMEntity::getInstance($this->getName());
			$focus->id = $recordId;
			$entityIds = $focus->getRelatedContactsIds();
			$entityIds = implode(',', $entityIds);
			$potentialIds = $focus->getRelatedPotentialIds($recordId);
			$potentialIds = implode(',', $potentialIds);

			$userNameSql = getSqlForNameInDisplayFormat(array('last_name' => 'vtiger_users.last_name', 'first_name' => 'vtiger_users.first_name', ), 'Users');

			$query = "SELECT CASE WHEN (vtiger_users.user_name not like '') THEN $userNameSql ELSE vtiger_groups.groupname END AS user_name,
						vtiger_crmentity.*, vtiger_activity.activitytype, vtiger_activity.subject, vtiger_activity.date_start, vtiger_activity.time_start,
						vtiger_activity.recurringtype, vtiger_activity.due_date, vtiger_activity.time_end, vtiger_activity.visibility, vtiger_seactivityrel.crmid AS parent_id,
						CASE WHEN (vtiger_activity.activitytype = 'Task') THEN (vtiger_activity.status) ELSE (vtiger_activity.eventstatus) END AS status
						FROM vtiger_activity
						INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_activity.activityid
						LEFT JOIN vtiger_seactivityrel ON vtiger_seactivityrel.activityid = vtiger_activity.activityid
						LEFT JOIN vtiger_cntactivityrel ON vtiger_cntactivityrel.activityid = vtiger_activity.activityid
						LEFT JOIN vtiger_users ON vtiger_users.id = vtiger_crmentity.smownerid
						LEFT JOIN vtiger_groups ON vtiger_groups.groupid = vtiger_crmentity.smownerid
							WHERE vtiger_crmentity.deleted = 0 AND vtiger_activity.activitytype <> 'Emails'
								AND (vtiger_seactivityrel.crmid = ".$recordId;
			if($entityIds) {
				$query .= " OR vtiger_cntactivityrel.contactid IN (".$entityIds.")";
			}
			if($potentialIds) {
				$query .= " OR vtiger_seactivityrel.crmid IN (".$potentialIds.")";
			}
			$query .= ")";

			$relatedModuleName = $relatedModule->getName();
			$query .= $this->getSpecificRelationQuery($relatedModuleName);
			$nonAdminQuery = $this->getNonAdminAccessControlQueryForRelation($relatedModuleName);
			if ($nonAdminQuery) {
				$query = appendFromClauseToQuery($query, $nonAdminQuery);

				if(trim($nonAdminQuery)) {
					$relModuleFocus = CRMEntity::getInstance($relatedModuleName);
					$condition = $relModuleFocus->buildWhereClauseConditionForCalendar();
					if($condition) {
						$query .= ' AND '.$condition;
					}
				}
			}

			// There could be more than one contact for an activity.
			$query .= ' GROUP BY vtiger_activity.activityid';
		} else {
			$query = parent::getRelationQuery($recordId, $functionName, $relatedModule, $relationId);
		}

		return $query;
	}

	/**
	 * Function returns the Calendar Events for the module
	 * @param <String> $mode - upcoming/overdue mode
	 * @param <Vtiger_Paging_Model> $pagingModel - $pagingModel
	 * @param <String> $user - all/userid
	 * @param <String> $recordId - record id
	 * @return <Array>
	 */
	function getCalendarActivities($mode, $pagingModel, $user, $recordId = false) {
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$db = PearDatabase::getInstance();

		if (!$user) {
			$user = $currentUser->getId();
		}

		$nowInUserFormat = Vtiger_Datetime_UIType::getDisplayDateTimeValue(date('Y-m-d H:i:s'));
		$nowInDBFormat = Vtiger_Datetime_UIType::getDBDateTimeValue($nowInUserFormat);
		list($currentDate, $currentTime) = explode(' ', $nowInDBFormat);

		$query = "SELECT distinct * FROM
		( ";

		$query .= 
			"SELECT distinct
				vtiger_crmentity.crmid
				, crmentity2.crmid AS parent_id
				, vtiger_crmentity.smownerid
				, vtiger_crmentity.setype
				, vtiger_crmentity.description
				, vtiger_activity.* 
			FROM
				vtiger_activity 
				INNER JOIN vtiger_crmentity 
					ON vtiger_crmentity.crmid = vtiger_activity.activityid 
				LEFT JOIN vtiger_seactivityrel 
					ON vtiger_seactivityrel.activityid = vtiger_activity.activityid 
				LEFT JOIN vtiger_crmentity AS crmentity2 
					ON vtiger_seactivityrel.crmid = crmentity2.crmid 
					AND crmentity2.deleted = 0 
					AND crmentity2.setype = ? 
				LEFT JOIN ( 
					SELECT distinct
						crmid
						, module
						, relcrmid
						, relmodule 
					FROM
						( 
						SELECT
							cer1.crmid
							, cer1.module
							, cer1.relcrmid
							, cer1.relmodule 
						FROM
							vtiger_crmentityrel as cer1 
						WHERE
							cer1.module = 'Calendar' 
							and cer1.relcrmid = ? 
						UNION ALL 
						SELECT
							cer2.relcrmid as crmid
							, cer2.relmodule as module
							, cer2.crmid as relcrmid
							, cer2.module as relmodule 
						FROM
							vtiger_crmentityrel as cer2 
						WHERE
							cer2.relmodule = 'Calendar' 
							and cer2.crmid = ?
						) tmp
					) AS vtiger_crmentityrel 
						ON vtiger_crmentityrel.crmid = vtiger_activity.activityid 
				LEFT JOIN vtiger_groups 
					ON vtiger_groups.groupid = vtiger_crmentity.smownerid";
		$query .= " WHERE vtiger_crmentity.deleted=0";
		$params = array($this->getName());
		array_push($params, $recordId);
		array_push($params, $recordId);

		if($recordId) {
			$query .=" AND vtiger_seactivityrel.crmid = ? ";
			array_push($params, $recordId);
		}

		$query .= "UNION ALL ";

		$query .= 
			"SELECT distinct
				vtiger_crmentity.crmid
				, crmentity2.crmid AS parent_id
				, vtiger_crmentity.smownerid
				, vtiger_crmentity.setype
				, vtiger_crmentity.description
				, vtiger_activity.* 
			FROM
				vtiger_activity 
				INNER JOIN vtiger_crmentity 
					ON vtiger_crmentity.crmid = vtiger_activity.activityid 
				LEFT JOIN vtiger_seactivityrel 
					ON vtiger_seactivityrel.activityid = vtiger_activity.activityid 
				LEFT JOIN vtiger_crmentity AS crmentity2 
					ON vtiger_seactivityrel.crmid = crmentity2.crmid 
					AND crmentity2.deleted = 0 
					AND crmentity2.setype = ? 
				LEFT JOIN ( 
					SELECT distinct
						crmid
						, module
						, relcrmid
						, relmodule 
					FROM
						( 
						SELECT
							cer1.crmid
							, cer1.module
							, cer1.relcrmid
							, cer1.relmodule 
						FROM
							vtiger_crmentityrel as cer1 
						WHERE
							cer1.module = 'Calendar' 
							and cer1.relcrmid = ? 
						UNION ALL 
						SELECT
							cer2.relcrmid as crmid
							, cer2.relmodule as module
							, cer2.crmid as relcrmid
							, cer2.module as relmodule 
						FROM
							vtiger_crmentityrel as cer2 
						WHERE
							cer2.relmodule = 'Calendar' 
							and cer2.crmid = ?
						) tmp
					) AS vtiger_crmentityrel 
						ON vtiger_crmentityrel.crmid = vtiger_activity.activityid 
				LEFT JOIN vtiger_groups 
					ON vtiger_groups.groupid = vtiger_crmentity.smownerid";
		$query .= " WHERE vtiger_crmentity.deleted=0";
		array_push($params, $this->getName());
		array_push($params, $recordId);
		array_push($params, $recordId);

		if($recordId) {
			$query .=" AND vtiger_seactivityrel.crmid = null ";
//			array_push($params, $recordId);
		}

		if ($recordId) {
			$focus = CRMEntity::getInstance($this->getName());
			$focus->id = $recordId;
			$entityIds = $focus->getRelatedContactsIds();
			$entityIds = implode(',', $entityIds);
			if(!$entityIds) {
				$entityIds = "0";
			}
			$query .= "UNION ALL ";
			$query .=
				"SELECT distinct
					vtiger_crmentity.crmid
					, null AS parent_id
					, vtiger_crmentity.smownerid
					, vtiger_crmentity.setype
					, vtiger_crmentity.description
					, vtiger_activity.* 
				FROM
					vtiger_activity 
				INNER JOIN vtiger_crmentity 
					ON vtiger_crmentity.crmid = vtiger_activity.activityid 
				LEFT JOIN vtiger_cntactivityrel 
					ON vtiger_cntactivityrel.activityid = vtiger_activity.activityid 
				LEFT JOIN vtiger_seactivityrel 
					ON vtiger_seactivityrel.activityid = vtiger_activity.activityid 
				LEFT JOIN vtiger_groups 
					ON vtiger_groups.groupid = vtiger_crmentity.smownerid
				LEFT JOIN vtiger_potential 
					ON vtiger_seactivityrel.crmid = vtiger_potential.potentialid";
			$query .= " WHERE vtiger_crmentity.deleted=0";
			$query .= " AND ( ( vtiger_cntactivityrel.contactid IN (".$entityIds.",null) )";
			$query .= " OR ( vtiger_potential.related_to = ".$recordId. ") ) ";
		}

		$query .= ") tmp";

		$query .= Users_Privileges_Model::getNonAdminAccessControlQuery('Calendar');

		if (empty($recordId) && $mode === 'upcoming') {
			$query .= " AND due_date >= '$currentDate'";
		} elseif (empty($recordId) && $mode === 'overdue') {
			$query .= " AND due_date < '$currentDate'";
		}

		if($user != 'all' && $user != '') {
			if($user === $currentUser->id) {
				$query .= " AND vtiger_crmentity.smownerid = ?";
				array_push($params, $user);
			}
		}

		$query .= " ORDER BY date_start DESC, time_start DESC LIMIT ". $pagingModel->getStartIndex() .", ". ($pagingModel->getPageLimit()+1);

		$result = $db->pquery($query, $params);
		$numOfRows = $db->num_rows($result);

		$groupsIds = Vtiger_Util_Helper::getGroupsIdsForUsers($currentUser->getId());
		$activities = array();
		for($i=0; $i<$numOfRows; $i++) {
			$newRow = $db->query_result_rowdata($result, $i);
			$model = Vtiger_Record_Model::getCleanInstance('Calendar');
			$ownerId = $newRow['smownerid'];
			$currentUser = Users_Record_Model::getCurrentUserModel();
			$visibleFields = array('activitytype','date_start','time_start','due_date','time_end','assigned_user_id','visibility','smownerid','crmid', 'description');
			$visibility = true;
			if(in_array($ownerId, $groupsIds)) {
				$visibility = false;
			} else if($ownerId == $currentUser->getId()){
				$visibility = false;
			}
			if($newRow['activitytype'] != 'Task' && $newRow['visibility'] == 'Private' && $ownerId && $visibility) {
				foreach($newRow as $data => $value) {
					if(in_array($data, $visibleFields) != -1) {
						unset($newRow[$data]);
					}
				}
				$newRow['subject'] = vtranslate('Busy','Events').'*';
			}
			if($newRow['activitytype'] == 'Task') {
				unset($newRow['visibility']);
			}

			$model->setData($newRow);
			$model->setId($newRow['crmid']);
			$activities[] = $model;
		}

		$pagingModel->calculatePageRange($activities);
		if($numOfRows > $pagingModel->getPageLimit()){
			array_pop($activities);
			$pagingModel->set('nextPageExists', true);
		} else {
			$pagingModel->set('nextPageExists', false);
		}

		return $activities;
	}}
