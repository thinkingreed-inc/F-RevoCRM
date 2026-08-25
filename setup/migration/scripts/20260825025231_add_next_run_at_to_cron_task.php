<?php
/**
 * マイグレーション: add_next_run_at_to_cron_task
 * 生成日時: 20260825025231
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
require_once 'vtlib/Vtiger/Cron.php';

class Migration20260825025231_AddNextRunAtToCronTask extends FRMigrationClass {

    /**
     * cron タスクに次回実行予定時刻（next_run_at）を追加する。
     *
     * 従来の実行判定は「前回開始時刻からの経過が周期を超えたか」という相対的なもので、
     * 直前のタスクを待って開始が遅れるとその遅れが積み上がり、15 分毎の設定でも実行時刻が
     * 少しずつずれていった。next_run_at に周期のグリッド上の時刻を保持し、そこと現在時刻を
     * 比較する方式へ変更するためのカラムを追加する。
     *
     * あわせて、新規インストールのスキーマに含まれていない retry_timeout も無ければ追加する。
     * 実行中のまま終わらないタスクの判定に必要なため。
     */
    public function process() {
        $db = PearDatabase::getInstance();

        // retry_timeout（実行中のまま終わらないタスクを再実行可能とみなすまでの秒数）
        $result = $db->pquery('SHOW COLUMNS FROM vtiger_cron_task LIKE ?', array('retry_timeout'));
        if ($db->num_rows($result) == 0) {
            $db->query('ALTER TABLE vtiger_cron_task ADD COLUMN retry_timeout INT DEFAULT 0');
            $this->log('vtiger_cron_task に retry_timeout を追加しました');

            $defaultTimeouts = array(
                'Workflow'         => 60 * 60,      // 1 hour
                'RecurringInvoice' => 24 * 60 * 60, // 24 hours
                'SendReminder'     => 60 * 60,      // 1 hour
                'MailScanner'      => 60 * 60,      // 1 hour
                'Scheduled Import' => 6 * 60 * 60,  // 6 hours
                'ScheduleReports'  => 3 * 60 * 60,  // 3 hours
            );
            foreach ($defaultTimeouts as $taskName => $timeout) {
                $db->pquery('UPDATE vtiger_cron_task SET retry_timeout = ? WHERE name = ? AND retry_timeout = 0',
                        array($timeout, $taskName));
            }
        }

        // next_run_at（次回実行予定時刻。実行周期のグリッド上に固定される）
        $result = $db->pquery('SHOW COLUMNS FROM vtiger_cron_task LIKE ?', array('next_run_at'));
        if ($db->num_rows($result) == 0) {
            $db->query('ALTER TABLE vtiger_cron_task ADD COLUMN next_run_at INT(11) UNSIGNED DEFAULT 0');
            $this->log('vtiger_cron_task に next_run_at を追加しました');
        }

        // 既存タスクの next_run_at を、それぞれの周期に応じた次のグリッドへ揃える。
        // 未設定（0）のものだけを対象にし、再実行しても既存の予定を壊さないようにする。
        $result = $db->pquery('SELECT id, name, frequency FROM vtiger_cron_task WHERE next_run_at IS NULL OR next_run_at = 0', array());
        $updated = 0;
        while ($row = $db->fetch_array($result)) {
            $nextRunAt = Vtiger_Cron::computeNextRunAt($row['frequency']);
            $db->pquery('UPDATE vtiger_cron_task SET next_run_at = ? WHERE id = ?', array($nextRunAt, $row['id']));
            $this->log(sprintf('%s の次回実行予定を %s に設定しました', $row['name'], date('Y-m-d H:i:s', $nextRunAt)));
            $updated++;
        }

        $this->log(sprintf('マイグレーション add_next_run_at_to_cron_task が正常に完了しました（%d 件を初期化）', $updated));
    }
}
