<?php
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

		if ($fieldName === 'enabled') {
			return $fieldValue ? vtranslate('LBL_YES', 'Settings:MCPTokens') : vtranslate('LBL_NO', 'Settings:MCPTokens');
		}
		if ($fieldName === 'created_at') {
			if ($fieldValue && $fieldValue !== '0000-00-00 00:00:00') {
				return Vtiger_Datetime_UIType::getDateTimeValue($fieldValue);
			}
			return '---';
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
