<?php
/**
 * マイグレーション: add_holiday_settings
 * 生成日時: 20260806095141
 *
 * 休祝日マスタの設定（週休の曜日など）を保持するテーブルを作成する。
 * 画面（設定 > 休祝日マスタ）から変更できるようにするため、
 * これまで config.customize.php の $business_week_holidays で指定していた
 * 週休の曜日を DB へ移行する。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260806095141_AddHolidaySettings extends FRMigrationClass {

    /** 設定テーブル名 */
    const TABLE_NAME = 'vtiger_holiday_settings';

    /** 週休の曜日を保持する設定名 */
    const WEEKLY_HOLIDAYS = 'weekly_holidays';

    public function process() {
        $this->createTable();
        $this->seedWeeklyHolidays();
    }

    /**
     * 設定テーブルを作成する（名前と値の組で保持する）
     */
    private function createTable() {
        if ($this->checkTableExists(self::TABLE_NAME)) {
            $this->log('テーブル ' . self::TABLE_NAME . ' は既に存在するためスキップします');
            return;
        }

        $this->db->pquery('CREATE TABLE ' . self::TABLE_NAME . " (
            name VARCHAR(50) NOT NULL,
            value TEXT DEFAULT NULL,
            modified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci", array());
        $this->log('テーブル ' . self::TABLE_NAME . ' を作成しました');
    }

    /**
     * 週休の曜日の初期値を登録する
     *
     * config.customize.php で $business_week_holidays を設定していた環境では
     * その値をそのまま引き継ぐ。設定していなければ土日（0,6）とする。
     */
    private function seedWeeklyHolidays() {
        $existing = $this->db->pquery(
            'SELECT name FROM ' . self::TABLE_NAME . ' WHERE name = ?',
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
            'INSERT INTO ' . self::TABLE_NAME . ' (name, value) VALUES (?, ?)',
            array(self::WEEKLY_HOLIDAYS, $value)
        );
        $this->log('週休の曜日を登録しました: ' . ($value === '' ? '（なし）' : $value));
    }
}
