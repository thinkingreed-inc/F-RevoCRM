<?php
/**
 * マイグレーション: add_documents_deadline_settings
 * 生成日時: 20260806102225
 *
 * 電子帳簿保存法対応（スキャナ保存）の入力期限自動計算に必要な設定と
 * 期限状態を日次で更新する定期ジョブを登録する。
 *
 * 入力期限の方針（input_deadline_policy）
 *   prompt: 速やかに（受領後おおむね7営業日以内）… 既定
 *   cycle : 業務処理サイクル後速やかに（最長2か月＋おおむね7営業日以内）
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
require_once 'modules/Documents/utils/DeadlineCalculator.php';

class Migration20260806102225_AddDocumentsDeadlineSettings extends FRMigrationClass {

    /** 設定テーブル名 */
    const TABLE_NAME = 'vtiger_documents_settings';

    /** 定期ジョブ名 */
    const CRON_NAME = 'DocumentsInputDeadlineStatus';

    /** 定期ジョブのハンドラ */
    const CRON_HANDLER = 'cron/modules/Documents/UpdateInputDeadlineStatus.service';

    /** 定期ジョブの実行間隔（秒）。日付でしか変わらないため1日1回 */
    const CRON_FREQUENCY = 86400;

    public function process() {
        $this->createTable();
        $this->seedSettings();
        $this->registerCronTask();
        $this->initializeDeadlines();
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
    private function seedSettings() {
        $defaults = array(
            Documents_DeadlineCalculator::SETTING_POLICY => Documents_DeadlineCalculator::POLICY_PROMPT,
            Documents_DeadlineCalculator::SETTING_BUSINESS_DAYS => Documents_DeadlineCalculator::DEFAULT_BUSINESS_DAYS,
            Documents_DeadlineCalculator::SETTING_CYCLE_MONTHS => Documents_DeadlineCalculator::DEFAULT_CYCLE_MONTHS,
            Documents_DeadlineCalculator::SETTING_WARNING_DAYS => Documents_DeadlineCalculator::DEFAULT_WARNING_DAYS,
        );

        foreach ($defaults as $name => $value) {
            $existing = $this->db->pquery(
                'SELECT name FROM ' . self::TABLE_NAME . ' WHERE name = ?',
                array($name)
            );
            if ($existing !== false && $this->db->num_rows($existing) > 0) {
                $this->log("設定 {$name} は既に登録済みのためスキップします");
                continue;
            }
            $this->db->pquery(
                'INSERT INTO ' . self::TABLE_NAME . ' (name, value) VALUES (?, ?)',
                array($name, (string) $value)
            );
            $this->log("設定 {$name} を登録しました（{$value}）");
        }
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
}
