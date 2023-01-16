<?php
/*+*******************************************************************************
 *  The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *
 *********************************************************************************/

require_once 'data/CRMEntity.php';
require_once 'modules/CustomView/CustomView.php';
require_once 'include/Webservices/Utils.php';
require_once 'include/Webservices/RelatedModuleMeta.php';

/**
 * Description of QueryGenerator
 *
 * @author MAK
 */
class QueryGenerator {
	protected $module;
	protected $customViewColumnList;
	protected $stdFilterList;
	protected $conditionals;
	protected $manyToManyRelatedModuleConditions;
	protected $groupType;
	protected $whereFields;
	/**
	 * @var VtigerCRMObjectMeta
	 */
	protected $meta;
	/**
	 * @var Users
	 */
	protected $user;
	protected $advFilterList;
	protected $fields;
	protected $referenceModuleMetaInfo;
	protected $moduleNameFields;
	protected $referenceFieldInfoList;
	protected $referenceFieldList;
	protected $ownerFields;
	protected $columns;
	protected $fromClause;
	protected $whereClause;
	protected $query;
	protected $groupInfo;
	public $conditionInstanceCount;
	protected $conditionalWhere;
	public static $AND = 'AND';
	public static $OR = 'OR';
	protected $customViewFields;
	/**
	 * Import Feature
	 */
	protected $ignoreComma;
	public function __construct($module, $user) {
		$db = PearDatabase::getInstance();
		$this->module = $module;
		$this->customViewColumnList = null;
		$this->stdFilterList = null;
		$this->conditionals = array();
		$this->user = $user;
		$this->advFilterList = null;
		$this->fields = array();
		$this->referenceModuleMetaInfo = array();
		$this->moduleNameFields = array();
		$this->whereFields = array();
		$this->groupType = self::$AND;
		$this->meta = $this->getMeta($module);
		$this->moduleNameFields[$module] = $this->meta->getNameFields();
		$this->referenceFieldInfoList = $this->meta->getReferenceFieldDetails();
		$this->referenceFieldList = array_keys($this->referenceFieldInfoList);
		$this->ownerFields = $this->meta->getOwnerFields();
		$this->columns = null;
		$this->fromClause = null;
		$this->whereClause = null;
		$this->query = null;
		$this->conditionalWhere = null;
		$this->groupInfo = '';
		$this->manyToManyRelatedModuleConditions = array();
		$this->conditionInstanceCount = 0;
		$this->customViewFields = array();
	}

	/**
	 *
	 * @param String:ModuleName $module
	 * @return EntityMeta
	 */
	public function getMeta($module) {
		$db = PearDatabase::getInstance();
		if (empty($this->referenceModuleMetaInfo[$module])) {
			$handler = vtws_getModuleHandlerFromName($module, $this->user);
			$meta = $handler->getMeta();
			$this->referenceModuleMetaInfo[$module] = $meta;
			$this->moduleNameFields[$module] = $meta->getNameFields();
		}
		return $this->referenceModuleMetaInfo[$module];
	}

	public function reset() {
		$this->fromClause = null;
		$this->whereClause = null;
		$this->columns = null;
		$this->query = null;
	}

	public function setFields($fields) {
		$this->fields = $fields;
	}

	public function getCustomViewFields() {
		return $this->customViewFields;
	}

	public function getFields() {
		return $this->fields;
	}

	public function getWhereFields() {
		return $this->whereFields;
	}

	public function addWhereField($fieldName) {
		$this->whereFields[] = $fieldName;
	}

	public function getOwnerFieldList() {
		return $this->ownerFields;
	}

	public function getModuleNameFields($module) {
		return $this->moduleNameFields[$module];
	}

	public function getReferenceFieldList() {
		return $this->referenceFieldList;
	}

	public function getReferenceFieldInfoList() {
		return $this->referenceFieldInfoList;
	}

	public function getModule () {
		return $this->module;
	}

	public function getModuleFields() {
		$moduleFields = $this->meta->getModuleFields();

		$module = $this->getModule();
		if($module == 'Calendar') {
			$eventmoduleMeta = $this->getMeta('Events');
			$eventModuleFieldList = $eventmoduleMeta->getModuleFields();
			$moduleFields = array_merge($moduleFields, $eventModuleFieldList);
		}
		return $moduleFields;
	}

	public function getConditionalWhere() {
		return $this->conditionalWhere;
	}

	public function getDefaultCustomViewQuery() {
		$customView = new CustomView($this->module);
		$viewId = $customView->getViewId($this->module);
		return $this->getCustomViewQueryById($viewId);
	}

	public function initForDefaultCustomView() {
		$customView = new CustomView($this->module);
		$viewId = $customView->getViewId($this->module);
		$this->initForCustomViewById($viewId);
	}

	public function initForCustomViewById($viewId) {
		$customView = new CustomView($this->module);
		$this->customViewColumnList = $customView->getColumnsListByCvid($viewId);
		if ($this->customViewColumnList && is_array($this->customViewColumnList)) {
			foreach ($this->customViewColumnList as $customViewColumnInfo) {
				$details = explode(':', $customViewColumnInfo);
			if(empty($details[2]) && $details[1] == 'crmid' && $details[0] == 'vtiger_crmentity') {
					$name = 'id';
					$this->customViewFields[] = $name;
				} else {
					$this->fields[] = $details[2];
					$this->customViewFields[] = $details[2];
				}
			}
		}

		if($this->module == 'Calendar' && !in_array('activitytype', $this->fields)) {
			$this->fields[] = 'activitytype';
		}

		if($this->module == 'Documents') {
			if(in_array('filename', $this->fields)) {
				if(!in_array('filelocationtype', $this->fields)) {
					$this->fields[] = 'filelocationtype';
				}
				if(!in_array('filestatus', $this->fields)) {
					$this->fields[] = 'filestatus';
				}
			}
		}
		$this->fields[] = 'id';

		$this->stdFilterList = $customView->getStdFilterByCvid($viewId);
		$this->advFilterList = $customView->getAdvFilterByCvid($viewId);

		if(is_array($this->stdFilterList)) {
			$value = array();
			if(!empty($this->stdFilterList['columnname'])) {
				$this->startGroup('');
				$name = explode(':',$this->stdFilterList['columnname']);
				$name = $name[2];
				$value[] = $this->fixDateTimeValue($name, $this->stdFilterList['startdate']);
				$value[] = $this->fixDateTimeValue($name, $this->stdFilterList['enddate'], false);
				$this->addCondition($name, $value, 'BETWEEN');
			}
		}
		if($this->conditionInstanceCount <= 0 && is_array($this->advFilterList) && count($this->advFilterList) > 0) {
			$this->startGroup('');
		} elseif($this->conditionInstanceCount > 0 && is_array($this->advFilterList) && count($this->advFilterList) > 0) {
			$this->addConditionGlue(self::$AND);
		}
		if(is_array($this->advFilterList) && count($this->advFilterList) > 0) {
			$this->parseAdvFilterList($this->advFilterList);
		}
		if($this->conditionInstanceCount > 0) {
			$this->endGroup();
		}
	}

	public function parseAdvFilterList($advFilterList, $glue=''){
		if(!empty($glue)) $this->addConditionGlue($glue);

		$customView = new CustomView($this->module);
		$dateSpecificConditions = $customView->getStdFilterConditions();
		foreach ($advFilterList as $groupindex=>$groupcolumns) {
			$filtercolumns = $groupcolumns['columns'];
			if(count($filtercolumns) > 0) {
				$this->startGroup('');
				foreach ($filtercolumns as $index=>$filter) {
					$nameComponents = explode(':',$filter['columnname']);
					// For Events "End Date & Time" field datatype should be DT. But, db will give D for due_date field
					if($nameComponents[2] == 'due_date' && $nameComponents[3] == 'Events_End_Date_&_Time')
						$nameComponents[4] = 'DT';
					if(empty($nameComponents[2]) && $nameComponents[1] == 'crmid' && $nameComponents[0] == 'vtiger_crmentity') {
						$name = $this->getSQLColumn('id');
					} else {
						$name = $nameComponents[2];
					}
					if(($nameComponents[4] == 'D' || $nameComponents[4] == 'DT') && in_array($filter['comparator'], $dateSpecificConditions)) {
						$filter['stdfilter'] = $filter['comparator'];
						$valueComponents = explode(',',$filter['value']);
						if($filter['comparator'] == 'custom') {
							if($nameComponents[4] == 'DT') {
								$startDateTimeComponents = explode(' ',$valueComponents[0]);
								$endDateTimeComponents = explode(' ',$valueComponents[1]);
								$filter['startdate'] = DateTimeField::convertToDBFormat($startDateTimeComponents[0]);
								$filter['enddate'] = DateTimeField::convertToDBFormat($endDateTimeComponents[0]);
							} else {
								$filter['startdate'] = DateTimeField::convertToDBFormat($valueComponents[0]);
								$filter['enddate'] = DateTimeField::convertToDBFormat($valueComponents[1]);
							}
						}
						$dateFilterResolvedList = $customView->resolveDateFilterValue($filter);
						// If datatype is DT then we should append time also
						if($nameComponents[4] == 'DT'){
							$startdate = explode(' ', $dateFilterResolvedList['startdate']);
							if($startdate[1] == '')
								$startdate[1] = '00:00:00';
							$dateFilterResolvedList['startdate'] = $startdate[0].' '.$startdate[1];

							$enddate = explode(' ',$dateFilterResolvedList['enddate']);
							if($enddate[1] == '')
								$enddate[1] = '23:59:59';
							$dateFilterResolvedList['enddate'] = $enddate[0].' '.$enddate[1];
						}
						$value = array();
						$value[] = $this->fixDateTimeValue($name, $dateFilterResolvedList['startdate']);
						$value[] = $this->fixDateTimeValue($name, $dateFilterResolvedList['enddate'], false);
						$this->addCondition($name, $value, 'BETWEEN');
					} else if($nameComponents[4] == 'DT' && ($filter['comparator'] == 'e' || $filter['comparator'] == 'n')) {
						$filter['stdfilter'] = $filter['comparator'];
						$dateTimeComponents = explode(' ',$filter['value']);
						$filter['startdate'] = DateTimeField::convertToDBFormat($dateTimeComponents[0]);
						$filter['enddate'] = DateTimeField::convertToDBFormat($dateTimeComponents[0]);

						$startDate = $this->fixDateTimeValue($name, $filter['startdate']);
						$endDate = $this->fixDateTimeValue($name, $filter['enddate'],false);

						$value = array();
						$start = explode(' ', $startDate);
						if($start[1] == "")
							$startDate = $start[0].' '.'00:00:00';

						$end = explode(' ',$endDate);
						if($end[1] == "")
							$endDate = $end[0].' '.'23:59:59';

						$value[] = $startDate;
						$value[] = $endDate;
						if($filter['comparator'] == 'n') {
							$this->addCondition($name, $value, 'NOTEQUAL');
						} else {
							$this->addCondition($name, $value, 'BETWEEN');
						}
					} else if($nameComponents[4] == 'DT' && ($filter['comparator'] == 'a' || $filter['comparator'] == 'b')) {
						$dateTime = explode(' ', $filter['value']);
						$date = DateTimeField::convertToDBFormat($dateTime[0]);
						$value = array();
						$value[] = $this->fixDateTimeValue($name, $date, false);
						// Still fixDateTimeValue returns only date value, we need to append time because it is DT type
						for($i=0;$i<count($value);$i++){
							$values = explode(' ', $value[$i]);
							if($values[1] == ''){
								$values[1] = '00:00:00';
							}
							$value[$i] = $values[0].' '.$values[1];
						}
						$this->addCondition($name, $value, $filter['comparator']);
					} else{
						$this->addCondition($name, $filter['value'], $filter['comparator']);
					}
					$columncondition = $filter['column_condition'];
					if(!empty($columncondition)) {
						$this->addConditionGlue($columncondition);
					}
				}
				$this->endGroup();
				$groupConditionGlue = $groupcolumns['condition'];
				if(!empty($groupConditionGlue))
					$this->addConditionGlue($groupConditionGlue);
			}
		}
	}

	public function getCustomViewQueryById($viewId) {
		$this->initForCustomViewById($viewId);
		return $this->getQuery();
	}

	public function getQuery() {
		if(empty($this->query)) {
			$conditionedReferenceFields = array();
			$allFields = array_merge($this->fields, (array)$this->whereFields);
			foreach ($allFields as $fieldName) {
				if(in_array($fieldName,$this->referenceFieldList)) {
					$moduleList = $this->referenceFieldInfoList[$fieldName];
					foreach ($moduleList as $module) {
						if(empty($this->moduleNameFields[$module])) {
							$meta = $this->getMeta($module);
						}
					}
				} elseif(in_array($fieldName, $this->ownerFields )) {
					$meta = $this->getMeta('Users');
					$meta = $this->getMeta('Groups');
				}
			}

			$query = "SELECT ";
			$query .= $this->getSelectClauseColumnSQL();
			$query .= $this->getFromClause();
			$query .= $this->getWhereClause();
			$this->query = $query;
			return $query;
		} else {
			return $this->query;
		}
	}

	public function getSQLColumn($name, $fieldObj=false) {
		if ($name == 'id') {
			$baseTable = $this->meta->getEntityBaseTable();
			$moduleTableIndexList = $this->meta->getEntityTableIndexList();
			$baseTableIndex = $moduleTableIndexList[$baseTable];
			return $baseTable.'.'.$baseTableIndex;
		}

		$moduleFields = $this->getModuleFields();
		$field = $moduleFields[$name];
		$sql = '';
		//TODO optimization to eliminate one more lookup of name, incase the field refers to only
		//one module or is of type owner.
		$column = $field->getColumnName();
		return $field->getTableName().'.'.$column;
	}

	public function getSelectClauseColumnSQL(){
		$columns = array();
		$moduleFields = $this->getModuleFields();
		$accessibleFieldList = array_keys($moduleFields);
		$accessibleFieldList[] = 'id';
		$this->fields = array_intersect($this->fields, $accessibleFieldList);
		foreach ($this->fields as $field) {
			$sql = $this->getSQLColumn($field);
			$columns[] = $sql;

			//To merge date and time fields
			if($this->meta->getEntityName() == 'Calendar' && ($field == 'date_start' || $field == 'due_date' || $field == 'taskstatus' || $field == 'eventstatus')) {
				if($field=='date_start') {
					$timeField = 'time_start';
					$sql = $this->getSQLColumn($timeField);
				} else if ($field == 'due_date') {
					$timeField = 'time_end';
					$sql = $this->getSQLColumn($timeField);
				} else if ($field == 'taskstatus' || $field == 'eventstatus') {
					//In calendar list view, Status value = Planned is not displaying
					$sql = "CASE WHEN (vtiger_activity.status not like '') THEN vtiger_activity.status ELSE vtiger_activity.eventstatus END AS ";
					if ( $field == 'taskstatus') {
						$sql .= "status";
					} else {
						$sql .= $field;
					}
				}
				$columns[] = $sql;
			}
		}
		$this->columns = implode(', ',$columns);
		return $this->columns;
	}

	public function getFromClause() {
		global $current_user;
		if(!empty($this->query) || !empty($this->fromClause)) {
			return $this->fromClause;
		}
		$baseModule = $this->getModule();
		$moduleFields = $this->getModuleFields();
		$tableList = array();
		$tableJoinMapping = array();
		$tableJoinCondition = array();
		$i =1;

		$moduleTableIndexList = $this->meta->getEntityTableIndexList();
		foreach ($this->fields as $fieldName) {
			if ($fieldName == 'id') {
				continue;
			}

			$field = $moduleFields[$fieldName];
			if(!$field) continue;
			$baseTable = $field->getTableName();
			$tableIndexList = $this->meta->getEntityTableIndexList();
			$baseTableIndex = $tableIndexList[$baseTable];

			$tableList[$field->getTableName()] = $field->getTableName();
			$tableJoinMapping[$field->getTableName()] = $this->meta->getJoinClause($field->getTableName());

			if($field->getFieldDataType() == 'reference') {
				$moduleList = $this->referenceFieldInfoList[$fieldName];
				foreach($moduleList as $module) {
					if($module == 'Users' && $baseModule != 'Users') {
						if($fieldName == 'created_user_id' || $fieldName == 'modifiedby') {
							$tableJoinCondition[$fieldName]['vtiger_users'.$fieldName] = $field->getTableName().
								".".$field->getColumnName()." = vtiger_users".$fieldName.".id";
							$tableJoinMapping['vtiger_users'.$fieldName] = 'LEFT JOIN vtiger_users AS';
							$i++;
						} else {
							$tableJoinCondition[$fieldName]['vtiger_users'.$fieldName] = $field->getTableName().
									".".$field->getColumnName()." = vtiger_users".$fieldName.".id";
							$tableJoinCondition[$fieldName]['vtiger_groups'.$fieldName] = $field->getTableName().
									".".$field->getColumnName()." = vtiger_groups".$fieldName.".groupid";
							$tableJoinMapping['vtiger_users'.$fieldName] = 'LEFT JOIN vtiger_users AS';
							$tableJoinMapping['vtiger_groups'.$fieldName] = 'LEFT JOIN vtiger_groups AS';
							$i++;
						}
					}
				}

				if($fieldName == 'roleid' && $baseModule == 'Users') {
					$tableJoinMapping['vtiger_role'] = 'INNER JOIN';
					$tableList['vtiger_role'] = 'vtiger_role';
				}
			} elseif($field->getFieldDataType() == 'owner') {
				$tableList['vtiger_users'] = 'vtiger_users';
				$tableList['vtiger_groups'] = 'vtiger_groups';
				$tableJoinMapping['vtiger_users'] = 'LEFT JOIN';
				$tableJoinMapping['vtiger_groups'] = 'LEFT JOIN';
				if($fieldName == "created_user_id"){
					$tableJoinCondition[$fieldName]['vtiger_users'.$fieldName] = $field->getTableName().
							".".$field->getColumnName()." = vtiger_users".$fieldName.".id";
					$tableJoinMapping['vtiger_users'.$fieldName] = 'LEFT JOIN vtiger_users AS';
				}
			}
		}
		$baseTable = $this->meta->getEntityBaseTable();
		$baseTableIndex = $moduleTableIndexList[$baseTable];
		foreach ($this->whereFields as $fieldName) {
			if(empty($fieldName)) {
				continue;
			}
			$field = $moduleFields[$fieldName];
			if(empty($field)) {
				// not accessible field.
				continue;
			}
			$baseTable = $field->getTableName();
			// When a field is included in Where Clause, but not is Select Clause, and the field table is not base table,
			// The table will not be present in tablesList and hence needs to be added to the list.
			if(empty($tableList[$baseTable])) {
				if($fieldName == 'tags') {
						$tableList['vtiger_freetagged_objects'] = 'vtiger_freetagged_objects';
						$tableJoinMapping['vtiger_freetagged_objects'] = 'INNER JOIN';
				}else{
					$tableList[$baseTable] = $field->getTableName();
					$tableJoinMapping[$baseTable] = $this->meta->getJoinClause($field->getTableName());
				}
			}
			if($field->getFieldDataType() == 'reference') {
				$moduleList = $this->referenceFieldInfoList[$fieldName];
				// This is special condition as the data is not stored in the base table,
				// If empty search is performed on this field then it fails to retrieve any information.
				if($fieldName == 'parent_id' && $field->getTableName() == 'vtiger_seactivityrel') {
					$tableJoinMapping[$field->getTableName()] = 'LEFT JOIN';
				} else if($fieldName == 'contact_id' && $field->getTableName() == 'vtiger_cntactivityrel') {
					$tableJoinMapping[$field->getTableName()] = "LEFT JOIN";
				} else {
					$tableJoinMapping[$field->getTableName()] = 'INNER JOIN';
				}
				foreach($moduleList as $module) {
					$meta = $this->getMeta($module);
					$nameFields = $this->moduleNameFields[$module];
					$nameFieldList = explode(',',$nameFields);
					foreach ($nameFieldList as $index=>$column) {
						$referenceField = $meta->getFieldByColumnName($column);
						$referenceTable = $referenceField->getTableName();
						$tableIndexList = $meta->getEntityTableIndexList();
						$referenceTableIndex = $tableIndexList[$referenceTable];

						$referenceTableName = "$referenceTable $referenceTable$fieldName";
						$referenceTable = "$referenceTable$fieldName";
						//should always be left join for cases where we are checking for null
						//reference field values.
						if(!array_key_exists($referenceTable, $tableJoinMapping)) {		// table already added in from clause
							$tableJoinMapping[$referenceTableName] = 'LEFT JOIN';
							$tableJoinCondition[$fieldName][$referenceTableName] = $baseTable.'.'.
								$field->getColumnName().' = '.$referenceTable.'.'.$referenceTableIndex;
						}
					}
				}

				if($fieldName == 'roleid' && $baseModule == 'Users') {
					$tableJoinMapping['vtiger_role'] = 'INNER JOIN';
					$tableList['vtiger_role'] = 'vtiger_role';
				}
			} elseif($field->getFieldDataType() == 'owner') {
				$tableList['vtiger_users'] = 'vtiger_users';
				$tableList['vtiger_groups'] = 'vtiger_groups';
				$tableJoinMapping['vtiger_users'] = 'LEFT JOIN';
				$tableJoinMapping['vtiger_groups'] = 'LEFT JOIN';
			} else {
				$tableList[$field->getTableName()] = $field->getTableName();
				$tableJoinMapping[$field->getTableName()] =
						$this->meta->getJoinClause($field->getTableName());
			}
		}

		$defaultTableList = $this->meta->getEntityDefaultTableList();
		foreach ($defaultTableList as $table) {
			if(!in_array($table, $tableList)) {
				$tableList[$table] = $table;
				$tableJoinMapping[$table] = 'INNER JOIN';
			}
		}
		$ownerFields = $this->meta->getOwnerFields();
		if (count($ownerFields) > 0) {
			$ownerField = $ownerFields[0];
		}
		$baseTable = $this->meta->getEntityBaseTable();
		$sql = " FROM $baseTable ";
		unset($tableList[$baseTable]);
		foreach ($defaultTableList as $tableName) {
			$sql .= " $tableJoinMapping[$tableName] $tableName ON $baseTable.".
					"$baseTableIndex = $tableName.$moduleTableIndexList[$tableName]";
			unset($tableList[$tableName]);
		}
		foreach ($tableList as $tableName) {
			if($tableName == 'vtiger_users') {
				$field = $moduleFields[$ownerField];
				$sql .= " $tableJoinMapping[$tableName] $tableName ON ".$field->getTableName().".".
					$field->getColumnName()." = $tableName.id";
			} elseif($tableName == 'vtiger_groups') {
				$field = $moduleFields[$ownerField];
				$sql .= " $tableJoinMapping[$tableName] $tableName ON ".$field->getTableName().".".
					$field->getColumnName()." = $tableName.groupid";
			} elseif($tableName == 'vtiger_freetagged_objects') {
				$sql .= " $tableJoinMapping[$tableName] $tableName ON $baseTable.$baseTableIndex = $tableName.object_id ".
						"INNER JOIN vtiger_freetags ON $tableName.tag_id = vtiger_freetags.id ";
			} elseif($tableName == 'vtiger_role') {
				$sql .= " $tableJoinMapping[$tableName] $tableName ON vtiger_role.roleid = vtiger_user2role.roleid";
			} else {
				$tableCondition = $tableName.'.'.$moduleTableIndexList[$tableName];
				if(Vtiger_Functions::isUserSpecificFieldTable($tableName, $this->getModule())) {
					$tableCondition.=  ' AND '.$tableName.'.userid='.$this->user->id;
				}
				$sql .= " $tableJoinMapping[$tableName] $tableName ON $baseTable.".
					"$baseTableIndex = $tableCondition";
			}
		}

		if( $this->meta->getTabName() == 'Documents') {
			$tableJoinCondition['folderid'] = array(
				'vtiger_attachmentsfolderfolderid'=>"$baseTable.folderid = vtiger_attachmentsfolderfolderid.folderid"
			);
			$tableJoinMapping['vtiger_attachmentsfolderfolderid'] = 'INNER JOIN vtiger_attachmentsfolder';
		}

		foreach ($tableJoinCondition as $fieldName=>$conditionInfo) {
			foreach ($conditionInfo as $tableName=>$condition) {
				if(!empty($tableList[$tableName])) {
					$tableNameAlias = $tableName.'2';
					$condition = str_replace($tableName, $tableNameAlias, $condition);
				} else {
					$tableNameAlias = '';
				}
				$sql .= " $tableJoinMapping[$tableName] $tableName $tableNameAlias ON $condition";
			}
		}

		foreach ($this->manyToManyRelatedModuleConditions as $conditionInfo) {
			$relatedModuleMeta = RelatedModuleMeta::getInstance($this->meta->getTabName(),
					$conditionInfo['relatedModule']);
			$relationInfo = $relatedModuleMeta->getRelationMeta();
			$relatedModule = $this->meta->getTabName();
			$sql .= ' INNER JOIN '.$relationInfo['relationTable']." ON ".
			$relationInfo['relationTable'].".$relationInfo[$relatedModule]=".
				"$baseTable.$baseTableIndex";
		}

		// Adding support for conditions on reference module fields
		if($this->referenceModuleField) {
			$referenceFieldTableList = array();
			foreach ($this->referenceModuleField as $index=>$conditionInfo) {

				$handler = vtws_getModuleHandlerFromName($conditionInfo['relatedModule'], $current_user);
				$meta = $handler->getMeta();
				$tableList = $meta->getEntityTableIndexList();
				$fieldName = $conditionInfo['fieldName'];
				$referenceFieldObject = $moduleFields[$conditionInfo['referenceField']];
				$fields = $meta->getModuleFields();
				$fieldObject = $fields[$fieldName];

				if(empty($fieldObject)) continue;

				$tableName = $fieldObject->getTableName();
				if(!in_array($tableName, $referenceFieldTableList)) {
					if($referenceFieldObject->getFieldName() == 'parent_id' && ($this->getModule() == 'Calendar' || $this->getModule() == 'Events')) {
						$sql .= ' LEFT JOIN vtiger_seactivityrel ON vtiger_seactivityrel.activityid = vtiger_activity.activityid ';
					}
					//TODO : this will create duplicates, need to find a better way
					if($referenceFieldObject->getFieldName() == 'contact_id' && ($this->getModule() == 'Calendar' || $this->getModule() == 'Events')) {
						$sql .= ' LEFT JOIN vtiger_cntactivityrel ON vtiger_cntactivityrel.activityid = vtiger_activity.activityid ';
					}
					$sql .= " LEFT JOIN ".$tableName.' AS '.$tableName.$conditionInfo['referenceField'].' ON
							'.$tableName.$conditionInfo['referenceField'].'.'.$tableList[$tableName].'='.
						$referenceFieldObject->getTableName().'.'.$referenceFieldObject->getColumnName();
					$referenceFieldTableList[] = $tableName;
				}
			}
		}

		$sql .= $this->meta->getEntityAccessControlQuery();
		$this->fromClause = $sql;
		return $sql;
	}

	public function getWhereClause() {
		global $current_user;
		if(!empty($this->query) || !empty($this->whereClause)) {
			return $this->whereClause;
		}
		$deletedQuery = $this->meta->getEntityDeletedQuery();
		$sql = '';
		if(!empty($deletedQuery)) {
			$sql .= " WHERE $deletedQuery";
		}
		if($this->conditionInstanceCount > 0) {
			$sql .= ' AND ';
		} elseif(empty($deletedQuery)) {
			$sql .= ' WHERE ';
		}
		$baseModule = $this->getModule();
		$moduleFieldList = $this->getModuleFields();
		$baseTable = $this->meta->getEntityBaseTable();
		$moduleTableIndexList = $this->meta->getEntityTableIndexList();
		$baseTableIndex = $moduleTableIndexList[$baseTable];
		$groupSql = $this->groupInfo;
		$fieldSqlList = array();
		foreach ($this->conditionals as $index=>$conditionInfo) {
			$fieldName = $conditionInfo['name'];
			$field = $moduleFieldList[$fieldName];
			if(empty($field) || $conditionInfo['operator'] == 'None') {
				continue;
			}
			$fieldSql = '(';
			$fieldGlue = '';
			$valueSqlList = $this->getConditionValue($conditionInfo['value'],
				$conditionInfo['operator'], $field);
			$operator = strtolower($conditionInfo['operator']);
			if($operator == 'between' && $this->isDateType($field->getFieldDataType())){
				$start = explode(' ', $conditionInfo['value'][0]);
				if(count($start) == 2)
					$conditionInfo['value'][0] = getValidDBInsertDateTimeValue($start[0].' '.$start[1]);

				$end = explode(' ', $conditionInfo['values'][1]);
				// Dates will be equal for Today, Tomorrow, Yesterday.
				if(count($end) == 2){
					if($start[0] == $end[0]){
						$dateTime = new DateTime($conditionInfo['value'][0]);
						$nextDay = $dateTime->modify('+1 days');
						$nextDay = $nextDay->format('Y-m-d H:i:s');
						$values = explode(' ', $nextDay);
						$conditionInfo['value'][1] = getValidDBInsertDateTimeValue($values[0]).' '.$values[1];
					}else{
						$end = $conditionInfo['value'][1];
						$dateObject = new DateTimeField($end);
						$conditionInfo['value'][1] = $dateObject->getDBInsertDateTimeValue();
					}
				}

			}
			if(!is_array($valueSqlList)) {
				$valueSqlList = array($valueSqlList);
			}
			foreach ($valueSqlList as $valueSql) {
				if (in_array($fieldName, $this->referenceFieldList)) {
					if($conditionInfo['operator'] == 'y'){
						$columnName = $field->getColumnName();
						$tableName = $field->getTableName();
						// We are checking for zero since many reference fields will be set to 0 if it doest not have any value
						$fieldSql .= "$fieldGlue $tableName.$columnName $valueSql OR $tableName.$columnName = '0'";
						$fieldGlue = ' OR';
					}else{
						$moduleList = $this->referenceFieldInfoList[$fieldName];
						foreach($moduleList as $module) {
							$meta = $this->getMeta($module);
							$nameFields = $this->moduleNameFields[$module];
							$nameFieldList = explode(',',$nameFields);
							$columnList = array();
							foreach ($nameFieldList as $column) {
								if($module == 'Users') {
									$instance = CRMEntity::getInstance($module);
									$referenceTable = $instance->table_name;
									// PriceBook don't have any owner fields
									if(count($this->ownerFields) > 0 ||
											$this->getModule() == 'Quotes' || $this->getModule() == 'PriceBooks') {
										$referenceTable .= $fieldName;
									}
								} else {
									$referenceField = $meta->getFieldByColumnName($column);
									$referenceTable = $referenceField->getTableName().$fieldName;
								}
								if(isset($moduleTableIndexList[$referenceTable])) {
									$referenceTable = "$referenceTable$fieldName";
								}
								$columnList[] = "$referenceTable.$column";
							}
							if(count($columnList) > 1) {
								$columnSql = getSqlForNameInDisplayFormat(array('last_name'=>$columnList[1],'first_name'=>$columnList[0],),'Users');
							} else {
								$columnSql = implode('', $columnList);
							}

							$fieldSql .= "$fieldGlue trim($columnSql) $valueSql";
							$fieldGlue = ' OR';
						}
						if ($fieldName == 'roleid'){
							$columnSql = 'vtiger_role.rolename';
							$fieldSql .= "$fieldGlue trim($columnSql) $valueSql";
							$fieldGlue = ' OR';
						}
					}
				} elseif (in_array($fieldName, $this->ownerFields)) {
					if($fieldName == 'created_user_id'){
						$concatSql = getSqlForNameInDisplayFormat(array('last_name'=>"vtiger_users$fieldName.last_name",'first_name'=>"vtiger_users$fieldName.first_name",), 'Users');
						$fieldSql .= "$fieldGlue (trim($concatSql) $valueSql)";
					}else{
						$concatSql = getSqlForNameInDisplayFormat(array('last_name'=>"vtiger_users.last_name",'first_name'=>"vtiger_users.first_name",), 'Users');
						$fieldSql .= "$fieldGlue (trim($concatSql) $valueSql or "."vtiger_groups.groupname $valueSql)";
					}
				} elseif($field->getFieldDataType() == 'date' && ($baseModule == 'Events' || $baseModule == 'Calendar') && ($fieldName == 'date_start' || $fieldName == 'due_date')) {
					$value = $conditionInfo['value'];
					$operator = $conditionInfo['operator'];
					if($fieldName == 'date_start') {
						$dateFieldColumnName = 'vtiger_activity.date_start';
						$timeFieldColumnName = 'vtiger_activity.time_start';
					} else {
						$dateFieldColumnName = 'vtiger_activity.due_date';
						$timeFieldColumnName = 'vtiger_activity.time_end';
					}
					if($operator == 'bw') {
						$values = explode(',', $value);
						$startDateValue = explode(' ', $values[0]);
						$endDateValue = explode(' ', $values[1]);
						if(count($startDateValue) == 2 && count($endDateValue) == 2) {
							$fieldSql .= " CAST(CONCAT($dateFieldColumnName,' ',$timeFieldColumnName) AS DATETIME) $valueSql";
						} else {
							$fieldSql .= "$dateFieldColumnName $valueSql";
						}
					} else {
						if(is_array($value)){
							$value = $value[0];
						}
						$values = explode(' ', $value);
						if(count($values) == 2) {
								$fieldSql .= "$fieldGlue CAST(CONCAT($dateFieldColumnName,' ',$timeFieldColumnName) AS DATETIME) $valueSql ";
						} else {
								$fieldSql .= "$fieldGlue $dateFieldColumnName $valueSql";
						}
					}
				} elseif($field->getFieldDataType() == 'datetime') {
					$value = $conditionInfo['value'];
					$operator = strtolower($conditionInfo['operator']);
					if($operator == 'bw') {
						$values = explode(',', $value);
						$startDateValue = explode(' ', $values[0]);
						$endDateValue = explode(' ', $values[1]);
						if($startDateValue[1] == '00:00:00' && ($endDateValue[1] == '00:00:00' || $endDateValue[1] == '23:59:59')) {
							$fieldSql .= "$fieldGlue CAST(".$field->getTableName().'.'.$field->getColumnName()." AS DATE) $valueSql";
						} else {
							$fieldSql .= "$fieldGlue ".$field->getTableName().'.'.$field->getColumnName().' '.$valueSql;
						}
					} elseif($operator == 'between' || $operator == 'notequal' || $operator == 'a' || $operator == 'b') {
						$fieldSql .= "$fieldGlue ".$field->getTableName().'.'.$field->getColumnName().' '.$valueSql;
					} else {
						$values = explode(' ', $value);
						if($values[1] == '00:00:00') {
							$fieldSql .= "$fieldGlue CAST(".$field->getTableName().'.'.$field->getColumnName()." AS DATE) $valueSql";
						} else {
							$fieldSql .= "$fieldGlue ".$field->getTableName().'.'.$field->getColumnName().' '.$valueSql;
						}
					}
				} else if (($baseModule == 'Events' || $baseModule == 'Calendar')
						&& ($field->getColumnName() == 'status' || $field->getColumnName() == 'eventstatus')) {
					$otherFieldName = 'eventstatus';
					if($field->getColumnName() == 'eventstatus'){
						$otherFieldName = 'taskstatus';
					}
					$otherField = $moduleFieldList[$otherFieldName];

					$specialCondition = '';
					$specialConditionForOtherField='';
					$conditionGlue = ' OR ';
					if($conditionInfo['operator'] == 'n' || $conditionInfo['operator'] == 'k' || $conditionInfo['operator'] == 'y') {
					   $conditionGlue = ' AND ';
					   if($conditionInfo['operator'] == 'n') {
						   $specialCondition = ' OR '.$field->getTableName().'.'.$field->getColumnName().' IS NULL ';
						   if(!empty($otherField))
						   $specialConditionForOtherField = ' OR '.$otherField->getTableName().'.'.$otherField->getColumnName().' IS NULL ';
						}
					}

					$otherFieldValueSql = $valueSql;
					if($conditionInfo['operator'] == 'ny' && !empty($otherField)){
						$otherFieldValueSql = "IS NOT NULL AND ".$otherField->getTableName().'.'.$otherField->getColumnName()." != ''";
					}

					$fieldSql .= "$fieldGlue ((". $field->getTableName().'.'.$field->getColumnName().' '.$valueSql." $specialCondition) ";
					if(!empty($otherField))
						$fieldSql .= $conditionGlue .'('.$otherField->getTableName().'.'.$otherField->getColumnName() . ' '. $otherFieldValueSql .' '.$specialConditionForOtherField .'))';
					else
						$fieldSql .= ')';
				}else if (Vtiger_Functions::isUserSpecificFieldTable($field->getTableName(), getTabModuleName($field->getTabId())) && $fieldName == "starred"
						&& $conditionInfo['value'] != 1) {
						// since not for all records you will have entry in starred field table. So for disabled (value 0) we need to check both 0 and null
						$fieldSql .= "$fieldGlue (".$field->getTableName().'.'.$field->getColumnName().' '.$valueSql.' OR ';
						$fieldSql .=                $field->getTableName().'.'.$field->getColumnName().' IS NULL)';
				} else if($fieldName == "tags") {
					$fieldSql .= " $fieldGlue ( vtiger_freetags.id " . $valueSql . ' AND '.
							'( vtiger_freetagged_objects.tagger_id = ' . $this->user->id . ' OR vtiger_freetags.visibility = "public")) ';
				} else {
					if($fieldName == 'birthday' && !$this->isRelativeSearchOperators(
							$conditionInfo['operator'])) {
						$fieldSql .= "$fieldGlue DATE_FORMAT(".$field->getTableName().'.'.
						$field->getColumnName().",'%m%d') ".$valueSql;
					} else {
						$fieldSql .= "$fieldGlue ".$field->getTableName().'.'.
						$field->getColumnName().' '.$valueSql;
					}
				}
				if(($conditionInfo['operator'] == 'n' || $conditionInfo['operator'] == 'k') && ($field->getFieldDataType() == 'owner' || $field->getFieldDataType() == 'picklist') ) {
					$fieldGlue = ' AND';
				} else {
					$fieldGlue = ' OR';
				}
			}
			$fieldSql .= ')';
			$fieldSqlList[$index] = $fieldSql;
		}
		foreach ($this->manyToManyRelatedModuleConditions as $index=>$conditionInfo) {
			$relatedModuleMeta = RelatedModuleMeta::getInstance($this->meta->getTabName(), $conditionInfo['relatedModule']);
			$relationInfo = $relatedModuleMeta->getRelationMeta();
			$relatedModule = $this->meta->getTabName();
			$fieldSql = "(".$relationInfo['relationTable'].'.'.
			$relationInfo[$conditionInfo['column']].$conditionInfo['SQLOperator'].
			$conditionInfo['value'].")";
			$fieldSqlList[$index] = $fieldSql;
		}

		// This is added to support reference module fields
		if($this->referenceModuleField) {
			foreach ($this->referenceModuleField as $index=>$conditionInfo) {
				if(empty($conditionInfo['SQLOperator'])) continue;
				$handler = vtws_getModuleHandlerFromName($conditionInfo['relatedModule'], $current_user);
				$meta = $handler->getMeta();
				$fieldName = $conditionInfo['fieldName'];
				$fields = $meta->getModuleFields();
				$fieldObject = $fields[$fieldName];
				$columnName = $fieldObject->getColumnName();
				$tableName = $fieldObject->getTableName();
				$valueSQL = $this->getConditionValue($conditionInfo['value'], $conditionInfo['SQLOperator'], $fieldObject);
				$fieldSql = "(".$tableName.$conditionInfo['referenceField'].'.'.$columnName.' '.$valueSQL[0].")";
				$fieldSqlList[$index] = $fieldSql;
			}
		}

		$currentUserModel = Users_Record_Model::getCurrentUserModel();
		if(($baseModule == 'Calendar' || $baseModule == 'Events') && !$currentUserModel->isAdminUser()) {
			$moduleFocus = CRMEntity::getInstance('Calendar');
			$condition = $moduleFocus->buildWhereClauseConditionForCalendar();
			if ($condition) {
				if ($this->conditionInstanceCount > 0) {
					$sql .= $condition . ' AND ';
				} else {
					$sql .= ' AND ' . $condition;
				}
			}
		}

		// This is needed as there can be condition in different order and there is an assumption in makeGroupSqlReplacements API
		// that it expects the array in an order and then replaces the sql with its the corresponding place
		ksort($fieldSqlList);
		$groupSql = $this->makeGroupSqlReplacements($fieldSqlList, $groupSql);
		if($this->conditionInstanceCount > 0) {
			$this->conditionalWhere = $groupSql;
			$sql .= $groupSql;
		}
		$sql .= " AND $baseTable.$baseTableIndex > 0";
		$this->whereClause = $sql;
		return $sql;
	}

	/**
	 *
	 * @param mixed $value
	 * @param String $operator
	 * @param WebserviceField $field
	 */
	protected function getConditionValue($value, $operator, $field) {

		$operator = strtolower($operator);
		$db = PearDatabase::getInstance();

		if(is_string($value) && $this->ignoreComma == false) {
			$commaSeparatedFieldTypes = array('picklist', 'multipicklist', 'owner', 'date', 'datetime', 'time');
			if(in_array($operator, array('s', 'ew', 'c', 'k')) || in_array($field->getFieldDataType(), $commaSeparatedFieldTypes) || ($field->getFieldName() == 'salutationtype') || ($field->getFieldDataType()=='reference' && in_array('Users', $field->getReferenceList()))) {
			$valueArray = explode(',' , $value);
			if ($field->getFieldDataType() == 'multipicklist' && in_array($operator, array('e', 'n'))) {
				$valueArray = getCombinations($valueArray);
				foreach ($valueArray as $key => $value) {
					$valueArray[$key] = ltrim($value, ' |##| ');
				}
			}
			} else {
				$valueArray = array($value);
			}
		} elseif(is_array($value)) {
			$valueArray = $value;
		} else{
			$valueArray = array($value);
		}
		$sql = array();
		if($operator == 'between' || $operator == 'bw' || $operator == 'notequal') {
			if($field->getFieldName() == 'birthday') {
				$valueArray[0] = getValidDBInsertDateTimeValue($valueArray[0]);
				$valueArray[1] = getValidDBInsertDateTimeValue($valueArray[1]);
				$sql[] = "BETWEEN DATE_FORMAT(".$db->quote($valueArray[0]).", '%m%d') AND ".
						"DATE_FORMAT(".$db->quote($valueArray[1]).", '%m%d')";
			} else {
				if($this->isDateType($field->getFieldDataType())) {
					$start = explode(' ', $valueArray[0]);
					$end = explode(' ',$valueArray[1]);
					if(($operator == 'between' || $operator == 'bw') && count($start) == 2 && count($end) == 2){
							$valueArray[0] = getValidDBInsertDateTimeValue($start[0].' '.$start[1]);

							if($start[0] == $end[0]){
								$dateTime = new DateTime($valueArray[0]);
								$nextDay = $dateTime->modify('+1 days');
								$nextDay = strtotime($nextDay->format('Y-m-d H:i:s'))-1;
								$nextDay = date('Y-m-d H:i:s', $nextDay);
								$values = explode(' ', $nextDay);
								$valueArray[1] = getValidDBInsertDateTimeValue($values[0]).' '.$values[1];
							}else{
								$end = $valueArray[1];
								$dateObject = new DateTimeField($end);
								$valueArray[1] = $dateObject->getDBInsertDateTimeValue();
							}
					}else{
						$valueArray[0] = getValidDBInsertDateTimeValue($valueArray[0]);
						$dateTimeStart = explode(' ',$valueArray[0]);
						if($dateTimeStart[1] == '00:00:00' && $operator != 'between' && $field->getFieldDataType()=='date') {
							$valueArray[0] = $dateTimeStart[0];
						}
						$valueArray[1] = getValidDBInsertDateTimeValue($valueArray[1]);
						$dateTimeEnd = explode(' ', $valueArray[1]);
						if(($dateTimeEnd[1] == '00:00:00' || $dateTimeEnd[1] == '23:59:59') && $field->getFieldDataType()=='date' ) {
							$valueArray[1] = $dateTimeEnd[0];
						}
					}
				}

				if($operator == 'notequal') {
					$sql[] = "NOT BETWEEN ".$db->quote($valueArray[0])." AND ".
							$db->quote($valueArray[1]);
				} else {
					$sql[] = "BETWEEN ".$db->quote($valueArray[0])." AND ".
							$db->quote($valueArray[1]);
				}
			}
			return $sql;
		}
		foreach ($valueArray as $value) {
			if(!$this->isStringType($field->getFieldDataType())) {
				$value = trim($value);
			}
			if ($operator == 'empty' || $operator == 'y') {
				if($this->isDateType($field->getFieldDataType())) {
					$sql[] = sprintf("IS NULL", $this->getSQLColumn($field->getFieldName(), $field));
				} else {
					$sql[] = sprintf("IS NULL OR %s = ''", $this->getSQLColumn($field->getFieldName(), $field));
				}
				continue;
			}
			if($operator == 'ny'){
				if($this->isDateType($field->getFieldDataType())) {
					$sql[] = sprintf("IS NOT NULL", $this->getSQLColumn($field->getFieldName(), $field));
				} else {
					$sql[] = sprintf("IS NOT NULL AND %s != ''", $this->getSQLColumn($field->getFieldName(), $field));
				}
				continue;
			}
			if((strtolower(trim($value)) == 'null') ||
					(trim($value) == '' && !$this->isStringType($field->getFieldDataType())) &&
							($operator == 'e' || $operator == 'n')) {
				if($operator == 'e'){
					$sql[] = "IS NULL";
					$sql[] = "= ''";
					continue;
				} else {
					$sql[] = "IS NOT NULL";
					$sql[] = "!= ''";
					continue;
				}
			} elseif($field->getFieldDataType() == 'boolean') {
				$value = strtolower($value);
				if ($value == 'yes') {
					$value = 1;
				} elseif($value == 'no') {
					$value = 0;
				}
			} elseif($this->isDateType($field->getFieldDataType())) {
				// For "after" and "before" conditions
				$values = explode(' ',$value);
				if(($operator == 'a' || $operator == 'b') && count($values) == 2){
					if($operator == 'a'){
						// for after comparator we should check the date after the given
						$dateTime = new DateTime($value);
						$modifiedDate = $dateTime->modify('+1 days');
						$nextday = $modifiedDate->format('Y-m-d H:i:s');
						$temp = strtotime($nextday)-1;
						$date = date('Y-m-d H:i:s', $temp);
						$value = getValidDBInsertDateTimeValue($date);
					}else{
						$dateTime = new DateTime($value);
						$prevday = $dateTime->format('Y-m-d H:i:s');
						$temp = strtotime($prevday)-1;
						$date = date('Y-m-d H:i:s', $temp);
						$value = getValidDBInsertDateTimeValue($date);
					}
				}else{
					$value = getValidDBInsertDateTimeValue($value);
					$dateTime = explode(' ', $value);
					if($dateTime[1] == '00:00:00') {
						$value = $dateTime[0];
					}
				}
			} else if ($field->getFieldDataType() === 'currency') {
				$uiType = $field->getUIType();
				if ($uiType == 72) {
					$value = CurrencyField::convertToDBFormat($value, null, true);
				} elseif ($uiType == 71) {
					$value = CurrencyField::convertToDBFormat($value);
				}
			}

			if($field->getFieldName() == 'birthday' && !$this->isRelativeSearchOperators(
					$operator)) {
				$value = "DATE_FORMAT(".$db->quote($value).", '%m%d')";
			} else {
				$value = $db->sql_escape_string($value);
			}

			if(trim($value) == '' && ($operator == 's' || $operator == 'ew' || $operator == 'c')
					&& ($this->isStringType($field->getFieldDataType()) ||
					$field->getFieldDataType() == 'picklist' ||
					$field->getFieldDataType() == 'multipicklist')) {
				$sql[] = "LIKE ''";
				continue;
			}

			if(trim($value) == '' && ($operator == 'k') &&
					$this->isStringType($field->getFieldDataType())) {
				$sql[] = "NOT LIKE ''";
				continue;
			}

			switch($operator) {
				case 'e': $sqlOperator = "=";
					break;
				case 'n': $sqlOperator = "<>";
					break;
				case 's': $sqlOperator = "LIKE";
					$value = "$value%";
					break;
				case 'ew': $sqlOperator = "LIKE";
					$value = "%$value";
					break;
				case 'c': $sqlOperator = "LIKE";
					$value = "%$value%";
					break;
				case 'k': $sqlOperator = "NOT LIKE";
					$value = "%$value%";
					break;
				case 'l': $sqlOperator = "<";
					break;
				case 'g': $sqlOperator = ">";
					break;
				case 'm': $sqlOperator = "<=";
					break;
				case 'h': $sqlOperator = ">=";
					break;
				case 'a': $sqlOperator = ">";
					break;
				case 'b': $sqlOperator = "<";
					break;
			}

			// for not equal(!=) and for equal(=) with empty value we need to check for null
			if(($operator == 'n' && !isset($value) && ($field->getFieldName() != 'activitytype' && $value != 'Emails')) ||
					($operator == 'e' && !isset($value))) {
				$sql[] = "IS NULL";
			}

			if( ($field->getFieldName() != 'birthday' || ($field->getFieldName() == 'birthday'
							&& $this->isRelativeSearchOperators($operator)))){
				$value = "'$value'";
			}

			if(($this->isNumericType($field->getFieldDataType())) && empty($value)) {
				$value = '0';
			}
			
			if ($operator === "own") {
				$currentUser = Users_Record_Model::getCurrentUserModel();
				$currentUserName = decode_html($currentUser->getName());
				$sql[] = "= '$currentUserName'";
			}else{
				$sql[] = "$sqlOperator $value";
			}
		}
		return $sql;
	}

	protected function makeGroupSqlReplacements($fieldSqlList, $groupSql) {
		$pos = 0;
		$nextOffset = 0;
		foreach ($fieldSqlList as $index => $fieldSql) {
			$pos = strpos($groupSql, $index.'', $nextOffset);
			if($pos !== false) {
				$beforeStr = substr($groupSql,0,$pos);
				$afterStr = substr($groupSql, $pos + strlen($index));
				$nextOffset = strlen($beforeStr.$fieldSql);
				$groupSql = $beforeStr.$fieldSql.$afterStr;
			}
		}
		return $groupSql;
	}

	protected function isRelativeSearchOperators($operator) {
		$nonDaySearchOperators = array('l','g','m','h');
		return in_array($operator, $nonDaySearchOperators);
	}
	protected function isNumericType($type) {
		return ($type == 'integer' || $type == 'double' || $type == 'currency');
	}

	protected function isStringType($type) {
		return ($type == 'string' || $type == 'text' || $type == 'email' || $type == 'reference');
	}

	protected function isDateType($type) {
		return ($type == 'date' || $type == 'datetime');
	}

	public function fixDateTimeValue($name, $value, $first = true) {
		$moduleFields = $this->getModuleFields();
		$field = $moduleFields[$name];
		$type = $field ? $field->getFieldDataType() : false;
		if($type == 'datetime') {
			if(strrpos($value, ' ') === false) {
				if($first) {
					return $value.' 00:00:00';
				}else{
					return $value.' 23:59:59';
				}
			}
		}
		return $value;
	}

	public function clearConditionals(){
		$this->conditionals = null;
		$this->conditionInstanceCount = 0;
		$this->groupInfo = null;
		$this->whereFields = null;
	}

	public function addCondition($fieldname,$value,$operator,$glue= null,$newGroup = false,
		$newGroupType = null, $ignoreComma = false) {
		$conditionNumber = $this->conditionInstanceCount++;
		if($glue != null && $conditionNumber > 0)
			$this->addConditionGlue ($glue);

		$this->groupInfo .= "$conditionNumber ";
		$this->whereFields[] = $fieldname;
		$this->ignoreComma = $ignoreComma;
		$this->reset();
		$this->conditionals[$conditionNumber] = $this->getConditionalArray($fieldname,
				$value, $operator);
	}

	public function hasConditionals() {
		if(count($this->conditionals) > 0) {
			return true;
		}
		return false;
	}

	public function addRelatedModuleCondition($relatedModule,$column, $value, $SQLOperator) {
		$conditionNumber = $this->conditionInstanceCount++;
		$this->groupInfo .= "$conditionNumber ";
		$this->manyToManyRelatedModuleConditions[$conditionNumber] = array('relatedModule'=>
			$relatedModule,'column'=>$column,'value'=>$value,'SQLOperator'=>$SQLOperator);
	}

	public function addReferenceModuleFieldCondition($relatedModule, $referenceField, $fieldName, $value, $SQLOperator, $glue=null) {
		$conditionNumber = $this->conditionInstanceCount++;
		if($glue != null && $conditionNumber > 0)
			$this->addConditionGlue($glue);

		$this->groupInfo .= "$conditionNumber ";
		$this->referenceModuleField[$conditionNumber] = array('relatedModule'=> $relatedModule,'referenceField'=> $referenceField,'fieldName'=>$fieldName,'value'=>$value,
			'SQLOperator'=>$SQLOperator);
	}

	protected function getConditionalArray($fieldname,$value,$operator) {
		if(is_string($value)) {
			$value = trim($value);
		} elseif(is_array($value)) {
			$value = array_map(trim, $value);
		}
		return array('name'=>$fieldname,'value'=>$value,'operator'=>$operator);
	}

	public function startGroup($groupType) {
		$this->groupInfo .= " $groupType (";
	}

	public function endGroup() {
		$this->groupInfo .= ')';
	}

	public function addConditionGlue($glue) {
		$this->groupInfo .= " $glue ";
	}

	public function addUserSearchConditions($input) {
		global $log,$default_charset;
		if($input['searchtype']=='advance') {

			$json = new Zend_Json();
			$advft_criteria = $_REQUEST['advft_criteria'];
			if(!empty($advft_criteria))	$advft_criteria = $json->decode($advft_criteria);
			$advft_criteria_groups = $_REQUEST['advft_criteria_groups'];
			if(!empty($advft_criteria_groups))	$advft_criteria_groups = $json->decode($advft_criteria_groups);

			if(empty($advft_criteria) || count($advft_criteria) <= 0) {
				return ;
			}

			$advfilterlist = getAdvancedSearchCriteriaList($advft_criteria, $advft_criteria_groups, $this->getModule());

			if(empty($advfilterlist) || count($advfilterlist) <= 0) {
				return ;
			}

			if($this->conditionInstanceCount > 0) {
				$this->startGroup(self::$AND);
			} else {
				$this->startGroup('');
			}
			foreach ($advfilterlist as $groupindex=>$groupcolumns) {
				$filtercolumns = $groupcolumns['columns'];
				if(count($filtercolumns) > 0) {
					$this->startGroup('');
					foreach ($filtercolumns as $index=>$filter) {
						$name = explode(':',$filter['columnname']);
						if(empty($name[2]) && $name[1] == 'crmid' && $name[0] == 'vtiger_crmentity') {
							$name = $this->getSQLColumn('id');
						} else {
							$name = $name[2];
						}
						$this->addCondition($name, $filter['value'], $filter['comparator']);
						$columncondition = $filter['column_condition'];
						if(!empty($columncondition)) {
							$this->addConditionGlue($columncondition);
						}
					}
					$this->endGroup();
					$groupConditionGlue = $groupcolumns['condition'];
					if(!empty($groupConditionGlue))
						$this->addConditionGlue($groupConditionGlue);
				}
			}
			$this->endGroup();
		} elseif($input['type']=='dbrd') {
			if($this->conditionInstanceCount > 0) {
				$this->startGroup(self::$AND);
			} else {
				$this->startGroup('');
			}
			$allConditionsList = $this->getDashBoardConditionList();
			$conditionList = $allConditionsList['conditions'];
			$relatedConditionList = $allConditionsList['relatedConditions'];
			$noOfConditions = count($conditionList);
			$noOfRelatedConditions = count($relatedConditionList);
			foreach ($conditionList as $index=>$conditionInfo) {
				$this->addCondition($conditionInfo['fieldname'], $conditionInfo['value'],
						$conditionInfo['operator']);
				if($index < $noOfConditions - 1 || $noOfRelatedConditions > 0) {
					$this->addConditionGlue(self::$AND);
				}
			}
			foreach ($relatedConditionList as $index => $conditionInfo) {
				$this->addRelatedModuleCondition($conditionInfo['relatedModule'],
						$conditionInfo['conditionModule'], $conditionInfo['finalValue'],
						$conditionInfo['SQLOperator']);
				if($index < $noOfRelatedConditions - 1) {
					$this->addConditionGlue(self::$AND);
				}
			}
			$this->endGroup();
		} else {
			if(isset($input['search_field']) && $input['search_field'] !="") {
				$fieldName=vtlib_purify($input['search_field']);
			} else {
				return ;
			}
			if($this->conditionInstanceCount > 0) {
				$this->startGroup(self::$AND);
			} else {
				$this->startGroup('');
			}
			$moduleFields = $this->getModuleFields();
			$field = $moduleFields[$fieldName];
			$type = $field->getFieldDataType();
			if(isset($input['search_text']) && $input['search_text']!="") {
				// search other characters like "|, ?, ?" by jagi
				$value = $input['search_text'];
				$stringConvert = function_exists(iconv) ? @iconv("UTF-8",$default_charset,$value)
						: $value;
				if(!$this->isStringType($type)) {
					$value=trim($stringConvert);
				}

				if($type == 'picklist') {
					global $mod_strings;
					// Get all the keys for the for the Picklist value
					$mod_keys = array_keys($mod_strings, $value);
					if(sizeof($mod_keys) >= 1) {
						// Iterate on the keys, to get the first key which doesn't start with LBL_      (assuming it is not used in PickList)
						foreach($mod_keys as $mod_idx=>$mod_key) {
							$stridx = strpos($mod_key, 'LBL_');
							// Use strict type comparision, refer strpos for more details
							if ($stridx !== 0) {
								$value = $mod_key;
								break;
							}
						}
					}
				}
				if($type == 'currency') {
					// Some of the currency fields like Unit Price, Total, Sub-total etc of Inventory modules, do not need currency conversion
					if($field->getUIType() == '72') {
						$value = CurrencyField::convertToDBFormat($value, null, true);
					} else {
						$currencyField = new CurrencyField($value);
						$value = $currencyField->getDBInsertedValue();
					}
				}
			}
			if(!empty($input['operator'])) {
				$operator = $input['operator'];
			} elseif(trim(strtolower($value)) == 'null'){
				$operator = 'e';
			} else {
				if(!$this->isNumericType($type) && !$this->isDateType($type)) {
					$operator = 'c';
				} else {
					$operator = 'h';
				}
			}
			$this->addCondition($fieldName, $value, $operator);
			$this->endGroup();
		}
	}

	public function getDashBoardConditionList() {
		if(isset($_REQUEST['leadsource'])) {
			$leadSource = vtlib_purify($_REQUEST['leadsource']);
		}
		if(isset($_REQUEST['date_closed'])) {
			$dateClosed = vtlib_purify($_REQUEST['date_closed']);
		}
		if(isset($_REQUEST['sales_stage'])) {
			$salesStage = vtlib_purify($_REQUEST['sales_stage']);
		}
		if(isset($_REQUEST['closingdate_start'])) {
			$dateClosedStart = vtlib_purify($_REQUEST['closingdate_start']);
		}
		if(isset($_REQUEST['closingdate_end'])) {
			$dateClosedEnd = vtlib_purify($_REQUEST['closingdate_end']);
		}
		if(isset($_REQUEST['owner'])) {
			$owner = vtlib_purify($_REQUEST['owner']);
		}
		if(isset($_REQUEST['campaignid'])) {
			$campaignId = vtlib_purify($_REQUEST['campaignid']);
		}
		if(isset($_REQUEST['quoteid'])) {
			$quoteId = vtlib_purify($_REQUEST['quoteid']);
		}
		if(isset($_REQUEST['invoiceid'])) {
			$invoiceId = vtlib_purify($_REQUEST['invoiceid']);
		}
		if(isset($_REQUEST['purchaseorderid'])) {
			$purchaseOrderId = vtlib_purify($_REQUEST['purchaseorderid']);
		}

		$conditionList = array();
		if(!empty($dateClosedStart) && !empty($dateClosedEnd)) {

			$conditionList[] = array('fieldname'=>'closingdate', 'value'=>$dateClosedStart,
				'operator'=>'h');
			$conditionList[] = array('fieldname'=>'closingdate', 'value'=>$dateClosedEnd,
				'operator'=>'m');
		}
		if(!empty($salesStage)) {
			if($salesStage == 'Other') {
				$conditionList[] = array('fieldname'=>'sales_stage', 'value'=>'Closed Won',
					'operator'=>'n');
				$conditionList[] = array('fieldname'=>'sales_stage', 'value'=>'Closed Lost',
					'operator'=>'n');
			} else {
				$conditionList[] = array('fieldname'=>'sales_stage', 'value'=> $salesStage,
					'operator'=>'e');
			}
		}
		if(!empty($leadSource)) {
			$conditionList[] = array('fieldname'=>'leadsource', 'value'=>$leadSource,
					'operator'=>'e');
		}
		if(!empty($dateClosed)) {
			$conditionList[] = array('fieldname'=>'closingdate', 'value'=>$dateClosed,
					'operator'=>'h');
		}
		if(!empty($owner)) {
			$conditionList[] = array('fieldname'=>'assigned_user_id', 'value'=>$owner,
					'operator'=>'e');
		}
		$relatedConditionList = array();
		if(!empty($campaignId)) {
			$relatedConditionList[] = array('relatedModule'=>'Campaigns','conditionModule'=>
				'Campaigns','finalValue'=>$campaignId, 'SQLOperator'=>'=');
		}
		if(!empty($quoteId)) {
			$relatedConditionList[] = array('relatedModule'=>'Quotes','conditionModule'=>
				'Quotes','finalValue'=>$quoteId, 'SQLOperator'=>'=');
		}
		if(!empty($invoiceId)) {
			$relatedConditionList[] = array('relatedModule'=>'Invoice','conditionModule'=>
				'Invoice','finalValue'=>$invoiceId, 'SQLOperator'=>'=');
		}
		if(!empty($purchaseOrderId)) {
			$relatedConditionList[] = array('relatedModule'=>'PurchaseOrder','conditionModule'=>
				'PurchaseOrder','finalValue'=>$purchaseOrderId, 'SQLOperator'=>'=');
		}
		return array('conditions'=>$conditionList,'relatedConditions'=>$relatedConditionList);
	}

	public function initForGlobalSearchByType($type, $value, $operator='s') {
		$fieldList = $this->meta->getFieldNameListByType($type);
		if($this->conditionInstanceCount <= 0) {
			$this->startGroup('');
		} else {
			$this->startGroup(self::$AND);
		}
		$nameFieldList = explode(',',$this->getModuleNameFields($this->module));
		foreach ($nameFieldList as $nameList) {
			$field = $this->meta->getFieldByColumnName($nameList);
			$this->fields[] = $field->getFieldName();
		}
		foreach ($fieldList as $index => $field) {
			$fieldName = $this->meta->getFieldByColumnName($field);
			$this->fields[] = $fieldName->getFieldName();
			if($index > 0) {
				$this->addConditionGlue(self::$OR);
			}
			$this->addCondition($fieldName->getFieldName(), $value, $operator);
		}
		$this->endGroup();
		if(!in_array('id', $this->fields)) {
				$this->fields[] = 'id';
		}
	}

}
?>
