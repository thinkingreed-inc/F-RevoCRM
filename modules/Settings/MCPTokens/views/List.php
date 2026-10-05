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
 * MCPトークン管理 - List View
 *
 * 一覧画面を表示し、「新規発行」ボタンと無効化操作を提供する。
 * アクティブユーザー一覧をテンプレートに渡してモーダルの選択肢にする。
 */
class Settings_MCPTokens_List_View extends Settings_Vtiger_List_View {

	public function process(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		// アクティブユーザー一覧（発行モーダルの選択肢）
		$activeUsers = Settings_MCPTokens_Record_Model::getActiveUsers();
		$viewer->assign('ACTIVE_USERS', $activeUsers);
		parent::process($request);
	}
}
