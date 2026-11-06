<?php

/**
 * マイグレーション: add_autonumber_uitype
 * 生成日時: 20261028100000
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
require_once 'include/events/include.inc';

/**
 * 自動採番項目（uitype 997）を使えるようにする。
 *
 * vtiger_ws_fieldtype
 *   997 → string の対応を追加する。項目のデータ型は string のままなので、
 *   検索・条件・並べ替え・レポート・ワークフローなどは文字の項目として使える。
 *
 * テーブル
 *   vtiger_autonumber_config   項目ごとの設定
 *   vtiger_autonumber_counter  項目・スコープごとの連番
 *   vtiger_autonumber_job      遡及採番のジョブ
 *
 * イベントハンドラ（AutoNumberHandler）
 *   保存前: 新規レコードに番号を付ける。既存レコードは保存済みの値に固定する。
 *
 * 定期ジョブ（AutoNumberBackfill）
 *   遡及採番のジョブを少しずつ進める。ジョブが無いときは何もしない。
 *
 * いずれも既にあれば手を触れない。
 */
class Migration20261028100000_AddAutonumberUitype extends FRMigrationClass
{
    public const UITYPE = '997';
    public const FIELD_TYPE = 'string';
    public const HANDLER_PATH = 'modules/Vtiger/handlers/AutoNumberHandler.php';
    public const HANDLER_CLASS = 'AutoNumberHandler';
    public const CRON_NAME = 'AutoNumberBackfill';
    public const CRON_HANDLER = 'cron/modules/Vtiger/AutoNumberBackfill.service';
    /** 実行の間隔（秒）。OS の cron が毎分呼ぶ前提 */
    public const CRON_FREQUENCY = 60;

    public function process(): void
    {
        $this->addFieldType();
        $this->createTables();
        $this->registerHandlers();
        $this->registerCronTask();
        $this->log("マイグレーション add_autonumber_uitype が正常に完了しました");
    }

    /**
     * uitype とデータ型の対応を追加する
     */
    public function addFieldType(): void
    {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SELECT 1 FROM vtiger_ws_fieldtype WHERE uitype = ?', [self::UITYPE]);
        if ($db->num_rows($result) > 0) {
            $this->log('vtiger_ws_fieldtype の uitype ' . self::UITYPE . ' は登録済みです');
            return;
        }
        $db->pquery(
            'INSERT INTO vtiger_ws_fieldtype (uitype, fieldtype) VALUES (?, ?)',
            [self::UITYPE, self::FIELD_TYPE]
        );
        $this->log('vtiger_ws_fieldtype に uitype ' . self::UITYPE . ' を追加しました');
    }

    /**
     * 設定・連番・ジョブのテーブルを作る
     */
    public function createTables(): void
    {
        $db = PearDatabase::getInstance();
        $db->query(
            'CREATE TABLE IF NOT EXISTS vtiger_autonumber_config (
                fieldid INT NOT NULL,
                format VARCHAR(100) NOT NULL,
                reset_type VARCHAR(10) NOT NULL DEFAULT \'none\',
                start_number INT NOT NULL DEFAULT 1,
                base_date VARCHAR(50) NOT NULL DEFAULT \'\',
                effective_from DATETIME NULL DEFAULT NULL,
                active TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (fieldid)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        $db->query(
            'CREATE TABLE IF NOT EXISTS vtiger_autonumber_counter (
                fieldid INT NOT NULL,
                scope_key VARCHAR(10) NOT NULL DEFAULT \'\',
                cur_value INT NOT NULL,
                PRIMARY KEY (fieldid, scope_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        $db->query(
            'CREATE TABLE IF NOT EXISTS vtiger_autonumber_job (
                jobid INT NOT NULL AUTO_INCREMENT,
                fieldid INT NOT NULL,
                status VARCHAR(12) NOT NULL DEFAULT \'queued\',
                created_from DATETIME NULL DEFAULT NULL,
                created_to DATETIME NULL DEFAULT NULL,
                total INT NOT NULL DEFAULT 0,
                processed INT NOT NULL DEFAULT 0,
                failed INT NOT NULL DEFAULT 0,
                failed_ids TEXT NULL,
                last_number VARCHAR(100) NOT NULL DEFAULT \'\',
                lock_until DATETIME NULL DEFAULT NULL,
                userid INT NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL,
                started_at DATETIME NULL DEFAULT NULL,
                finished_at DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (jobid),
                KEY idx_autonumber_job_field_status (fieldid, status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
        $this->log('自動採番のテーブルを用意しました');
    }

    /**
     * 新規レコードへの採番と、既存レコードの値の固定を行うハンドラを登録する
     */
    public function registerHandlers(): void
    {
        $em = new VTEventsManager(PearDatabase::getInstance());
        $em->registerHandler('vtiger.entity.beforesave', self::HANDLER_PATH, self::HANDLER_CLASS);
        $this->log('AutoNumberHandler を登録しました');
    }

    /**
     * 遡及採番を進める定期ジョブを登録する
     */
    public function registerCronTask(): void
    {
        $db = PearDatabase::getInstance();
        $existing = $db->pquery(
            'SELECT id FROM vtiger_cron_task WHERE name = ? OR handler_file = ?',
            [self::CRON_NAME, self::CRON_HANDLER]
        );
        if ($existing !== false && $db->num_rows($existing) > 0) {
            $this->log('定期ジョブ ' . self::CRON_NAME . ' は既に登録済みのためスキップします');
            return;
        }
        $sequenceResult = $db->pquery('SELECT COALESCE(MAX(sequence), 0) + 1 AS next_seq FROM vtiger_cron_task', []);
        $sequence = (int) $db->query_result($sequenceResult, 0, 'next_seq');
        $db->pquery(
            'INSERT INTO vtiger_cron_task
                (name, handler_file, frequency, status, module, sequence, description, retry_timeout)
             VALUES (?, ?, ?, 1, ?, ?, ?, 0)',
            [
                self::CRON_NAME,
                self::CRON_HANDLER,
                self::CRON_FREQUENCY,
                'Vtiger',
                $sequence,
                '自動採番項目の遡及採番を進めます。ジョブが無いときは何もしません。推奨間隔は1分です。',
            ]
        );
        $this->log('定期ジョブ ' . self::CRON_NAME . " を登録しました（sequence={$sequence}）");
    }
}
