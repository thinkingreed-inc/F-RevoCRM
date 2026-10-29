<?php
/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

class Settings_Vtiger_CustomRecordNumberingModule_Model extends Vtiger_Module_Model {

	/**
	 * Function to get focus of this object
	 * @return <type>
	 */
	public function getFocus() {
		if (!$this->focus) {
			$this->focus = CRMEntity::getInstance($this->getName());
		}
		return $this->focus;
	}

	/**
	 * Function to get Instance of this module
	 * @param <String> $moduleName
	 * @return <Settings_Vtiger_CustomRecordNumberingModule_Model> $moduleModel
	 */
	public static function getInstance($moduleName, $tabId = false) {
		$moduleModel = new self();
		$moduleModel->name = $moduleName;
		if ($tabId) {
			$moduleModel->id = $tabId;
		}
		return $moduleModel;
	}

	/**
	 * 自動採番項目（uitype 997）を持つモジュール（連番管理の画面で設定できるモジュール）
	 * @return <Array> モジュール名の昇順ではなく tabid 順の Settings_Vtiger_CustomRecordNumberingModule_Model
	 */
	public static function getSupportedModules() {
		$db = PearDatabase::getInstance();

		$sql = "SELECT tabid, name FROM vtiger_tab WHERE isentitytype = ? AND presence = ? AND tabid IN (SELECT DISTINCT tabid FROM vtiger_field WHERE uitype = ? AND presence IN (0, 2)) ORDER BY tabid";
		$result = $db->pquery($sql, array(1, 0, 997));
		$numOfRows = $db->num_rows($result);

		$modulesModels = array();
		for($i=0; $i<$numOfRows; $i++) {
			$tabId = $db->query_result($result, $i, 'tabid');
			$modulesModels[$tabId] = Settings_Vtiger_CustomRecordNumberingModule_Model::getInstance($db->query_result($result, $i, 'name'), $tabId);
		}

		return $modulesModels;
	}

	/**
	 * モジュールの自動採番項目と設定（項目名 => 設定）。画面の入力欄に入れる値で、適用開始日時はユーザーのタイムゾーン
	 * @return <Array>
	 */
	public function getFieldConfigs() {
		require_once 'modules/Vtiger/utils/AutoNumber.php';
		$configs = Vtiger_AutoNumber::getModuleConfigs($this->getName());
		foreach ($configs as $fieldName => $config) {
			if (!empty($config['effective_from'])) {
				$configs[$fieldName]['effective_from'] = Vtiger_AutoNumber::dbTimeToUser((string) $config['effective_from']);
			}
			$configs[$fieldName]['fieldlabel'] = vtranslate($config['fieldlabel'], $this->getName());
		}
		return $configs;
	}

	/**
	 * 自動採番の基準日に選べる、モジュールの日付項目
	 * @return <Array> 項目名 => 表示名
	 */
	public function getDateFields() {
		$fields = array();
		$moduleModel = Vtiger_Module_Model::getInstance($this->getName());
		if (!$moduleModel) {
			return $fields;
		}
		foreach ($moduleModel->getFields() as $fieldModel) {
			if ($fieldModel->get('uitype') == 5 && $fieldModel->isActiveField()) {
				$fields[$fieldModel->getName()] = vtranslate($fieldModel->get('label'), $this->getName());
			}
		}
		return $fields;
	}

	/**
	 * 画面の入力から、自動採番の設定を取り出す
	 * @param <Array> $params format / reset_type / start_number / base_date / effective_from / active
	 * @return <Array> Vtiger_AutoNumber の設定（適用開始日時は DB の時刻）
	 */
	public static function getConfigFromParams($params) {
		require_once 'modules/Vtiger/utils/AutoNumber.php';
		$effectiveFrom = isset($params['effective_from']) ? trim((string) $params['effective_from']) : '';
		$startNumber = isset($params['start_number']) ? trim((string) $params['start_number']) : '';
		return array(
			'format' => isset($params['format']) ? trim(decode_html((string) $params['format'])) : '',
			'reset_type' => isset($params['reset_type']) ? (string) $params['reset_type'] : 'none',
			'start_number' => $startNumber === '' ? 1 : $startNumber,
			'base_date' => isset($params['base_date']) ? (string) $params['base_date'] : '',
			'effective_from' => $effectiveFrom === '' ? null : Vtiger_AutoNumber::userTimeToDb($effectiveFrom),
			'active' => isset($params['active']) && (string) $params['active'] === '0' ? 0 : 1,
		);
	}

	/**
	 * 設定を調べて保存する
	 * @param <String> $fieldName 自動採番項目の項目名
	 * @param <Array> $config getConfigFromParams の戻り値
	 * @return <Array> エラーのキー（無ければ空。Vtiger_AutoNumber::validateConfig のキー + FIELD_NOT_FOUND）
	 */
	public function saveFieldConfig($fieldName, $config) {
		require_once 'modules/Vtiger/utils/AutoNumber.php';
		$configs = Vtiger_AutoNumber::getModuleConfigs($this->getName());
		if (!isset($configs[$fieldName])) {
			return array('FIELD_NOT_FOUND');
		}
		$fieldId = (int) $configs[$fieldName]['fieldid'];
		$errors = Vtiger_AutoNumber::validateConfig($this->getName(), $fieldId, $config);
		if (empty($errors)) {
			Vtiger_AutoNumber::saveConfig($fieldId, $config);
		}
		return $errors;
	}

	/**
	 * 自動採番の設定のエラーを、画面に出す文にする
	 * @param <Array> $errors saveFieldConfig の戻り値
	 * @return <String>
	 */
	public static function getErrorMessage($errors) {
		$messages = array();
		foreach ($errors as $error) {
			$messages[] = vtranslate('LBL_AUTONUMBER_ERR_' . $error, 'Vtiger');
		}
		return implode("\n", $messages);
	}

}
