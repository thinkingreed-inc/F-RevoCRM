<?php
/**
 * マイグレーション: fix_workflow_task_serialization
 * 生成日時: 20260205065656
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260205065656_FixWorkflowTaskSerialization extends FRMigrationClass {
    
    /**
     * マイグレーションを実行する
     *
     * Issue #1285: ワークフロータスクのシリアライズ形式を修正
     * PHP 8.3でunserialize()が失敗する不正な形式 s:0:"1" を s:1:"1" に修正
     */
    public function process() {
        // 不正なシリアライズ形式を修正
        // s:0:"1" は「長さ0の文字列」と宣言しながら "1" を持つ不正な形式
        // 正しくは s:1:"1"（長さ1の文字列 "1"）
        // 対象は初期インストール時のデフォルトタスク（task_id: 1-16）のみ
        $sql = "UPDATE com_vtiger_workflowtasks
                SET task = REPLACE(task, 's:0:\"1\"', 's:1:\"1\"')
                WHERE task LIKE '%s:0:\"1\"%'
                AND task_id <= 16";
        $this->db->pquery($sql, array());

        $this->log("Fixed workflow task serialization format (Issue #1285)");
    }
}