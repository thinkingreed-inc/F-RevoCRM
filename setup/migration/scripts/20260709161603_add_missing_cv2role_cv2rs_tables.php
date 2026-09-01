<?php
/**
 * マイグレーション: add_missing_cv2role_cv2rs_tables
 * 生成日時: 20260709161603
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260709161603_AddMissingCv2roleCv2rsTables extends FRMigrationClass {
    
    /**
     * マイグレーションを実行する
     * ここにマイグレーション処理を記述してください
     */
    public function process() {
        // Task 9 (list.cv.shared.other) の調査で判明: このDBには
        // vtiger_cv2role / vtiger_cv2rs テーブルが存在しなかった。
        // setup/sql/dump_firstinstall.sql および
        // modules/Migration/schema/660_to_700.php では標準スキーマとして
        // 定義されているテーブルで、CustomView_Record_Model::getAll() の
        // 非admin向けSQL(cv2role/cv2rsをサブクエリで参照)がこのテーブル欠落により
        // エラーになり、admin以外のユーザーには CustomView(個人/共有リスト)が
        // 一件も表示されなくなっていた(この環境のDB構築時の欠落と判断)。
        // 660_to_700.php の定義に合わせ、IF NOT EXISTS 相当のガードを入れて
        // 冪等に作成する(既存環境で二重実行されても安全)。
        // 参照先 vtiger_role.roleid は utf8mb4 / utf8mb4_general_ci のため、
        // FK 対象の文字列列(roleid/rsid)は同一の charset/collation を明示する
        // (DB 既定は utf8mb4_0900_ai_ci で collation 不一致になり FK が張れないため)。
        // 制約名はスキーマ内で一意である必要があるため、既存 vtiger_customview_ibfk_*
        // との衝突を避けて専用の名前を付ける。
        if (!$this->checkTableExists('vtiger_cv2role')) {
            $this->db->query("CREATE TABLE `vtiger_cv2role` (
                `cvid` int NOT NULL,
                `roleid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                KEY `vtiger_cv2role_cvid_idx` (`cvid`),
                KEY `vtiger_cv2role_roleid_idx` (`roleid`),
                CONSTRAINT `fk_cv2role_cvid` FOREIGN KEY (`cvid`) REFERENCES `vtiger_customview` (`cvid`) ON DELETE CASCADE,
                CONSTRAINT `fk_cv2role_roleid` FOREIGN KEY (`roleid`) REFERENCES `vtiger_role` (`roleid`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $this->log("vtiger_cv2role テーブルを作成しました");
        } else {
            $this->log("vtiger_cv2role は既に存在するためスキップしました");
        }

        if (!$this->checkTableExists('vtiger_cv2rs')) {
            $this->db->query("CREATE TABLE `vtiger_cv2rs` (
                `cvid` int NOT NULL,
                `rsid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                KEY `vtiger_cv2rs_cvid_idx` (`cvid`),
                KEY `vtiger_cv2rs_rsid_idx` (`rsid`),
                CONSTRAINT `fk_cv2rs_cvid` FOREIGN KEY (`cvid`) REFERENCES `vtiger_customview` (`cvid`) ON DELETE CASCADE,
                CONSTRAINT `fk_cv2rs_rsid` FOREIGN KEY (`rsid`) REFERENCES `vtiger_role` (`roleid`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $this->log("vtiger_cv2rs テーブルを作成しました");
        } else {
            $this->log("vtiger_cv2rs は既に存在するためスキップしました");
        }
    }
}