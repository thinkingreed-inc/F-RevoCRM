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
 * MCP連携アプリケーション管理 - 解除Action
 *
 * 指定の連携を物理削除せず enabled=0 に更新する（軟削除）。
 * 失効処理は個人設定・失効端点と同じ Mcp_OAuthStorage::disable() に収束させる。
 */
class Settings_MCPOAuthApps_Delete_Action extends Settings_Vtiger_Basic_Action {

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();

		try {
			// 管理者権限チェック（Settings配下だがAction側でも明示）
			$currentUser = Users_Record_Model::getCurrentUserModel();
			if (!$currentUser->isAdminUser()) {
				throw new Exception(vtranslate('LBL_MCP_OAUTH_ADMIN_REQUIRED', 'Settings:MCPOAuthApps'));
			}

			$recordId = (int) $request->get('record');
			if ($recordId <= 0) {
				throw new Exception(vtranslate('LBL_MCP_OAUTH_INVALID_RECORD', 'Settings:MCPOAuthApps'));
			}

			require_once 'include/Mcp/OAuthStorage.php';
			if (Mcp_OAuthStorage::getById($recordId) === null) {
				throw new Exception(vtranslate('LBL_MCP_OAUTH_NOT_FOUND', 'Settings:MCPOAuthApps'));
			}

			// 解除（物理削除ではない）
			Mcp_OAuthStorage::disable($recordId);

			$response->setResult(array('success' => true));

		} catch (Exception $e) {
			$response->setError($e->getCode(), $e->getMessage());
		}

		$response->emit();
	}

	/**
	 * CSRF対策: 書き込みアクセスを検証
	 */
	public function validateRequest(Vtiger_Request $request) {
		$request->validateWriteAccess();
	}
}
