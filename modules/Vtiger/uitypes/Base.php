<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Vtiger_Base_UIType extends Vtiger_Base_Model {

	/**
	 * Function to get the Template name for the current UI Type Object
	 * @return <String> - Template Name
	 */
	public function getTemplateName() {
		return 'uitypes/String.tpl';
	}

	/**
	 * Function to get the DB Insert Value, for the current field type with given User Value
	 * @param <Object> $value
	 * @return <Object>
	 */
	public function getDBInsertValue($value) {
		return $value;
	}

	/**
	 * Function to get the Value of the field in the format, the user provides it on Save
	 * @param <Object> $value
	 * @return <Object>
	 */
	public function getUserRequestValue($value) {
		return $value;
	}

	/**
	 * Function to get the Display Value, for the current field type with given DB Insert Value
	 * @param <Object> $value
	 * @return <Object>
	 */
	public function getDisplayValue($value, $record=false, $recordInstance=false) {
		return $value;
	}

	/**
	 * uitype 専用の UIType クラスの対応表 (uitype => クラス名の接尾辞)
	 * 対応表にある uitype は、データ型名に関係なく専用クラスで解決する。
	 * データ型名を既存のもの (date など) のままにして、日付等の既存機能に乗せたい型で使う。
	 */
	const UITYPE_CLASS_MAP = array(
		'996' => 'YearMonth',
		'997' => 'AutoNumber',
	);

	/**
	 * UIType クラス名の接尾辞を解決する
	 * @param mixed $uitype vtiger_field.uitype (文字列・整数・null のいずれも可)
	 * @param string $fieldDataType データ型名
	 * @return string 対応表にあればその接尾辞、無ければデータ型名の先頭を大文字にしたもの
	 */
	public static function resolveClassSuffix($uitype, $fieldDataType) {
		$key = (string) $uitype;
		if ($key !== '' && isset(self::UITYPE_CLASS_MAP[$key])) {
			return self::UITYPE_CLASS_MAP[$key];
		}
		return ucfirst($fieldDataType);
	}

	/**
	 * Static function to get the UIType object from Vtiger Field Model
	 * @param Vtiger_Field_Model $fieldModel
	 * @return Vtiger_Base_UIType or UIType specific object instance
	 */
	public static function getInstanceFromField($fieldModel) {
		$fieldDataType = $fieldModel->getFieldDataType();
		$uiTypeClassSuffix = self::resolveClassSuffix($fieldModel->get('uitype'), $fieldDataType);
		$moduleName = $fieldModel->getModuleName();
		$moduleSpecificUiTypeClassName = $moduleName.'_'.$uiTypeClassSuffix.'_UIType';
		$uiTypeClassName = 'Vtiger_'.$uiTypeClassSuffix.'_UIType';
		$fallBackClassName = 'Vtiger_Base_UIType';

		$moduleSpecificFileName = 'modules.'. $moduleName .'.uitypes.'.$uiTypeClassSuffix;
		$uiTypeClassFileName = 'modules.Vtiger.uitypes.'.$uiTypeClassSuffix;

		$moduleSpecificFilePath = Vtiger_Loader::resolveNameToPath($moduleSpecificFileName);
		$completeFilePath = Vtiger_Loader::resolveNameToPath($uiTypeClassFileName);

		if(file_exists($moduleSpecificFilePath)) {
			$instance = new $moduleSpecificUiTypeClassName();
		}
		else if(file_exists($completeFilePath)) {
			$instance = new $uiTypeClassName();
		} else {
			$instance = new $fallBackClassName();
		}
		$instance->set('field', $fieldModel);
		return $instance;
	}

	/**
	 * Function to get the display value in edit view
	 * @param reference record id
	 * @return link
	 */
	public function getEditViewDisplayValue($value) {
		return $value;
	}

    /**
	 * Function to get the Detailview template name for the current UI Type Object
	 * @return <String> - Template Name
	 */
	public function getDetailViewTemplateName() {
		return 'uitypes/StringDetailView.tpl';
	}

	/**
	 * Function to get Display value for RelatedList
	 * @param <String> $value
	 * @return <String>
	 */
	public function getRelatedListDisplayValue($value) {
		return $this->getDisplayValue($value);
	}
    
    public function getListSearchTemplateName() {
        return 'uitypes/FieldSearchView.tpl';
    }
}