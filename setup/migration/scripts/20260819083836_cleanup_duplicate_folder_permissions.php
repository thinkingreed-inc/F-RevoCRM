<?php
/**
 * マイグレーション: cleanup_duplicate_folder_permissions
 * 生成日時: 20260819083836
 *
 * フォルダ権限（vtiger_folder_permissions）に溜まった不要な行を片付ける。
 *
 *   1. 存在しないフォルダに対する行（フォルダ作成が失敗しても権限行だけ残っていた）
 *   2. 内容が同じ重複行（target_id が NULL の行は UNIQUE 制約で防げず、
 *      MySQL が NULL を別値として扱うため「全員」の行が積み上がっていた）
 *
 * 権限の内容自体は変えない（同じ内容の行を1件に畳むだけ）。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260819083836_CleanupDuplicateFolderPermissions extends FRMigrationClass {

    /** 権限テーブル名 */
    const PERMISSIONS_TABLE = 'vtiger_folder_permissions';

    public function process() {
        if (!$this->checkTableExists(self::PERMISSIONS_TABLE)) {
            $this->log('テーブル ' . self::PERMISSIONS_TABLE . ' が存在しないためスキップします');
            return;
        }

        $this->deleteOrphanRows();
        $this->deleteDuplicateRows();

        $this->log('マイグレーション cleanup_duplicate_folder_permissions が正常に完了しました');
    }

    /**
     * 存在しないフォルダに紐づく権限行を削除する
     */
    private function deleteOrphanRows() {
        $result = $this->db->pquery(
            'DELETE fp FROM ' . self::PERMISSIONS_TABLE . ' fp
            LEFT JOIN vtiger_attachmentsfolder f ON f.folderid = fp.folderid
            WHERE f.folderid IS NULL',
            array()
        );
        if ($result === false) {
            $this->log('存在しないフォルダの権限行の削除に失敗しました');
            return;
        }
        $this->log('存在しないフォルダの権限行を削除しました');
    }

    /**
     * 同じ内容の重複行を、最小の permission_id だけ残して削除する
     *
     * target_id が NULL の場合も同一視するため、比較には NULL セーフ等価（<=>）を使う。
     */
    private function deleteDuplicateRows() {
        $result = $this->db->pquery(
            'DELETE fp FROM ' . self::PERMISSIONS_TABLE . ' fp
            INNER JOIN ' . self::PERMISSIONS_TABLE . ' keep
                ON keep.folderid = fp.folderid
                AND keep.permission_type = fp.permission_type
                AND keep.target_type = fp.target_type
                AND keep.target_id <=> fp.target_id
                AND keep.permission_id < fp.permission_id',
            array()
        );
        if ($result === false) {
            $this->log('重複した権限行の削除に失敗しました');
            return;
        }
        $this->log('重複した権限行を1件に畳みました');
    }
}
