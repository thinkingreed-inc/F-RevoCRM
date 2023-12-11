<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class PriceBooks_RelationListView_Model extends Vtiger_RelationListView_Model {

	public function getHeaders() {
		$headerFields = parent::getHeaders();

		//Added to support List Price
		$field = new Vtiger_Field_Model();
		$field->set('name', 'listprice');
		$field->set('column', 'listprice');
		$field->set('label', 'List Price');
		$headerFields['listprice'] = $field;

		return $headerFields;
	}

	public function getEntries($pagingModel) {
		$db = PearDatabase::getInstance();
		$parentModule = $this->getParentRecordModel()->getModule();
		$relationModule = $this->getRelationModel()->getRelationModuleModel();
                $relationModuleName = $relationModule->get('name');
		$relatedColumnFieldMapping = $relationModule->getConfigureRelatedListFields();
		if(php7_count($relatedColumnFieldMapping) <= 0){
			$relatedColumnFieldMapping = $relationModule->getRelatedListFields();
		}

		$query = $this->getRelationQuery();
                
                if ($this->get('whereCondition') && is_array($this->get('whereCondition'))) {
                    $currentUser = Users_Record_Model::getCurrentUserModel();
                    //EnhancedQueryGenerator is used instead of QueryGenerator since below case was faling 
                    //AssignTo Empty 
                    $queryGenerator = new EnhancedQueryGenerator($relationModuleName, $currentUser);
                    $queryGenerator->setFields(array_values($relatedColumnFieldMapping));
                    $whereCondition = $this->get('whereCondition');
                    foreach ($whereCondition as $fieldName => $fieldValue) {
                        $fieldModel = $relationModule->getField($fieldName);
                        $fieldType= explode('~',$fieldModel->get('typeofdata'))[0];
                        $referenceModuleList = $fieldModel->getReferenceList();
                        if (is_array($fieldValue)) {
                            $comparator = $fieldValue[1];
                            $searchValue = $fieldValue[2];
                            $type = $fieldValue[3];
                            if ($type == 'time') {
                                $searchValue = Vtiger_Time_UIType::getTimeValueWithSeconds($searchValue);
                            } else if($type == 'owner' || ($type == 'reference' && in_array('Users', $referenceModuleList))) {
                                $searchValue = $fieldValue[2];
                                if(!$fieldModel->isCustomField()) {
                                    $userFieldValues = explode(',', $searchValue);
                                    $userValues = array();
                                    foreach ($userFieldValues as $key => $value) {
                                        if(is_numeric($value)) {
                                            $userValues[$key] = getUserFullName($value);
                                        } else {
                                            $userValues[$key] = $value;
                                        }
                                    }
                                    $searchValue = implode(',',$userValues);
                                }
                            }
                            else if ($fieldType == 'DT') {
                                $dateValues = explode(',', $searchValue);
                                //Indicate whether it is fist date in the between condition
                                $isFirstDate = true;
                                foreach ($dateValues as $key => $dateValue) {
                                        $dateTimeCompoenents = explode(' ', $dateValue);
                                        if (empty($dateTimeCompoenents[1])) {
                                                if ($isFirstDate)
                                                        $dateTimeCompoenents[1] = '00:00:00';
                                                else
                                                        $dateTimeCompoenents[1] = '23:59:59';
                                        }
                                        $dateValue = implode(' ', $dateTimeCompoenents);
                                        $dateValues[$key] = $dateValue;
                                        $isFirstDate = false;
                                }
                                $searchValue = implode(',', $dateValues);
                            }
                            //Relation fields column's search fields are missing in related list view.
                            $query = ($type == 'reference') ? $this->getReferenceFieldJoinClause($query, $relationModuleName, $fieldModel) : $query;
                            $queryGenerator->addCondition($fieldName, $searchValue, $comparator, "AND");
                        }
                    }
                    $whereQuerySplit = split("WHERE", $queryGenerator->getWhereClause());
                    $query.=" AND " . $whereQuerySplit[1];
                }

		$startIndex = $pagingModel->getStartIndex();
		$pageLimit = $pagingModel->getPageLimit();

		$orderBy = $this->getForSql('orderby');
		$sortOrder = $this->getForSql('sortorder');
		if($orderBy) {
			$query = "$query ORDER BY $orderBy $sortOrder";
		}

		$limitQuery = $query .' LIMIT '.$startIndex.','.$pageLimit;
		$result = $db->pquery($limitQuery, array());
		$relatedRecordList = array();

		for($i=0; $i< $db->num_rows($result); $i++ ) {
			$row = $db->fetch_row($result,$i);
			$newRow = array();
			foreach($row as $col=>$val){
				if(array_key_exists($col,$relatedColumnFieldMapping))
					$newRow[$relatedColumnFieldMapping[$col]] = $val;
			}
			
			$recordId = $row['crmid'];
			$newRow['id'] = $recordId;
			//Added to support List Price
			$newRow['listprice'] = CurrencyField::convertToUserFormat($row['listprice'], null, true);

			$record = Vtiger_Record_Model::getCleanInstance($relationModule->get('name'));
			$relatedRecordList[$recordId] = $record->setData($newRow)->setModuleFromInstance($relationModule);
		}
		$pagingModel->calculatePageRange($relatedRecordList);

		$nextLimitQuery = $query. ' LIMIT '.($startIndex+$pageLimit).' , 1';
		$nextPageLimitResult = $db->pquery($nextLimitQuery, array());
		if($db->num_rows($nextPageLimitResult) > 0){
			$pagingModel->set('nextPageExists', true);
		}else{
			$pagingModel->set('nextPageExists', false);
		}
		return $relatedRecordList;
	}
}