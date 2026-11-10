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
 * MCP連携アプリケーション管理 - Settings Module Model
 *
 * 設定画面に MCP 連携アプリケーション管理メニューを表示するためのモジュールモデル。
 * vtiger_mcp_oauth_token テーブルの一覧表示に使用する。
 */
class Settings_MCPOAuthApps_Module_Model extends Settings_Vtiger_Module_Model {

	var $baseTable = 'vtiger_mcp_oauth_token';
	var $baseIndex = 'id';
	var $listFields = array(
		'client_name' => 'LBL_MCP_OAUTH_APP_NAME',
		'user_name'   => 'LBL_USER',
		'created_at'  => 'LBL_MCP_OAUTH_CONNECTED_AT',
	);

	var $name = 'MCPOAuthApps';

	/**
	 * デフォルトビューのURL
	 * @return string
	 */
	public function getDefaultUrl() {
		return 'index.php?module=MCPOAuthApps&parent=Settings&view=List';
	}
}
