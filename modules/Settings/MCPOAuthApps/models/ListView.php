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
 * MCP連携アプリケーション管理 - ListView Model
 *
 * 連携一覧のクエリ組み立てとカウント取得を担当。
 * vtiger_mcp_oauth_client と vtiger_users をJOINして名前を表示する。
 */
class Settings_MCPOAuthApps_ListView_Model extends Settings_Vtiger_ListView_Model {

	/** 利用者名で絞り込むときの search_key */
	const FILTER_KEY_USER_NAME = 'user_name';

	/**
	 * 絞り込む利用者名を返す（親クラスの search_key / search_value に乗る）
	 * @return string '' = 絞り込みなし
	 */
	public function getFilterUserName() {
		if ($this->get('search_key') !== self::FILTER_KEY_USER_NAME) {
			return '';
		}
		return trim((string) $this->get('search_value'));
	}

	/**
	 * 一覧に出す利用者名の SQL 式を返す（表示と検索で同じ式を使う）
	 * @return string
	 */
	private function getUserNameExpression() {
		$module = $this->getModule();
		$userNameSql = getSqlForNameInDisplayFormat(
			array('last_name' => 'vtiger_users.last_name', 'first_name' => 'vtiger_users.first_name'),
			'Users'
		);
		return "CASE WHEN {$userNameSql} <> '' THEN {$userNameSql} ELSE CONCAT('User#', {$module->baseTable}.userid) END";
	}

	/**
	 * 利用者名の部分一致条件を返す
	 *
	 * 表示用の CASE WHEN 式（getUserNameExpression()）を検索条件では使い回さず、
	 * 実体である vtiger_users.last_name / first_name（vtiger_field 登録済みの実フィールド）に
	 * 直接 LIKE をかける。
	 *
	 * escape してから '%…%' に組んで直接埋め込む書き方は、CRM 一覧の検索実行エンジン
	 * （QueryGenerator）の contains 演算子と同じ流儀（束縛パラメータではない。
	 * include/QueryGenerator/QueryGenerator.php:1098 の $db->sql_escape_string($value)、
	 * :1160 の case 'c': $value = "%$value%"; を参照）。
	 *
	 * @return string 絞り込みが無ければ空文字
	 */
	private function getUserNameCondition() {
		$userName = $this->getFilterUserName();
		if ($userName === '') {
			return '';
		}
		$db = PearDatabase::getInstance();
		$escaped = $db->sql_escape_string($userName);
		return " AND (vtiger_users.last_name LIKE '%{$escaped}%' OR vtiger_users.first_name LIKE '%{$escaped}%')";
	}

	/**
	 * 一覧取得の基本SQLを組み立てる
	 *
	 * LIMIT は親クラス（Settings/Vtiger/models/ListView.php:78-83）が後付けするため内包しない。
	 * ORDER BY は並び替え指定がない場合のみ既定値を内包する（指定がある場合は親クラスが
	 * 後付けするため、内包すると orderby 付きURLで SQL が失敗する）。
	 * 親クラスは束縛値を渡さない（pquery の第2引数が空配列）ため、
	 * 絞り込み値は sql_escape_string して埋め込む。
	 *
	 * @return string SQL文字列
	 */
	public function getBasicListQuery() {
		$module = $this->getModule();
		// 利用者のフルネーム。空なら User#<id> で代替する
		$userNameExpr = $this->getUserNameExpression();

		$query = "SELECT {$module->baseTable}.id,
					vtiger_mcp_oauth_client.client_name,
					{$userNameExpr} AS user_name,
					{$module->baseTable}.created_at,
					{$module->baseTable}.userid
				FROM {$module->baseTable}
				LEFT JOIN vtiger_mcp_oauth_client ON vtiger_mcp_oauth_client.client_id = {$module->baseTable}.client_id
				LEFT JOIN vtiger_users ON vtiger_users.id = {$module->baseTable}.userid
				WHERE {$module->baseTable}.enabled = 1";

		$query .= $this->getUserNameCondition();

		// 並び替え指定がない場合のみ新しい順を既定とする
		// （指定がある場合は親クラスが ORDER BY を追加する）
		if (!$this->get('orderby')) {
			$query .= " ORDER BY {$module->baseTable}.created_at DESC";
		}

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
	 * レコード総数を返す（失効済みは数えない）
	 * @return int
	 */
	public function getListViewCount() {
		$db = PearDatabase::getInstance();
		$module = $this->getModule();

		// 絞り込み条件が利用者名のため、一覧と同じ JOIN が必要
		$query = "SELECT COUNT(*) AS count
					FROM {$module->baseTable}
					LEFT JOIN vtiger_users ON vtiger_users.id = {$module->baseTable}.userid
				   WHERE {$module->baseTable}.enabled = 1" . $this->getUserNameCondition();

		$result = $db->pquery($query, array());
		if ($result === false) {
			throw new Exception(vtranslate('LBL_MCP_OAUTH_COUNT_FAILED', 'Settings:MCPOAuthApps'));
		}
		return $db->query_result($result, 0, 'count');
	}
}
