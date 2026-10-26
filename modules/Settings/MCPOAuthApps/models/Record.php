<?php
/*+***********************************************************************************
 * The contents of this file are subject to the Vtiger Public License Version 1.2
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: frevo-mcp (https://github.com/ratorin/frevo-mcp)
 * The Initial Developer of the Original Code is ratorin.
 * Portions created by ratorin are Copyright (C) ratorin.
 * All Rights Reserved.
 *************************************************************************************/
/**
 * MCP連携アプリケーション管理 - Record Model
 *
 * 1件の OAuth 連携を表現する。トークンのハッシュは表示に使わない。
 */
class Settings_MCPOAuthApps_Record_Model extends Settings_Vtiger_Record_Model {

	/**
	 * レコードIDを取得
	 * @return int
	 */
	public function getId() {
		return $this->get('id');
	}

	/**
	 * レコード名（アプリケーション名）を取得
	 * @return string
	 */
	public function getName() {
		return $this->get('client_name');
	}

	/**
	 * 表示値を整形して返す
	 * @param string $fieldName
	 * @param mixed $recordId
	 * @return string
	 */
	public function getDisplayValue($fieldName, $recordId = false) {
		$fieldValue = $this->get($fieldName);

		if ($fieldName === 'client_name') {
			return ($fieldValue === null || $fieldValue === '') ? '—' : $fieldValue;
		}
		if ($fieldName === 'created_at') {
			if ($fieldValue && $fieldValue !== '0000-00-00 00:00:00') {
				return Vtiger_Datetime_UIType::getDateTimeValue($fieldValue);
			}
			return '---';
		}
		return $fieldValue;
	}
}
