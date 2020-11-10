<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Products_SubProducts_Action extends Vtiger_Action_Controller {

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'module', 'action' => 'DetailView', 'record_parameter' => 'record');
		return $permissions;
	}

	function process(Vtiger_Request $request) {
		$productId = $request->get('record');
		$productModel = Vtiger_Record_Model::getInstanceById($productId, 'Products');
		$subProducts = $productModel->getSubProducts($active = true);
		$values = array();
		foreach($subProducts as $id => $subProduct) {
			$stockMessage = '';
			if ($subProduct->get('quantityInBundle') > $subProduct->get('qtyinstock')) {
				$stockMessage = vtranslate('LBL_STOCK_NOT_ENOUGH', $request->getModule());
			}
			$values[$id] = array('productName'	=> $subProduct->getName(),
								 'quantity'		=> $subProduct->get('quantityInBundle'),
								 'stockMessage'	=> $stockMessage);
		}

		$result = array('isBundleViewable' => $productModel->isBundleViewable(), 'values' => $values);
		$response = new Vtiger_Response();
		$response->setResult($result);
		$response->emit();
	}
}
