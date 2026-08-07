<?php
/**
 * 電子帳簿保存法対応: 入力期限の自動計算
 *
 * スキャナ保存の書類は、受領（作成）から一定期間内にシステムへ入力する必要がある。
 * 入力期限の考え方は事務処理規程の有無で次の2通りになるため、方針を設定で切り替える。
 *
 *   prompt（速やかに）              : 受領後おおむね7営業日以内
 *   cycle （業務処理サイクル後速やかに）: 業務処理サイクル（最長2か月）経過後おおむね7営業日以内
 *
 * 営業日は休祝日マスタ（設定 > システム構成 > 休祝日マスタ）の内容と週休の設定で判定する。
 * 方針・日数は vtiger_documents_settings に保持し、未設定なら既定値（prompt / 7営業日 /
 * 2か月 / 期限3営業日前から警告）を使う。
 *
 * 使い方:
 *   require_once 'modules/Documents/utils/DeadlineCalculator.php';
 *   Documents_DeadlineCalculator::calculate('2026-08-06');       // 入力期限
 *   Documents_DeadlineCalculator::recalculate($notesId);         // レコードへ反映
 *   Documents_DeadlineCalculator::updateStatuses();              // 期限状態の一括更新（cron）
 */
require_once 'include/utils/BusinessDay.php';

class Documents_DeadlineCalculator {

    /** 方針: 速やかに（受領後おおむね7営業日以内） */
    const POLICY_PROMPT = 'prompt';

    /** 方針: 業務処理サイクル後速やかに（最長2か月＋おおむね7営業日以内） */
    const POLICY_CYCLE = 'cycle';

    /** 期限内 */
    const STATUS_WITHIN = 'within';

    /** 期限間近 */
    const STATUS_WARNING = 'warning';

    /** 期限超過 */
    const STATUS_OVERDUE = 'overdue';

    /** 入力期限を計算する対象の保存区分 */
    const TARGET_PRESERVATION_TYPE = 'scanner';

    /** 設定テーブル */
    const SETTINGS_TABLE = 'vtiger_documents_settings';

    /** 設定名: 方針 */
    const SETTING_POLICY = 'input_deadline_policy';

    /** 設定名: 猶予の営業日数 */
    const SETTING_BUSINESS_DAYS = 'input_deadline_business_days';

    /** 設定名: 業務処理サイクルの月数 */
    const SETTING_CYCLE_MONTHS = 'input_deadline_cycle_months';

    /** 設定名: 期限間近とみなす営業日数 */
    const SETTING_WARNING_DAYS = 'input_deadline_warning_days';

    /** 既定の猶予営業日数（おおむね7営業日以内） */
    const DEFAULT_BUSINESS_DAYS = 7;

    /** 既定の業務処理サイクル（最長2か月） */
    const DEFAULT_CYCLE_MONTHS = 2;

    /** 既定の警告営業日数（期限まで3営業日以内で「期限間近」） */
    const DEFAULT_WARNING_DAYS = 3;

    /** 設定値のキャッシュ（未読込は null） */
    private static $settings = null;

    /**
     * 入力期限の方針を返す
     *
     * @return string self::POLICY_PROMPT または self::POLICY_CYCLE
     */
    public static function getPolicy() {
        $policy = self::getSetting(self::SETTING_POLICY, self::POLICY_PROMPT);
        return ($policy === self::POLICY_CYCLE) ? self::POLICY_CYCLE : self::POLICY_PROMPT;
    }

    /**
     * 猶予の営業日数を返す
     *
     * @return int
     */
    public static function getBusinessDays() {
        return self::getPositiveInt(self::SETTING_BUSINESS_DAYS, self::DEFAULT_BUSINESS_DAYS);
    }

    /**
     * 業務処理サイクルの月数を返す
     *
     * @return int
     */
    public static function getCycleMonths() {
        return self::getPositiveInt(self::SETTING_CYCLE_MONTHS, self::DEFAULT_CYCLE_MONTHS);
    }

    /**
     * 期限間近とみなす営業日数を返す
     *
     * @return int
     */
    public static function getWarningDays() {
        return self::getPositiveInt(self::SETTING_WARNING_DAYS, self::DEFAULT_WARNING_DAYS);
    }

    /**
     * 入力期限の設定を保存する
     *
     * 渡された項目だけを更新する。値の妥当性は呼び出し側（設定画面のAPI）で検証する。
     *
     * @param array $settings 設定名 => 値
     * @return array 保存後の設定値
     */
    public static function saveSettings($settings) {
        $db = PearDatabase::getInstance();
        $allowed = array(
            self::SETTING_POLICY, self::SETTING_BUSINESS_DAYS,
            self::SETTING_CYCLE_MONTHS, self::SETTING_WARNING_DAYS,
        );

        foreach ($settings as $name => $value) {
            if (!in_array($name, $allowed, true)) {
                continue;
            }
            $existing = $db->pquery(
                'SELECT name FROM ' . self::SETTINGS_TABLE . ' WHERE name = ?',
                array($name)
            );
            if ($existing !== false && $db->num_rows($existing) > 0) {
                $db->pquery(
                    'UPDATE ' . self::SETTINGS_TABLE . ' SET value = ? WHERE name = ?',
                    array((string) $value, $name)
                );
            } else {
                $db->pquery(
                    'INSERT INTO ' . self::SETTINGS_TABLE . ' (name, value) VALUES (?, ?)',
                    array($name, (string) $value)
                );
            }
        }

        self::clearCache();
        return self::getSettings();
    }

    /**
     * 入力期限の設定をまとめて返す
     *
     * @return array
     */
    public static function getSettings() {
        return array(
            self::SETTING_POLICY => self::getPolicy(),
            self::SETTING_BUSINESS_DAYS => self::getBusinessDays(),
            self::SETTING_CYCLE_MONTHS => self::getCycleMonths(),
            self::SETTING_WARNING_DAYS => self::getWarningDays(),
        );
    }

    /**
     * 入力期限があるドキュメントの期限を再計算する
     *
     * 方針や日数を変更した場合、既存ドキュメントの期限は古い設定のままになるため、
     * 設定画面から明示的に再計算できるようにする。
     *
     * @return array ['checked' => int, 'updated' => int]
     */
    public static function recalculateAll() {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT vtiger_notes.notesid, vtiger_notes.input_deadline
             FROM vtiger_notes
             INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
             WHERE vtiger_crmentity.deleted = 0
               AND vtiger_notes.preservation_type = ?
               AND vtiger_notes.receipt_date IS NOT NULL",
            array(self::TARGET_PRESERVATION_TYPE)
        );
        if ($result === false) {
            return array('checked' => 0, 'updated' => 0);
        }

        $checked = 0;
        $updated = 0;
        $count = $db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $row = $db->query_result_rowdata($result, $i);
            $checked++;
            $before = self::normalizeDate($row['input_deadline']);
            $after = self::recalculate((int) $row['notesid']);
            if ($before !== $after['input_deadline']) {
                $updated++;
            }
        }
        return array('checked' => $checked, 'updated' => $updated);
    }

    /**
     * 受領日から入力期限を計算する
     *
     * @param string $receiptDate 受領日 'Y-m-d'
     * @param string|null $policy 方針（省略時は設定値）
     * @return string|null 入力期限 'Y-m-d'（受領日が未入力の場合は null）
     * @throws InvalidArgumentException 受領日が日付として解釈できない場合
     */
    public static function calculate($receiptDate, $policy = null) {
        // 未入力は null、解釈できない日付は例外（黙って別の日に繰り上げない）
        $base = FR_BusinessDay::normalizeDate($receiptDate);
        if ($base === null) {
            return null;
        }

        if ($policy === null) {
            $policy = self::getPolicy();
        }
        if ($policy === self::POLICY_CYCLE) {
            // 業務処理サイクル（既定2か月）経過後を起算日とする
            $base = self::addMonths($base, self::getCycleMonths());
        }

        // 起算日の翌営業日から数えて n 営業日目が期限
        return FR_BusinessDay::addBusinessDays($base, self::getBusinessDays());
    }

    /**
     * 入力期限から期限状態を判定する
     *
     * @param string $deadline 入力期限 'Y-m-d'
     * @param string|null $today 基準日 'Y-m-d'（省略時は当日）
     * @return string|null within / warning / overdue（期限が未入力の場合は null）
     * @throws InvalidArgumentException 日付として解釈できない場合
     */
    public static function calculateStatus($deadline, $today = null) {
        $deadline = FR_BusinessDay::normalizeDate($deadline);
        if ($deadline === null) {
            return null;
        }
        $today = ($today === null) ? date('Y-m-d') : FR_BusinessDay::normalizeDate($today);
        if ($today === null) {
            $today = date('Y-m-d');
        }

        if ($deadline < $today) {
            return self::STATUS_OVERDUE;
        }
        // 残りの営業日数（当日と期限日を含む）で警告するかを決める
        $remaining = FR_BusinessDay::countBusinessDays($today, $deadline);
        return ($remaining <= self::getWarningDays()) ? self::STATUS_WARNING : self::STATUS_WITHIN;
    }

    /**
     * ドキュメントの入力期限と期限状態を再計算して保存する
     *
     * スキャナ保存で受領日がある場合のみ計算し、対象外になった場合は値を消す。
     *
     * @param int $notesId ドキュメントID
     * @return array ['input_deadline' => string|null, 'input_deadline_status' => string|null]
     */
    public static function recalculate($notesId) {
        $notesId = (int) $notesId;
        $empty = array('input_deadline' => null, 'input_deadline_status' => null);
        if ($notesId <= 0) {
            return $empty;
        }

        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT preservation_type, receipt_date, input_deadline, input_deadline_status
             FROM vtiger_notes WHERE notesid = ?",
            array($notesId)
        );
        if ($result === false || $db->num_rows($result) === 0) {
            return $empty;
        }
        $row = $db->query_result_rowdata($result, 0);

        $deadline = null;
        $status = null;
        if ($row['preservation_type'] === self::TARGET_PRESERVATION_TYPE) {
            try {
                $deadline = self::calculate($row['receipt_date']);
                $status = self::calculateStatus($deadline);
            } catch (InvalidArgumentException $e) {
                // 保存されている受領日が不正な場合は期限を空にする（一括処理を止めない）
                $deadline = null;
                $status = null;
            }
        }

        // 値が変わらない場合は更新しない（modifiedtime を動かさない）
        $currentDeadline = self::normalizeDate($row['input_deadline']);
        $currentStatus = ($row['input_deadline_status'] === '') ? null : $row['input_deadline_status'];
        if ($currentDeadline === $deadline && $currentStatus === $status) {
            return array('input_deadline' => $deadline, 'input_deadline_status' => $status);
        }

        $db->pquery(
            "UPDATE vtiger_notes SET input_deadline = ?, input_deadline_status = ? WHERE notesid = ?",
            array($deadline, $status, $notesId)
        );
        return array('input_deadline' => $deadline, 'input_deadline_status' => $status);
    }

    /**
     * 入力期限がある全ドキュメントの期限状態を更新する（cron から実行）
     *
     * 日付の経過だけで期限内→期限間近→期限超過と変わるため、日次で洗い替える。
     *
     * @param string|null $today 基準日 'Y-m-d'（省略時は当日）
     * @return array ['checked' => int, 'updated' => int]
     */
    public static function updateStatuses($today = null) {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT vtiger_notes.notesid, vtiger_notes.input_deadline, vtiger_notes.input_deadline_status
             FROM vtiger_notes
             INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
             WHERE vtiger_crmentity.deleted = 0
               AND vtiger_notes.preservation_type = ?
               AND vtiger_notes.input_deadline IS NOT NULL",
            array(self::TARGET_PRESERVATION_TYPE)
        );
        if ($result === false) {
            return array('checked' => 0, 'updated' => 0);
        }

        $checked = 0;
        $updated = 0;
        $count = $db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $row = $db->query_result_rowdata($result, $i);
            $checked++;
            try {
                $status = self::calculateStatus($row['input_deadline'], $today);
            } catch (InvalidArgumentException $e) {
                // 不正な値が保存されている1件で全体を止めない
                continue;
            }
            if ($status === null || $status === $row['input_deadline_status']) {
                continue;
            }
            $db->pquery(
                "UPDATE vtiger_notes SET input_deadline_status = ? WHERE notesid = ?",
                array($status, (int) $row['notesid'])
            );
            $updated++;
        }
        return array('checked' => $checked, 'updated' => $updated);
    }

    /**
     * 設定値のキャッシュを破棄する（設定を変更した後に呼ぶ）
     */
    public static function clearCache() {
        self::$settings = null;
    }

    /**
     * 日付に月数を加算する（月末は加算先の月末に丸める）
     *
     * strtotime('+2 month') は 1/31 → 3/3 のように翌月へあふれるため独自に計算する。
     *
     * @param string $date 'Y-m-d'
     * @param int $months 加算する月数
     * @return string 'Y-m-d'
     */
    private static function addMonths($date, $months) {
        $timestamp = strtotime($date);
        $year = (int) date('Y', $timestamp);
        $month = (int) date('n', $timestamp) + (int) $months;
        $day = (int) date('j', $timestamp);

        $year += (int) floor(($month - 1) / 12);
        $month = (($month - 1) % 12) + 1;
        if ($month < 1) {
            $month += 12;
        }

        $lastDay = (int) date('t', mktime(0, 0, 0, $month, 1, $year));
        return sprintf('%04d-%02d-%02d', $year, $month, min($day, $lastDay));
    }

    /**
     * 日付を 'Y-m-d' に正規化する（空・0000-00-00 は null）
     *
     * DB から読み出した値の比較に使う。不正な値が保存されていても
     * 一括処理を止めないよう、ここでは例外にせず null として扱う。
     *
     * @param string|null $date
     * @return string|null
     */
    private static function normalizeDate($date) {
        try {
            return FR_BusinessDay::normalizeDate($date);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * 1以上の整数の設定値を返す
     *
     * @param string $name 設定名
     * @param int $default 既定値
     * @return int
     */
    private static function getPositiveInt($name, $default) {
        $value = self::getSetting($name, null);
        if ($value === null || !is_numeric($value) || (int) $value < 1) {
            return $default;
        }
        return (int) $value;
    }

    /**
     * 設定値を返す（未設定なら既定値）
     *
     * @param string $name 設定名
     * @param mixed $default 既定値
     * @return mixed
     */
    private static function getSetting($name, $default) {
        if (self::$settings === null) {
            self::$settings = self::loadSettings();
        }
        return array_key_exists($name, self::$settings) ? self::$settings[$name] : $default;
    }

    /**
     * 設定テーブルを読み込む（テーブルが無ければ空配列）
     *
     * @return array 設定名 => 値
     */
    private static function loadSettings() {
        $db = PearDatabase::getInstance();
        // マイグレーション前でもエラーにしない
        $tableExists = $db->pquery('SHOW TABLES LIKE ?', array(self::SETTINGS_TABLE));
        if ($tableExists === false || $db->num_rows($tableExists) === 0) {
            return array();
        }
        $result = $db->pquery('SELECT name, value FROM ' . self::SETTINGS_TABLE, array());
        if ($result === false) {
            return array();
        }
        $settings = array();
        $count = $db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $row = $db->query_result_rowdata($result, $i);
            $settings[$row['name']] = $row['value'];
        }
        return $settings;
    }
}
