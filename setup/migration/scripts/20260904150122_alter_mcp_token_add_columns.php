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
 * マイグレーション: alter_mcp_token_add_columns
 * 生成日時: 20260904150122
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260904150122_AlterMcpTokenAddColumns extends FRMigrationClass
{
    /**
     * vtiger_mcp_token に接頭辞、有効期限、最終使用日時を追加
     */
    public function process()
    {
        $this->addColumnIfMissing('token_prefix', "varchar(20) NULL COMMENT '表示用の接頭辞'");
        $this->addColumnIfMissing('expires_at', "datetime NULL COMMENT '有効期限。NULL=無期限'");
        $this->addColumnIfMissing('last_used_at', "datetime NULL COMMENT '最終使用日時。NULL=未使用'");

        $this->log("マイグレーション alter_mcp_token_add_columns が正常に完了しました");
    }

    /**
     * DDL は暗黙的にコミットされるため、再実行時の Duplicate column name を防ぐ目的で
     * ADD COLUMN の前に checkColumnExists() でカラムの存在を確認する。
     */
    private function addColumnIfMissing($column, $definition)
    {
        if ($this->checkColumnExists('vtiger_mcp_token', $column)) {
            $this->log($column . ' 列は既に存在するためスキップしました');
            return;
        }
        $result = $this->db->query("ALTER TABLE vtiger_mcp_token ADD COLUMN {$column} {$definition}");
        if ($result === false) {
            throw new Exception($column . ' 列の追加に失敗しました');
        }
    }
}
