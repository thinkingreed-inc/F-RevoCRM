<?php

/*+***********************************************************************************
 * The contents of this file are subject to the Vtiger Public License Version 1.2
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is: frevo-mcp (https://github.com/ratorin/frevo-mcp)
 * The Initial Developer of the Original Code is ratorin.
 * Portions created by ratorin are Copyright (C) ratorin.
 * All Rights Reserved.
 *************************************************************************************/

// PHPUnit テスト環境用: MCP の失効アクション（Users_DeleteMcpTokenAjax_Action /
// Users_DeleteMcpOAuthAjax_Action）を DB なしで動かすためのスタブ。
//
// - Users_Record_Model: ParametersApiTestSupport.php の共有スタブを使う
//   （id と管理者フラグは ParametersApiTestState で切り替える）。
// - McpTokenTableStub: PearDatabase::getInstance() が返す $adb の代替。
//   getById() の SELECT と disable() の UPDATE だけをメモリ上の行に対して評価する。

$root = dirname(__DIR__, 2);

require_once __DIR__ . '/VtigerActionTestSupport.php';
require_once __DIR__ . '/ParametersApiTestSupport.php';
require_once $root . '/includes/http/Response.php';
require_once $root . '/include/Zend/Json.php';

/**
 * vtiger_mcp_token / vtiger_mcp_oauth_token を 1 表だけ持つ PearDatabase の代替。
 */
class McpTokenTableStub extends \PearDatabase
{
    private string $table;

    /** @var array<int, array<string, int>> id => 行 */
    private array $rows;

    /** @var int[] UPDATE で失効させた id の記録 */
    public array $disabledIds = [];

    /** @param array<int, array<string, int>> $rows */
    public function __construct(string $table, array $rows)
    {
        // 親は接続設定を要求するため呼ばない
        $this->table = $table;
        $this->rows = $rows;
    }

    /**
     * @param string $sql
     * @param array<int, mixed> $params
     * @param bool $dieOnError
     * @param string $msg
     * @return \ArrayIterator<int, array<string, int>>|bool
     */
    public function pquery($sql, $params = [], $dieOnError = false, $msg = '')
    {
        $id = is_numeric($params[0] ?? null) ? (int) $params[0] : 0;
        if (preg_match('/^SELECT .* FROM ' . $this->table . ' WHERE id = \?$/', $sql)) {
            return new \ArrayIterator(isset($this->rows[$id]) ? [$this->rows[$id]] : []);
        }
        if ($sql === "UPDATE {$this->table} SET enabled = 0 WHERE id = ?") {
            $this->disabledIds[] = $id;

            return true;
        }
        throw new \LogicException('Unexpected SQL: ' . $sql);
    }

    /**
     * @param \ArrayIterator<int, array<string, int>> $result
     * @return int
     */
    public function num_rows(&$result)
    {
        return count($result);
    }

    /**
     * @param \ArrayIterator<int, array<string, int>> $result
     * @param int $row
     * @param string|int $col
     * @return int|null
     */
    public function query_result(&$result, $row, $col = 0)
    {
        return $result[$row][$col] ?? null;
    }
}
