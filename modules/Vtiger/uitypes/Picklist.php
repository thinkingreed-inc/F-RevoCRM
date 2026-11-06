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

class Vtiger_Picklist_UIType extends Vtiger_Base_UIType {

	/**
	 * #1677: 表示設定(input_style)が 'radio'/'checkbox' かつ選択肢数が閾値以下のとき WebComponent(ラジオ)で描画する。
	 * 既定(null/auto/dropdown)や閾値超は従来の select2 テンプレートを維持する（全ピックリストが勝手に React 化しないように）。
	 * @return <String> - Template Name
	 */
	public function getTemplateName() {
		$fieldModel = $this->get('field');
		// 選択肢数の源は buildProps と共通化（shouldUseWebComponentForField）。
		// テンプレ選択と React 側 effectiveStyle を閾値境界でも一致させる。
		if (PicklistDisplayConfig::shouldUseWebComponentForField($fieldModel)) {
			return 'uitypes/WebComponentField.tpl';
		}
		return 'uitypes/Picklist.tpl';
	}

	/** #1677: WebComponent のタグ名（React 側 createFieldWebComponent の登録名と一致させる） */
	public function getWebComponentTagName() {
		return 'fr-field-picklist';
	}

	/**
	 * #1677: WebComponent(props 属性)に渡すデータ。options＋色＋表示設定(inputStyle/threshold/columns)を載せる。
	 * 送信・必須検証は WebComponentField.tpl の hidden input が担う。
	 * @param mixed $value 現在値（単一値）
	 * @param string $mode 'edit' または 'detail'
	 */
	public function getWebComponentProps($value, $mode, $recordModel = false) {
		return PicklistDisplayConfig::buildProps($this->get('field'), $value, $mode, false);
	}

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param <Object> $value
	 * @return <Object>
	 */
	public function getDisplayValue($value, $record=false, $recordInstance=false) {
		return Vtiger_Language_Handler::getTranslatedString($value, $this->get('field')->getModuleName());
	}
    
    public function getListSearchTemplateName() {
        return 'uitypes/PickListFieldSearchView.tpl';
    }

}