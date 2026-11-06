<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

require_once 'include/fields/PicklistDisplayConfig.php';

class Vtiger_Multipicklist_UIType extends Vtiger_Base_UIType {

	/**
	 * #1677: 表示設定(input_style)と選択肢数(閾値)から描画テンプレートを切替える。
	 * 実効スタイルが 'dropdown' 以外（checkbox）のとき WebComponent で描画し、
	 * dropdown（明示指定 or 選択肢が閾値超）のときのみ従来の select2 テンプレートを維持する。
	 * @return <String> - Template Name
	 */
	public function getTemplateName() {
		$fieldModel = $this->get('field');
		// 選択肢数の源は buildProps と共通化（shouldUseWebComponentForField）。
		// これによりテンプレ選択と React 側 effectiveStyle が閾値境界でも必ず一致する。
		if (PicklistDisplayConfig::shouldUseWebComponentForField($fieldModel)) {
			return 'uitypes/WebComponentField.tpl';
		}
		return 'uitypes/MultiPicklist.tpl';
	}

	/** #1677: WebComponent のタグ名（React 側 createFieldWebComponent の登録名と一致させる） */
	public function getWebComponentTagName() {
		return 'fr-field-multipicklist';
	}

	/**
	 * #1677: WebComponent(props 属性)に渡すデータ。options＋色＋表示設定(inputStyle/threshold/columns)を載せる。
	 * 送信・必須検証は WebComponentField.tpl の hidden input が担う（値は ' |##| ' 連結で hidden に入る）。
	 * @param mixed $value 現在値（' |##| ' 連結文字列）
	 * @param string $mode 'edit' または 'detail'
	 */
	public function getWebComponentProps($value, $mode, $recordModel = false) {
		return PicklistDisplayConfig::buildProps($this->get('field'), $value, $mode, true);
	}

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param <Object> $value
	 * @return <Object>
	 */
	public function getDisplayValue($value, $record=false, $recordInstance=false) {

		$moduleName = $this->get('field')->getModuleName();
		$value = explode(' |##| ', $value);
		foreach ($value as $key => $val) {
			$value[$key] = vtranslate($val, $moduleName);
		}

		if(is_array($value)){
            $value = implode(' |##| ', $value);
        }
		return str_ireplace(' |##| ', ', ', $value);
	}
    
    public function getDBInsertValue($value) {
		if(is_array($value)){
            $value = implode(' |##| ', $value);
        }
        return $value;
	}
    
    
    public function getListSearchTemplateName() {
        return 'uitypes/MultiSelectFieldSearchView.tpl';
    }
}