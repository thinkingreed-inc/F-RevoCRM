<?php
/**
 * マイグレーション: fix_documents_deadline_cron_frequency
 * 生成日時: 20260825012811
 *
 * 入力期限の状態を更新する定期ジョブの周期を 24時間 から 15分 に短くする。
 *
 * 期限状態（期限内・期限間近・期限超過）は日付が変わると変わる値だが、
 * vtiger の定期ジョブは「最終開始時刻からの経過時間」で起動を判断する
 * （vtlib/Vtiger/Cron.php: elapsed >= frequency - 60）。
 * 24時間周期では最初に動いた時刻が基準になるため、日付が変わっても
 * 次にその時刻が来るまで状態が古いままになる（最大でほぼ1日）。
 *
 * 他の日時に関わるジョブ（ワークフロー・リマインダー等）と同じ15分にする。
 * 更新は状態が変わる行だけを対象にするため、頻度を上げても負荷は増えない。
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260825012811_FixDocumentsDeadlineCronFrequency extends FRMigrationClass {

    /** 対象の定期ジョブ */
    const CRON_NAME = 'DocumentsInputDeadlineStatus';

    /** 変更後の周期（15分） */
    const TARGET_FREQUENCY = 900;

    public function process() {
        $current = $this->getCurrentFrequency();
        if ($current === null) {
            $this->log('定期ジョブ ' . self::CRON_NAME . ' が未登録のためスキップします');
            return;
        }
        if ((int) $current === self::TARGET_FREQUENCY) {
            $this->log('既に ' . self::TARGET_FREQUENCY . '秒のためスキップします');
            return;
        }

        $this->db->pquery(
            'UPDATE vtiger_cron_task SET frequency = ? WHERE name = ?',
            array(self::TARGET_FREQUENCY, self::CRON_NAME));
        $this->log("定期ジョブの周期を {$current}秒 から " . self::TARGET_FREQUENCY . '秒 に変更しました');
    }

    /**
     * 現在の周期（秒）を返す
     *
     * @return int|null 未登録なら null
     */
    private function getCurrentFrequency() {
        $result = $this->db->pquery(
            'SELECT frequency FROM vtiger_cron_task WHERE name = ?', array(self::CRON_NAME));
        if ($result === false || $this->db->num_rows($result) === 0) {
            return null;
        }
        return (int) $this->db->query_result($result, 0, 'frequency');
    }

}
