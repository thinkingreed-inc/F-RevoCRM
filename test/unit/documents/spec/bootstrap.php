<?php
/**
 * テスト仕様書（docs/tests/Documents）の自動テスト共通基盤
 *
 * 各テストスクリプトの冒頭で読み込む。
 *   require_once dirname(__FILE__) . '/bootstrap.php';
 *
 * 提供するもの:
 *   - F-RevoCRM の初期化（config.php / ランタイム / 管理者ユーザー）
 *   - アサーション（assertSame / assertTrue / assertThrows など）
 *   - テストデータの後始末（登録したレコードを終了時に必ず削除する）
 *
 * 実行例:
 *   php test/unit/documents/spec/run_all.php
 */

// ---- F-RevoCRM の初期化 -------------------------------------------------

chdir(dirname(__FILE__) . '/../../../../');
require_once 'config.php';
require_once 'include/utils/utils.php';
require_once 'include/database/PearDatabase.php';
vimport('includes.runtime.Globals');
vimport('includes.runtime.LanguageHandler');
vimport('includes.runtime.BaseModel');
vimport('includes.runtime.Controller');
vimport('includes.http.Request');
vimport('includes.http.Response');
require_once 'modules/Users/Users.php';
require_once 'modules/Users/models/Record.php';
require_once 'modules/Users/models/Module.php';
require_once 'modules/Documents/Documents.php';
require_once 'modules/Documents/models/Record.php';
require_once 'modules/Documents/models/Module.php';
require_once 'modules/Settings/Vtiger/models/Module.php';
require_once 'modules/Settings/Vtiger/models/Record.php';
require_once 'vtlib/Vtiger/Module.php';
require_once 'include/Webservices/Utils.php';

date_default_timezone_set('Asia/Tokyo');

/** 管理者として実行する */
function specLoginAsAdmin($userId = 1) {
    global $current_user;
    $current_user = CRMEntity::getInstance('Users');
    $current_user->id = $userId;
    $current_user->retrieve_entity_info($userId, 'Users');
    $current_user->column_fields = (array) $current_user->column_fields;
    vglobal('current_user', $current_user);

    $model = new Users_Record_Model();
    $model->setData($current_user->column_fields);
    $model->setModule('Users');
    $model->setEntity($current_user);
    foreach (get_object_vars($current_user) as $key => $value) {
        if (!is_object($value)) $model->$key = $value;
    }
    Users_Record_Model::$currentUserModels[$userId] = $model;
    return $model;
}

specLoginAsAdmin();

// ---- アサーション -------------------------------------------------------

class SpecRunner {

    /** 実行結果 [['id'=>, 'label'=>, 'ok'=>bool, 'detail'=>string], ...] */
    public static $results = array();

    /** 現在のセクション名 */
    private static $section = '';

    /** 後始末の処理 */
    private static $cleanups = array();

    public static function section($title) {
        self::$section = $title;
        echo "\n--- {$title}\n";
    }

    /**
     * 結果を記録する
     *
     * @param string $caseId 仕様書のケースID（例: TC-BD-001）
     * @param string $label 何を確認したか
     * @param bool $ok
     * @param string $detail 失敗時に原因が分かる情報
     */
    public static function report($caseId, $label, $ok, $detail = '') {
        self::$results[] = array(
            'id' => $caseId, 'label' => $label, 'ok' => (bool) $ok,
            'detail' => $detail, 'section' => self::$section,
        );
        printf("  %s %-12s %s%s\n", $ok ? '[OK]' : '[NG]', $caseId, $label,
            ($ok || $detail === '') ? '' : "  <-- {$detail}");
    }

    /** 値の一致を確認する */
    public static function assertSame($caseId, $label, $expected, $actual) {
        $ok = ($expected === $actual);
        self::report($caseId, $label, $ok, $ok ? '' :
            '期待: ' . self::stringify($expected) . ' / 実際: ' . self::stringify($actual));
    }

    /** 真であることを確認する */
    public static function assertTrue($caseId, $label, $actual, $detail = '') {
        self::report($caseId, $label, $actual === true,
            $actual === true ? '' : ($detail !== '' ? $detail : '実際: ' . self::stringify($actual)));
    }

    /** 偽であることを確認する */
    public static function assertFalse($caseId, $label, $actual, $detail = '') {
        self::report($caseId, $label, $actual === false,
            $actual === false ? '' : ($detail !== '' ? $detail : '実際: ' . self::stringify($actual)));
    }

    /**
     * 指定した例外が投げられることを確認する
     *
     * @param callable $callback
     * @param string $exceptionClass 期待する例外クラス
     */
    public static function assertThrows($caseId, $label, $callback, $exceptionClass = 'Exception') {
        try {
            $result = call_user_func($callback);
            self::report($caseId, $label, false,
                '例外が投げられなかった（戻り値: ' . self::stringify($result) . '）');
        } catch (Exception $e) {
            $ok = ($e instanceof $exceptionClass);
            self::report($caseId, $label, $ok, $ok ? '' :
                "期待: {$exceptionClass} / 実際: " . get_class($e) . ' - ' . $e->getMessage());
        }
    }

    /** 例外が投げられないことを確認する */
    public static function assertNotThrows($caseId, $label, $callback) {
        try {
            call_user_func($callback);
            self::report($caseId, $label, true);
        } catch (Exception $e) {
            self::report($caseId, $label, false, get_class($e) . ' - ' . $e->getMessage());
        }
    }

    /** 終了時に実行する後始末を登録する */
    public static function addCleanup($callback) {
        self::$cleanups[] = $callback;
    }

    /** 後始末を実行する */
    public static function cleanup() {
        foreach (array_reverse(self::$cleanups) as $callback) {
            try {
                call_user_func($callback);
            } catch (Exception $e) {
                echo "  [WARN] 後始末に失敗: " . $e->getMessage() . "\n";
            }
        }
        self::$cleanups = array();
    }

    /**
     * 結果を集計して表示する
     *
     * @return int 失敗数（終了コードに使う）
     */
    public static function summarize($title) {
        $total = count(self::$results);
        $failed = array_filter(self::$results, function ($r) { return !$r['ok']; });
        echo "\n" . str_repeat('=', 62) . "\n";
        printf("%s: %d件中 %d件成功 / %d件失敗\n", $title, $total, $total - count($failed), count($failed));
        foreach ($failed as $r) {
            printf("  [NG] %s %s : %s\n", $r['id'], $r['label'], $r['detail']);
        }
        echo str_repeat('=', 62) . "\n";
        return count($failed);
    }

    /** 値を読める形にする */
    private static function stringify($value) {
        if (is_bool($value)) return $value ? 'true' : 'false';
        if ($value === null) return 'null';
        if (is_array($value)) return json_encode($value, JSON_UNESCAPED_UNICODE);
        return "'" . $value . "'";
    }
}

// ---- テストデータのヘルパー ---------------------------------------------

/** テストデータの目印（後始末で使う） */
define('SPEC_PREFIX', 'SPECTEST_');

/** テスト用に使う年（初期データと衝突しない） */
define('SPEC_YEAR', 2035);

/**
 * 休祝日マスタのテストデータを消す
 */
function specCleanHolidays() {
    $db = PearDatabase::getInstance();
    $db->pquery("DELETE FROM vtiger_holidays WHERE YEAR(holiday_date) = ?", array(SPEC_YEAR));
    $db->pquery("DELETE FROM vtiger_holidays WHERE holiday_name LIKE ?", array(SPEC_PREFIX . '%'));
    FR_BusinessDay::clearCache();
}

/**
 * 休日を1件登録する
 *
 * @param string $date 'Y-m-d'
 * @param string $dayType holiday / workday
 */
function specAddHoliday($date, $dayType = 'holiday', $name = null) {
    $db = PearDatabase::getInstance();
    $db->pquery(
        "INSERT INTO vtiger_holidays (holiday_date, holiday_name, day_type, holiday_type)
         VALUES (?, ?, ?, 'company')",
        array($date, $name === null ? SPEC_PREFIX . $date : $name, $dayType)
    );
    FR_BusinessDay::clearCache();
}

/**
 * 週休の曜日を設定する（テスト後は specRestoreWeeklyHolidays() で戻す）
 *
 * @param array $weekdays
 */
function specSetWeeklyHolidays($weekdays) {
    FR_BusinessDay::setWeeklyHolidays($weekdays);
    FR_BusinessDay::clearCache();
}

/** 週休を既定（土日）に戻す */
function specRestoreWeeklyHolidays() {
    FR_BusinessDay::setWeeklyHolidays(array(0, 6));
    FR_BusinessDay::clearCache();
}

/**
 * ドキュメント設定を退避する
 *
 * @return array 退避した設定
 */
function specBackupDocumentsSettings() {
    $db = PearDatabase::getInstance();
    $result = $db->pquery("SELECT name, value FROM vtiger_documents_settings", array());
    $settings = array();
    if ($result !== false) {
        for ($i = 0; $i < $db->num_rows($result); $i++) {
            $row = $db->query_result_rowdata($result, $i);
            $settings[$row['name']] = $row['value'];
        }
    }
    return $settings;
}

/**
 * 退避したドキュメント設定を書き戻す
 *
 * @param array $settings
 */
function specRestoreDocumentsSettings($settings) {
    $db = PearDatabase::getInstance();
    $db->pquery("DELETE FROM vtiger_documents_settings", array());
    foreach ($settings as $name => $value) {
        $db->pquery("INSERT INTO vtiger_documents_settings (name, value) VALUES (?, ?)",
            array($name, $value));
    }
    if (class_exists('Documents_DeadlineCalculator')) {
        Documents_DeadlineCalculator::clearCache();
    }
    if (class_exists('Documents_ComplianceChecker')) {
        Documents_ComplianceChecker::clearCache();
    }
}

/**
 * ドキュメント設定を1件書き込む
 */
function specSetDocumentsSetting($name, $value) {
    $db = PearDatabase::getInstance();
    $exists = $db->pquery("SELECT name FROM vtiger_documents_settings WHERE name = ?", array($name));
    if ($exists !== false && $db->num_rows($exists) > 0) {
        $db->pquery("UPDATE vtiger_documents_settings SET value = ? WHERE name = ?", array($value, $name));
    } else {
        $db->pquery("INSERT INTO vtiger_documents_settings (name, value) VALUES (?, ?)", array($name, $value));
    }
    if (class_exists('Documents_DeadlineCalculator')) {
        Documents_DeadlineCalculator::clearCache();
    }
    if (class_exists('Documents_ComplianceChecker')) {
        Documents_ComplianceChecker::clearCache();
    }
}

/**
 * テスト用のドキュメントを作成する（外部URL。ファイル操作を伴わない）
 *
 * @param string $suffix タイトルの接尾辞
 * @param array $fields 追加で設定する項目
 * @return int 作成したドキュメントID
 */
function specCreateDocument($suffix, $fields = array()) {
    $recordModel = Vtiger_Record_Model::getCleanInstance('Documents');
    $recordModel->set('mode', '');
    $recordModel->set('notes_title', SPEC_PREFIX . $suffix);
    $recordModel->set('filelocationtype', 'E');
    $recordModel->set('filename', 'http://example.com/spec-test');
    $recordModel->set('filestatus', 1);
    $recordModel->set('folderid', 1);
    $recordModel->set('assigned_user_id', 1);
    foreach ($fields as $key => $value) {
        $recordModel->set($key, $value);
    }
    $recordModel->save();
    return (int) $recordModel->getId();
}

/**
 * ドキュメントのカラムを直接更新する（画面を経由しない前提条件の作成に使う）
 */
function specUpdateNotes($notesId, $columns) {
    $db = PearDatabase::getInstance();
    $sets = array();
    $params = array();
    foreach ($columns as $column => $value) {
        $sets[] = "$column = ?";
        $params[] = $value;
    }
    $params[] = (int) $notesId;
    $db->pquery("UPDATE vtiger_notes SET " . implode(', ', $sets) . " WHERE notesid = ?", $params);
}

/**
 * テストで作成したドキュメントを消す
 */
function specCleanDocuments() {
    $db = PearDatabase::getInstance();
    $result = $db->pquery(
        "SELECT notesid FROM vtiger_notes WHERE title LIKE ?", array(SPEC_PREFIX . '%'));
    if ($result === false) return;
    for ($i = 0; $i < $db->num_rows($result); $i++) {
        $notesId = (int) $db->query_result($result, $i, 'notesid');
        $db->pquery("DELETE FROM vtiger_notes_audit_log WHERE notesid = ?", array($notesId));
        $db->pquery("DELETE FROM vtiger_notes_file_versions WHERE notesid = ?", array($notesId));
        $db->pquery("DELETE FROM vtiger_senotesrel WHERE notesid = ?", array($notesId));
        $db->pquery("DELETE FROM vtiger_notes WHERE notesid = ?", array($notesId));
        $db->pquery("DELETE FROM vtiger_crmentity WHERE crmid = ?", array($notesId));
    }
}
