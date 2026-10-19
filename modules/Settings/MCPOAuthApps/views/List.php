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
 * MCP連携アプリケーション管理 - List View
 *
 * 全利用者の OAuth 連携を1画面で確認・解除する。
 * 絞り込みは利用者名の部分一致（一覧の検索行から送られる）。
 */
class Settings_MCPOAuthApps_List_View extends Settings_Vtiger_List_View {

	public function preProcess(Vtiger_Request $request, $display = true) {
		$this->assignFilterData($request);
		parent::preProcess($request, $display);
	}

	public function process(Vtiger_Request $request) {
		$this->assignFilterData($request);
		parent::process($request);
	}

	/**
	 * 絞り込み用のデータをテンプレートへ渡す
	 * @param Vtiger_Request $request
	 */
	private function assignFilterData(Vtiger_Request $request) {
		$viewer = $this->getViewer($request);
		// 絞り込みは親クラスの search_key / search_value に乗せる
		$isUserNameSearch = ($request->get('search_key') === Settings_MCPOAuthApps_ListView_Model::FILTER_KEY_USER_NAME);
		$viewer->assign('FILTER_USER_NAME', $isUserNameSearch ? trim($request->get('search_value')) : '');
	}
}
