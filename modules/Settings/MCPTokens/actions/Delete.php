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
 * MCPトークン管理 - 無効化Action
 *
 * 指定トークンを物理削除せず enabled=0 に更新する（無効化）。
 * トークン消滅を防ぐため物理削除は行わない方針。
 */
class Settings_MCPTokens_Delete_Action extends Settings_Vtiger_Basic_Action {

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();

		try {
			// 管理者権限チェック（Settings配下だがAction側でも明示）
			$currentUser = Users_Record_Model::getCurrentUserModel();
			if (!$currentUser->isAdminUser()) {
				throw new Exception(vtranslate('LBL_MCP_ADMIN_REQUIRED', 'Settings:MCPTokens'));
			}

			$recordId = (int) $request->get('record');
			if ($recordId <= 0) {
				throw new Exception(vtranslate('LBL_MCP_INVALID_RECORD', 'Settings:MCPTokens'));
			}

			require_once 'include/Mcp/TokenAuth.php';
			if (Mcp_TokenAuth::getById($recordId) === null) {
				throw new Exception(vtranslate('LBL_MCP_TOKEN_NOT_FOUND', 'Settings:MCPTokens'));
			}

			// 無効化（物理削除ではない）
			Mcp_TokenAuth::disable($recordId);

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
