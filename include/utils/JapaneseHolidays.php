<?php
/**
 * 日本の国民の祝日を扱うユーティリティ
 *
 * 休祝日マスタ（vtiger_holidays）へ祝日を登録するために使用する。
 *
 * 正式な休日は内閣府が公表する「国民の祝日について」のデータが正となる。
 *   https://www8.cao.go.jp/chosei/shukujitsu/gaiyou.html
 * 一時的な移動（例: 2020・2021年の海の日／スポーツの日／山の日）や春分・秋分の
 * 官報告示は計算では再現できないため、公表データの取り込み（parseOfficialCsv）を
 * 優先し、計算（forYear）は公表されていない将来年の暫定登録に使う。
 *
 * forYear の対応範囲: 2020年以降（祝日法の改正が反映済みの年）。
 *   2019年以前は改正前の祝日（天皇誕生日 12/23 など）が含まれないため、
 *   公表データの取り込みまたは管理画面からの個別登録を使う。
 */
class FR_JapaneseHolidays {

    /** 対応開始年 */
    const SUPPORTED_FROM_YEAR = 2020;

    /**
     * 特例で移動・追加された祝日
     * 年 => array(月日 => 名称)。ここに定義した年は該当祝日を上書きする。
     */
    private static $exceptions = array(
        // 東京オリンピック・パラリンピックに伴う移動
        2020 => array(
            '07-23' => '海の日',
            '07-24' => 'スポーツの日',
            '08-10' => '山の日',
        ),
        2021 => array(
            '07-22' => '海の日',
            '07-23' => 'スポーツの日',
            '08-08' => '山の日',
        ),
    );

    /** 特例年に通常日付を無効化する祝日 */
    private static $exceptionRemovals = array(
        2020 => array('海の日', 'スポーツの日', '山の日'),
        2021 => array('海の日', 'スポーツの日', '山の日'),
    );

    /** 内閣府が公表している「国民の祝日・休日」CSVの既定URL */
    const OFFICIAL_CSV_URL = 'https://www8.cao.go.jp/chosei/shukujitsu/syukujitsu.csv';

    /** 公表データで振替休日・国民の休日に使われている名称 */
    const OFFICIAL_SUBSTITUTE_NAME = '休日';

    /**
     * 内閣府公表CSVを解析する
     *
     * 形式: ヘッダー1行 + 「YYYY/M/D,名称」。文字コードは Shift_JIS。
     * 振替休日・国民の休日はいずれも「休日」と表記されるため、
     * 前後の祝日から振替休日／国民の休日を判別して名称を補う。
     *
     * @param string $content CSVの内容
     * @return array 'Y-m-d' => 名称（日付の昇順）
     * @throws Exception 解析できない場合
     */
    public static function parseOfficialCsv($content) {
        if (!is_string($content) || trim($content) === '') {
            throw new Exception(vtranslate('LBL_CSV_EMPTY', 'Settings:Holidays'));
        }
        // Shift_JIS で公開されているため UTF-8 に変換する（既に UTF-8 の場合はそのまま）
        $encoding = mb_detect_encoding($content, array('UTF-8', 'SJIS-win', 'CP932', 'EUC-JP'), true);
        if ($encoding !== false && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        $holidays = array();
        foreach (preg_split('/\r\n|\r|\n/', $content) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $columns = str_getcsv($line);
            if (count($columns) < 2) {
                continue;
            }
            $rawDate = trim($columns[0]);
            $name = trim($columns[1]);
            if ($name === '' || !preg_match('#^(\d{4})[/-](\d{1,2})[/-](\d{1,2})$#', $rawDate, $m)) {
                // ヘッダー行や空行
                continue;
            }
            $date = sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
            $holidays[$date] = $name;
        }

        if (empty($holidays)) {
            throw new Exception(vtranslate('LBL_CSV_INVALID', 'Settings:Holidays'));
        }
        ksort($holidays);
        return self::resolveSubstituteNames($holidays);
    }

    /**
     * 公表データの「休日」を振替休日／国民の休日に読み替える
     *
     * 前日が祝日なら振替休日、前後が祝日なら国民の休日として扱う。
     * 判別できない場合は公表データの名称のままにする。
     *
     * @param array $holidays 'Y-m-d' => 名称
     * @return array
     */
    private static function resolveSubstituteNames($holidays) {
        $resolved = array();
        foreach ($holidays as $date => $name) {
            if ($name !== self::OFFICIAL_SUBSTITUTE_NAME) {
                $resolved[$date] = $name;
                continue;
            }
            $previous = date('Y-m-d', strtotime($date . ' -1 day'));
            $next = date('Y-m-d', strtotime($date . ' +1 day'));
            $previousIsHoliday = isset($holidays[$previous]);
            $nextIsHoliday = isset($holidays[$next]);
            if ($previousIsHoliday && $nextIsHoliday) {
                $resolved[$date] = '国民の休日';
            } elseif ($previousIsHoliday) {
                $resolved[$date] = '振替休日';
            } else {
                $resolved[$date] = $name;
            }
        }
        return $resolved;
    }

    /**
     * 指定年の祝日を返す
     *
     * @param int $year 西暦
     * @return array 'Y-m-d' => 名称（日付の昇順）
     */
    public static function forYear($year) {
        $year = (int) $year;
        $holidays = self::getFixedAndHappyMondayHolidays($year);

        // 特例年の差し替え
        if (isset(self::$exceptionRemovals[$year])) {
            foreach ($holidays as $date => $name) {
                if (in_array($name, self::$exceptionRemovals[$year])) {
                    unset($holidays[$date]);
                }
            }
        }
        if (isset(self::$exceptions[$year])) {
            foreach (self::$exceptions[$year] as $monthDay => $name) {
                $holidays[$year . '-' . $monthDay] = $name;
            }
        }

        ksort($holidays);
        $holidays = array_merge($holidays, self::getSubstituteHolidays($holidays));
        ksort($holidays);
        $holidays = array_merge($holidays, self::getNationalHolidays($holidays, $year));
        ksort($holidays);

        return $holidays;
    }

    /**
     * 固定日の祝日とハッピーマンデー、春分・秋分を返す
     *
     * @param int $year
     * @return array
     */
    private static function getFixedAndHappyMondayHolidays($year) {
        $holidays = array();

        $fixed = array(
            '01-01' => '元日',
            '02-11' => '建国記念の日',
            '02-23' => '天皇誕生日',
            '04-29' => '昭和の日',
            '05-03' => '憲法記念日',
            '05-04' => 'みどりの日',
            '05-05' => 'こどもの日',
            '07-第3月' => '海の日',
            '08-11' => '山の日',
            '11-03' => '文化の日',
            '11-23' => '勤労感謝の日',
        );
        foreach ($fixed as $monthDay => $name) {
            if (strpos($monthDay, '第') !== false) {
                continue;
            }
            $holidays[$year . '-' . $monthDay] = $name;
        }

        // ハッピーマンデー
        $holidays[self::nthMonday($year, 1, 2)] = '成人の日';
        $holidays[self::nthMonday($year, 7, 3)] = '海の日';
        $holidays[self::nthMonday($year, 9, 3)] = '敬老の日';
        $holidays[self::nthMonday($year, 10, 2)] = 'スポーツの日';

        // 春分の日・秋分の日（1980〜2099年に有効な近似式）
        $holidays[sprintf('%04d-03-%02d', $year, self::vernalEquinoxDay($year))] = '春分の日';
        $holidays[sprintf('%04d-09-%02d', $year, self::autumnalEquinoxDay($year))] = '秋分の日';

        return $holidays;
    }

    /**
     * 振替休日（祝日が日曜と重なった場合の直後の平日）
     *
     * @param array $holidays 'Y-m-d' => 名称
     * @return array
     */
    private static function getSubstituteHolidays($holidays) {
        $substitutes = array();
        foreach ($holidays as $date => $name) {
            if ((int) date('w', strtotime($date)) !== 0) {
                continue;
            }
            // 祝日でない最初の日まで進める
            $candidate = date('Y-m-d', strtotime($date . ' +1 day'));
            while (isset($holidays[$candidate]) || isset($substitutes[$candidate])) {
                $candidate = date('Y-m-d', strtotime($candidate . ' +1 day'));
            }
            $substitutes[$candidate] = '振替休日';
        }
        return $substitutes;
    }

    /**
     * 国民の休日（前日と翌日が祝日である平日）
     *
     * @param array $holidays
     * @param int $year
     * @return array
     */
    private static function getNationalHolidays($holidays, $year) {
        $result = array();
        foreach (array_keys($holidays) as $date) {
            $next = date('Y-m-d', strtotime($date . ' +2 day'));
            if (!isset($holidays[$next])) {
                continue;
            }
            $between = date('Y-m-d', strtotime($date . ' +1 day'));
            if (isset($holidays[$between]) || (int) date('Y', strtotime($between)) !== $year) {
                continue;
            }
            // 日曜は振替休日の対象となるため国民の休日にはしない
            if ((int) date('w', strtotime($between)) === 0) {
                continue;
            }
            $result[$between] = '国民の休日';
        }
        return $result;
    }

    /**
     * 指定月の第n月曜日を返す
     *
     * @return string 'Y-m-d'
     */
    private static function nthMonday($year, $month, $nth) {
        $firstDayOfWeek = (int) date('w', mktime(0, 0, 0, $month, 1, $year));
        // 日曜=0 として、最初の月曜日を求める
        $firstMonday = 1 + (($firstDayOfWeek === 0) ? 1 : (8 - $firstDayOfWeek) % 7);
        $day = $firstMonday + ($nth - 1) * 7;
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * 春分日（1980〜2099年に有効な近似式）
     */
    private static function vernalEquinoxDay($year) {
        return (int) floor(20.8431 + 0.242194 * ($year - 1980) - floor(($year - 1980) / 4));
    }

    /**
     * 秋分日（1980〜2099年に有効な近似式）
     */
    private static function autumnalEquinoxDay($year) {
        return (int) floor(23.2488 + 0.242194 * ($year - 1980) - floor(($year - 1980) / 4));
    }
}
