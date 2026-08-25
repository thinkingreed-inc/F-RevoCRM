<?php
/**
 * マイグレーション: add_folder_owner_permission
 * 生成日時: 20260825025049
 *
 * ドキュメントフォルダの権限に「オーナー」を追加する。
 *
 * これまでフォルダの権限を変更できるのは管理者だけだったため、一般ユーザーは
 * 自分で作ったフォルダでも公開範囲を決められず、チーム限定のフォルダを作るのに
 * 毎回管理者へ依頼する必要があった。
 *
 * 権限は強い順に owner > edit > view で、強い権限は弱い権限を兼ねる。
 *   view  参照のみ
 *   edit  参照＋変更
 *   owner 参照＋変更＋そのフォルダの権限設定
 *
 * 既存フォルダにはオーナーがいないため、作成者（createdby）をオーナーにする。
 * これを入れないと、既に作ってあるフォルダは今までどおり管理者しか
 * 権限を変えられないままになる。
 * 元に戻したい場合は該当フォルダの permission_type = 'owner' の行を削除する。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260825025049_AddFolderOwnerPermission extends FRMigrationClass {

    /** 権限テーブル */
    const TABLE = 'vtiger_folder_permissions';

    public function process() {
        if (!$this->checkTableExists(self::TABLE)) {
            $this->log(self::TABLE . ' が無いためスキップします');
            return;
        }

        $this->updateColumnComment();
        $this->backfillOwners();
    }

    /**
     * permission_type の説明に owner を足す（値の意味をテーブル定義からも追えるように）
     */
    private function updateColumnComment() {
        $this->db->pquery(
            'ALTER TABLE ' . self::TABLE . " MODIFY COLUMN permission_type
             VARCHAR(10) COLLATE utf8mb4_unicode_ci NOT NULL
             COMMENT 'view, edit or owner'", array());
        $this->log('permission_type の説明に owner を追加しました');
    }

    /**
     * 既存フォルダの作成者をオーナーにする
     *
     * 既にオーナーがいるフォルダ、作成者が退職・削除済みのフォルダは対象外。
     */
    private function backfillOwners() {
        $result = $this->db->pquery(
            'SELECT f.folderid, f.createdby
             FROM vtiger_attachmentsfolder f
             INNER JOIN vtiger_users u ON u.id = f.createdby AND u.deleted = 0
             WHERE NOT EXISTS (
               SELECT 1 FROM ' . self::TABLE . ' fp
                WHERE fp.folderid = f.folderid AND fp.permission_type = ?
             )',
            array('owner'));
        if ($result === false) {
            $this->log('既存フォルダの取得に失敗したため、オーナーの補完をスキップします');
            return;
        }

        $added = 0;
        $count = $this->db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $row = $this->db->query_result_rowdata($result, $i);
            $this->db->pquery(
                'INSERT IGNORE INTO ' . self::TABLE . '
                    (folderid, permission_type, target_type, target_id)
                 VALUES (?, ?, ?, ?)',
                array((int) $row['folderid'], 'owner', 'user', (string) $row['createdby']));
            $added++;
        }
        $this->log("既存フォルダ {$added}件に作成者をオーナーとして設定しました");
    }

}
