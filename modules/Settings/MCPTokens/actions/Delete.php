<?php
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
			if ($currentUser->get('is_admin') !== 'on') {
				throw new Exception('管理者権限が必要です');
			}

			$recordId = (int) $request->get('record');
			if ($recordId <= 0) {
				throw new Exception('無効なレコードIDです');
			}

			$db = PearDatabase::getInstance();

			// レコード存在確認
			$check = $db->pquery(
				'SELECT id, enabled FROM vtiger_mcp_token WHERE id = ?',
				array($recordId)
			);
			if ($db->num_rows($check) === 0) {
				throw new Exception('指定されたトークンが見つかりません');
			}

			// 無効化（物理削除ではない）
			$db->pquery(
				'UPDATE vtiger_mcp_token SET enabled = 0 WHERE id = ?',
				array($recordId)
			);

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
