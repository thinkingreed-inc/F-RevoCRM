<?php
/**
 * マイグレーション: create_holidays_master
 * 生成日時: 20260806084006
 *
 * 休祝日マスタ（vtiger_holidays）を作成し、システム管理者向けの設定メニューに登録する。
 * 営業日計算を必要とする機能（ドキュメントの入力期限など）から共通で参照する。
 *
 * day_type
 *   holiday : 休日（営業日に数えない）
 *   workday : 所定休日だが営業日として扱う日（休日出勤日・振替出勤日）
 * holiday_type
 *   national: 国民の祝日 / company: 会社休日 / other: その他
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
require_once 'include/utils/JapaneseHolidays.php';

class Migration20260806084006_CreateHolidaysMaster extends FRMigrationClass {

    /** 初期登録する年数（実行年の前年から数えて4年分） */
    const SEED_YEARS = 4;

    /** 設定メニューを追加するブロック（システム構成） */
    const SETTINGS_BLOCK = 'LBL_CONFIGURATION';

    public function process() {
        $this->createTable();
        $this->registerSettingsMenu();
        $this->seedNationalHolidays();
    }

    /**
     * 休祝日マスタのテーブルを作成する
     */
    private function createTable() {
        if ($this->tableExists('vtiger_holidays')) {
            $this->log("テーブル vtiger_holidays は既に存在するためスキップします");
            return;
        }

        $this->db->pquery("CREATE TABLE vtiger_holidays (
            holidayid INT AUTO_INCREMENT,
            holiday_date DATE NOT NULL,
            holiday_name VARCHAR(200) NOT NULL,
            day_type VARCHAR(20) NOT NULL DEFAULT 'holiday' COMMENT 'holiday: 休日 / workday: 休日だが営業日',
            holiday_type VARCHAR(20) NOT NULL DEFAULT 'company' COMMENT 'national: 国民の祝日 / company: 会社休日 / other: その他',
            description TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            modified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (holidayid),
            UNIQUE INDEX idx_holidays_date (holiday_date),
            INDEX idx_holidays_type (holiday_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", array());
        $this->log("テーブル vtiger_holidays を作成しました");
    }

    /**
     * 設定画面のメニューに休祝日マスタを追加する
     */
    private function registerSettingsMenu() {
        $existing = $this->db->pquery(
            "SELECT fieldid FROM vtiger_settings_field WHERE name = ?",
            array('LBL_HOLIDAYS')
        );
        if ($existing !== false && $this->db->num_rows($existing) > 0) {
            $this->log("設定メニュー LBL_HOLIDAYS は既に登録済みのためスキップします");
            return;
        }

        // 「システム構成」ブロックに追加する
        $blockResult = $this->db->pquery(
            "SELECT blockid FROM vtiger_settings_blocks WHERE label = ?",
            array(self::SETTINGS_BLOCK)
        );
        if ($blockResult === false || $this->db->num_rows($blockResult) === 0) {
            $this->log("ブロック " . self::SETTINGS_BLOCK . " が見つからないため設定メニューの登録をスキップします");
            return;
        }
        $blockId = (int) $this->db->query_result($blockResult, 0, 'blockid');

        $sequenceResult = $this->db->pquery(
            "SELECT COALESCE(MAX(sequence), 0) + 1 AS next_seq FROM vtiger_settings_field WHERE blockid = ?",
            array($blockId)
        );
        $sequence = (int) $this->db->query_result($sequenceResult, 0, 'next_seq');
        $fieldId = $this->db->getUniqueID('vtiger_settings_field');

        $this->db->pquery(
            "INSERT INTO vtiger_settings_field (fieldid, blockid, name, iconpath, description, linkto, sequence, active)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0)",
            array(
                $fieldId,
                $blockId,
                'LBL_HOLIDAYS',
                'adminIcon-calendar',
                'LBL_HOLIDAYS_DESCRIPTION',
                'index.php?module=Holidays&parent=Settings&view=List',
                $sequence,
            )
        );
        $this->log("設定メニューに休祝日マスタを追加しました（sequence={$sequence}）");
    }

    /**
     * 国民の祝日の初期データを登録する（実行年の前年から4年分）
     */
    private function seedNationalHolidays() {
        $startYear = max((int) date('Y') - 1, FR_JapaneseHolidays::SUPPORTED_FROM_YEAR);
        $registered = 0;

        for ($i = 0; $i < self::SEED_YEARS; $i++) {
            $year = $startYear + $i;
            foreach (FR_JapaneseHolidays::forYear($year) as $date => $name) {
                $result = $this->db->pquery(
                    "INSERT IGNORE INTO vtiger_holidays
                        (holiday_date, holiday_name, day_type, holiday_type)
                     VALUES (?, ?, 'holiday', 'national')",
                    array($date, $name)
                );
                if ($result !== false) {
                    $registered += $this->db->getAffectedRowCount($result);
                }
            }
        }
        $this->log("国民の祝日を {$startYear}年から" . self::SEED_YEARS . "年分登録しました（{$registered}件）");
    }

    /**
     * テーブルの存在を確認する
     */
    private function tableExists($table) {
        $result = $this->db->pquery(
            "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?",
            array($table)
        );
        return $result !== false && (int) $this->db->query_result($result, 0, 'cnt') > 0;
    }
}
