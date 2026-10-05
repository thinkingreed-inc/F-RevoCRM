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
