<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Products_RelationListView_Model extends Vtiger_RelationListView_Model {

	/**
	 * Function to get the links for related list
	 * @return <Array> List of action models <Vtiger_Link_Model>
	 */
	public function getLinks() {
		$relationModel = $this->getRelationModel();
		$parentModel = $this->getParentRecordModel();
		
		$isSubProduct = false;
		if($parentModel->getModule()->getName() == $relationModel->getRelationModuleModel()->getName()) {
			$isSubProduct = $relationModel->isSubProduct($parentModel->getId());
		}
		
		if(!$isSubProduct){
			return parent::getLinks();
		}
	}
	
	public function getHeaders() {
		$headerFields = parent::getHeaders();
		if($this->getRelationModel()->getRelationModuleModel()->getName() == 'PriceBooks') {
			//Added to support Unit Price
			$unitPriceField = new Vtiger_Field_Model();
			$unitPriceField->set('name', 'unit_price');
			$unitPriceField->set('column', 'unit_price');
			$unitPriceField->set('label', 'Unit Price');
			
			$headerFields['unit_price'] = $unitPriceField;
			
			//Added to support List Price
			$field = new Vtiger_Field_Model();
			$field->set('name', 'listprice');
			$field->set('column', 'listprice');
			$field->set('label', 'List Price');
			
			$headerFields['listprice'] = $field;
		}
		
		return $headerFields;
	}
	
	public function getRelationQuery() {
		$query = parent::getRelationQuery();

		$relationModel = $this->getRelationModel();
		$parentModule = $relationModel->getParentModuleModel();
		$parentModuleName = $parentModule->getName();
		$relatedModuleName = $this->getRelatedModuleModel()->getName();
		$quantityField = $parentModule->getField('qty_per_unit');

		if ($parentModuleName === $relatedModuleName && $this->tab_label === 'Product Bundles' && $quantityField->isActiveField()) {//Products && Child Products
			$queryComponents = preg_split('/ FROM /i', $query);
			$count = php7_count($queryComponents);

			$query = $queryComponents[0]. ', vtiger_seproductsrel.quantity AS qty_per_unit ';
			for($i=1; $i<$count; $i++) {
				$query .= ' FROM '.$queryComponents[$i];
			}
		}

		$nonAdminQuery = Users_Privileges_Model::getNonAdminAccessControlQuery($relatedModuleName);
		if (trim($nonAdminQuery)) {
			if($relatedModuleName == 'Calendar') {
				$query = appendFromClauseToQuery($query, $nonAdminQuery);

				$moduleFocus = CRMEntity::getInstance('Calendar');
				$condition = $moduleFocus->buildWhereClauseConditionForCalendar();
				if($condition) {
					$query .= ' AND '.$condition;
				}
			} else {
				$query = appendFromClauseToQuery($query, $nonAdminQuery);
			}
		}

		return $query;
	}

}
