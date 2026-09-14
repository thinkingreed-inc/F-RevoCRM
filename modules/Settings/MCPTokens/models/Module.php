<?php
/**
 * MCPトークン管理 - Settings Module Model
 *
 * 設定画面にMCPトークン管理メニューを表示するためのモジュールモデル。
 * vtiger_mcp_token テーブルの一覧表示に使用する。
 */
class Settings_MCPTokens_Module_Model extends Settings_Vtiger_Module_Model {

	var $baseTable = 'vtiger_mcp_token';
	var $baseIndex = 'id';
	var $listFields = array(
		'label'      => 'LBL_LABEL',
		'user_name'  => 'LBL_USER',
		'enabled'    => 'LBL_ENABLED',
		'created_at' => 'LBL_CREATED_AT',
	);

	var $name = 'MCPTokens';

	/**
	 * デフォルトビューのURL
	 * @return string
	 */
	public function getDefaultUrl() {
		return 'index.php?module=MCPTokens&parent=Settings&view=List';
	}
}
