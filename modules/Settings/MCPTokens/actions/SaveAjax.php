<?php
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
			if ($currentUser->get('is_admin') !== 'on') {
				throw new Exception('管理者権限が必要です');
			}

			$userid = (int) $request->get('userid');
			$label  = trim($request->get('label'));
			$expiresDays = (int) $request->get('expires_days');

			// バリデーション
			if ($userid <= 0) {
				throw new Exception('ユーザーを選択してください');
			}
			if ($label === '') {
				throw new Exception('ラベルを入力してください');
			}
			if (mb_strlen($label) > 100) {
				throw new Exception('ラベルは100文字以内で入力してください');
			}
			require_once 'include/Mcp/TokenAuth.php';
			if (!in_array($expiresDays, Mcp_TokenAuth::ALLOWED_EXPIRES_DAYS, true)) {
				throw new Exception('有効期限の指定が不正です');
			}

			// 指定ユーザーが存在し、Activeかチェック
			$db = PearDatabase::getInstance();
			$userCheck = $db->pquery(
				'SELECT id FROM vtiger_users WHERE id = ? AND status = ?',
				array($userid, 'Active')
			);
			if ($db->num_rows($userCheck) === 0) {
				throw new Exception('指定されたユーザーが見つからないか無効です');
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
