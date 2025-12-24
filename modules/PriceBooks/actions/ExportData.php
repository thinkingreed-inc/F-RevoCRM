<?php
/* +**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 * ***********************************************************************************/

class PriceBooks_ExportData_Action extends Vtiger_ExportData_Action {
	/**
	 * this function takes in an array of values for an user and sanitizes it for export
	 * @param array $arr - the array of values
	 */
	function sanitizeValues($arr, $format) {
		$db = PearDatabase::getInstance();
		$relatedto = $arr['relatedto'];
		$listPrice = $arr['listprice'];

		unset($arr['relatedto']);
		unset($arr['listprice']);

		$arr = parent::sanitizeValues($arr, $format);
		if ($relatedto) {
			$relatedModule = getSalesEntityType($relatedto);
			$result = getEntityName($relatedModule, $relatedto, false);
			$displayValue = $result[$relatedto];
			$query = "select entityidfield from vtiger_entityname where modulename = ?";
			$result = $db->pquery($query, array($relatedModule));
			$entityidfield = $db->query_result($result, 0, 'entityidfield');
			
			switch($format) {
				case 'ExportImportableFormat'	:	if(!empty($relatedModule) && !empty($entityidfield)){
														$arr['relatedto'] = $relatedModule."::::".$entityidfield."====".$relatedto;
													}else{
														$arr['relatedto'] = "";
													}
													break;

				case 'ExportLabelOnly'	:	if(!empty($relatedModule) && !empty($displayValue)){
												$arr['relatedto'] = $displayValue;
											}else{
												$arr['relatedto'] = "";
											}
											break;

				case 'ExportBoth'	:	if(!empty($relatedModule) && !empty($displayValue) && !empty($entityidfield)){
											$arr['relatedto'] =  $displayValue;
											$arr['relatedto_import_format']  = $relatedModule."::::".$entityidfield."====".$relatedto;
										}else{
											$arr['relatedto'] = "";
											$arr['relatedto_import_format'] = "";
										}
										break;
				
				default :	break;
			}			
		}else{
			if($format == 'ExportBoth'){
				$arr['relatedto'] = '';
				$arr['relatedto_import_format'] = '';
			}else{
				$arr['relatedto'] = '';
			}
		}
		$arr['listprice'] = $listPrice;
		$displayValue = $relatedto = $listPrice = NULL;
		return $arr;
	}

	public function getHeaders($format=null) {
		if (!$this->headers) {
			$translatedHeaders = parent::getHeaders($format);
			if($format == 'ExportBoth'){
				$fieldList = array('Related To (Label)','Related To', 'ListPrice');
				foreach ($fieldList as $fieldName) {
					$translatedHeaders[] = $fieldName;
				}
			}else{
				$fieldList = array('Related To', 'ListPrice');
				foreach ($fieldList as $fieldName) {
					$translatedHeaders[] = $fieldName;
				}
			}
			
			$this->headers = $translatedHeaders;
		}
		return $this->headers;
	}
}
