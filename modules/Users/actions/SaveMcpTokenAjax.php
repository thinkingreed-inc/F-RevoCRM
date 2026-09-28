<?php
/*+***********************************************************************************
 * The contents of this file are subject to the Vtiger Public License Version 1.2
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: frevo-mcp (https://github.com/ratorin/frevo-mcp)
 * The Initial Developer of the Original Code is ratorin.
 * Portions created by ratorin are Copyright (C) ratorin.
 * All Rights Reserved.
 *************************************************************************************/

class Users_SaveMcpTokenAjax_Action extends Vtiger_SaveAjax_Action {

    public function requiresPermission(\Vtiger_Request $request) {
        return array();
    }

    /**
     * 発行対象は常に操作者本人のため、ログイン済みであれば通す。
     * 対象ユーザーはリクエストから受け取らない（process 参照）。
     */
    public function checkPermission(Vtiger_Request $request) {
    }

    /**
     * CSRF 対策: 書き込みアクセスを検証する
     */
    public function validateRequest(Vtiger_Request $request) {
        $request->validateWriteAccess();
    }

    public function process(Vtiger_Request $request) {
        $response = new Vtiger_Response();
        $response->setEmitType(Vtiger_Response::$EMIT_JSON);

        try {
            // 対象ユーザーはリクエストではなく操作者本人に固定する
            $currentUser = Users_Record_Model::getCurrentUserModel();
            $userid = (int) $currentUser->getId();

            $label = trim((string) $request->get('label'));
            if ($label === '') {
                throw new Exception(vtranslate('LBL_MCP_TOKEN_LABEL_REQUIRED', 'Users'));
            }
            if (mb_strlen($label) > 100) {
                throw new Exception(vtranslate('LBL_MCP_TOKEN_LABEL_TOO_LONG', 'Users'));
            }

            require_once 'include/Mcp/TokenAuth.php';
            $expiresDays = (int) $request->get('expires_days');
            if (!in_array($expiresDays, Mcp_TokenAuth::ALLOWED_EXPIRES_DAYS, true)) {
                throw new Exception(vtranslate('LBL_MCP_TOKEN_EXPIRES_INVALID', 'Users'));
            }

            $rawToken = Mcp_TokenAuth::generateToken($userid, $label, $expiresDays ?: null);

            // 発行した行の表示用データ（一覧に即時追加するため。平文以外）
            $db = PearDatabase::getInstance();
            $newRow = $db->pquery(
                'SELECT id, token_prefix, created_at, expires_at FROM vtiger_mcp_token WHERE token_hash = ?',
                array(hash('sha256', $rawToken))
            );
            $rowId   = (int) $db->query_result($newRow, 0, 'id');
            $prefix  = $db->query_result($newRow, 0, 'token_prefix');
            $created = $db->query_result($newRow, 0, 'created_at');
            $expires = $db->query_result($newRow, 0, 'expires_at');

            // 平文トークンはこの 1 回だけ返す
            $response->setResult(array(
                'success'    => true,
                'token'      => $rawToken,
                'id'         => $rowId,
                'label'      => $label,
                'prefix'     => $prefix ?: '',
                'created_at' => $created,
                'expires_at' => $expires,
            ));
        } catch (Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }

        $response->emit();
    }
}
