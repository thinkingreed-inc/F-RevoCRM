<?php
/*+***********************************************************************************
 * The contents of this file are subject to the Vtiger Public License Version 1.2
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: frevo-mcp (https://github.com/ratorin/frevo-mcp)
 * The Initial Developer of the Original Code is ratorin.
 * Portions created by ratorin are Copyright (C) ratorin.
 * All Rights Reserved.
 *************************************************************************************/

class Users_DeleteMcpTokenAjax_Action extends Vtiger_Action_Controller {

    public function requiresPermission(\Vtiger_Request $request) {
        return array();
    }

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
            $currentUser = Users_Record_Model::getCurrentUserModel();
            $recordId = (int) $request->get('record');
            if ($recordId <= 0) {
                throw new Exception(vtranslate('LBL_MCP_TOKEN_INVALID_RECORD', 'Users'));
            }

            require_once 'include/Mcp/TokenAuth.php';
            $token = Mcp_TokenAuth::getById($recordId);
            if ($token === null) {
                throw new Exception(vtranslate('LBL_MCP_TOKEN_NOT_FOUND', 'Users'));
            }

            // 画面のパラメータではなく、取得した行の userid で判定する
            $isOwner = ((int) $token['userid'] === (int) $currentUser->getId());
            if (!$isOwner && !$currentUser->isAdminUser()) {
                throw new Exception(vtranslate('LBL_MCP_TOKEN_PERMISSION_DENIED', 'Users'));
            }

            Mcp_TokenAuth::disable($recordId);
            $response->setResult(array('success' => true));
        } catch (Exception $e) {
            $response->setError($e->getCode(), $e->getMessage());
        }

        $response->emit();
    }
}
