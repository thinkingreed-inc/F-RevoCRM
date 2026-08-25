<?php
/**
 * マイグレーション: add_cron_task_owner_columns
 * 生成日時: 20260825065226
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

class Migration20260825065226_AddCronTaskOwnerColumns extends FRMigrationClass {

    /**
     * cron タスクに「どのサーバーが実行しているか」を記録する列を追加する。
     *
     * アプリケーションサーバーが複数台ある構成では、実行中のタスクの状態をサーバーの
     * ローカルファイル（PIDファイル）で管理できない。他のサーバーからは見えず、
     * プロセスの生死も判定できないためである。実行権を持つホスト名・子プロセスの PID・
     * 稼働中であることを示すハートビートを、共有されているデータベース側で持つ。
     *
     *   owner_host     : 実行権を持つサーバーのホスト名
     *   owner_pid      : 実行中の子プロセスの PID（そのサーバー上での値）
     *   last_heartbeat : 担当サーバーが「子プロセスは生きている」と最後に確認した時刻
     *
     * last_heartbeat が一定時間更新されない＝担当サーバーが落ちたとみなし、
     * 他のサーバーがタスクを引き継げるようにする。
     */
    public function process() {
        $db = PearDatabase::getInstance();

        $columns = array(
            'owner_host'     => "ALTER TABLE vtiger_cron_task ADD COLUMN owner_host VARCHAR(255) DEFAULT NULL",
            'owner_pid'      => "ALTER TABLE vtiger_cron_task ADD COLUMN owner_pid INT UNSIGNED DEFAULT 0",
            'last_heartbeat' => "ALTER TABLE vtiger_cron_task ADD COLUMN last_heartbeat INT UNSIGNED DEFAULT 0",
        );

        foreach ($columns as $columnName => $sql) {
            $result = $db->pquery('SHOW COLUMNS FROM vtiger_cron_task LIKE ?', array($columnName));
            if ($db->num_rows($result) == 0) {
                $db->query($sql);
                $this->log(sprintf('vtiger_cron_task に %s を追加しました', $columnName));
            }
        }

        // 移行時点で実行中のまま残っているタスクは、どのサーバーが担当か分からない。
        // ハートビートを 0 にしておくことで、retry_timeout 経過後に
        // いずれかのサーバーが引き継げるようにする。
        $db->query('UPDATE vtiger_cron_task SET last_heartbeat = 0 WHERE status = 2');

        $this->log('マイグレーション add_cron_task_owner_columns が正常に完了しました');
    }
}
