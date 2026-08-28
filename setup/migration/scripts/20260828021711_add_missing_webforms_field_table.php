<?php
/**
 * マイグレーション: add_missing_webforms_field_table
 * 生成日時: 20260828021711
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260828021711_AddMissingWebformsFieldTable extends FRMigrationClass {

    /**
     * マイグレーションを実行する
     */
    public function process() {
        // E2E で Webフォームの編集画面(Settings > 自動化 > Webフォーム)を開いたところ
        // 次の Fatal error になった:
        //   array_key_exists(): Argument #2 ($array) must be of type array, null given
        //   in .../Settings/Webforms/FieldsEditView.tpl.php
        // 原因は vtiger_webforms_field テーブルの欠落。
        // Settings_Webforms_Record_Model::getSelectedFieldsList() がこのテーブルを
        // SELECT しており、テーブルが無いとクエリが失敗して選択項目リストが
        // null のままテンプレートに渡り、上記の Fatal error になる。
        //
        // setup/sql/dump_firstinstall.sql および modules/Migration/schema/660_to_700.php
        // では標準スキーマとして定義されているテーブルで、この環境の DB 構築時に
        // 欠落したものと判断する(vtiger_cv2role / vtiger_cv2rs と同じ経緯。
        // 20260709161603_add_missing_cv2role_cv2rs_tables.php を参照)。
        //
        // 定義は dump_firstinstall.sql に合わせる。FK は vtiger_webforms(id) のみ張り、
        // vtiger_field.fieldname は一意ではないため FK ではなくトリガーで追従させる
        // (dump_firstinstall.sql と同じ扱い)。
        if (!$this->checkTableExists('vtiger_webforms_field')) {
            $this->db->query("CREATE TABLE `vtiger_webforms_field` (
                `id` int NOT NULL AUTO_INCREMENT,
                `webformid` int NOT NULL,
                `fieldname` varchar(50) NOT NULL,
                `neutralizedfield` varchar(50) NOT NULL,
                `defaultvalue` text,
                `required` int NOT NULL DEFAULT '0',
                `sequence` int DEFAULT NULL,
                `hidden` int DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `webforms_webforms_field_idx` (`id`),
                KEY `fk_1_vtiger_webforms_field` (`webformid`),
                KEY `fk_2_vtiger_webforms_field` (`fieldname`),
                CONSTRAINT `fk_1_vtiger_webforms_field` FOREIGN KEY (`webformid`)
                    REFERENCES `vtiger_webforms` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $this->log("vtiger_webforms_field テーブルを作成しました");
        } else {
            $this->log("vtiger_webforms_field は既に存在するためスキップしました");
        }

        // vtiger_field が削除されたとき、対応する Webフォーム項目も消す
        // (dump_firstinstall.sql に含まれるトリガー)。
        $this->db->query("DROP TRIGGER IF EXISTS `tr_vtiger_field_delete_webforms_field`");
        $this->db->query("CREATE TRIGGER `tr_vtiger_field_delete_webforms_field`
            AFTER DELETE ON `vtiger_field`
            FOR EACH ROW
            DELETE FROM `vtiger_webforms_field` WHERE `fieldname` = OLD.`fieldname`");
        $this->log("トリガー tr_vtiger_field_delete_webforms_field を作成しました");
    }
}
