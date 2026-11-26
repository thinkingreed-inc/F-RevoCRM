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
 * MCPトークン管理 - Record Model
 *
 * 1件のMCPトークンレコードを表現する。
 * 平文トークンはDB・モデルに保持しない（発行時のみActionレスポンスで返す）。
 */
class Settings_MCPTokens_Record_Model extends Settings_Vtiger_Record_Model {

	/**
	 * レコードIDを取得
	 * @return int
	 */
	public function getId() {
		return $this->get('id');
	}

	/**
	 * レコード名（ラベル）を取得
	 * @return string
	 */
	public function getName() {
		return $this->get('label');
	}

	/**
	 * 表示値を整形して返す
	 * @param string $fieldName
	 * @param mixed $recordId
	 * @return string
	 */
	public function getDisplayValue($fieldName, $recordId = false) {
		$fieldValue = $this->get($fieldName);

		if ($fieldName === 'created_at' || $fieldName === 'last_used_at') {
			if ($fieldValue && $fieldValue !== '0000-00-00 00:00:00') {
				return Vtiger_Datetime_UIType::getDateTimeValue($fieldValue);
			}
			return '---';
		}
		if ($fieldName === 'token_prefix') {
			return $fieldValue ? $fieldValue . '…' : '---';
		}
		if ($fieldName === 'expires_at') {
			require_once 'include/Mcp/TokenAuth.php';
			switch (Mcp_TokenAuth::getExpiryState($fieldValue)) {
				case Mcp_TokenAuth::EXPIRY_NONE:
					return vtranslate('LBL_MCP_EXPIRES_NONE', 'Settings:MCPTokens');
				case Mcp_TokenAuth::EXPIRY_EXPIRED:
					return vtranslate('LBL_MCP_EXPIRED', 'Settings:MCPTokens');
				default:
					return Vtiger_Datetime_UIType::getDateTimeValue($fieldValue);
			}
		}
		return $fieldValue;
	}

	/**
	 * アクティブなユーザー一覧を取得（発行フォームのユーザー選択用）
	 * @return array [userid => 表示名]
	 */
	public static function getActiveUsers() {
		$db = PearDatabase::getInstance();
		$usersListArray = array();
		$result = $db->pquery(
			'SELECT id, user_name, first_name, last_name FROM vtiger_users WHERE status = ? ORDER BY user_name',
			array('Active')
		);
		$rows = $db->num_rows($result);
		for ($i = 0; $i < $rows; $i++) {
			$uid = $db->query_result($result, $i, 'id');
			$row = array(
				'first_name' => $db->query_result($result, $i, 'first_name'),
				'last_name'  => $db->query_result($result, $i, 'last_name'),
			);
			$fullName = getFullNameFromArray('Users', $row);
			$usersListArray[$uid] = $fullName;
		}
		return $usersListArray;
	}
}
