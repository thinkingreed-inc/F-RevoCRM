<?php
/**
 * マイグレーション: setup_documents_settings
 * 生成日時: 20260806102225
 *
 * 電子帳簿保存法対応（スキャナ保存）の設定・定期ジョブ・設定メニューを用意し、
 * 既存ドキュメントの入力期限と適合状態を初期化する。
 *
 * 入力期限の方針（input_deadline_policy）
 *   prompt: 速やかに（受領後おおむね7営業日以内）… 既定
 *   cycle : 業務処理サイクル後速やかに（最長2か月＋おおむね7営業日以内）
 *
 * 適合判定の取引モジュール（compliance_transaction_modules）
 *   「取引レコードに関連付けされているか」を確認する対象を書類区分ごとに設定する。
 *   例）契約書は「契約」に紐づいていれば適合、請求書は「請求」「受注」「発注」
 *       「顧客企業」「仕入先」のいずれかに紐づいていれば適合。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
require_once 'modules/Documents/utils/DeadlineCalculator.php';
require_once 'modules/Documents/utils/ComplianceChecker.php';

class Migration20260806102225_SetupDocumentsSettings extends FRMigrationClass {

    /** 設定テーブル名 */
    const TABLE_NAME = 'vtiger_documents_settings';

    /** 定期ジョブ名 */
    const CRON_NAME = 'DocumentsInputDeadlineStatus';

    /** 定期ジョブのハンドラ */
    const CRON_HANDLER = 'cron/modules/Documents/UpdateInputDeadlineStatus.service';

    /** 定期ジョブの実行間隔（秒）。日付でしか変わらないため1日1回 */
    const CRON_FREQUENCY = 86400;

    /** 設定メニューを追加するブロック（システム構成） */
    const SETTINGS_BLOCK = 'LBL_CONFIGURATION';

    /** 設定メニュー名 */
    const SETTINGS_FIELD = 'LBL_DOCUMENTS_COMPLIANCE';

    public function process() {
        $this->createTable();
        $this->seedDeadlineSettings();
        $complianceSeeded = $this->seedComplianceTransactionModules();
        $this->registerCronTask();
        $this->registerSettingsMenu();
        $this->initializeDeadlines();
        if ($complianceSeeded) {
            $this->recheckCompliance();
        }
    }

    /**
     * ドキュメント機能の設定テーブルを作成する（名前と値の組で保持する）
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
     * 入力期限の設定の初期値を登録する
     */
    private function seedDeadlineSettings() {
        $defaults = array(
            Documents_DeadlineCalculator::SETTING_POLICY => Documents_DeadlineCalculator::POLICY_PROMPT,
            Documents_DeadlineCalculator::SETTING_BUSINESS_DAYS => Documents_DeadlineCalculator::DEFAULT_BUSINESS_DAYS,
            Documents_DeadlineCalculator::SETTING_CYCLE_MONTHS => Documents_DeadlineCalculator::DEFAULT_CYCLE_MONTHS,
            Documents_DeadlineCalculator::SETTING_WARNING_DAYS => Documents_DeadlineCalculator::DEFAULT_WARNING_DAYS,
        );

        foreach ($defaults as $name => $value) {
            if ($this->settingExists($name)) {
                $this->log("設定 {$name} は既に登録済みのためスキップします");
                continue;
            }
            $this->insertSetting($name, (string) $value);
            $this->log("設定 {$name} を登録しました（{$value}）");
        }
    }

    /**
     * 書類区分ごとの取引モジュールの既定値を登録する
     *
     * @return boolean 新たに登録した場合は true（既存の設定は上書きしない）
     */
    private function seedComplianceTransactionModules() {
        $name = Documents_ComplianceChecker::SETTING_CATEGORY_MODULES;
        if ($this->settingExists($name)) {
            $this->log("設定 {$name} は既に登録済みのためスキップします");
            return false;
        }

        $defaults = Documents_ComplianceChecker::DEFAULT_CATEGORY_TRANSACTION_MODULES;
        $this->insertSetting($name, json_encode($defaults, JSON_UNESCAPED_UNICODE));
        foreach ($defaults as $category => $modules) {
            $this->log("  {$category}: " . implode(', ', $modules));
        }
        $this->log("設定 {$name} を登録しました");
        return true;
    }

    /**
     * 期限状態を日次で更新する定期ジョブを登録する
     */
    private function registerCronTask() {
        $existing = $this->db->pquery(
            'SELECT id FROM vtiger_cron_task WHERE name = ? OR handler_file = ?',
            array(self::CRON_NAME, self::CRON_HANDLER)
        );
        if ($existing !== false && $this->db->num_rows($existing) > 0) {
            $this->log('定期ジョブ ' . self::CRON_NAME . ' は既に登録済みのためスキップします');
            return;
        }

        $sequenceResult = $this->db->pquery(
            'SELECT COALESCE(MAX(sequence), 0) + 1 AS next_seq FROM vtiger_cron_task', array());
        $sequence = (int) $this->db->query_result($sequenceResult, 0, 'next_seq');

        $this->db->pquery(
            'INSERT INTO vtiger_cron_task
                (name, handler_file, frequency, status, module, sequence, description, retry_timeout)
             VALUES (?, ?, ?, 1, ?, ?, ?, 0)',
            array(
                self::CRON_NAME,
                self::CRON_HANDLER,
                self::CRON_FREQUENCY,
                'Documents',
                $sequence,
                'スキャナ保存の入力期限状態（期限内・期限間近・期限超過）を更新します。推奨間隔は1日です。',
            )
        );
        $this->log('定期ジョブ ' . self::CRON_NAME . " を登録しました（sequence={$sequence}）");
    }

    /**
     * 電子帳簿保存法設定の画面を設定メニューに追加する
     */
    private function registerSettingsMenu() {
        $existing = $this->db->pquery(
            'SELECT fieldid FROM vtiger_settings_field WHERE name = ?',
            array(self::SETTINGS_FIELD)
        );
        if ($existing !== false && $this->db->num_rows($existing) > 0) {
            $this->log('設定メニュー ' . self::SETTINGS_FIELD . ' は既に登録済みのためスキップします');
            return;
        }

        $blockResult = $this->db->pquery(
            'SELECT blockid FROM vtiger_settings_blocks WHERE label = ?',
            array(self::SETTINGS_BLOCK)
        );
        if ($blockResult === false || $this->db->num_rows($blockResult) === 0) {
            $this->log('ブロック ' . self::SETTINGS_BLOCK . ' が見つからないため設定メニューの登録をスキップします');
            return;
        }
        $blockId = (int) $this->db->query_result($blockResult, 0, 'blockid');

        $sequenceResult = $this->db->pquery(
            'SELECT COALESCE(MAX(sequence), 0) + 1 AS next_seq FROM vtiger_settings_field WHERE blockid = ?',
            array($blockId)
        );
        $sequence = (int) $this->db->query_result($sequenceResult, 0, 'next_seq');
        $fieldId = $this->db->getUniqueID('vtiger_settings_field');

        $this->db->pquery(
            'INSERT INTO vtiger_settings_field
                (fieldid, blockid, name, iconpath, description, linkto, sequence, active)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0)',
            array(
                $fieldId,
                $blockId,
                self::SETTINGS_FIELD,
                'adminIcon-documents',
                'LBL_DOCUMENTS_COMPLIANCE_DESCRIPTION',
                'index.php?module=DocumentsCompliance&parent=Settings&view=List',
                $sequence,
            )
        );
        $this->log('設定メニューに電子帳簿保存法設定を追加しました（sequence=' . $sequence . '）');
    }

    /**
     * 既存ドキュメントの入力期限を計算して登録する
     *
     * これまで自動計算が行われていなかったため、受領日が入っている
     * スキャナ保存のドキュメントに期限と期限状態を設定する。
     */
    private function initializeDeadlines() {
        $result = $this->db->pquery(
            "SELECT vtiger_notes.notesid FROM vtiger_notes
             INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
             WHERE vtiger_crmentity.deleted = 0
               AND vtiger_notes.preservation_type = ?
               AND vtiger_notes.receipt_date IS NOT NULL",
            array(Documents_DeadlineCalculator::TARGET_PRESERVATION_TYPE)
        );
        if ($result === false) {
            $this->log('入力期限の初期計算をスキップしました（対象を取得できませんでした）');
            return;
        }

        $updated = 0;
        $count = $this->db->num_rows($result);
        for ($i = 0; $i < $count; $i++) {
            $notesId = (int) $this->db->query_result($result, $i, 'notesid');
            $deadline = Documents_DeadlineCalculator::recalculate($notesId);
            if (!empty($deadline['input_deadline'])) {
                $updated++;
            }
        }
        $this->log("既存ドキュメントの入力期限を計算しました（対象 {$count}件 / 設定 {$updated}件）");
    }

    /**
     * 既存ドキュメントの適合状態を判定する
     */
    private function recheckCompliance() {
        Documents_ComplianceChecker::clearCache();
        $result = Documents_ComplianceChecker::batchCheck();
        $this->log("適合状態を再判定しました（対象 {$result['checked']}件 / "
            . "適合 {$result['compliant']}件 / 不適合 {$result['non_compliant']}件）");
    }

    /**
     * 設定が登録済みかどうかを返す
     */
    private function settingExists($name) {
        $result = $this->db->pquery(
            'SELECT name FROM ' . self::TABLE_NAME . ' WHERE name = ?',
            array($name)
        );
        return ($result !== false && $this->db->num_rows($result) > 0);
    }

    /**
     * 設定を登録する
     */
    private function insertSetting($name, $value) {
        $this->db->pquery(
            'INSERT INTO ' . self::TABLE_NAME . ' (name, value) VALUES (?, ?)',
            array($name, $value)
        );
    }
}
