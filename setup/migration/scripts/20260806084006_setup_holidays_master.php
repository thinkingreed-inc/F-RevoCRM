<?php
/**
 * マイグレーション: setup_holidays_master
 * 生成日時: 20260806084006
 *
 * 休祝日マスタと、その設定（週休の曜日）を用意し、システム管理者向けの
 * 設定メニュー（システム構成）に登録する。
 * 営業日計算を必要とする機能（ドキュメントの入力期限など）から共通で参照する。
 *
 * day_type
 *   holiday : 休日（営業日に数えない）
 *   workday : 所定休日だが営業日として扱う日（休日出勤日・振替出勤日）
 * holiday_type
 *   national: 国民の祝日 / company: 会社休日 / other: その他
 *
 * 設定メニューが「他の設定」など別ブロックに登録済みの環境では、システム構成へ移動する。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
require_once 'include/utils/JapaneseHolidays.php';

class Migration20260806084006_SetupHolidaysMaster extends FRMigrationClass {

    /** 初期登録する年数（実行年の前年から数えて4年分） */
    const SEED_YEARS = 4;

    /** 設定メニューを追加するブロック（システム構成） */
    const SETTINGS_BLOCK = 'LBL_CONFIGURATION';

    /** 設定メニュー名 */
    const SETTINGS_FIELD = 'LBL_HOLIDAYS';

    /** 設定テーブル名 */
    const SETTINGS_TABLE = 'vtiger_holiday_settings';

    /** 週休の曜日を保持する設定名 */
    const WEEKLY_HOLIDAYS = 'weekly_holidays';

    public function process() {
        $this->createHolidaysTable();
        $this->createSettingsTable();
        $this->seedWeeklyHolidays();
        $this->registerSettingsMenu();
        $this->seedNationalHolidays();
    }

    /**
     * 休祝日マスタのテーブルを作成する
     */
    private function createHolidaysTable() {
        if ($this->checkTableExists('vtiger_holidays')) {
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
     * 設定テーブルを作成する（名前と値の組で保持する）
     */
    private function createSettingsTable() {
        if ($this->checkTableExists(self::SETTINGS_TABLE)) {
            $this->log('テーブル ' . self::SETTINGS_TABLE . ' は既に存在するためスキップします');
            return;
        }

        $this->db->pquery('CREATE TABLE ' . self::SETTINGS_TABLE . " (
            name VARCHAR(50) NOT NULL,
            value TEXT DEFAULT NULL,
            modified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", array());
        $this->log('テーブル ' . self::SETTINGS_TABLE . ' を作成しました');
    }

    /**
     * 週休の曜日の初期値を登録する
     *
     * config.customize.php で $business_week_holidays を設定していた環境では
     * その値をそのまま引き継ぐ。設定していなければ土日（0,6）とする。
     */
    private function seedWeeklyHolidays() {
        $existing = $this->db->pquery(
            'SELECT name FROM ' . self::SETTINGS_TABLE . ' WHERE name = ?',
            array(self::WEEKLY_HOLIDAYS)
        );
        if ($existing !== false && $this->db->num_rows($existing) > 0) {
            $this->log('設定 ' . self::WEEKLY_HOLIDAYS . ' は既に登録済みのためスキップします');
            return;
        }

        $days = array(0, 6);// 日曜・土曜
        if (isset($GLOBALS['business_week_holidays']) && is_array($GLOBALS['business_week_holidays'])) {
            $days = array();
            foreach ($GLOBALS['business_week_holidays'] as $day) {
                $day = (int) $day;
                if ($day >= 0 && $day <= 6 && !in_array($day, $days, true)) {
                    $days[] = $day;
                }
            }
            sort($days);
            $this->log('config.customize.php の $business_week_holidays を引き継ぎます');
        }

        $value = implode(',', $days);
        $this->db->pquery(
            'INSERT INTO ' . self::SETTINGS_TABLE . ' (name, value) VALUES (?, ?)',
            array(self::WEEKLY_HOLIDAYS, $value)
        );
        $this->log('週休の曜日を登録しました: ' . ($value === '' ? '（なし）' : $value));
    }

    /**
     * 設定画面のメニューに休祝日マスタを追加する
     *
     * 既に別のブロックに登録されている場合は「システム構成」へ移動する。
     */
    private function registerSettingsMenu() {
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

        $existing = $this->db->pquery(
            "SELECT fieldid, blockid FROM vtiger_settings_field WHERE name = ?",
            array(self::SETTINGS_FIELD)
        );
        if ($existing !== false && $this->db->num_rows($existing) > 0) {
            $fieldId = (int) $this->db->query_result($existing, 0, 'fieldid');
            $currentBlockId = (int) $this->db->query_result($existing, 0, 'blockid');
            if ($currentBlockId === $blockId) {
                $this->log('設定メニュー ' . self::SETTINGS_FIELD . ' は既に登録済みのためスキップします');
                return;
            }
            $sequence = $this->nextSettingsSequence($blockId);
            $this->db->pquery(
                'UPDATE vtiger_settings_field SET blockid = ?, sequence = ? WHERE fieldid = ?',
                array($blockId, $sequence, $fieldId)
            );
            $this->log('設定メニュー ' . self::SETTINGS_FIELD . " をシステム構成へ移動しました（sequence={$sequence}）");
            return;
        }

        $sequence = $this->nextSettingsSequence($blockId);
        $fieldId = $this->db->getUniqueID('vtiger_settings_field');

        $this->db->pquery(
            "INSERT INTO vtiger_settings_field (fieldid, blockid, name, iconpath, description, linkto, sequence, active)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0)",
            array(
                $fieldId,
                $blockId,
                self::SETTINGS_FIELD,
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
     * 設定メニューの並び順（ブロック内の末尾）を求める
     */
    private function nextSettingsSequence($blockId) {
        $result = $this->db->pquery(
            "SELECT COALESCE(MAX(sequence), 0) + 1 AS next_seq FROM vtiger_settings_field WHERE blockid = ?",
            array($blockId)
        );
        return (int) $this->db->query_result($result, 0, 'next_seq');
    }
}
