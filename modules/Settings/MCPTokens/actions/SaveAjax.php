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
 * MCPトークン管理 - 発行Action
 *
 * POSTで userid と label を受け取り、Mcp_TokenAuth::generateToken() で
 * トークンを発行する。平文トークンはレスポンスで1度だけ返す。
 * ログ・DB・画面再表示に平文を絶対残さない。
 */
class Settings_MCPTokens_SaveAjax_Action extends Settings_Vtiger_Basic_Action {

	public function process(Vtiger_Request $request) {
		$response = new Vtiger_Response();

		try {
			// 管理者権限チェック（Settings配下だがAction側でも明示）
			$currentUser = Users_Record_Model::getCurrentUserModel();
			if (!$currentUser->isAdminUser()) {
				throw new Exception(vtranslate('LBL_MCP_ADMIN_REQUIRED', 'Settings:MCPTokens'));
			}

			$userid = (int) $request->get('userid');
			$label  = trim($request->get('label'));
			$expiresDays = (int) $request->get('expires_days');

			// バリデーション
			if ($userid <= 0) {
				throw new Exception(vtranslate('LBL_MCP_SELECT_USER_REQUIRED', 'Settings:MCPTokens'));
			}
			if ($label === '') {
				throw new Exception(vtranslate('LBL_MCP_LABEL_REQUIRED', 'Settings:MCPTokens'));
			}
			if (mb_strlen($label) > 100) {
				throw new Exception(vtranslate('LBL_MCP_LABEL_TOO_LONG', 'Settings:MCPTokens'));
			}
			require_once 'include/Mcp/TokenAuth.php';
			if (!in_array($expiresDays, Mcp_TokenAuth::ALLOWED_EXPIRES_DAYS, true)) {
				throw new Exception(vtranslate('LBL_MCP_INVALID_EXPIRES', 'Settings:MCPTokens'));
			}

			// 指定ユーザーが存在し、Activeかチェック
			$db = PearDatabase::getInstance();
			$userCheck = $db->pquery(
				'SELECT id FROM vtiger_users WHERE id = ? AND status = ?',
				array($userid, 'Active')
			);
			if ($db->num_rows($userCheck) === 0) {
				throw new Exception(vtranslate('LBL_MCP_USER_NOT_FOUND', 'Settings:MCPTokens'));
			}

			// トークン発行（平文は戻り値で1度だけ取得）
			$rawToken = Mcp_TokenAuth::generateToken($userid, $label, $expiresDays ?: null);

			// 平文トークンをレスポンスで返す（この1回きり）
			$response->setResult(array(
				'success' => true,
				'token'   => $rawToken,
				'label'   => htmlspecialchars($label, ENT_QUOTES, 'UTF-8'),
			));

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
