<?php
/**
 * 営業日・休祝日の判定ユーティリティ
 *
 * 休祝日マスタ（vtiger_holidays）と週休の設定をもとに、休日判定と営業日計算を行う。
 * 特定のモジュールに依存しないため、営業日計算が必要な機能から共通で利用できる。
 *
 * 週休の曜日は設定 > 休祝日マスタの画面から変更する（vtiger_holiday_settings に保存。既定は土日）。
 *
 * 使い方:
 *   require_once 'include/utils/BusinessDay.php';
 *   FR_BusinessDay::isBusinessDay('2026-01-01');      // false（元日）
 *   FR_BusinessDay::addBusinessDays('2026-01-05', 7); // 7営業日後
 *   FR_BusinessDay::countBusinessDays('2026-01-01', '2026-01-31');
 */
class FR_BusinessDay {

    /** 休日として扱う日 */
    const DAY_TYPE_HOLIDAY = 'holiday';

    /** 所定休日だが営業日として扱う日（休日出勤日） */
    const DAY_TYPE_WORKDAY = 'workday';

    /**
     * 営業日を探索する際の最大日数（無限ループ防止）
     * すべての曜日が週休の設定でも打ち切れるようにするためのもので、
     * 期間内の営業日数（countBusinessDays）には上限を設けない。
     */
    const MAX_SEARCH_DAYS = 3650;

    /** 休祝日マスタの設定テーブル */
    const SETTINGS_TABLE = 'vtiger_holiday_settings';

    /** 週休の曜日を保持する設定名 */
    const SETTING_WEEKLY_HOLIDAYS = 'weekly_holidays';

    /** 読み込み済みのマスタ（'Y-m-d' => day_type）。リクエスト内キャッシュ */
    private static $cache = array();

    /** キャッシュ済みの年 */
    private static $loadedYears = array();

    /** 週休の曜日のキャッシュ（未読込は null） */
    private static $weeklyHolidays = null;

    /**
     * 週休の曜日を返す
     *
     * 設定 > 休祝日マスタの画面で保存した値（vtiger_holiday_settings）を使う。
     * 未設定の場合は土日を既定とする。
     *
     * @return array 0（日曜）〜6（土曜）の配列（昇順）
     */
    public static function getWeeklyHolidays() {
        if (self::$weeklyHolidays !== null) {
            return self::$weeklyHolidays;
        }

        $stored = self::readSetting(self::SETTING_WEEKLY_HOLIDAYS);
        if ($stored !== null) {
            self::$weeklyHolidays = self::normalizeWeekdays($stored);
            return self::$weeklyHolidays;
        }

        // 設定テーブルが未作成の環境では config.customize.php の指定を引き継ぐ
        // （vglobal() が未ロードの文脈でも動くよう $GLOBALS を直接見る）
        if (isset($GLOBALS['business_week_holidays']) && is_array($GLOBALS['business_week_holidays'])) {
            self::$weeklyHolidays = self::normalizeWeekdays($GLOBALS['business_week_holidays']);
            return self::$weeklyHolidays;
        }

        self::$weeklyHolidays = array(0, 6);// 日曜・土曜
        return self::$weeklyHolidays;
    }

    /**
     * 週休の曜日を保存する
     *
     * @param array $weekdays 0（日曜）〜6（土曜）の配列。空配列なら週休なし
     * @return array 保存した曜日の配列
     */
    public static function setWeeklyHolidays($weekdays) {
        $weekdays = self::normalizeWeekdays($weekdays);
        $db = PearDatabase::getInstance();
        $value = implode(',', $weekdays);

        $existing = $db->pquery(
            'SELECT name FROM ' . self::SETTINGS_TABLE . ' WHERE name = ?',
            array(self::SETTING_WEEKLY_HOLIDAYS)
        );
        if ($existing !== false && $db->num_rows($existing) > 0) {
            $db->pquery(
                'UPDATE ' . self::SETTINGS_TABLE . ' SET value = ? WHERE name = ?',
                array($value, self::SETTING_WEEKLY_HOLIDAYS)
            );
        } else {
            $db->pquery(
                'INSERT INTO ' . self::SETTINGS_TABLE . ' (name, value) VALUES (?, ?)',
                array(self::SETTING_WEEKLY_HOLIDAYS, $value)
            );
        }

        self::$weeklyHolidays = $weekdays;
        return $weekdays;
    }

    /**
     * 曜日の配列を 0〜6 の重複なし昇順に整える
     *
     * @param array|string $weekdays 配列またはカンマ区切りの文字列
     * @return array
     */
    public static function normalizeWeekdays($weekdays) {
        if (!is_array($weekdays)) {
            $weekdays = ($weekdays === '' || $weekdays === null) ? array() : explode(',', (string) $weekdays);
        }
        $days = array();
        foreach ($weekdays as $weekday) {
            if (!is_numeric(trim((string) $weekday))) {
                continue;
            }
            $day = (int) trim((string) $weekday);
            if ($day >= 0 && $day <= 6 && !in_array($day, $days, true)) {
                $days[] = $day;
            }
        }
        sort($days);
        return $days;
    }

    /**
     * 設定テーブルから値を読み出す（テーブル・行が無ければ null）
     *
     * @param string $name 設定名
     * @return string|null
     */
    private static function readSetting($name) {
        $db = PearDatabase::getInstance();
        // 設定テーブルが未作成でもエラー画面にしない（マイグレーション前の実行を考慮）
        $tableExists = $db->pquery('SHOW TABLES LIKE ?', array(self::SETTINGS_TABLE));
        if ($tableExists === false || $db->num_rows($tableExists) === 0) {
            return null;
        }
        $result = $db->pquery(
            'SELECT value FROM ' . self::SETTINGS_TABLE . ' WHERE name = ?',
            array($name)
        );
        if ($result === false || $db->num_rows($result) === 0) {
            return null;
        }
        return (string) $db->query_result($result, 0, 'value');
    }

    /**
     * 週休の曜日かどうか
     *
     * 空（未入力）は false。日付として解釈できない値は例外を投げる。
     *
     * @param string $date 'Y-m-d'
     * @return bool
     * @throws InvalidArgumentException 日付が不正な場合
     */
    public static function isWeeklyHoliday($date) {
        $timestamp = self::toTimestamp($date);
        if ($timestamp === false) {
            return false;
        }
        return in_array((int) date('w', $timestamp), self::getWeeklyHolidays(), true);
    }

    /**
     * 休日かどうか（週休またはマスタの休日。営業日指定があればそちらを優先）
     *
     * 空（未入力）は false（＝休日ではない）。日付として解釈できない値は例外を投げる。
     *
     * @param string $date 'Y-m-d'
     * @return bool
     * @throws InvalidArgumentException 日付が不正な場合
     */
    public static function isHoliday($date) {
        $date = self::normalize($date);
        if ($date === null) {
            return false;
        }
        $registered = self::getRegisteredType($date);
        if ($registered === self::DAY_TYPE_WORKDAY) {
            // 休日出勤日として登録されている場合は営業日
            return false;
        }
        if ($registered === self::DAY_TYPE_HOLIDAY) {
            return true;
        }
        return self::isWeeklyHoliday($date);
    }

    /**
     * 営業日かどうか
     *
     * @param string $date 'Y-m-d'
     * @return bool
     */
    public static function isBusinessDay($date) {
        return !self::isHoliday($date);
    }

    /**
     * 指定日から n 営業日後の日付を返す
     *
     * 起算日は含めない（1営業日後 = 次の営業日）。
     * $days に負数を渡すと過去方向に数える。
     *
     * @param string $date 'Y-m-d'
     * @param int $days 営業日数
     * @return string|null 'Y-m-d'（未入力・探索上限に達した場合は null）
     * @throws InvalidArgumentException 日付が不正な場合
     */
    public static function addBusinessDays($date, $days) {
        $date = self::normalize($date);
        if ($date === null) {
            return null;
        }
        $days = (int) $days;
        if ($days === 0) {
            return $date;
        }

        $step = ($days > 0) ? '+1 day' : '-1 day';
        $remaining = abs($days);
        $current = $date;
        for ($i = 0; $i < self::MAX_SEARCH_DAYS && $remaining > 0; $i++) {
            $current = date('Y-m-d', strtotime($current . ' ' . $step));
            if (self::isBusinessDay($current)) {
                $remaining--;
            }
        }
        return ($remaining === 0) ? $current : null;
    }

    /**
     * 指定日以降（当日を含む）で最初の営業日を返す
     *
     * @param string $date 'Y-m-d'
     * @return string|null
     */
    public static function nextBusinessDay($date) {
        $date = self::normalize($date);
        if ($date === null) {
            return null;
        }
        $current = $date;
        for ($i = 0; $i < self::MAX_SEARCH_DAYS; $i++) {
            if (self::isBusinessDay($current)) {
                return $current;
            }
            $current = date('Y-m-d', strtotime($current . ' +1 day'));
        }
        return null;
    }

    /**
     * 期間内の営業日数を数える（両端を含む）
     *
     * 期間の長さに上限は設けない。1日ずつ数えると長期間で時間がかかるため、
     * 週休の曜日から算出し、休祝日マスタの登録分だけを補正する。
     *
     * @param string $from 'Y-m-d'
     * @param string $to 'Y-m-d'
     * @return int
     * @throws InvalidArgumentException 日付が不正な場合
     */
    public static function countBusinessDays($from, $to) {
        $from = self::normalize($from);
        $to = self::normalize($to);
        if ($from === null || $to === null) {
            return 0;
        }
        if ($from > $to) {
            $swap = $from;
            $from = $to;
            $to = $swap;
        }

        $fromTimestamp = strtotime($from);
        $totalDays = (int) round((strtotime($to) - $fromTimestamp) / 86400) + 1;

        // 1. 週休の曜日から営業日数を求める
        $weeklyHolidays = self::getWeeklyHolidays();
        $holidayCount = 0;
        if (!empty($weeklyHolidays)) {
            $fullWeeks = (int) floor($totalDays / 7);
            $holidayCount = $fullWeeks * count($weeklyHolidays);
            $startWeekday = (int) date('w', $fromTimestamp);
            for ($i = 0; $i < $totalDays % 7; $i++) {
                if (in_array(($startWeekday + $i) % 7, $weeklyHolidays, true)) {
                    $holidayCount++;
                }
            }
        }
        $count = $totalDays - $holidayCount;

        // 2. 休祝日マスタの登録で補正する
        //    休日:  週休でない日に登録されていれば1日減る
        //    営業日: 週休の日に登録されていれば1日増える
        foreach (self::getRegisteredDays($from, $to) as $row) {
            $isWeeklyHoliday = self::isWeeklyHoliday($row['holiday_date']);
            if ($row['day_type'] === self::DAY_TYPE_HOLIDAY && !$isWeeklyHoliday) {
                $count--;
            } elseif ($row['day_type'] === self::DAY_TYPE_WORKDAY && $isWeeklyHoliday) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * 期間内の休祝日マスタの登録を返す
     *
     * @param string $from 'Y-m-d'
     * @param string $to 'Y-m-d'
     * @param string|null $dayType 'holiday' / 'workday' / null（すべて）
     * @return array 日付昇順の配列。各要素は holiday_date / holiday_name / day_type / holiday_type
     */
    public static function getRegisteredDays($from, $to, $dayType = null) {
        $from = self::normalize($from);
        $to = self::normalize($to);
        if ($from === null || $to === null) {
            return array();
        }

        $db = PearDatabase::getInstance();
        $query = "SELECT holiday_date, holiday_name, day_type, holiday_type
                  FROM vtiger_holidays WHERE holiday_date BETWEEN ? AND ?";
        $params = array($from, $to);
        if ($dayType !== null) {
            $query .= " AND day_type = ?";
            $params[] = $dayType;
        }
        $query .= " ORDER BY holiday_date";

        $result = $db->pquery($query, $params);
        if ($result === false) {
            return array();
        }
        $rows = array();
        $count = $db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $row = $db->query_result_rowdata($result, $i);
            $rows[] = array(
                'holiday_date' => $row['holiday_date'],
                'holiday_name' => decode_html($row['holiday_name']),
                'day_type' => $row['day_type'],
                'holiday_type' => $row['holiday_type'],
            );
        }
        return $rows;
    }

    /**
     * 日付を 'Y-m-d' に正規化する（他のクラスから共通の検証を使うための入口）
     *
     * @param string|null $date
     * @return string|null 空の場合 null
     * @throws InvalidArgumentException 日付が不正な場合
     */
    public static function normalizeDate($date) {
        return self::normalize($date);
    }

    /**
     * キャッシュを破棄する（マスタ・設定を更新した後に呼ぶ）
     */
    public static function clearCache() {
        self::$cache = array();
        self::$loadedYears = array();
        self::$weeklyHolidays = null;
    }

    /**
     * マスタに登録された種別を返す（未登録なら null）
     *
     * @param string $date 'Y-m-d'
     * @return string|null
     */
    private static function getRegisteredType($date) {
        $year = (int) substr($date, 0, 4);
        self::loadYear($year);
        return isset(self::$cache[$date]) ? self::$cache[$date] : null;
    }

    /**
     * 指定年のマスタをまとめて読み込む（日付ごとのクエリを避ける）
     *
     * @param int $year
     */
    private static function loadYear($year) {
        if (isset(self::$loadedYears[$year])) {
            return;
        }
        self::$loadedYears[$year] = true;

        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT holiday_date, day_type FROM vtiger_holidays WHERE holiday_date BETWEEN ? AND ?",
            array(sprintf('%04d-01-01', $year), sprintf('%04d-12-31', $year))
        );
        if ($result === false) {
            return;
        }
        $count = $db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $row = $db->query_result_rowdata($result, $i);
            self::$cache[$row['holiday_date']] = $row['day_type'];
        }
    }

    /**
     * 日付を 'Y-m-d' に正規化する
     *
     * 空（未入力）は null を返し、書式が解釈できない・実在しない日付は例外を投げる。
     * 誤った日付を黙って別の日に繰り上げる（2月30日 → 3月2日）と、
     * 期限計算の結果が静かにずれるため、呼び出し元に気付かせる。
     *
     * @param string|null $date
     * @return string|null 空の場合 null
     * @throws InvalidArgumentException 日付として解釈できない場合
     */
    private static function normalize($date) {
        $timestamp = self::toTimestamp($date);
        return ($timestamp === false) ? null : date('Y-m-d', $timestamp);
    }

    /**
     * タイムスタンプに変換する
     *
     * @param string|null $date
     * @return int|false 空の場合 false
     * @throws InvalidArgumentException 日付として解釈できない場合
     */
    private static function toTimestamp($date) {
        if (self::isEmptyDate($date)) {
            return false;
        }
        $value = trim((string) $date);
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            throw new InvalidArgumentException('Invalid date: ' . $value);
        }
        // strtotime は 2026-02-30 のような実在しない日付を翌月へ繰り上げるため、
        // 年月日の形をしている場合は実在するかどうかを確認する
        if (preg_match('#^(\d{4})[-/](\d{1,2})[-/](\d{1,2})#', $value, $matches)
            && !checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            throw new InvalidArgumentException('Invalid date: ' . $value);
        }
        return $timestamp;
    }

    /**
     * 未入力とみなす値かどうか（空文字・null・ゼロ日付）
     *
     * @param mixed $date
     * @return bool
     */
    private static function isEmptyDate($date) {
        if ($date === null || $date === false || $date === '') {
            return true;
        }
        $value = trim((string) $date);
        return ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00');
    }
}
