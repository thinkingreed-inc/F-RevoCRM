<?php
/**
 * 電子帳簿保存法対応: 適合チェックロジック
 *
 * ドキュメントが電帳法の要件を満たしているかを検証し、compliance_status を更新する。
 */
class Documents_ComplianceChecker {

    /** 書類区分ごとの設定が無い場合に取引レコードとみなすモジュール */
    const DEFAULT_TRANSACTION_MODULES = array(
        'SalesOrder', 'Invoice', 'PurchaseOrder', 'Quotes',
        'ServiceContracts', 'Accounts', 'Vendors',
    );

    /**
     * 書類区分ごとに取引レコードとみなすモジュールの既定値
     * 設定画面（設定 > システム構成 > 電子帳簿保存法）で変更できる。
     */
    const DEFAULT_CATEGORY_TRANSACTION_MODULES = array(
        'invoice' => array('Invoice', 'SalesOrder', 'PurchaseOrder', 'Accounts', 'Vendors'),
        'receipt' => array('Invoice', 'PurchaseOrder', 'Accounts', 'Vendors'),
        'contract' => array('ServiceContracts', 'Accounts', 'Vendors'),
        'estimate' => array('Quotes', 'Potentials', 'Accounts', 'Vendors'),
        'order' => array('SalesOrder', 'PurchaseOrder', 'Accounts', 'Vendors'),
        'delivery' => array('SalesOrder', 'Invoice', 'Accounts', 'Vendors'),
        'other' => array('Invoice', 'SalesOrder', 'PurchaseOrder', 'Quotes',
            'ServiceContracts', 'Accounts', 'Vendors'),
    );

    /** 設定テーブル（ドキュメント機能の設定） */
    const SETTINGS_TABLE = 'vtiger_documents_settings';

    /** 書類区分ごとの取引モジュールを保持する設定名 */
    const SETTING_CATEGORY_MODULES = 'compliance_transaction_modules';

    /** 設定値のキャッシュ（未読込は null） */
    private static $categoryModules = null;

    /** 書類区分の有効値 */
    const VALID_CATEGORIES = array(
        'invoice', 'receipt', 'contract', 'estimate', 'order', 'delivery', 'other',
    );

    /** 保存区分の有効値 */
    const VALID_PRESERVATION_TYPES = array(
        'electronic_transaction', 'scanner',
    );

    /** スキャナ保存で必要な解像度（dpi） */
    const MIN_SCAN_RESOLUTION_DPI = 200;

    /**
     * 電帳法対象を抽出する SQL 条件
     *
     * 書類区分が空文字のレコードは対象外（isComplianceTarget() と判定を揃える）。
     * 集計や絞り込みで対象外のドキュメントを数えないよう、条件をここに集約する。
     */
    const TARGET_SQL_CONDITION =
        "vtiger_notes.document_category IS NOT NULL AND vtiger_notes.document_category != ''";

    /**
     * 解像度の値を整数に整える
     *
     * 未入力（空文字・null）は 0 として扱い、要件を満たさない扱いにする。
     * 数値として解釈できない値は、誤った判定をしないよう例外にする。
     *
     * @param mixed $value
     * @return int
     * @throws InvalidArgumentException 数値として解釈できない場合
     */
    public static function normalizeResolution($value) {
        if ($value === null || $value === false) {
            return 0;
        }
        $value = trim((string) $value);
        if ($value === '') {
            return 0;
        }
        if (!preg_match('/^-?\d+$/', $value)) {
            throw new InvalidArgumentException('Invalid scan resolution: ' . $value);
        }
        return (int) $value;
    }

    /**
     * ドキュメントが電帳法対象かどうかを判定する
     *
     * @param int $notesId ドキュメントID
     * @return bool
     */
    public static function isComplianceTarget($notesId) {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT document_category FROM vtiger_notes WHERE notesid = ?",
            array($notesId)
        );
        if ($result === false || $db->num_rows($result) === 0) {
            return false;
        }
        $category = $db->query_result($result, 0, 'document_category');
        return !empty($category);
    }

    /**
     * 書類区分ごとに取引レコードとみなすモジュールの設定を返す
     *
     * @return array 書類区分 => モジュール名の配列
     */
    public static function getCategoryTransactionModules() {
        if (self::$categoryModules !== null) {
            return self::$categoryModules;
        }

        $stored = self::readSetting(self::SETTING_CATEGORY_MODULES);
        // 保存時に HTML エンティティ化されるため、復号してから JSON を解析する
        $settings = ($stored === null) ? null : json_decode(decode_html($stored), true);
        if (!is_array($settings)) {
            self::$categoryModules = self::DEFAULT_CATEGORY_TRANSACTION_MODULES;
            return self::$categoryModules;
        }

        // 設定に無い書類区分は既定値を使う
        $categoryModules = self::DEFAULT_CATEGORY_TRANSACTION_MODULES;
        foreach ($settings as $category => $modules) {
            if (!is_array($modules)) {
                continue;
            }
            $categoryModules[$category] = array_values(array_unique(array_map('strval', $modules)));
        }
        self::$categoryModules = $categoryModules;
        return self::$categoryModules;
    }

    /**
     * 指定した書類区分で取引レコードとみなすモジュールを返す
     *
     * @param string|null $category 書類区分（未設定なら全区分の和集合）
     * @return array モジュール名の配列
     */
    public static function getTransactionModules($category = null) {
        $categoryModules = self::getCategoryTransactionModules();
        if ($category !== null && $category !== '') {
            if (isset($categoryModules[$category])) {
                return $categoryModules[$category];
            }
            // 未知の書類区分は既定のモジュールで判定する
            return self::DEFAULT_TRANSACTION_MODULES;
        }

        $modules = array();
        foreach ($categoryModules as $categoryModuleList) {
            foreach ($categoryModuleList as $module) {
                if (!in_array($module, $modules, true)) {
                    $modules[] = $module;
                }
            }
        }
        return empty($modules) ? self::DEFAULT_TRANSACTION_MODULES : $modules;
    }

    /**
     * 設定を保存する
     *
     * @param array $categoryModules 書類区分 => モジュール名の配列
     * @return array 保存後の設定
     */
    public static function saveCategoryTransactionModules($categoryModules) {
        $db = PearDatabase::getInstance();
        $value = json_encode($categoryModules, JSON_UNESCAPED_UNICODE);

        $existing = $db->pquery(
            'SELECT name FROM ' . self::SETTINGS_TABLE . ' WHERE name = ?',
            array(self::SETTING_CATEGORY_MODULES)
        );
        if ($existing !== false && $db->num_rows($existing) > 0) {
            $db->pquery(
                'UPDATE ' . self::SETTINGS_TABLE . ' SET value = ? WHERE name = ?',
                array($value, self::SETTING_CATEGORY_MODULES)
            );
        } else {
            $db->pquery(
                'INSERT INTO ' . self::SETTINGS_TABLE . ' (name, value) VALUES (?, ?)',
                array(self::SETTING_CATEGORY_MODULES, $value)
            );
        }

        self::$categoryModules = null;
        return self::getCategoryTransactionModules();
    }

    /**
     * 設定値のキャッシュを破棄する
     */
    public static function clearCache() {
        self::$categoryModules = null;
    }

    /**
     * 設定テーブルから値を読み出す（テーブル・行が無ければ null）
     *
     * @param string $name
     * @return string|null
     */
    private static function readSetting($name) {
        $db = PearDatabase::getInstance();
        // マイグレーション前でもエラーにしない
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
     * 関連レコードの有無をチェックする
     *
     * @param int $notesId ドキュメントID
     * @param string|null $category 書類区分（省略時はドキュメントの書類区分を使う）
     * @return array ['has_related' => bool, 'related_records' => array, 'modules' => array]
     */
    public static function checkRelatedRecords($notesId, $category = null) {
        $db = PearDatabase::getInstance();
        if ($category === null) {
            $categoryResult = $db->pquery(
                "SELECT document_category FROM vtiger_notes WHERE notesid = ?", array($notesId));
            if ($categoryResult !== false && $db->num_rows($categoryResult) > 0) {
                $category = $db->query_result($categoryResult, 0, 'document_category');
            }
        }
        $transactionModules = self::getTransactionModules($category);
        if (empty($transactionModules)) {
            // 対象モジュールが無い設定なら関連付けは要求しない
            return array('has_related' => true, 'related_records' => array(), 'modules' => array());
        }

        $result = $db->pquery(
            "SELECT vtiger_senotesrel.crmid, vtiger_crmentity.setype, vtiger_crmentity.label
            FROM vtiger_senotesrel
            INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_senotesrel.crmid
            WHERE vtiger_senotesrel.notesid = ? AND vtiger_crmentity.deleted = 0
            AND vtiger_crmentity.setype IN (" . generateQuestionMarks($transactionModules) . ")",
            array_merge(array($notesId), $transactionModules)
        );

        $relatedRecords = array();
        if ($result !== false) {
            $numRows = $db->num_rows($result);
            for ($i = 0; $i < $numRows; $i++) {
                $row = $db->query_result_rowdata($result, $i);
                $relatedRecords[] = array(
                    'id' => (int) $row['crmid'],
                    'module' => $row['setype'],
                    'label' => decode_html($row['label']),
                );
            }
        }

        return array(
            'has_related' => count($relatedRecords) > 0,
            'related_records' => $relatedRecords,
            'modules' => $transactionModules,
        );
    }

    /**
     * ドキュメントの適合チェックを実行し、ステータスを更新する
     *
     * @param int $notesId ドキュメントID
     * @return array ['status' => string, 'issues' => array]
     */
    public static function check($notesId) {
        $db = PearDatabase::getInstance();
        // 不適合の理由は言語非依存の翻訳キーで保持し、表示時に翻訳する
        // （compliance_notes に翻訳済みの文字列を保存すると保存時の言語で固定されてしまう）
        $issueKeys = array();

        // 電帳法対象でなければスキップ
        if (!self::isComplianceTarget($notesId)) {
            return array('status' => null, 'issues' => array());
        }

        // ドキュメント情報取得
        $result = $db->pquery(
            "SELECT document_category, preservation_type, file_hash,
                    filelocationtype, scan_resolution_dpi, scan_color_type
            FROM vtiger_notes WHERE notesid = ?",
            array($notesId)
        );
        if ($result === false || $db->num_rows($result) === 0) {
            return array(
                'status' => 'non_compliant',
                'issues' => array(vtranslate('LBL_ISSUE_RECORD_NOT_FOUND', 'Documents')),
            );
        }
        $row = $db->query_result_rowdata($result, 0);

        // 1. 関連レコードチェック（書類区分ごとに対象モジュールが異なる）
        $relCheck = self::checkRelatedRecords($notesId, $row['document_category']);
        if (!$relCheck['has_related']) {
            $issueKeys[] = 'LBL_NO_RELATED_RECORD';
        }

        // 2. ファイルハッシュチェック（内部ファイルのみ）
        if ($row['filelocationtype'] === 'I' && empty($row['file_hash'])) {
            $issueKeys[] = 'LBL_ISSUE_NO_FILE_HASH';
        }

        // 3. 保存区分チェック
        if (empty($row['preservation_type'])) {
            $issueKeys[] = 'LBL_ISSUE_NO_PRESERVATION_TYPE';
        }

        // 4. スキャナ保存固有チェック
        //    解像度は未入力（空・NULL）を 0 として扱い、要件（200dpi以上）を満たさない扱いにする
        if ($row['preservation_type'] === 'scanner') {
            if (self::normalizeResolution($row['scan_resolution_dpi']) < self::MIN_SCAN_RESOLUTION_DPI) {
                $issueKeys[] = 'LBL_ISSUE_LOW_SCAN_RESOLUTION';
            }
        }

        // ステータス判定
        $status = empty($issueKeys) ? 'compliant' : 'non_compliant';

        // DBを更新（翻訳キーのまま保存する）
        $db->pquery(
            "UPDATE vtiger_notes SET compliance_status = ?, compliance_checked_at = NOW(),
             compliance_notes = ? WHERE notesid = ?",
            array($status, implode('; ', $issueKeys), $notesId)
        );

        return array('status' => $status, 'issues' => self::translateNotes($issueKeys));
    }

    /**
     * compliance_notes を表示用の文字列に変換する
     *
     * 翻訳キーで保存された不適合理由を現在の言語に翻訳する。
     * 過去に日本語のまま保存された値はキーに一致しないため、そのまま返る。
     *
     * @param string|array $notes 「; 」区切りの文字列またはキーの配列
     * @param bool $asArray true なら配列で返す
     * @return string|array
     */
    public static function translateNotes($notes, $asArray = true) {
        if ($notes === null || $notes === '') {
            return $asArray ? array() : '';
        }
        $keys = is_array($notes) ? $notes : explode('; ', $notes);
        $labels = array();
        foreach ($keys as $key) {
            $key = trim($key);
            if ($key === '') {
                continue;
            }
            $labels[] = vtranslate($key, 'Documents');
        }
        return $asArray ? $labels : implode('; ', $labels);
    }

    /**
     * 電帳法対象ドキュメントの一括適合チェック
     *
     * 電帳法対象でないドキュメント（書類区分が未設定・空文字）は判定せず、
     * 適合・不適合のどちらにも数えない。
     *
     * @return array ['checked' => int, 'compliant' => int, 'non_compliant' => int, 'skipped' => int]
     */
    public static function batchCheck() {
        $db = PearDatabase::getInstance();
        $result = $db->pquery(
            "SELECT vtiger_notes.notesid FROM vtiger_notes
            INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
            WHERE " . self::TARGET_SQL_CONDITION . " AND vtiger_crmentity.deleted = 0",
            array()
        );

        if ($result === false) {
            throw new Exception(vtranslate('LBL_BATCH_CHECK_FAILED', 'Documents'));
        }

        $checked = 0;
        $compliant = 0;
        $nonCompliant = 0;
        $skipped = 0;

        $numRows = $db->num_rows($result);
        for ($i = 0; $i < $numRows; $i++) {
            $notesId = $db->query_result($result, $i, 'notesid');
            try {
                $checkResult = self::check($notesId);
            } catch (InvalidArgumentException $e) {
                // 値が不正で判定できないドキュメントで全体を止めない（不適合として数える）
                global $log;
                if (isset($log) && is_object($log)) {
                    $log->error("Documents compliance check failed for record {$notesId}: "
                        . $e->getMessage());
                }
                $checked++;
                $nonCompliant++;
                continue;
            }
            if ($checkResult['status'] === null) {
                // 電帳法対象外。不適合として数えない
                $skipped++;
                continue;
            }
            $checked++;
            if ($checkResult['status'] === 'compliant') {
                $compliant++;
            } else {
                $nonCompliant++;
            }
        }

        return array(
            'checked' => $checked,
            'compliant' => $compliant,
            'non_compliant' => $nonCompliant,
            'skipped' => $skipped,
        );
    }
}
