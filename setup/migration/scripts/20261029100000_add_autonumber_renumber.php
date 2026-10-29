<?php

/**
 * マイグレーション: add_autonumber_renumber
 * 生成日時: 20261029100000
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';

/**
 * 自動採番項目の「振り直し」（既存の番号を、開始番号から作成日時の古い順に付け直す）に使うテーブルの列を足す。
 *
 * vtiger_autonumber_job
 *   mode            fill（空の項目にだけ付ける）/ renumber（すべて振り直す）
 *   cursor_created  振り直しの進み具合（最後に処理したレコードの作成日時）
 *   cursor_id       同（最後に処理したレコードの ID）
 *
 * vtiger_autonumber_job_log
 *   振り直しで変わった番号（旧 → 新）。画面から CSV で取り出せる。
 *
 * いずれも既にあれば手を触れない。
 */
class Migration20261029100000_AddAutonumberRenumber extends FRMigrationClass
{
    public function process(): void
    {
        $this->addJobColumns();
        $this->createLogTable();
        $this->log("マイグレーション add_autonumber_renumber が正常に完了しました");
    }

    /**
     * ジョブのテーブルに、振り直しのための列を足す
     */
    public function addJobColumns(): void
    {
        $db = PearDatabase::getInstance();
        $columns = [
            'mode' => "VARCHAR(10) NOT NULL DEFAULT 'fill'",
            'cursor_created' => 'DATETIME NULL DEFAULT NULL',
            'cursor_id' => 'INT NOT NULL DEFAULT 0',
        ];
        foreach ($columns as $name => $definition) {
            $result = $db->pquery('SHOW COLUMNS FROM vtiger_autonumber_job LIKE ?', [$name]);
            if ($result !== false && $db->num_rows($result) > 0) {
                $this->log("vtiger_autonumber_job.{$name} は登録済みです");
                continue;
            }
            $db->query("ALTER TABLE vtiger_autonumber_job ADD COLUMN {$name} {$definition}");
            $this->log("vtiger_autonumber_job に {$name} を追加しました");
        }
    }

    /**
     * 振り直しで変わった番号を残すテーブルを作る
     */
    public function createLogTable(): void
    {
        $db = PearDatabase::getInstance();
        $db->query(
            'CREATE TABLE IF NOT EXISTS vtiger_autonumber_job_log (
                jobid INT NOT NULL,
                crmid INT NOT NULL,
                old_number VARCHAR(100) NOT NULL DEFAULT \'\',
                new_number VARCHAR(100) NOT NULL DEFAULT \'\',
                PRIMARY KEY (jobid, crmid)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        $this->log('振り直しの記録のテーブルを用意しました');
    }
}
