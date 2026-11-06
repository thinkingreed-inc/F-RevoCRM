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
 * OAuth 2.1 認可エンドポイント（同意画面）
 * GET index.php?module=Users&view=McpOAuthAuthorize
 *
 * フロー:
 *   1. 未ログインなら本体の checkLogin() がログイン画面へ送る（認可側はログイン処理を持たない）
 *   2. OAuthパラメータ検証 → 同意画面表示
 *   3. 許可/拒否の受付と認可コード発行は Users_McpOAuthConsent_Action
 *
 * OAuthパラメータの検証とリダイレクトは同意処理側でもやり直すため、
 * 双方から呼べる Users_McpOAuth_Helper に持つ。
 */
class Users_McpOAuthAuthorize_View extends Vtiger_Index_View
{
    /**
     * 権限チェックは requiresPermission/checkPermission を無効化する（権限行=空配列）。
     * ログイン済みユーザーは誰でも「自分のアカウント」として認可できればよく、
     * 追加の権限は必要ないため。
     */
    public function requiresPermission(\Vtiger_Request $request) {
        return array();
    }

    /**
     * 認可URLは外部のMCPクライアントから開かれるため、リファラ検証を通さない
     */
    public function validateRequest(Vtiger_Request $request) {
    }

    /**
     * 同意画面は自前で完結したHTMLを返すため、CRM共通のヘッダ・フッタを出力しない
     */
    public function preProcess(Vtiger_Request $request, $display = true) {
        return false;
    }

    public function postProcess(Vtiger_Request $request) {
        return false;
    }

    public function process(Vtiger_Request $request) {
        $params = Users_McpOAuth_Helper::collectOAuthParams($request);
        $validation = Users_McpOAuth_Helper::validateOAuthParams($params);

        if (isset($validation['message_key'])) {
            Users_McpOAuth_Helper::renderErrorPage($validation['message_key']);
            return;
        }
        if (isset($validation['error'])) {
            Users_McpOAuth_Helper::redirectWithError($params['redirect_uri'], $validation['error'], $validation['error_description'], $params['state']);
            return;
        }

        $client = $validation['client'];
        $currentUser = Users_Record_Model::getCurrentUserModel();
        $userName = $currentUser->getDisplayName();
        if (empty($userName)) {
            $userName = $currentUser->get('user_name');
        }

        $viewer = $this->getViewer($request);
        $viewer->assign('MCP_OAUTH_ERROR', false);
        $viewer->assign('MCP_OAUTH_CLIENT_NAME', Users_McpOAuth_Helper::getClientDisplayName($client));
        $viewer->assign('MCP_OAUTH_USER_NAME', $userName);
        $viewer->assign('MCP_OAUTH_PERMISSION_LABELS', Users_McpOAuth_Helper::getScopePermissionLabels($params['scope']));
        $viewer->assign('MCP_OAUTH_PARAMS', $params);
        $viewer->view('McpOAuthAuthorize.tpl', 'Users');
    }
}
