<?php
/*+*******************************************************************************
 *  The contents of this file are subject to the vtiger CRM Public License Version 1.0
 *  ("License"); You may not use this file except in compliance with the License
 *  The Original Code is:  vtiger CRM Open Source
 *  The Initial Developer of the Original Code is vtiger.
 *  Portions created by vtiger are Copyright (C) vtiger.
 *  All Rights Reserved.
 ******************************************************************************** */

/**
 * Function to get the field information from module name and field label
 */
function getFieldByReportLabel($module, $label, $mode = 'label') {
	$cacheLabel = VTCacheUtils::getReportFieldByLabel($module, $label);
	if($cacheLabel) return $cacheLabel;

	// this is required so the internal cache is populated or reused.
	getColumnFields($module);
	//lookup all the accessible fields
	$cachedModuleFields = VTCacheUtils::lookupFieldInfo_Module($module);
	$label = decode_html($label);
	
	if($module == 'Calendar') {
		$cachedEventsFields = VTCacheUtils::lookupFieldInfo_Module('Events');
		if ($cachedEventsFields) {
			if(empty($cachedModuleFields)) $cachedModuleFields = $cachedEventsFields;
			else $cachedModuleFields = array_merge($cachedModuleFields, $cachedEventsFields);
		}
		if($label == 'Start_Date_and_Time') {
			$label = 'Start_Date_&_Time';
		}
	}
	
	if(empty($cachedModuleFields)) {
		return null;
	}
    
	foreach ($cachedModuleFields as $fieldInfo) {
        if($mode == 'name') {
            $fieldLabel = $fieldInfo['fieldname'];
        } else {
            $fieldLabel = str_replace(' ', '_', $fieldInfo['fieldlabel']);
        }
        $fieldLabel = decode_html($fieldLabel);
		if($label == $fieldLabel) {
			VTCacheUtils::setReportFieldByLabel($module, $label, $fieldInfo);
			return $fieldInfo;
		}
	}
	return null;
}

function isReferenceUIType($uitype) {
	static $options = array('101', '116', '117', '26', '357',
		'50', '51', '52', '53', '57', '58', '59', '66', '68',
		'73', '75', '76', '77', '78', '80', '81'
	);

	if(in_array($uitype, $options)) {
		return true;
	}
	return false;
}

function IsDateField($reportColDetails) {
	list($tablename, $colname, $module_field, $fieldname, $typeOfData) = split(":", $reportColDetails);
	if ($typeOfData == "D") {
		return true;
	} else {
		return false;
	}
}

/**
 *
 * @global Users $current_user
 * @param ReportRun $report
 * @param Array $picklistArray
 * @param ADOFieldObject $dbField
 * @param Array $valueArray
 * @param String $fieldName
 * @return String
 */
function getReportFieldValue ($report, $picklistArray, $dbField, $valueArray, $fieldName, $operation = false) {
	global $current_user, $default_charset;

	$db = PearDatabase::getInstance();
	$value = $valueArray[$fieldName];
	$fld_type = $dbField->type;
	list($module, $fieldLabel) = explode('_', $dbField->name, 2);
	$fieldInfo = getFieldByReportLabel($module, $fieldLabel);
	$fieldType = null;
	$fieldvalue = $value;
	if(!empty($fieldInfo)) {
		$field = WebserviceField::fromArray($db, $fieldInfo);
		$fieldType = $field->getFieldDataType();
	}

	// Added to support pricebook List Price in reports
	if ($report->primarymodule == 'PriceBooks' && $fieldLabel == 'List_Price') {
		$fieldInfo = array(
			'tabid'		=> getTabid($module),
			'fieldid'	=> '',
			'fieldname' => 'listprice',
			'fieldlabel'=> 'List Price',
			'columnname'=> 'listprice',
			'tablename'	=> 'vtiger_pricebookproductrel',
			'uitype'	=> 72,
			'typeofdata'=> 'Currency',
			'presence'	=> 0,
		);
		$field = WebserviceField::fromArray($db, $fieldInfo);
		$fieldType = $field->getFieldDataType();
	}
	if(is_object($field) &&	$field->getUIType() == 401){
		if ($value) {
			$value = explode('_', $value);
			$module = 'RecurringInvoice';
			$frequency = ucfirst($value[0]);
			if($frequency == 'Monthly'){
				$fieldvalue = vtranslate('LBL_MONTHLY', $module, $value[1], vtranslate($value[2], $module)); 
			}elseif($frequency == 'Yearly'){
				$fieldvalue = vtranslate('LBL_YEARLY', $module, vtranslate(ucfirst($value[1]), $module), vtranslate($value[2], $module));
			}elseif($frequency == 'Weekly'){
				$fieldvalue = vtranslate('LBL_WEEKLY', $module, vtranslate(ucfirst($value[1])));
			}elseif($frequency == 'Daily'){
				$fieldvalue = vtranslate($frequency, $module);
			}
		}
	}else if ($fieldType == 'currency' && $value != '') {
		// Some of the currency fields like Unit Price, Total, Sub-total etc of Inventory modules, do not need currency conversion
		if ($field->getUIType() == '72') {
			$curid_value = explode("::", $value);
			$currency_id = $curid_value[0];
			$currency_value = $curid_value[1];
			$cur_sym_rate = getCurrencySymbolandCRate($currency_id);
			if ($value != '') {
				if (($dbField->name == 'Products_Unit_Price')) { // need to do this only for Products Unit Price
					if ($currency_id != 1) {
						$currency_value = (float) $cur_sym_rate['rate'] * (float) $currency_value;
					}
				}

				if ($operation == 'ExcelExport') {
					$fieldvalue = $currency_value;
				} else {
					$formattedCurrencyValue = CurrencyField::convertToUserFormat($currency_value, null, true);
					$fieldvalue = CurrencyField::appendCurrencySymbol($formattedCurrencyValue, $cur_sym_rate['symbol']);
				}
			}
		} else {
			if ($operation == 'ExcelExport') {
				$currencyField = new CurrencyField($value);
				$fieldvalue = $currencyField->getDisplayValue(null, false, true);
			} else {
				$currencyField = new CurrencyField($value);
				$userCurrencyInfo = getCurrencySymbolandCRate($current_user->currency_id);
				$fieldvalue = CurrencyField::appendCurrencySymbol($currencyField->getDisplayValue(), $userCurrencyInfo['symbol']);
			}
		}
	} elseif ($dbField->name == "PurchaseOrder_Currency" || $dbField->name == "SalesOrder_Currency"
				|| $dbField->name == "Invoice_Currency" || $dbField->name == "Quotes_Currency" || $dbField->name == "PriceBooks_Currency") {
		if($value!='') {
			$fieldvalue = getTranslatedCurrencyString($value);
		}
	} elseif (in_array($dbField->name,$report->ui101_fields) && !empty($value)) {
		$entityNames = getEntityName('Users', $value);
		$fieldvalue = $entityNames[$value];
	} elseif( $fieldType == 'date' && !empty($value)) {
		if($module == 'Calendar' && ($field->getFieldName() == 'due_date' || $field->getFieldName() == 'date_start')) {
            if($field->getFieldName() == 'due_date'){
                $endTime = $valueArray['calendar_end_time'];
                if(empty($endTime)) {
                    $recordId = $valueArray['calendar_id'];
                    $endTime = getSingleFieldValue('vtiger_activity', 'time_end', 'activityid', $recordId);
                }
                $date = new DateTimeField($value.' '.$endTime);
                $fieldvalue = $date->getDisplayDate();
            }
            else{
                $date = new DateTimeField($fieldvalue);
                $fieldvalue = $date->getDisplayDateTimeValue();
            }
		} else {
            $date = new DateTimeField($fieldvalue);
            $fieldvalue = $date->getDisplayDate();
		}
	} elseif( $fieldType == "datetime" && !empty($value)) {
		$date = new DateTimeField($value);
		$fieldvalue = $date->getDisplayDateTimeValue();
	} elseif( $fieldType == 'time' && !empty($value) && $field->getFieldName()
			!= 'duration_hours') {
		if($field->getFieldName() == "time_start" || $field->getFieldName() == "time_end") {
			$date = new DateTimeField($value);
			$fieldvalue = $date->getDisplayTime();
		} else {
			$userModel = Users_Privileges_Model::getCurrentUserModel();
			if($userModel->get('hour_format') == '12'){
				$value = Vtiger_Time_UIType::getTimeValueInAMorPM($value);
			}
			$fieldvalue = $value;
		}
	} elseif( $fieldType == "picklist" && !empty($value) ) {
			$fieldvalue = getTranslatedString($value, $module);
	} elseif( $fieldType == "multipicklist" && !empty($value) ) {
		if(is_array($picklistArray[1])) {
			$valueList = explode(' |##| ', $value);
			$translatedValueList = array();
			foreach ( $valueList as $value) {
				$translatedValueList[] = getTranslatedString($value, $module);
			}
		}
		if (!is_array($picklistArray[1]) || !is_array($picklistArray[1][$dbField->name])) {
			$fieldvalue = str_replace(' |##| ', ', ', $value);
		} else {
			implode(', ', $translatedValueList);
		}
	} elseif ($fieldType == 'double' && $operation != 'ExcelExport') {
        if($current_user->truncate_trailing_zeros == true)
            $fieldvalue = decimalFormat($fieldvalue);
    }
    if($fieldType == 'currency' && $value == "" && $operation != 'ExcelExport'){
        $currencyField = new CurrencyField($value);
        $fieldvalue = $currencyField->getDisplayValue();
        return $fieldvalue;
    } else if($fieldvalue == "" && $operation != 'ExcelExport') {
        return "";
    }
	$fieldvalue = str_replace("<", "&lt;", $fieldvalue);
	$fieldvalue = str_replace(">", "&gt;", $fieldvalue);
	$fieldvalue = decode_html($fieldvalue);

	if (stristr($fieldvalue, "|##|") && empty($fieldType)) {
		$fieldvalue = str_ireplace(' |##| ', ', ', $fieldvalue);
	} elseif ($fld_type == "date" && empty($fieldType)) {
		$fieldvalue = DateTimeField::convertToUserFormat($fieldvalue);
	} elseif ($fld_type == "datetime" && empty($fieldType)) {
		$date = new DateTimeField($fieldvalue);
		$fieldvalue = $date->getDisplayDateTimeValue();
	}

	// Added to render html tag for description fields
	if($fieldInfo['uitype'] == '19' && ($module == 'Documents' || $module == 'Emails')) {
		return $fieldvalue;
	}
	if ( is_object($field) ) {
		if($module == 'HelpDesk' && ($field->getFieldName() == 'description' || $field->getFieldName() == 'solution')) {
			return $fieldvalue;
		}
		if($module == 'Faq' && ($field->getFieldName() == 'question' || $field->getFieldName() == 'faq_answer')) {
			return $fieldvalue;
		}
	}
        if($operation == 'ExcelExport') {
            return array('value' => htmlentities($fieldvalue, ENT_QUOTES, $default_charset), 'type' => $fieldType);
        }
	return htmlentities($fieldvalue, ENT_QUOTES, $default_charset);
}

function transformAdvFilterListToDBFormat($advFilterList) {
    $db = PearDatabase::getInstance();
    foreach($advFilterList as $k => $columnConditions) {
        foreach($columnConditions['columns'] as $j => $columnCondition) {
            if(empty($columnCondition)) continue;

            $advFilterColumn = $columnCondition["columnname"];
            $advFilterComparator = $columnCondition["comparator"];
            $advFilterValue = $columnCondition["value"];

            $columnInfo = explode(":",$advFilterColumn);
            $moduleFieldLabel = $columnInfo[2];

            list($module, $fieldLabel) = explode('_', $moduleFieldLabel, 2);
            $fieldInfo = getFieldByReportLabel($module, $fieldLabel);
            $fieldType = null;
            if(!empty($fieldInfo)) {
                $field = WebserviceField::fromArray($db, $fieldInfo);
                $fieldType = $field->getFieldDataType();
            }

            if($fieldType == 'currency') {
                if($field->getUIType() == '72') {
                    $advFilterValue = Vtiger_Currency_UIType::convertToDBFormat($advFilterValue, null, true);
                } else {
                    $advFilterValue = Vtiger_Currency_UIType::convertToDBFormat($advFilterValue);
                }
            }

            $specialDateConditions = Vtiger_Functions::getSpecialDateTimeCondtions();
            $tempVal = explode(",",$advFilterValue);
            if(($columnInfo[4] == 'D' || ($columnInfo[4] == 'T' && $columnInfo[1] != 'time_start' && $columnInfo[1] != 'time_end') ||
                            ($columnInfo[4] == 'DT')) && ($columnInfo[4] != '' && $advFilterValue != '' ) && !in_array($advFilterComparator, $specialDateConditions)) {
                $val = Array();
                for($i=0; $i<php7_count($tempVal); $i++) {
                    if(trim($tempVal[$i]) != '') {
                        $date = new DateTimeField(trim($tempVal[$i]));
                        if($columnInfo[4] == 'D') {
                            $val[$i] = DateTimeField::convertToDBFormat(trim($tempVal[$i]));
                        } elseif($columnInfo[4] == 'DT') {
                            $values = explode(' ', $tempVal[$i]);
                            $date = new DateTimeField($values[0]);
                            $val[$i] = $date->getDBInsertDateValue();
                        } elseif($fieldType == 'time') {
                            $val[$i] = Vtiger_Time_UIType::getTimeValueWithSeconds($tempVal[$i]);
                        } else {
                            $val[$i] = $date->getDBInsertTimeValue();
                        }
                    }
                }
                $advFilterValue = implode(",", $val);
            }
            $advFilterList[$k]['columns'][$j]['value'] = $advFilterValue;
        }
    }
    
    return $advFilterList;
}

function getReportSearchCondition($searchParams, $filterId) {
	if (!empty($searchParams)) {
		$db = PearDatabase::getInstance();
		$params = array();
		$conditionQuery = '';
		if ($filterId === 'All') {
			$conditionQuery .= ' WHERE ';
		} else {
			$conditionQuery .= " AND ";
		}
		$conditionQuery .= " (( ";
		foreach ($searchParams as $i => $condition) {
			$fieldName = $condition[0];
			$searchValue = $condition[2];
			if ($fieldName == 'reportname' || $fieldName == 'description') {
				$conditionQuery .= " vtiger_report.$fieldName LIKE ? ";
				array_push($params, "%$searchValue%");
			} else if ($fieldName == 'reporttype' || $fieldName == 'foldername' || $fieldName == 'owner') {
				$searchValue = explode(',', $searchValue);
				if ($fieldName == 'foldername') {
					$fieldName = 'folderid';
				}
				if ($fieldName == 'reporttype' && in_array('tabular', $searchValue)) {
					array_push($searchValue, 'summary');
				}
				$conditionQuery .= " vtiger_report.$fieldName IN (".generateQuestionMarks($searchValue).") ";
				foreach ($searchValue as $value) {
					array_push($params, $value);
				}
			} else if ($fieldName == 'primarymodule') {
				$searchValue = explode(',', $searchValue);
				$conditionQuery .= " vtiger_reportmodules.$fieldName IN (".generateQuestionMarks($searchValue).") ";
				foreach ($searchValue as $value) {
					array_push($params, $value);
				}
			}
			if ($i < (php7_count($searchParams) - 1)) {
				$conditionQuery .= ' AND ';
			}
		}
		$conditionQuery .= " ) ";
		$conditionQuery .= ") ";
		return $db->convert2Sql($conditionQuery, $params);
	}
	return false;
}

?>