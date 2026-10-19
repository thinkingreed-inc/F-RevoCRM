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
 * MCP トークン系（固定Bearer / OAuth）共通ヘルパー
 *
 * 認証方式は独立（Mcp_TokenAuth / Mcp_OAuthStorage）だが、失効処理
 * （行の物理削除ではなく enabled=0 にする軟削除）は両方式で同形のため
 * ここに集約する。テーブル名・エラーメッセージだけが異なる。
 */

/**
 * 指定テーブルの1行を失効させる（enabled=0）。
 *
 * @param string $table        vtiger_mcp_token または vtiger_mcp_oauth_token
 * @param int    $id           行ID
 * @param string $errorMessage 失敗時にthrowするException メッセージ
 * @throws Exception SQL実行に失敗したとき
 */
function mcp_disable_token_row(string $table, int $id, string $errorMessage): void
{
    $db = PearDatabase::getInstance();
    $result = $db->pquery("UPDATE {$table} SET enabled = 0 WHERE id = ?", [$id]);
    if ($result === false) {
        throw new Exception($errorMessage);
    }
}
