<?php

/**
 * マイグレーション: create_customview_favorites_table
 * 生成日時: 20261029120000
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20261029120000_CreateCustomviewFavoritesTable extends FRMigrationClass
{
    /**
     * マイグレーションを実行する
     */
    public function process(): void
    {
        if (!$this->checkTableExists('vtiger_customview_favorites')) {
            $this->db->query("CREATE TABLE `vtiger_customview_favorites` (
                `userid` int NOT NULL,
                `cvid` int NOT NULL,
                `createdtime` datetime NOT NULL,
                PRIMARY KEY (`userid`, `cvid`),
                KEY `customview_favorites_cvid_idx` (`cvid`),
                CONSTRAINT `fk_1_vtiger_customview_favorites` FOREIGN KEY (`cvid`)
                    REFERENCES `vtiger_customview` (`cvid`) ON DELETE CASCADE,
                CONSTRAINT `fk_2_vtiger_customview_favorites` FOREIGN KEY (`userid`)
                    REFERENCES `vtiger_users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
            $this->log("vtiger_customview_favorites テーブルを作成しました");
        } else {
            $this->log("vtiger_customview_favorites は既に存在するためスキップしました");
        }
    }
}
