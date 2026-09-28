<?php
/**
 * MCPトークン管理 - ListView Model
 *
 * トークン一覧のクエリ組み立てとカウント取得を担当。
 * vtiger_mcp_token と vtiger_users をJOINしてユーザー名を表示する。
 */
class Settings_MCPTokens_ListView_Model extends Settings_Vtiger_ListView_Model {

	/**
	 * 一覧取得の基本SQLを組み立てる
	 * @return string SQL文字列
	 */
	public function getBasicListQuery() {
		$db = PearDatabase::getInstance();
		$module = $this->getModule();

		// ユーザーのフルネームをJOINで取得
		$userNameSql = getSqlForNameInDisplayFormat(
			array('last_name' => 'vtiger_users.last_name', 'first_name' => 'vtiger_users.first_name'),
			'Users'
		);

		$query = "SELECT {$module->baseTable}.id,
					{$module->baseTable}.label,
					CASE WHEN {$userNameSql} <> '' THEN {$userNameSql} ELSE CONCAT('User#', {$module->baseTable}.userid) END AS user_name,
					{$module->baseTable}.enabled,
					{$module->baseTable}.created_at,
					{$module->baseTable}.token_prefix,
					{$module->baseTable}.last_used_at,
					{$module->baseTable}.expires_at,
					{$module->baseTable}.userid
				FROM {$module->baseTable}
				LEFT JOIN vtiger_users ON vtiger_users.id = {$module->baseTable}.userid
				ORDER BY {$module->baseTable}.created_at DESC";

		return $query;
	}

	/**
	 * 一覧リンク（エクスポート等）は不要なので空配列
	 * @return array
	 */
	public function getListViewLinks() {
		return array();
	}

	/**
	 * レコード総数を返す
	 * @return int
	 */
	public function getListViewCount() {
		$db = PearDatabase::getInstance();
		$module = $this->getModule();
		$result = $db->pquery("SELECT COUNT(*) AS count FROM {$module->baseTable}", array());
		return $db->query_result($result, 0, 'count');
	}
}
