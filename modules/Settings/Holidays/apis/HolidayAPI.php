<?php
/**
 * 休祝日マスタ API
 *
 * 管理画面（Web コンポーネント）から呼び出す JSON API。
 * システム管理者のみ利用できる。
 *
 * Usage:
 *   ?module=Holidays&parent=Settings&api=HolidayAPI&mode=info
 *     → 選択肢・週休設定・画面のラベルを返す
 *   ?module=Holidays&parent=Settings&api=HolidayAPI&mode=list&year=2026
 *     → 指定年の一覧を返す
 *   POST mode=save   … 追加・更新（record を指定すると更新）
 *   POST mode=delete … 削除
 *   POST mode=generate&year=2026 … 国民の祝日を一括登録
 *   POST mode=save_settings&weekly_holidays=0,6 … 週休の曜日を保存
 */
require_once 'include/utils/BusinessDay.php';
require_once 'include/utils/JapaneseHolidays.php';

class Settings_Holidays_HolidayAPI_Api extends Vtiger_Api_Controller {

    /** 言語ファイル（languages/<lang>/Settings/Holidays.php）の識別子 */
    const QUALIFIED_MODULE = 'Settings:Holidays';

    public function loginRequired() {
        return true;
    }

    public function requiresPermission(Vtiger_Request $request) {
        // 設定機能のため権限判定は checkPermission で管理者チェックのみ行う
        return array();
    }

    public function checkPermission(Vtiger_Request $request) {
        $currentUser = Users_Record_Model::getCurrentUserModel();
        if (empty($currentUser) || !$currentUser->isAdminUser()) {
            throw new AppException(vtranslate('LBL_PERMISSION_DENIED'));
        }
        return true;
    }

    protected function processApi(Vtiger_Request $request) {
        $mode = $request->get('mode');
        switch ($mode) {
            case 'info':
                return $this->sendSuccess($this->getInfo());
            case 'list':
                return $this->sendSuccess($this->getList($request));
            case 'save':
                return $this->sendSuccess($this->saveRecord($request));
            case 'delete':
                return $this->sendSuccess($this->deleteRecord($request));
            case 'generate':
                return $this->sendSuccess($this->generate($request));
            case 'save_settings':
                return $this->sendSuccess($this->saveSettings($request));
            case 'import_url':
                return $this->sendSuccess($this->importFromUrl($request));
            case 'import':
                return $this->sendSuccess($this->importFromFile($request));
            default:
                $this->sendError('Invalid mode: ' . $mode, 400);
        }
    }

    /**
     * 画面の初期表示に必要な情報（選択肢・週休設定・ラベル）を返す
     */
    private function getInfo() {
        $dayTypes = array();
        foreach (Settings_Holidays_Module_Model::getDayTypes() as $value => $labelKey) {
            $dayTypes[] = array('value' => $value, 'label' => vtranslate($labelKey, self::QUALIFIED_MODULE));
        }
        $holidayTypes = array();
        foreach (Settings_Holidays_Module_Model::getHolidayTypes() as $value => $labelKey) {
            $holidayTypes[] = array('value' => $value, 'label' => vtranslate($labelKey, self::QUALIFIED_MODULE));
        }

        return array(
            'day_types' => $dayTypes,
            'holiday_types' => $holidayTypes,
            'weekly_holidays' => FR_BusinessDay::getWeeklyHolidays(),
            'weekday_labels' => $this->getWeekdayLabels(),
            'available_years' => Settings_Holidays_Module_Model::getAvailableYears(),
            'current_year' => (int) date('Y'),
            'supported_from_year' => FR_JapaneseHolidays::SUPPORTED_FROM_YEAR,
            'official_csv_url' => !empty($GLOBALS['holidays_official_csv_url'])
                ? (string) $GLOBALS['holidays_official_csv_url'] : FR_JapaneseHolidays::OFFICIAL_CSV_URL,
            'labels' => $this->getLabels(),
        );
    }

    /**
     * 曜日の表示名（日曜〜土曜）を返す
     *
     * @return array 添字 0（日曜）〜6（土曜）
     */
    private function getWeekdayLabels() {
        $keys = array('LBL_WEEKDAY_SUN', 'LBL_WEEKDAY_MON', 'LBL_WEEKDAY_TUE', 'LBL_WEEKDAY_WED',
            'LBL_WEEKDAY_THU', 'LBL_WEEKDAY_FRI', 'LBL_WEEKDAY_SAT');
        $labels = array();
        foreach ($keys as $key) {
            $labels[] = vtranslate($key, self::QUALIFIED_MODULE);
        }
        return $labels;
    }

    /**
     * 画面で使用するラベル（言語ファイルの内容）を返す
     */
    private function getLabels() {
        $keys = array(
            'LBL_HOLIDAYS', 'LBL_HOLIDAY_DATE', 'LBL_HOLIDAY_NAME', 'LBL_DAY_TYPE',
            'LBL_HOLIDAY_TYPE', 'LBL_DESCRIPTION', 'LBL_ADD_HOLIDAY', 'LBL_EDIT_HOLIDAY',
            'LBL_GENERATE_NATIONAL_HOLIDAYS', 'LBL_YEAR_SUFFIX', 'LBL_WEEKLY_HOLIDAY_NOTE',
            'LBL_NO_HOLIDAYS', 'LBL_SAVE', 'LBL_CANCEL', 'LBL_EDIT', 'LBL_DELETE',
            'LBL_SAVING', 'LBL_LOADING', 'LBL_CONFIRM_DELETE', 'LBL_CONFIRM_GENERATE',
            'LBL_GENERATE_RESULT', 'LBL_COUNT_SUFFIX',
            'LBL_IMPORT_OFFICIAL', 'LBL_IMPORT_CSV_FILE', 'LBL_IMPORT_RESULT',
            'LBL_OFFICIAL_SOURCE_NOTE', 'LBL_CONFIRM_IMPORT_OFFICIAL', 'LBL_GENERATE_NOTE',
            'LBL_YEAR_NOT_SUPPORTED', 'LBL_INVALID_DATE', 'LBL_NAME_REQUIRED',
            'LBL_WEEKLY_HOLIDAYS', 'LBL_WEEKLY_HOLIDAY_NONE', 'LBL_SETTINGS_SAVED',
        );
        $labels = array();
        foreach ($keys as $key) {
            $labels[$key] = vtranslate($key, self::QUALIFIED_MODULE);
        }
        return $labels;
    }

    /**
     * 指定年の休祝日一覧を返す
     */
    private function getList(Vtiger_Request $request) {
        $year = (int) $request->get('year');
        if ($year <= 0) {
            $year = (int) date('Y');
        }
        $rows = FR_BusinessDay::getRegisteredDays(
            sprintf('%04d-01-01', $year), sprintf('%04d-12-31', $year));

        $records = array();
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT holidayid, holiday_date, holiday_name, day_type, holiday_type, description
             FROM " . Settings_Holidays_Module_Model::tableName . "
             WHERE YEAR(holiday_date) = ? ORDER BY holiday_date",
            array($year)
        );
        if ($result !== false) {
            $count = $db->num_rows($result);
            for ($i = 0; $i < $count; $i++) {
                $row = $db->query_result_rowdata($result, $i);
                $records[] = array(
                    'holidayid' => (int) $row['holidayid'],
                    'holiday_date' => $row['holiday_date'],
                    'holiday_name' => decode_html($row['holiday_name']),
                    'day_type' => $row['day_type'],
                    'holiday_type' => $row['holiday_type'],
                    'description' => decode_html((string) $row['description']),
                    'weekday' => (int) date('w', strtotime($row['holiday_date'])),
                );
            }
        }

        return array(
            'year' => $year,
            'records' => $records,
            'total' => count($records),
            'available_years' => Settings_Holidays_Module_Model::getAvailableYears(),
            'registered_count' => count($rows),
        );
    }

    /**
     * 追加・更新
     */
    private function saveRecord(Vtiger_Request $request) {
        $recordModel = Settings_Holidays_Record_Model::getInstanceFromRequest($request);
        $recordId = $recordModel->save();
        $saved = Settings_Holidays_Record_Model::getInstanceById($recordId);
        return array(
            'holidayid' => $recordId,
            'holiday_date' => $saved->get('holiday_date'),
            'holiday_name' => $saved->get('holiday_name'),
            'day_type' => $saved->get('day_type'),
            'holiday_type' => $saved->get('holiday_type'),
            'description' => (string) $saved->get('description'),
        );
    }

    /**
     * 削除
     */
    private function deleteRecord(Vtiger_Request $request) {
        $recordId = (int) $request->get('record');
        if ($recordId <= 0) {
            throw new Exception(vtranslate('LBL_RECORD_NOT_FOUND', self::QUALIFIED_MODULE));
        }
        Settings_Holidays_Module_Model::delete($recordId);
        return array('holidayid' => $recordId, 'deleted' => true);
    }

    /**
     * 国民の祝日の一括登録
     */
    private function generate(Vtiger_Request $request) {
        $year = (int) $request->get('year');
        $result = Settings_Holidays_Module_Model::generateNationalHolidays($year);
        return array_merge(array('year' => $year), $result);
    }

    /**
     * 週休の曜日を保存する
     *
     * weekly_holidays は 0（日曜）〜6（土曜）のカンマ区切り。空文字なら週休なし。
     */
    private function saveSettings(Vtiger_Request $request) {
        if (!$request->has('weekly_holidays')) {
            throw new Exception(vtranslate('LBL_INVALID_WEEKLY_HOLIDAY', self::QUALIFIED_MODULE));
        }
        $raw = $request->get('weekly_holidays');
        if (!is_array($raw)) {
            $raw = ((string) $raw === '') ? array() : explode(',', (string) $raw);
        }
        // 想定外の値は保存前にエラーとする（黙って捨てない）
        foreach ($raw as $weekday) {
            $weekday = trim((string) $weekday);
            if (!preg_match('/^[0-6]$/', $weekday)) {
                throw new Exception(vtranslate('LBL_INVALID_WEEKLY_HOLIDAY', self::QUALIFIED_MODULE));
            }
        }

        $saved = FR_BusinessDay::setWeeklyHolidays($raw);
        return array('weekly_holidays' => $saved);
    }

    /**
     * 内閣府公表データをURLから取得して取り込む
     */
    private function importFromUrl(Vtiger_Request $request) {
        $content = Settings_Holidays_Module_Model::downloadOfficialCsv();
        return $this->importCsvContent($content, $request->get('year'));
    }

    /**
     * アップロードされたCSVを取り込む
     */
    private function importFromFile(Vtiger_Request $request) {
        if (!isset($_FILES['csv']) || !is_array($_FILES['csv'])) {
            throw new Exception(vtranslate('LBL_CSV_NOT_UPLOADED', self::QUALIFIED_MODULE));
        }
        $file = $_FILES['csv'];
        if (isset($file['error']) && (int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception(vtranslate('LBL_CSV_UPLOAD_FAILED', self::QUALIFIED_MODULE));
        }
        $content = file_get_contents($file['tmp_name']);
        if ($content === false) {
            throw new Exception(vtranslate('LBL_CSV_UPLOAD_FAILED', self::QUALIFIED_MODULE));
        }
        return $this->importCsvContent($content, $request->get('year'));
    }

    /**
     * CSVの内容を解析してマスタに取り込む
     *
     * @param string $content CSVの内容
     * @param mixed $year 指定された場合はその年のみ取り込む（空なら全期間）
     * @return array 取り込み結果
     */
    private function importCsvContent($content, $year) {
        $officialHolidays = FR_JapaneseHolidays::parseOfficialCsv($content);
        $onlyYear = ($year !== null && $year !== '' && (int) $year > 0) ? (int) $year : null;

        if ($onlyYear === null) {
            // 公表データは1955年から収録されているため、既定では前年以降だけを取り込む
            // （特定の年だけ取り込みたい場合は year を指定する）
            $fromYear = (int) date('Y') - 1;
            $officialHolidays = array_filter(
                $officialHolidays,
                function ($date) use ($fromYear) {
                    return (int) substr($date, 0, 4) >= $fromYear;
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        $result = Settings_Holidays_Module_Model::importOfficialHolidays($officialHolidays, $onlyYear);

        $years = $result['years'];
        return array_merge($result, array(
            'total_in_csv' => count($officialHolidays),
            'year_from' => count($years) ? min($years) : null,
            'year_to' => count($years) ? max($years) : null,
        ));
    }

}
