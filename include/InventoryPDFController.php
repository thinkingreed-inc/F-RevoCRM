<?php
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *
 ********************************************************************************/

include_once 'vtlib/Vtiger/PDF/models/Model.php';
include_once 'vtlib/Vtiger/PDF/inventory/HeaderViewer.php';
include_once 'vtlib/Vtiger/PDF/inventory/FooterViewer.php';
include_once 'vtlib/Vtiger/PDF/inventory/ContentViewer.php';
include_once 'vtlib/Vtiger/PDF/inventory/ContentViewer2.php';
include_once 'vtlib/Vtiger/PDF/viewers/PagerViewer.php';
include_once 'vtlib/Vtiger/PDF/PDFGenerator.php';
include_once 'data/CRMEntity.php';

class Vtiger_InventoryPDFController {

	protected $module;
	protected $focus = null;

	function __construct($module) {
		$this->moduleName = $module;
	}

	function loadRecord($id) {
		global $current_user;
		$this->focus = $focus = CRMEntity::getInstance($this->moduleName);
		$focus->retrieve_entity_info($id,$this->moduleName);
		$focus->apply_field_security();
		$focus->id = $id;
		$this->associated_products = getAssociatedProducts($this->moduleName,$focus);
	}

	function getPDFGenerator() {
		return new Vtiger_PDF_Generator();
	}

	function getContentViewer() {
		if($this->focusColumnValue('hdnTaxType') == "individual") {
			$contentViewer = new Vtiger_PDF_InventoryContentViewer();
		} else {
			$contentViewer = new Vtiger_PDF_InventoryTaxGroupContentViewer();
		}
		$contentViewer->setContentModels($this->buildContentModels());
		$contentViewer->setSummaryModel($this->buildSummaryModel());
		$contentViewer->setLabelModel($this->buildContentLabelModel());
		$contentViewer->setWatermarkModel($this->buildWatermarkModel());
		return $contentViewer;
	}

	function getHeaderViewer() {
		$headerViewer = new Vtiger_PDF_InventoryHeaderViewer();
		$headerViewer->setModel($this->buildHeaderModel());
		return $headerViewer;
	}

	function getFooterViewer() {
		$footerViewer = new Vtiger_PDF_InventoryFooterViewer();
		$footerViewer->setModel($this->buildFooterModel());
		$footerViewer->setLabelModel($this->buildFooterLabelModel());
		$footerViewer->setOnLastPage();
		return $footerViewer;
	}

	function getPagerViewer() {
		$pagerViewer = new Vtiger_PDF_PagerViewer();
		$pagerViewer->setModel($this->buildPagermodel());
		return $pagerViewer;
	}

	function Output($filename, $type) {
		if(is_null($this->focus)) return;

		$pdfgenerator = $this->getPDFGenerator();

		$pdfgenerator->setPagerViewer($this->getPagerViewer());
		$pdfgenerator->setHeaderViewer($this->getHeaderViewer());
		$pdfgenerator->setFooterViewer($this->getFooterViewer());
		$pdfgenerator->setContentViewer($this->getContentViewer());

		$pdfgenerator->generate($filename, $type);
	}


	// Helper methods

	function buildContentModels() {
		$associated_products = $this->associated_products;
		$contentModels = array();
		$productLineItemIndex = 0;
		$totaltaxes = 0;
		$no_of_decimal_places = getCurrencyDecimalPlaces();
		foreach($associated_products as $productLineItem) {
			++$productLineItemIndex;

			$contentModel = new Vtiger_PDF_Model();

			$discountPercentage  = 0.00;
			$total_tax_percent = 0.00;
			$producttotal_taxes = 0.00;
			$quantity = ''; $listPrice = ''; $discount = ''; $taxable_total = '';
			$tax_amount = ''; $producttotal = '';


			$quantity	= $productLineItem["qty{$productLineItemIndex}"];
			$listPrice	= $productLineItem["listPrice{$productLineItemIndex}"];
			$discount	= $productLineItem["discountTotal{$productLineItemIndex}"];
			$taxable_total = $quantity * $listPrice - $discount;
			$taxable_total = number_format($taxable_total, $no_of_decimal_places,'.','');
			$producttotal = $taxable_total;
			if($this->focus->column_fields["hdnTaxType"] == "individual") {
				for($tax_count=0;$tax_count<php7_count($productLineItem['taxes']);$tax_count++) {
					$tax_percent = $productLineItem['taxes'][$tax_count]['percentage'];
					$total_tax_percent += $tax_percent;
					$tax_amount = (($taxable_total*$tax_percent)/100);
					$producttotal_taxes += $tax_amount;
				}
			}

			$producttotal_taxes = number_format($producttotal_taxes, $no_of_decimal_places,'.','');
			$producttotal = $taxable_total+$producttotal_taxes;
			$producttotal = number_format($producttotal, $no_of_decimal_places,'.','');
			$tax = $producttotal_taxes;
			$totaltaxes += $tax;
			$totaltaxes = number_format($totaltaxes, $no_of_decimal_places,'.','');
			$discountPercentage = $productLineItem["discount_percent{$productLineItemIndex}"];
			$productName = decode_html($productLineItem["productName{$productLineItemIndex}"]);
			//get the sub product
			$subProducts = $productLineItem["subProductArray{$productLineItemIndex}"];
			if($subProducts != '') {
				foreach($subProducts as $subProduct) {
					$productName .="\n"." - ".decode_html($subProduct);
				}
			}
			$contentModel->set('Name', $productName);
			$contentModel->set('Code', decode_html($productLineItem["hdnProductcode{$productLineItemIndex}"]));
			$contentModel->set('Quantity', $quantity);
			$contentModel->set('Price',     $this->formatPrice($listPrice));
			$contentModel->set('Discount',  $this->formatPrice($discount)."\n ($discountPercentage%)");
			$contentModel->set('Tax',       $this->formatPrice($tax)."\n ($total_tax_percent%)");
			$contentModel->set('Total',     $this->formatPrice($producttotal));
			$contentModel->set('Comment',   decode_html($productLineItem["comment{$productLineItemIndex}"]));

			$contentModels[] = $contentModel;
		}
		$this->totaltaxes = $totaltaxes; //will be used to add it to the net total

		return $contentModels;
	}

	function buildContentLabelModel() {
		$labelModel = new Vtiger_PDF_Model();
		$labelModel->set('Code',      getTranslatedString('Product Code',$this->moduleName));
		$labelModel->set('Name',      getTranslatedString('Product Name',$this->moduleName));
		$labelModel->set('Quantity',  getTranslatedString('Quantity',$this->moduleName));
		$labelModel->set('Price',     getTranslatedString('LBL_LIST_PRICE',$this->moduleName));
		$labelModel->set('Discount',  getTranslatedString('Discount',$this->moduleName));
		$labelModel->set('Tax',       getTranslatedString('Tax',$this->moduleName));
		$labelModel->set('Total',     getTranslatedString('Total',$this->moduleName));
		$labelModel->set('Comment',   getTranslatedString('Comment'),$this->moduleName);
		return $labelModel;
	}

	function buildSummaryModel() {
		$associated_products = $this->associated_products;
		$final_details = $associated_products[1]['final_details'];

		$summaryModel = new Vtiger_PDF_Model();

		$netTotal = $discount = $handlingCharges =  $handlingTaxes = 0;
		$adjustment = $grandTotal = 0;

		$productLineItemIndex = 0;
		$sh_tax_percent = 0;
		foreach($associated_products as $productLineItem) {
			++$productLineItemIndex;
			$netTotal += $productLineItem["netPrice{$productLineItemIndex}"];
		}
		$netTotal = number_format(($netTotal + $this->totaltaxes), getCurrencyDecimalPlaces(),'.', '');
		$summaryModel->set(getTranslatedString("Net Total", $this->moduleName), $this->formatPrice($netTotal));

		$discount_amount = $final_details["discount_amount_final"];
		$discount_percent = $final_details["discount_percentage_final"];

		$discount = 0.0;
        $discount_final_percent = '0.00';
		if($final_details['discount_type_final'] == 'amount') {
			$discount = $discount_amount;
		} else if($final_details['discount_type_final'] == 'percentage') {
            $discount_final_percent = $discount_percent;
			$discount = (($discount_percent*$final_details["hdnSubTotal"])/100);
		}
		$summaryModel->set(getTranslatedString("Discount", $this->moduleName)."($discount_final_percent%)", $this->formatPrice($discount));

		$group_total_tax_percent = '0.00';
		//To calculate the group tax amount
		if($final_details['taxtype'] == 'group') {
			$group_tax_details = $final_details['taxes'];
			for($i=0;$i<php7_count($group_tax_details);$i++) {
				$group_total_tax_percent += $group_tax_details[$i]['percentage'];
			}
			$summaryModel->set(getTranslatedString("Tax:", $this->moduleName)."($group_total_tax_percent%)", $this->formatPrice($final_details['tax_totalamount']));
		}
		//Shipping & Handling taxes
		$sh_tax_details = $final_details['sh_taxes'];
		for($i=0;$i<php7_count($sh_tax_details);$i++) {
			$sh_tax_percent = $sh_tax_percent + $sh_tax_details[$i]['percentage'];
		}
		//obtain the Currency Symbol
		$currencySymbol = $this->buildCurrencySymbol();

		$summaryModel->set(getTranslatedString("Shipping & Handling Charges", $this->moduleName), $this->formatPrice($final_details['shipping_handling_charge']));
		$summaryModel->set(getTranslatedString("Shipping & Handling Tax:", $this->moduleName)."($sh_tax_percent%)", $this->formatPrice($final_details['shtax_totalamount']));
		$summaryModel->set(getTranslatedString("Adjustment", $this->moduleName), $this->formatPrice($final_details['adjustment']));
		$summaryModel->set(getTranslatedString("Grand Total:", $this->moduleName)."(in $currencySymbol)", $this->formatPrice($final_details['grandTotal'])); // TODO add currency string

		if ($this->moduleName == 'Invoice') {
			$receivedVal = $this->focusColumnValue("received");
			if (!$receivedVal) {
				$this->focus->column_fields["received"] = 0;
			}
			//If Received value is exist then only Recieved, Balance details should present in PDF
			if ($this->formatPrice($this->focusColumnValue("received")) > 0) {
				$summaryModel->set(getTranslatedString("Received", $this->moduleName), $this->formatPrice($this->focusColumnValue("received")));
				$summaryModel->set(getTranslatedString("Balance", $this->moduleName), $this->formatPrice($this->focusColumnValue("balance")));
			}
		}
		return $summaryModel;
	}

	function buildHeaderModel() {
		$headerModel = new Vtiger_PDF_Model();
		$headerModel->set('title', $this->buildHeaderModelTitle());
		$modelColumns = array($this->buildHeaderModelColumnLeft(), $this->buildHeaderModelColumnCenter(), $this->buildHeaderModelColumnRight());
		$headerModel->set('columns', $modelColumns);

		return $headerModel;
	}

	function buildHeaderModelTitle() {
		return $this->moduleName;
	}

	function buildHeaderModelColumnLeft() {
		global $adb;

		// Company information
		$result = $adb->pquery("SELECT * FROM vtiger_organizationdetails", array());
		$num_rows = $adb->num_rows($result);
		if($num_rows) {
			$resultrow = $adb->fetch_array($result);

			$addressValues = array();
			$addressValues[] = '';
			// if(!empty($resultrow['country'])) $addressValues[]= $resultrow['country'];
			if(!empty($resultrow['code'])) $addressValues[]= "\n".$resultrow['code'];
			if(!empty($resultrow['state'])) $addressValues[]= "\n".$resultrow['state'];
			if(!empty($resultrow['city'])) $addressValues[]= $resultrow['city'];
			if(!empty($resultrow['address'])) $addressValues[]= $resultrow['address'];

			$additionalCompanyInfo = array();
			if(!empty($resultrow['phone']))		$additionalCompanyInfo[]= "\n".getTranslatedString("Phone: ", $this->moduleName). $resultrow['phone'];
			if(!empty($resultrow['fax']))		$additionalCompanyInfo[]= "\n".getTranslatedString("Fax: ", $this->moduleName). $resultrow['fax'];
			if(!empty($resultrow['website']))	$additionalCompanyInfo[]= "\n".getTranslatedString("Website: ", $this->moduleName). $resultrow['website'];
                        if(!empty($resultrow['vatid']))         $additionalCompanyInfo[]= "\n".getTranslatedString("VAT ID: ", $this->moduleName). $resultrow['vatid']; 

			$modelColumnLeft = array(
					'logo' => "public/logo/".$resultrow['logoname'],
					'summary' => decode_html($resultrow['organizationname']),
					'content' => decode_html($this->joinValues($addressValues, ''). $this->joinValues($additionalCompanyInfo, ' '))
			);
		}
		return $modelColumnLeft;
	}

	function buildHeaderModelColumnCenter() {
		$customerName = $this->resolveReferenceLabel($this->focusColumnValue('account_id'), 'Accounts');
		$contactName = $this->resolveReferenceLabel($this->focusColumnValue('contact_id'), 'Contacts');

		$customerNameLabel = getTranslatedString('Customer Name', $this->moduleName);
		$contactNameLabel = getTranslatedString('Contact Name', $this->moduleName);
		$modelColumnCenter = array(
				$customerNameLabel => $customerName,
				$contactNameLabel  => $contactName,
		);
		return $modelColumnCenter;
	}

	function buildHeaderModelColumnRight() {
		$issueDateLabel = getTranslatedString('Issued Date', $this->moduleName);
		$validDateLabel = getTranslatedString('Valid Date', $this->moduleName);
		$billingAddressLabel = getTranslatedString('Billing Address', $this->moduleName);
		$shippingAddressLabel = getTranslatedString('Shipping Address', $this->moduleName);

		$modelColumnRight = array(
				'dates' => array(
						$issueDateLabel  => $this->formatDate(date("Y-m-d")),
						$validDateLabel  => $this->formatDate($this->focusColumnValue('validtill')),
				),
				$billingAddressLabel  => $this->buildHeaderBillingAddress(),
				$shippingAddressLabel => $this->buildHeaderShippingAddress()
		);
		return $modelColumnRight;
	}

	function buildFooterModel() {
		$footerModel = new Vtiger_PDF_Model();
		$footerModel->set(Vtiger_PDF_InventoryFooterViewer::$DESCRIPTION_DATA_KEY, from_html($this->focusColumnValue('description')));
		$footerModel->set(Vtiger_PDF_InventoryFooterViewer::$TERMSANDCONDITION_DATA_KEY, from_html($this->focusColumnValue('terms_conditions')));
		return $footerModel;
	}

	function buildFooterLabelModel() {
		$labelModel = new Vtiger_PDF_Model();
		$labelModel->set(Vtiger_PDF_InventoryFooterViewer::$DESCRIPTION_LABEL_KEY, getTranslatedString('Description',$this->moduleName));
		$labelModel->set(Vtiger_PDF_InventoryFooterViewer::$TERMSANDCONDITION_LABEL_KEY, getTranslatedString('Terms & Conditions',$this->moduleName));
		return $labelModel;
	}

	function buildPagerModel() {
		$footerModel = new Vtiger_PDF_Model();
		$footerModel->set('format', '-%s-');
		return $footerModel;
	}

	function getWatermarkContent() {
		return '';
	}

	function buildWatermarkModel() {
		$watermarkModel = new Vtiger_PDF_Model();
		$watermarkModel->set('content', $this->getWatermarkContent());
		return $watermarkModel;
	}

	function buildHeaderBillingAddress() {
		$billPoBox	= $this->focusColumnValues(array('bill_pobox'));
		$billStreet = $this->focusColumnValues(array('bill_street'));
		$billCity	= $this->focusColumnValues(array('bill_city'));
		$billState	= $this->focusColumnValues(array('bill_state'));
		$billCountry = $this->focusColumnValues(array('bill_country'));
		$billCode	=  $this->focusColumnValues(array('bill_code'));
		$address	= $billCode;
		$address .= " ".$billState.$billCity.$billStreet;
		return $address;
	}

	function buildHeaderShippingAddress() {
		$shipPoBox	= $this->focusColumnValues(array('ship_pobox'));
		$shipStreet = $this->focusColumnValues(array('ship_street'));
		$shipCity	= $this->focusColumnValues(array('ship_city'));
		$shipState	= $this->focusColumnValues(array('ship_state'));
		$shipCountry = $this->focusColumnValues(array('ship_country'));
		$shipCode = $this->focusColumnValues(array('ship_code'));
		$address	= $shipCode;
		$address .= " ".$shipState.$shipCity.$shipStreet;
		return $address;
	}

	function buildCurrencySymbol() {
		global $adb;
		$currencyId = $this->focus->column_fields['currency_id'];
		if(!empty($currencyId)) {
			$result = $adb->pquery("SELECT currency_symbol FROM vtiger_currency_info WHERE id=?", array($currencyId));
			return decode_html($adb->query_result($result,0,'currency_symbol'));
		}
		return false;
	}

	function focusColumnValues($names, $delimeter="\n") {
		if(!is_array($names)) {
			$names = array($names);
		}
		$values = array();
		foreach($names as $name) {
			$value = $this->focusColumnValue($name, false);
			if($value !== false) {
				$values[] = $value;
			}
		}
		return $this->joinValues($values, $delimeter);
	}

	function focusColumnValue($key, $defvalue='') {
		$focus = $this->focus;
		if(isset($focus->column_fields[$key])) {
			return decode_html($focus->column_fields[$key]);
		}
		return $defvalue;
	}

	function resolveReferenceLabel($id, $module=false) {
		if(empty($id)) {
			return '';
		}
		if($module === false) {
			$module = getSalesEntityType($id);
		}
		$label = getEntityName($module, array($id));
		return decode_html($label[$id]);
	}

	function joinValues($values, $delimeter= "\n") {
		$valueString = '';
		foreach($values as $value) {
			if(empty($value)) continue;
			$valueString .= $value . $delimeter;
		}
		return rtrim($valueString, $delimeter);
	}

	function formatNumber($value) {
		return number_format($value);
	}

	function formatPrice($value, $decimal=2) {
		$currencyField = new CurrencyField($value);
		return $currencyField->getDisplayValue(null, true);
	}

	function formatDate($value) {
		return DateTimeField::convertToUserFormat($value);
	}

	public static function getMergedDescription($template, $recordId, $moduleName, $startcount = false, $limitcount = false)
	{
		// base64データを含むimgタグをpreg_splitで取得した場合、Segmentation fault.が発生する場合がある。
		// preg_split前にimgタグを置換し、処理後にimgタグの内容を戻すようにする。
		preg_match_all('/<img.*?[\"|\'][\"|\'].*?>/is',$template,$matches_img);
		foreach ($matches_img[0] as $key => $imgtag) {
			$template = str_replace($imgtag, "imgtag".$key,$template);
		}
		// 子モジュールの値反映
		$template = self::getMergedChildDescription($template, $recordId, $startcount, $limitcount);

		// 製品サービスの値反映
		$template = self::getMergedProductsDescription($template, $moduleName, $recordId);

		foreach ($matches_img[0] as $key => $imgtag) {
			$template = str_replace("imgtag".$key,$imgtag,$template);
		}
		return $template;
	}

	// 子モジュールの値反映
	private static function getMergedChildDescription($template, $recordId, $startcount, $limitcount)
	{
		$childblocks = preg_split('/<tr((?:(?!<).)*)>((?:(?!<tr).)*)\$loop-child\$.*?<\/tr>/is', $template);

		// $loop-limit-x$ 取得する子レコードの最大数を設定
		preg_match_all('/(?<=' . preg_quote('$loop-limit-', "/") . ').*?(?=' . preg_quote('$', "/") . ')/', $template, $limitmatch);
		$looplimit = $limitmatch[0][0];

		preg_match_all('/<tr((?:(?!<).)*)>((?:(?!<tr).)*)\$loop-child\$.*?<\/tr>/is', $template, $blankrowsmatch);
		$template = preg_replace('/\$loop-limit-.*\$/', '', $template);

		preg_match_all('/\$loop-child\$.*/', $template, $match);

		$childcnt = 0;
		$loopcount = 0;
		$description = array();
		$referenceRecordIds = array();
		foreach ($childblocks as $key => $childblock) {
			$childdescription = array();
			if ($childcnt % 2 == 1) {
				//奇数の場合（ブロック内）	
				$resultchild = preg_match_all("/\\$\[(?:[a-zA-Z0-9]+)\](?:[a-zA-Z0-9]+)-(?:[a-zA-Z0-9]+)(?:_[a-zA-Z0-9]+)?(?::[a-zA-Z0-9]+)?(?:_[a-zA-Z0-9]+)*\\$/", $childblock, $matcheschild);
				// $loop-minrows-x$ 子レコード表示時に表示する最小行数。足りない分は空行を入れる。
				preg_match_all('/(?<=' . preg_quote('$loop-minrows-', "/") . ').*?(?=' . preg_quote('$', "/") . ')/', $blankrowsmatch[0][$childcnt-1], $minrows);
				$blankrowscount = $minrows[0][0];
				if ($resultchild != 0) {
					$templateVariablePairChild = $matcheschild[0];
					for ($i = 0; $i < php7_count($templateVariablePairChild); $i++) {
						$templateVariablePairChild[$i] = str_replace("​", "", str_replace('$', '', $templateVariablePairChild[$i]));
						list($childModuleName, $columnNameChild) = explode('-', $templateVariablePairChild[$i]);
						list($childModuleReferenceColumn, $childModuleColumnName) = explode(':', $columnNameChild);
						preg_match('/(?<=\[).*?(?=\])/', $childModuleName, $childmatches);
						$parentModuleName = $childmatches[0];
						$childModuleName = str_replace("[$parentModuleName]", '', $childModuleName);

						// 子モジュールのレコードIDを取得。
						$referenceRecordIds = self::getChildReferenceRecordIdForPDF($match, $loopcount, $childModuleName, $parentModuleName, $childModuleReferenceColumn, $recordId, $looplimit, $startcount, $limitcount);

						// 子モジュールの値を反映
						foreach ($referenceRecordIds as $key => $referenceRecordId) {
							if (empty($childdescription[$key])) {
								$loopChildBlock = $childblock;
								$loopChildBlock = preg_replace('/\$' . strtolower($parentModuleName) . '-child_key_no\$/', $key + 1, $loopChildBlock);
							} else {
								$loopChildBlock = $childdescription[$key];
							}
							$loopChildBlock = str_replace($templateVariablePairChild[$i], strtolower($childModuleName) . "-" . $childModuleColumnName, $loopChildBlock);
							$loopChildBlock = getMergedDescription($loopChildBlock, $referenceRecordId, $childModuleName);
							$childdescription[$key] = $loopChildBlock;
						}

						// $loop-minrows-x$ 子レコード表示時に表示する最小行数。足りない分は空行を入れる。
						if($blankrowscount){
							$limitremainingcount = $blankrowscount - php7_count($referenceRecordIds);
							if($limitremainingcount > 0){
								for ($k=0; $k < $limitremainingcount; $k++) { 
									$loopChildBlockForLimit = $childblock;
									$loopChildBlockForLimit = preg_replace('/\$' . strtolower($parentModuleName) . '-child_key_no\$/', "", $loopChildBlockForLimit);
	
									$loopChildBlockForLimit = str_replace($templateVariablePairChild[$i], strtolower($childModuleName) . "-" . $childModuleColumnName, $loopChildBlockForLimit);
									$loopChildBlockForLimit = preg_replace('/\[FUNCTION\|.*\|FUNCTION\]/', '', preg_replace("/\\$.*\\$/", '', $loopChildBlockForLimit));
									$childdescription[$key+$k+1] = $loopChildBlockForLimit;
								}
							}
						}
					}
				}
				$description[] = implode("", $childdescription);
				$loopcount += 1;
			} else {
				$description[] = $childblock;
			}
			$childcnt += 1;
		}
		return implode("", $description);
	}

	// 子モジュールのレコードIDを取得。
	private static function getChildReferenceRecordIdForPDF($match, $loopcount, $childModuleName, $parentModuleName, $childModuleReferenceColumn, $recordId, $looplimit, $startcount, $limitcount)
	{
		$childMouleWhereQuery = "";
		if ($match[$loopcount]) {
			$childConditions = str_replace('$loop-child$', '', strip_tags($match[$loopcount][0]));
			if (trim($childConditions)) {
				// 子モジュールの条件を設定する場合[FUNCTION|子モジュール項目名|=|値|FUNCTION]
				return self::getChildReferenceRecordIdWithConditions($parentModuleName, $childConditions, $childModuleName, $childModuleReferenceColumn, $recordId, $looplimit, $startcount, $limitcount);
			}
		}
		// 条件を指定せずに子モジュールを取得する。
		$referenceRecordIds = Vtiger_Functions::getChildReferenceRecordId($parentModuleName, $childModuleReferenceColumn, $childModuleName, $recordId, "", "", $looplimit, $startcount, $limitcount, $startcount, $limitcount);
		return $referenceRecordIds;
	}

	// 子モジュールのレコード数を取得。
	public static function getChildReferenceRecordCountForPDF($match, $loopcount, $childModuleName, $parentModuleName, $childModuleReferenceColumn, $recordId)
	{
		$childMouleWhereQuery = "";
		if ($match[$loopcount]) {
			$childConditions = str_replace('$loop-child$', '', strip_tags($match[$loopcount][0]));
			if (trim($childConditions)) {
				// 子モジュールの条件を設定する場合[FUNCTION|子モジュール項目名|=|値|FUNCTION]
				return self::getChildReferenceRecordCountWithConditions($parentModuleName, $childConditions, $childModuleName, $childModuleReferenceColumn, $recordId);
			}
		}
		// 条件を指定せずに子モジュールを取得する。
		$referenceRecordCount = Vtiger_Functions::getChildReferenceRecordCount($parentModuleName, $childModuleReferenceColumn, $childModuleName, $recordId);
		return $referenceRecordCount;
	}

	private static function getChildReferenceRecordIdWithConditions($parentModuleName, $childConditions, $childModuleName, $childModuleReferenceColumn, $recordId, $looplimit = "", $startcount, $limitcount)
	{
		// 子モジュールの条件を設定する場合[FUNCTION|子モジュール項目名|=|値|FUNCTION]
		$allowOperator = array("==" => "=", "===" => "=", "<=>" => "<=>", "<>" => "<>", "!=" => "!=", "!==" => "!=", "<" => "<", "<=" => "<=", ">" => ">", ">=" => ">=", "contain" => "LIKE", "notcontain" => "NOT LIKE");
		$keywordfrom = "[FUNCTION|";
		$keywordto = "|FUNCTION]";
		$pattern = '/(?<=' . preg_quote($keywordfrom, "/") . ').*?(?=' . preg_quote($keywordto, "/") . ')/';
		preg_match_all($pattern, $childConditions, $matchChildCondition);
		$childMouleWhereQuery = "";
		$childMouleSortQuery = "";
		foreach ($matchChildCondition[0] as $key => $matchChildConditionValue) {
			$matchingvaluearray = explode("|", $matchChildConditionValue);
			$ifcount = php7_count($matchingvaluearray);
			$ifjoincount = floor($ifcount / 4);
			$functionname = $matchingvaluearray[0];

			switch ($functionname) {
				case 'loop-child_where':
					for ($i = 0; $i < $ifjoincount; $i++) {
						$matchingvalue = str_replace("​", "", str_replace('$', '', $matchingvaluearray[$i * 4 + 1]));
						$matchingReferenceValue = explode('-', $matchingvalue)[1];
						$columnname = explode(':', $matchingReferenceValue)[1]; // 子モジュール項目名

						if ($allowOperator[$matchingvaluearray[$i * 4 + 2]]) {
							$operator = $allowOperator[$matchingvaluearray[$i * 4 + 2]]; // 比較演算子　$allowOperatorに含まれているものだけを使用する。
							$addstring = "";
							if (in_array($operator, array("LIKE", "NOT LIKE"))) {
								$addstring = "%";
							}
							$comparisonDestination = html_entity_decode($matchingvaluearray[$i * 4 + 3]); // 比較する値
							if ($i > 0) {
								$andor = $matchingvaluearray[$i * 4];
							}
							switch ($andor) {
								case 'AND':
									$childMouleWhereQuery .= " AND " . getTableNameForField($childModuleName, $columnname) . "." . $columnname . " " . $operator . " '$addstring" . $comparisonDestination . "$addstring' ";
									break;
								case 'OR':
									$childMouleWhereQuery .= " OR " . getTableNameForField($childModuleName, $columnname) . "." . $columnname .  " " . $operator . " '$addstring" . $comparisonDestination . "$addstring' ";
									break;
								default:
									$childMouleWhereQuery .= " AND (" . getTableNameForField($childModuleName, $columnname) . "." . $columnname .  " " . $operator . " '$addstring" . $comparisonDestination . "$addstring' ";
									break;
							}
						}
					}
					$childMouleWhereQuery .= " ) ";
					break;
				case 'loop-child_sortorder':
					$sortcolumnname = $matchingvaluearray[1];
					$matchingvalue = str_replace("​", "", str_replace('$', '', $sortcolumnname));
					$matchingReferenceValue = explode('-', $matchingvalue)[1];
					$columnname = explode(':', $matchingReferenceValue)[1]; // 子モジュール項目名

					$sortorder = $matchingvaluearray[2];

					$childMouleSortQuery .= " ORDER BY " . getTableNameForField($childModuleName, $columnname) . "." . $columnname . " ";
					if (in_array($sortorder, array("ASC", "DESC", "asc", "desc"))) {
						$childMouleSortQuery .= $sortorder . " ";
					}
					break;
				default:
					# code...
					break;
			}
		}

		// 条件を指定して子モジュールを取得する。
		$referenceRecordIds = Vtiger_Functions::getChildReferenceRecordId($parentModuleName, $childModuleReferenceColumn, $childModuleName, $recordId, $childMouleWhereQuery, $childMouleSortQuery, $looplimit, $startcount, $limitcount);
		return $referenceRecordIds;
	}

	private static function getChildReferenceRecordCountWithConditions($parentModuleName, $childConditions, $childModuleName, $childModuleReferenceColumn, $recordId)
	{
		// 子モジュールの条件を設定する場合[FUNCTION|子モジュール項目名|=|値|FUNCTION]
		$allowOperator = array("==" => "=", "===" => "=", "<=>" => "<=>", "<>" => "<>", "!=" => "!=", "!==" => "!=", "<" => "<", "<=" => "<=", ">" => ">", ">=" => ">=", "contain" => "LIKE", "notcontain" => "NOT LIKE");
		$keywordfrom = "[FUNCTION|";
		$keywordto = "|FUNCTION]";
		$pattern = '/(?<=' . preg_quote($keywordfrom, "/") . ').*?(?=' . preg_quote($keywordto, "/") . ')/';
		preg_match_all($pattern, $childConditions, $matchChildCondition);
		$childMouleWhereQuery = "";
		$childMouleSortQuery = "";
		foreach ($matchChildCondition[0] as $key => $matchChildConditionValue) {
			$matchingvaluearray = explode("|", $matchChildConditionValue);
			$ifcount = php7_count($matchingvaluearray);
			$ifjoincount = floor($ifcount / 4);
			$functionname = $matchingvaluearray[0];

			switch ($functionname) {
				case 'loop-child_where':
					for ($i = 0; $i < $ifjoincount; $i++) {
						$matchingvalue = str_replace("​", "", str_replace('$', '', $matchingvaluearray[$i * 4 + 1]));
						$matchingReferenceValue = explode('-', $matchingvalue)[1];
						$columnname = explode(':', $matchingReferenceValue)[1]; // 子モジュール項目名

						if ($allowOperator[$matchingvaluearray[$i * 4 + 2]]) {
							$operator = $allowOperator[$matchingvaluearray[$i * 4 + 2]]; // 比較演算子　$allowOperatorに含まれているものだけを使用する。
							$addstring = "";
							if (in_array($operator, array("LIKE", "NOT LIKE"))) {
								$addstring = "%";
							}
							$comparisonDestination = html_entity_decode($matchingvaluearray[$i * 4 + 3]); // 比較する値
							if ($i > 0) {
								$andor = $matchingvaluearray[$i * 4];
							}
							switch ($andor) {
								case 'AND':
									$childMouleWhereQuery .= " AND " . getTableNameForField($childModuleName, $columnname) . "." . $columnname . " " . $operator . " '$addstring" . $comparisonDestination . "$addstring' ";
									break;
								case 'OR':
									$childMouleWhereQuery .= " OR " . getTableNameForField($childModuleName, $columnname) . "." . $columnname .  " " . $operator . " '$addstring" . $comparisonDestination . "$addstring' ";
									break;
								default:
									$childMouleWhereQuery .= " AND (" . getTableNameForField($childModuleName, $columnname) . "." . $columnname .  " " . $operator . " '$addstring" . $comparisonDestination . "$addstring' ";
									break;
							}
						}
					}
					$childMouleWhereQuery .= " ) ";
					break;
				default:
					# code...
					break;
			}
		}

		// 条件を指定して子モジュールを取得する。
		$referenceRecordCount = Vtiger_Functions::getChildReferenceRecordCount($parentModuleName, $childModuleReferenceColumn, $childModuleName, $recordId, $childMouleWhereQuery, $childMouleSortQuery);
		return $referenceRecordCount;
	}

	// 製品サービスの値反映
	private static function getMergedProductsDescription($template, $moduleName, $recordId)
	{
		$blocks = preg_split('/<tr((?:(?!<).)*)>((?:(?!<tr).)*)\$loop-products\$.*?<\/tr>/is', $template);

		$prefix = strtolower($moduleName);
		$parentRecordModel = Vtiger_Record_Model::getInstanceById($recordId);

		$relatedProducts = null;
		if($parentRecordModel instanceof Inventory_Record_Model) {
			$relatedProducts = $parentRecordModel->getProducts();
		}

		$cnt = 0;
		$description = array();
		foreach ($blocks as $block) {
			if ($cnt % 2 == 1) {
				//奇数の場合（ブロック内）
				$block = self::getMergeInventoryBlock($block, $relatedProducts, $prefix);
			}
			$block = preg_replace('/\$' . $prefix . '-discount_amount\$/', self::getDiscountTotal($relatedProducts), $block);
			$block = preg_replace('/\$' . $prefix . '-discount_amount_final\$/', self::getDiscountTotal_Final($relatedProducts), $block);
			$block = preg_replace('/\$' . $prefix . '-pre_tax_total\$/', self::getPreTaxTotal($relatedProducts), $block);
			$block = preg_replace('/\$' . $prefix . '-total\$/', self::getTotalWithTax($relatedProducts), $block);
			$block = preg_replace('/\$' . $prefix . '-tax_totalamount\$/', self::getTotalTax($relatedProducts), $block);
			$block = getMergedDescription($block, $recordId, $moduleName);
			$description[] = $block;

			$cnt += 1;
		}

		$description = implode("", $description);

		//消費税内訳の作成
		$description_details = array();
		$blocks_details = preg_split('/<tr((?:(?!<).)*)>((?:(?!<tr).)*)\$loop-details\$.*?<\/tr>/is', $description);
		$cnt = 0;
		foreach($blocks_details as $block){
			if($cnt % 2 == 1) {
				//奇数の場合（ブロック内）
				$block = self::getMergeTaxInfoBlock($block, $relatedProducts, $prefix);			
			}
			$description_details[] = $block;
			$cnt += 1;
		}

		$returnstring = implode("", $description_details);
		return $returnstring;
	}

	private static function getMergeInventoryBlock($blocktemplate, $relatedProducts, $prefix) {
		if(empty($relatedProducts)) {
			return '';
		}
		$convertedArray = array();
		$cnt = 1;
		foreach($relatedProducts as $key => $product) {
			$block = $blocktemplate;
			$product = self::getPDFDisplayValue($product, $cnt);
			foreach($product as $name => $value) {
				if(is_array($value)) {
					continue;
				}
				$fieldname = strtolower($name);
				$fieldname = preg_replace('/'.$cnt.'$/', '', $fieldname);
				$fieldname = self::convertFieldName($fieldname);

				// 改行が適用されるように修正
				$value = nl2br($value);

				$block = preg_replace('/\$'.$prefix.'\-'.$fieldname.'\$/', $value, $block);
			}
			$convertedArray[] = $block;
			$cnt += 1;
		}
		return implode("\n", $convertedArray);
	}

	private static function convertFieldName($fieldname) {
		$name = $fieldname;
		if($name == 'productname') {
			$name = 'productid';
		}
		else if($name == 'qty') {
			$name = 'quantity';
		}
		else if($name == 'discount_amount'){
			$name = "discount_itemamount";
		}
		else if($name == 'discount_percent'){
			$name = "discount_itempercent";
		}
		else if($name == 'purchasecost'){
			$name = "purchase_cost";
		}
		return $name;
	}

	private static function getPDFDisplayValue($product, $number) {
		$currencyFieldsList = array('taxTotal', 'netPrice', 'listPrice', 'unitPrice', 'productTotal','purchaseCost','margin',
									'discountTotal', 'discount_amount', 'totalAfterDiscount');

		foreach ($currencyFieldsList as $fieldName) {
			$value = $product[$fieldName.$number];
			// if($fieldName == 'discountTotal' || $fieldName == 'discount_amount' || $fieldName == 'totalAfterDiscount') {
			// 	$value = '-'.$value;
			// }
			$currencyField = new CurrencyField($value);
			$product[$fieldName.$number] = $currencyField->getDisplayValueWithSymbol();
		}

		$percentageFieldsList = array('discount_percent',);

		foreach ($percentageFieldsList as $fieldName) {
			$percentageField = new Vtiger_Percentage_UIType(array($product[$fieldName.$number]));
			$value = $percentageField->getDisplayValue($product[$fieldName.$number]);
			if(preg_match('/^[0-9.,]*$/', $value)) {
				$value .= '%';
			}
			$product[$fieldName.$number] = $value;
		}

		//軽減税率対象のとき、マークをつける
		$reducedtaxrateList = array('reducedtaxrate',);
		foreach ($reducedtaxrateList as $fieldName) {
			$value = $product[$fieldName.$number];
			if($value == "1"){
				$value = "*";
			}else{
				$value = NULL;
			}
			$product[$fieldName.$number] = $value;
		}		
	
		return $product;
	}

	//内訳を作成するために追加した変数をPDFテンプレートで表示できるように設定
	private static function getMergeTaxInfoBlock($blocktemplate, $relatedProducts, $prefix) {
		$convertedArray = array();
		$cnt = 1;
		foreach($relatedProducts[1]['final_details']['taxinfo_invoice'] as $key => $info) {
			if($key != "0.000"){
				$block = $blocktemplate;
				$info = self::getPDFDisplayValuefortax($info, $cnt);
				foreach($info as $name => $value) {
					if(is_array($value)) {
						continue;
					}
					$fieldname = strtolower($name);
					$fieldname = preg_replace('/'.$cnt.'$/', '', $fieldname);
					// 改行が適用されるように修正
					$value = nl2br($value);
					$block = preg_replace('/\$'.$prefix.'\-'.$fieldname.'\$/', $value, $block);
				}
				$convertedArray[] = $block;
				$cnt += 1;
			}	
		}
		return implode("\n", $convertedArray);
	}
	private static function getPDFDisplayValuefortax($info, $number) {
		$currencyFieldsList = array('taxtotal', 'subtotalpertax');
		foreach ($currencyFieldsList as $fieldName) {
			$value = $info[$fieldName];
			// if($fieldName == 'discountTotal' || $fieldName == 'discount_amount' || $fieldName == 'totalAfterDiscount') {
			// 	$value = '-'.$value;
			// }
			$currencyField = new CurrencyField($value);
			$info[$fieldName] = $currencyField->getDisplayValueWithSymbol();
		}
		$percentageFieldsList = array('percentage',);

		foreach ($percentageFieldsList as $fieldName) {
			$percentageField = new Vtiger_Percentage_UIType(array($info[$fieldName]));
			$value = $percentageField->getDisplayValue($info[$fieldName]);
			if(preg_match('/^[0-9.,]*$/', $value)) {
				$value .= '%';
			}
			$info[$fieldName] = $value;
		}
		return $info;
	}
	
	private static function getDiscountTotal($relatedProducts) {
		if(empty($relatedProducts)) {
			return '';
		}
		$discount = 0;
		foreach($relatedProducts as $product) {
			foreach($product as $key => $value) {
				if(preg_match('/discountTotal[0-9]+/', $key)) {
					$discount += intval($value);
				}
			}
		}
		$currencyField = new CurrencyField($discount);
		$discount = $currencyField->getDisplayValueWithSymbol();

		return $discount;
	}

	private static function getDiscountTotal_Final($relatedProducts){
		$discountTotal = $relatedProducts[1]['final_details']['discountTotal_final'];
		$currencyField = new CurrencyField($discountTotal);
		$discountTotal = $currencyField->getDisplayValueWithSymbol();

		return  $discountTotal;
	}

	private static function getPreTaxTotal($relatedProducts) {
		if(empty($relatedProducts)) {
			return '';
		}
		$preTotal = $relatedProducts[1]['final_details']['preTaxTotal'];
		if($relatedProducts[1]['final_details']['taxtype'] == 'individual'){
			$preTotal -= $relatedProducts[1]['final_details']['tax_totalamount'];
		}
		$currencyField = new CurrencyField($preTotal);
		$preTotal = $currencyField->getDisplayValueWithSymbol();

		return $preTotal;
	}

	private static function getTotalTax($relatedProducts) {
		if(empty($relatedProducts)) {
			return '';
		}
		$tax = $relatedProducts[1]['final_details']['tax_totalamount'];
		$currencyField = new CurrencyField($tax);
		$tax = $currencyField->getDisplayValueWithSymbol();

		return $tax;
	}

	private static function getTotalWithTax($relatedProducts) {
		if(empty($relatedProducts)) {
			return '';
		}
		$preTotal = $relatedProducts[1]['final_details']['preTaxTotal'];
		if($relatedProducts[1]['final_details']['taxtype'] == 'individual'){
			$preTotal -= $relatedProducts[1]['final_details']['tax_totalamount'];
		}
		$tax = $relatedProducts[1]['final_details']['tax_totalamount'];
		$adjustment = $relatedProducts[1]['final_details']['adjustment'];
		$currencyField = new CurrencyField($preTotal + $tax + $adjustment);
		$total = $currencyField->getDisplayValueWithSymbol();

		return $total;
	}

	public static function applyingAggrFunctions($template, $recordId, $moduleName)
	{
		// 関数の処理を行う
		// 関数の取得
		$keywordfrom = "[FUNCTION|";
		$keywordto = "|FUNCTION]";
		// $pattern = '/(?<=' . preg_quote($keywordfrom) . ').*?(?=' . preg_quote($keywordto) . ')/';
		$pattern = '/(?<=' . preg_quote($keywordfrom, "/") . ').*(?=' . preg_quote($keywordto, "/") . ')/';
		preg_match_all($pattern, $template, $match);

		foreach ($match[0] as $arraykey => $matchingvalue) {
			$oldmatchingvalue = $matchingvalue;
			if(strpos($matchingvalue, '[FUNCTION|') !== false){
				$matchingvalue = Vtiger_InventoryPDFController::applyingAggrFunctions($matchingvalue, $recordId, $moduleName);
				if($oldmatchingvalue == $matchingvalue) return $template;
				
				$template = str_replace($oldmatchingvalue, $matchingvalue, $template);
			}

			$matchingvaluearray = explode("|", strip_tags($matchingvalue));
			$functionname = $matchingvaluearray[0]; // 例 関数名
			switch ($functionname) {
				case 'aggset_sum':
					$returnvalue = self::customFunction_aggset_sum($matchingvaluearray, $recordId);
					$template = str_replace($keywordfrom . $matchingvalue . $keywordto, $returnvalue, $template);
					break;
				case 'aggset_average':
					$returnvalue = self::customFunction_aggset_average($matchingvaluearray, $recordId);
					$template = str_replace($keywordfrom . $matchingvalue . $keywordto, $returnvalue, $template);
					break;
				case 'aggset_min':
					$returnvalue = self::customFunction_aggset_min($matchingvaluearray, $recordId);
					$template = str_replace($keywordfrom . $matchingvalue . $keywordto, $returnvalue, $template);
					break;
				case 'aggset_max':
					$returnvalue = self::customFunction_aggset_max($matchingvaluearray, $recordId);
					$template = str_replace($keywordfrom . $matchingvalue . $keywordto, $returnvalue, $template);
					break;
				default:
					# code...
					break;
			}
		}

		return $template;
	}

	public static function applyingFunctions($template, $recordId, $moduleName)
	{
		// 関数の処理を行う
		// 関数の取得
		$keywordfrom = "[FUNCTION|";
		$keywordto = "|FUNCTION]";
		// $pattern = '/(?<=' . preg_quote($keywordfrom) . ').*?(?=' . preg_quote($keywordto) . ')/';
		$pattern = '/(?<=' . preg_quote($keywordfrom, "/") . ').*(?=' . preg_quote($keywordto, "/") . ')/';
		preg_match_all($pattern, $template, $match);

		foreach ($match[0] as $arraykey => $matchingvalue) {
			$oldmatchingvalue = $matchingvalue;
			if(strpos($matchingvalue, '[FUNCTION|') !== false){
				$matchingvalue = Vtiger_InventoryPDFController::applyingFunctions($matchingvalue, $recordId, $moduleName);
				if($oldmatchingvalue == $matchingvalue) return $template;
				
				$template = str_replace($oldmatchingvalue, $matchingvalue, $template);
			}


			$matchingvaluearray = explode("|", strip_tags($matchingvalue));
			$functionname = $matchingvaluearray[0]; // 例 関数名
			$comparator = $matchingvaluearray[1]; // 例 項目名
			$param2 = $matchingvaluearray[2];
			$param3 = $matchingvaluearray[3];
			$param4 = $matchingvaluearray[4];
			$param5 = $matchingvaluearray[5];

			switch ($functionname) {
				case 'if':
					$returnvalue = self::customFunction_if($comparator, $param2, $param3, $param4, $param5);
					$template = str_replace($keywordfrom . $matchingvalue . $keywordto, $returnvalue, $template);
					break;
				
				case 'datefmt':
					$returnvalue = self::customFunction_datefmt($comparator, $param2);
					$template = str_replace($keywordfrom . $matchingvalue . $keywordto, $returnvalue, $template);
					break;

				case 'strreplace':
					$returnvalue = self::customFunction_strreplace($comparator, $param2, $param3);
					$template = str_replace($keywordfrom . $matchingvalue . $keywordto, $returnvalue, $template);
					break;
				case 'ifs':
					$returnvalue = self::customFunction_ifs($matchingvaluearray);
					$template = str_replace($keywordfrom . $matchingvalue . $keywordto, $returnvalue, $template);
					break;
				default:
					# code...
					break;
			}
		}

		return $template;
	}


	private static function customFunction_if($comparator, $operator, $comparisonDestination, $iftrue, $iffalse)
	{

		$comparator = html_entity_decode(strip_tags($comparator));
		$operator = strip_tags($operator);
		$comparisonDestination = html_entity_decode(strip_tags($comparisonDestination));
		if($iftrue !== true){
			$iftrue = strip_tags($iftrue);
		}
		if($iffalse !== false){
			$iffalse = strip_tags($iffalse);
		}

		switch (html_entity_decode($operator)) {
			case '==':
				return $comparator == $comparisonDestination ? $iftrue : $iffalse;
				break;

			case '===':
				return $comparator === $comparisonDestination ? $iftrue : $iffalse;
				break;
			case '!=':
				return $comparator != $comparisonDestination ? $iftrue : $iffalse;
				break;

			case '<>':
				return $comparator <> $comparisonDestination ? $iftrue : $iffalse;
				break;

			case '!==':
				return $comparator !== $comparisonDestination ? $iftrue : $iffalse;
				break;

			case '<':
				return $comparator < $comparisonDestination ? $iftrue : $iffalse;
				break;

			case '>':
				return $comparator > $comparisonDestination ? $iftrue : $iffalse;
				break;

			case '<=':
				return $comparator <= $comparisonDestination ? $iftrue : $iffalse;
				break;

			case '>=':
				return $comparator >= $comparisonDestination ? $iftrue : $iffalse;
				break;

			case '<=>':
				return $comparator <=> $comparisonDestination ? $iftrue : $iffalse;
				break;

			case 'contain':
				if (version_compare(phpversion(), '8.0.0') >= 0) {
					str_contains($comparator, $comparisonDestination) ? $iftrue : $iffalse;
				} else {
					return strpos($comparator, $comparisonDestination) !== false ? $iftrue : $iffalse;
				}
				break;
			case 'notcontain':
				if (version_compare(phpversion(), '8.0.0') >= 0) {
					!str_contains($comparator, $comparisonDestination) ? $iftrue : $iffalse;
				} else {
					return strpos($comparator, $comparisonDestination) === false ? $iftrue : $iffalse;
				}
				break;
			default:
				return $iftrue;
				break;
		}
	}

	private static function customFunction_ifs($matchingvaluearray){
		$ifcount = php7_count($matchingvaluearray);
		$ifjoincount = floor(($ifcount - 2) / 4);
		$iftrue = $matchingvaluearray[$ifcount - 2];
		$iffalse = $matchingvaluearray[$ifcount - 1];

		$returnresult = "";
		for ($i = 0; $i < $ifjoincount; $i++) {
			$columnname = $matchingvaluearray[$i * 4 + 1]; // 例 項目名
			$operator = $matchingvaluearray[$i * 4 + 2];
			$comparisonDestination = html_entity_decode($matchingvaluearray[$i * 4 + 3]);
			$andor = $matchingvaluearray[$i * 4 + 4];
			$ifsreturnvalue = self::customFunction_if($columnname, $operator, $comparisonDestination, true, false);
			if ($returnresult !== "") {
				switch ($andor) {
					case 'AND':
						$returnresult = $returnresult && $ifsreturnvalue;
						break;
					case 'OR':
						$returnresult = $returnresult || $ifsreturnvalue;
						break;
					default:
						# code...
						break;
				}
			} else {
				$returnresult = $ifsreturnvalue;
			}
		}

		if ($returnresult) {
			return $iftrue;
		} else {
			return $iffalse;
		}
	}

	// 比較元の文字列の型を指定
	private static function convertComparator($comparator, $type)
	{
		switch (strip_tags($type)) {
			case 'int':
				// 数値型の場合、数値のみ抽出する(￥10,000⇒10000)
				return intval(preg_replace('/[^0-9０-９]/', '', strip_tags($comparator)));
				break;
			default:
				return $comparator;
				break;
		}
	}

	private static function customFunction_datefmt($comparator, $dateformatstring)
	{
		return date(strip_tags($dateformatstring), strtotime(strip_tags($comparator)));
	}

	private static function customFunction_strreplace($comparator, $pattern, $replacement){
		return preg_replace('/'.strip_tags($pattern).'/', strip_tags($replacement), strip_tags($comparator));
	}

	private static function customFunction_aggset_sum($matchingvaluearray, $recordId)
	{
		$ifcount = php7_count($matchingvaluearray);
		$ifjoincount = floor(($ifcount - 1) / 4);

		$aggrcolumnname = $matchingvaluearray[1]; // 集計対象の項目名

		$referenceRecordIds = self::getChildReferenceRecordIdForAggrFunction($ifjoincount, $matchingvaluearray, $recordId);

		return self::getAggsetSum($referenceRecordIds, $aggrcolumnname);
	}

	private static function customFunction_aggset_average($matchingvaluearray, $recordId)
	{
		$ifcount = php7_count($matchingvaluearray);
		$ifjoincount = floor(($ifcount - 1) / 4);

		$aggrcolumnname = $matchingvaluearray[1]; // 集計対象の項目名

		$referenceRecordIds = self::getChildReferenceRecordIdForAggrFunction($ifjoincount, $matchingvaluearray, $recordId);

		return self::getAggsetAverage($referenceRecordIds, $aggrcolumnname);
	}

	private static function customFunction_aggset_min($matchingvaluearray, $recordId)
	{
		$ifcount = php7_count($matchingvaluearray);
		$ifjoincount = floor(($ifcount - 1) / 4);

		$aggrcolumnname = $matchingvaluearray[1]; // 集計対象の項目名

		$referenceRecordIds = self::getChildReferenceRecordIdForAggrFunction($ifjoincount, $matchingvaluearray, $recordId);

		return self::getAggsetMin($referenceRecordIds, $aggrcolumnname);
	}

	private static function customFunction_aggset_max($matchingvaluearray, $recordId)
	{
		$ifcount = php7_count($matchingvaluearray);
		$ifjoincount = floor(($ifcount - 1) / 4);

		$aggrcolumnname = $matchingvaluearray[1]; // 集計対象の項目名

		$referenceRecordIds = self::getChildReferenceRecordIdForAggrFunction($ifjoincount, $matchingvaluearray, $recordId);

		return self::getAggsetMax($referenceRecordIds, $aggrcolumnname);
	}

	private static function getChildReferenceRecordIdForAggrFunction($ifjoincount, $matchingvaluearray, $recordId){
		$childMouleWhereQuery = "";
		$allowOperator = array("==" => "=", "===" => "=", "<=>" => "<=>", "<>" => "<>", "!=" => "!=", "!==" => "!=", "<" => "<", "<=" => "<=", ">" => ">", ">=" => ">=", "contain" => "LIKE", "notcontain" => "NOT LIKE");
		if($ifjoincount > 0){
			for ($i = 0; $i < $ifjoincount; $i++) {
				if($i > 0){
					$andor = $matchingvaluearray[$i * 4 + 1];
				}
				$matchingvalue = $matchingvaluearray[$i * 4 + 2]; // 条件設定用の項目名
				$operator = $matchingvaluearray[$i * 4 + 3];
				$comparisonDestination = html_entity_decode($matchingvaluearray[$i * 4 + 4]);
	
				$matchingvalue = str_replace("​", "", str_replace('$', '', $matchingvalue));
				list($childModuleName, $matchingReferenceValue) = explode('-', $matchingvalue);
				list($childModuleReferenceColumn, $columnname) = explode(':', $matchingReferenceValue);
				preg_match('/(?<=\[).*?(?=\])/', $childModuleName, $childmatches);
				$parentModuleName = $childmatches[0];
				$childModuleName = str_replace("[$parentModuleName]", '', $childModuleName);
	
				// 子モジュールのレコードIDを取得。
				if ($allowOperator[$operator]) {
					$operator = $allowOperator[$operator]; // 比較演算子　$allowOperatorに含まれているものだけを使用する。
					$addstring = "";
					if (in_array($operator, array("LIKE", "NOT LIKE"))) {
						$addstring = "%";
					}
					switch ($andor) {
						case 'AND':
							$childMouleWhereQuery .= " AND " . getTableNameForField($childModuleName, $columnname) . "." . $columnname . " " . $operator . " '$addstring" . $comparisonDestination . "$addstring' ";
							break;
						case 'OR':
							$childMouleWhereQuery .= " OR " . getTableNameForField($childModuleName, $columnname) . "." . $columnname .  " " . $operator . " '$addstring" . $comparisonDestination . "$addstring' ";
							break;
						default:
							$childMouleWhereQuery .= " AND (" . getTableNameForField($childModuleName, $columnname) . "." . $columnname .  " " . $operator . " '$addstring" . $comparisonDestination . "$addstring' ";
							break;
					}
				}
			}
			$childMouleWhereQuery .= " ) ";
			// 条件を指定して子モジュールを取得する。
			$referenceRecordIds = Vtiger_Functions::getChildReferenceRecordId($parentModuleName, $childModuleReferenceColumn, $childModuleName, $recordId, $childMouleWhereQuery);
		}else{
			$matchingvalue = $matchingvaluearray[1]; // 条件設定用の項目名
			// $operator = $matchingvaluearray[3];
			// $comparisonDestination = $matchingvaluearray[4];

			$matchingvalue = str_replace("​", "", str_replace('$', '', $matchingvalue));
			list($childModuleName, $matchingReferenceValue) = explode('-', $matchingvalue);
			list($childModuleReferenceColumn, $columnname) = explode(':', $matchingReferenceValue);
			preg_match('/(?<=\[).*?(?=\])/', $childModuleName, $childmatches);
			$parentModuleName = $childmatches[0];
			$childModuleName = str_replace("[$parentModuleName]", '', $childModuleName);

			//条件未設定の場合
			$referenceRecordIds = Vtiger_Functions::getChildReferenceRecordId($parentModuleName, $childModuleReferenceColumn, $childModuleName, $recordId);
		}
		return $referenceRecordIds;
	}

	private static function getAggsetSum($referenceRecordIds, $agg_columnname)
	{
		$agg_columnname = strip_tags(str_replace("​", "", str_replace('$', '', $agg_columnname)));
		list($childModuleName, $columnNameChild) = explode('-', $agg_columnname);
		list($childModuleReferenceColumn, $childModuleColumnName) = explode(':', $columnNameChild);
		preg_match('/(?<=\[).*?(?=\])/', $childModuleName, $childmatches);
		$parentModuleName = $childmatches[0];
		$childModuleName = str_replace("[$parentModuleName]", '', $childModuleName);
		$resultvalue = array();
		foreach ($referenceRecordIds as $key => $recordid) {
			$childModuleRecordModel = Vtiger_Record_Model::getInstanceById($recordid, $childModuleName);
			$resultvalue[]= self::convertComparator($childModuleRecordModel->get($childModuleColumnName), 'int');
		}
		$resultvalue = array_sum($resultvalue);

		$modulemodel = Vtiger_Module::getInstance($childModuleName);
		$field = Vtiger_Field_Model::getInstance($childModuleColumnName, $modulemodel);
		$fieldtype = $field->getFieldDataType();
		if($fieldtype == 'currency'){
			$currencyField = new CurrencyField($resultvalue);
			return $currencyField->getDisplayValueWithSymbol();
		}else{
			$fieldmodel = $modulemodel->getField($childModuleColumnName);
			return $fieldmodel->getDisplayValue(null, true);
		}
	}

	private static function getAggsetAverage($referenceRecordIds, $agg_columnname)
	{
		$agg_columnname = strip_tags(str_replace("​", "", str_replace('$', '', $agg_columnname)));
		list($childModuleName, $columnNameChild) = explode('-', $agg_columnname);
		list($childModuleReferenceColumn, $childModuleColumnName) = explode(':', $columnNameChild);
		preg_match('/(?<=\[).*?(?=\])/', $childModuleName, $childmatches);
		$parentModuleName = $childmatches[0];
		$childModuleName = str_replace("[$parentModuleName]", '', $childModuleName);
		$resultvalue = array();
		foreach ($referenceRecordIds as $key => $recordid) {
			$childModuleRecordModel = Vtiger_Record_Model::getInstanceById($recordid, $childModuleName);
			$resultvalue[] = self::convertComparator($childModuleRecordModel->get($childModuleColumnName), 'int');
		}

		$resultvalue = array_sum($resultvalue) / php7_count($resultvalue);

		$modulemodel = Vtiger_Module::getInstance($childModuleName);
		$field = Vtiger_Field_Model::getInstance($childModuleColumnName, $modulemodel);
		$fieldtype = $field->getFieldDataType();
		if($fieldtype == 'currency'){
			$currencyField = new CurrencyField($resultvalue);
			return $currencyField->getDisplayValueWithSymbol();
		}else{
			$fieldmodel = $modulemodel->getField($childModuleColumnName);
			return $fieldmodel->getDisplayValue(null, true);
		}
	}

	private static function getAggsetMin($referenceRecordIds, $agg_columnname)
	{
		$agg_columnname = strip_tags(str_replace("​", "", str_replace('$', '', $agg_columnname)));
		list($childModuleName, $columnNameChild) = explode('-', $agg_columnname);
		list($childModuleReferenceColumn, $childModuleColumnName) = explode(':', $columnNameChild);
		preg_match('/(?<=\[).*?(?=\])/', $childModuleName, $childmatches);
		$parentModuleName = $childmatches[0];
		$childModuleName = str_replace("[$parentModuleName]", '', $childModuleName);
		$resultvalue = array();
		foreach ($referenceRecordIds as $key => $recordid) {
			$childModuleRecordModel = Vtiger_Record_Model::getInstanceById($recordid, $childModuleName);
			$resultvalue[] = self::convertComparator($childModuleRecordModel->get($childModuleColumnName), 'int');
		}
		$resultvalue = min($resultvalue);

		$modulemodel = Vtiger_Module::getInstance($childModuleName);
		$field = Vtiger_Field_Model::getInstance($childModuleColumnName, $modulemodel);
		$fieldtype = $field->getFieldDataType();
		if($fieldtype == 'currency'){
			$currencyField = new CurrencyField($resultvalue);
			return $currencyField->getDisplayValueWithSymbol();
		}else{
			$fieldmodel = $modulemodel->getField($childModuleColumnName);
			return $fieldmodel->getDisplayValue(null, true);
		}
	}

	private static function getAggsetMax($referenceRecordIds, $agg_columnname)
	{
		$agg_columnname = strip_tags(str_replace("​", "", str_replace('$', '', $agg_columnname)));
		list($childModuleName, $columnNameChild) = explode('-', $agg_columnname);
		list($childModuleReferenceColumn, $childModuleColumnName) = explode(':', $columnNameChild);
		preg_match('/(?<=\[).*?(?=\])/', $childModuleName, $childmatches);
		$parentModuleName = $childmatches[0];
		$childModuleName = str_replace("[$parentModuleName]", '', $childModuleName);
		$resultvalue = array();
		foreach ($referenceRecordIds as $key => $recordid) {
			$childModuleRecordModel = Vtiger_Record_Model::getInstanceById($recordid, $childModuleName);
			$resultvalue[] = self::convertComparator($childModuleRecordModel->get($childModuleColumnName), 'int');
		}
		$resultvalue = max($resultvalue);

		$modulemodel = Vtiger_Module::getInstance($childModuleName);
		$field = Vtiger_Field_Model::getInstance($childModuleColumnName, $modulemodel);
		$fieldtype = $field->getFieldDataType();
		if($fieldtype == 'currency'){
			$currencyField = new CurrencyField($resultvalue);
			return $currencyField->getDisplayValueWithSymbol();
		}else{
			$fieldmodel = $modulemodel->getField($childModuleColumnName);
			return $fieldmodel->getDisplayValue(null, true);
		}
	}

	public static function applyingSpecialSymbol($template)
	{
		$specialsymbols = array("#BR#" => "<br />", "#HIDETR#" => "#HIDETR#");
		foreach ($specialsymbols as $before => $after) {
			if ($before == "#HIDETR#") {
				$template = self::removetr($template);
			} else {
				$template = str_replace($before, $after, $template);
			}
		}
		return $template;
	}

	private static function removetr($template)
	{
		$keywordfrom = "<tr";
		$keywordto = "</tr>";
		$pattern = '/' . preg_quote($keywordfrom, "/") . '.*?\>.*?' . preg_quote($keywordto, "/") . '/is';

		preg_match_all($pattern, $template, $match);
		foreach ($match[0] as $arraykey => $matchingvalue) {
			if (strpos($matchingvalue, '#HIDETR#') !== false) {
				$template = str_replace($matchingvalue, "", $template);
			}
		}
		return $template;
	}

	public static function checkLoopPerPageCount($template, $recordId)
	{
		$childblocks = preg_split('/<tr((?:(?!<).)*)>((?:(?!<tr).)*)\$loop-child\$.*?<\/tr>/is', $template);

		// $loop-per-page-x$ 1ページに表示する子レコードの最大値を設定　最大値を超えた場合は同じフォーマットで2ページ目が生成される。
		preg_match_all('/(?<=' . preg_quote('loop-per-page-', "/") . ').*?(?=' . preg_quote('$', "/") . ')/', $template, $perpagematch);
		$loopperpage = $perpagematch[0][0];
		if(!$loopperpage) return 1;
		// $template = preg_replace('/\$loop-per-page-.*\$/', '', $template);

		preg_match_all('/\$loop-child\$.*/', $template, $match);

		$childcnt = 0;
		$loopcount = 0;
		$childrecordcount = 0;
		foreach ($childblocks as $key => $childblock) {
			if ($childcnt % 2 == 1) {
				//奇数の場合（ブロック内）	
				$resultchild = preg_match_all("/\\$\[(?:[a-zA-Z0-9]+)\](?:[a-zA-Z0-9]+)-(?:[a-zA-Z0-9]+)(?:_[a-zA-Z0-9]+)?(?::[a-zA-Z0-9]+)?(?:_[a-zA-Z0-9]+)*\\$/", $childblock, $matcheschild);
				if ($resultchild != 0) {
					$templateVariablePairChild = $matcheschild[0];
					for ($i = 0; $i < php7_count($templateVariablePairChild); $i++) {
						$templateVariablePairChild[$i] = str_replace("​", "", str_replace('$', '', $templateVariablePairChild[$i]));
						list($childModuleName, $columnNameChild) = explode('-', $templateVariablePairChild[$i]);
						list($childModuleReferenceColumn, $childModuleColumnName) = explode(':', $columnNameChild);
						preg_match('/(?<=\[).*?(?=\])/', $childModuleName, $childmatches);
						$parentModuleName = $childmatches[0];
						$childModuleName = str_replace("[$parentModuleName]", '', $childModuleName);

						// 子モジュールのレコード数を取得。
						$referenceRecordCount = self::getChildReferenceRecordCountForPDF($match, $loopcount, $childModuleName, $parentModuleName, $childModuleReferenceColumn, $recordId);
						if ($childrecordcount < $referenceRecordCount) {
							$childrecordcount = $referenceRecordCount;
						}
					}
				}
				$loopcount += 1;
			}
			$childcnt += 1;
		}

		return ceil($childrecordcount / $loopperpage);;
	}
}