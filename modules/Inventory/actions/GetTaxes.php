<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Inventory_GetTaxes_Action extends Vtiger_Action_Controller {

	public function requiresPermission(\Vtiger_Request $request) {
		$permissions = parent::requiresPermission($request);
		$permissions[] = array('module_parameter' => 'sourceModule', 'action' => 'DetailView');
		return $permissions;
	}
	
	function process(Vtiger_Request $request) {
		$decimalPlace = getCurrencyDecimalPlaces();
		$currencyId = $request->get('currency_id');
		$currencies = Inventory_Module_Model::getAllCurrencies();
		$conversionRate = $conversionRateForPurchaseCost = 1;

		$idList = $request->get('idlist');
		if (!$idList) {
			$recordId = $request->get('record');
			$idList = array($recordId);
		}

		$response = new Vtiger_Response();
		$namesList = $purchaseCostsList = $taxesList = $listPricesList = $listPriceValuesList = $reducedtaxrateList = array();
		$descriptionsList = $quantitiesList = $imageSourcesList = $productIdsList = $baseCurrencyIdsList = array();

		foreach($idList as $id) {
			$recordModel = Vtiger_Record_Model::getInstanceById($id);
			$taxes = $recordModel->getTaxes();
			foreach ($taxes as $key => $taxInfo) {
				$taxInfo['compoundOn'] = json_encode($taxInfo['compoundOn']);
				$taxes[$key] = $taxInfo;
			}

			$taxesList[$id]				= $taxes;
			$namesList[$id]				= decode_html($recordModel->getName());
			$quantitiesList[$id]		= $recordModel->get('qtyinstock');
			$descriptionsList[$id]		= decode_html($recordModel->get('description'));
			$listPriceValuesList[$id]	= $recordModel->getListPriceValues($recordModel->getId());
			$reducedtaxrateList[$id] = $recordModel->get('reducedtaxrate');

			$priceDetails = $recordModel->getPriceDetails();
			foreach ($priceDetails as $currencyDetails) {
				if ($currencyId == $currencyDetails['curid']) {
					$conversionRate = $currencyDetails['conversionrate'];
				}
			}
			$listPricesList[$id] = (float)$recordModel->get('unit_price') * (float)$conversionRate;

			foreach ($currencies as $currencyInfo) {
				if ($currencyId == $currencyInfo['curid']) {
					$conversionRateForPurchaseCost = $currencyInfo['conversionrate'];
					break;
				}
			}
			$purchaseCostsList[$id] = round((float)$recordModel->get('purchase_cost') * (float)$conversionRateForPurchaseCost, $decimalPlace);
			$baseCurrencyIdsList[$id] = getProductBaseCurrency($id, $recordModel->getModuleName());

			if ($recordModel->getModuleName() == 'Products') {
				$productIdsList[] = $id;
			}
		}

		if ($productIdsList) {
			$imageDetailsList = Products_Record_Model::getProductsImageDetails($productIdsList);
			foreach ($imageDetailsList as $productId => $imageDetails) {
				$imageSourcesList[$productId] = $imageDetails[0]['path'].'_'.$imageDetails[0]['orgname'];
			}
		}

		foreach($idList as $id) {
			$recordModel = Vtiger_Record_Model::getInstanceById($id);
			$resultData = array(
								'id'					=> $id,
								'name'					=> $namesList[$id],
								'taxes'					=> $taxesList[$id],
								'listprice'				=> $listPricesList[$id],
								'listpricevalues'		=> $listPriceValuesList[$id],
								'purchaseCost'			=> $purchaseCostsList[$id],
								'description'			=> $descriptionsList[$id],
								'baseCurrencyId'		=> $baseCurrencyIdsList[$id],
								'quantityInStock'		=> $quantitiesList[$id],
								'imageSource'			=> $imageSourcesList[$id],
					);
			if($recordModel->getModuleName() == 'Products'){
				$resultData['usageunit'] = vtranslate($recordModel->get('usageunit'), $recordModel->getModuleName());
			}else if($recordModel->getModuleName() == 'Services'){
				$resultData['usageunit'] = vtranslate($recordModel->get('service_usageunit'), $recordModel->getModuleName());
			}
			if($recordModel->getModuleName() == 'Products'){
				$resultData['reducedtaxrate'] = vtranslate($recordModel->get('reducedtaxrate'), $recordModel->getModuleName());
			}else if($recordModel->getModuleName() == 'Services'){
				$resultData['reducedtaxrate'] = vtranslate($recordModel->get('service_reducedtaxrate'), $recordModel->getModuleName());
			}

			$info[] = array($id => $resultData);
		}
		$response->setResult($info);
		$response->emit();
	}
}
