<?php
/**
 * マイグレーション: setup_cron_scheduler_stability
 * 生成日時: 20260825112920
 */

require_once dirname(__FILE__) . '/../FRMigrationClass.php';
require_once 'vtlib/Vtiger/Cron.php';

/**
 * スケジューラーの実行を安定化する（#1823）ために必要な移行をまとめて行う。
 *
 * データベース（vtiger_cron_task）
 *   retry_timeout       : 実行中のまま終わらないタスクを再実行可能とみなすまでの秒数
 *   next_run_at         : 次回実行予定時刻。実行判定を前回実行からの相対経過ではなく
 *                         予定時刻との比較で行い、遅延を次回以降へ持ち越さない
 *   owner_host          : 実行権を持つサーバーのホスト名
 *   owner_pid           : 実行中の子プロセスの PID（そのサーバー上での値）
 *   last_heartbeat      : 担当サーバーが「子プロセスは生きている」と最後に確認した時刻
 *   schedule_type       : 実行タイミングの種別（interval / daily / weekly / monthly）
 *   run_at_minutes      : 実行する時刻（0 時からの経過分。0〜1439）
 *   run_on_weekdays     : 実行する曜日。複数指定できるようカンマ区切り（0=日曜 〜 6=土曜）
 *   run_on_day          : 実行する日（1〜31。0 は月末）
 *   log_retention_count : 実行ログを残す世代数
 *
 * 設定ファイル（config.inc.php）
 *   スケジューラーの設定項目を、未記載のものだけ追記する。
 *
 * 移行はすべて冪等で、既にある列・設定には手を触れない。途中まで適用された環境でも
 * そのまま実行できる。
 */
class Migration20260825112920_SetupCronSchedulerStability extends FRMigrationClass {

    /** config.inc.php で設定を追記する位置の基準にする行（この行の直前へ挿入する） */
    const ANCHOR = "include_once 'config.security.php';";

    /** config.inc.php のバックアップに付ける接尾辞 */
    const BACKUP_SUFFIX = '.bak.20260825112920';

    /**
     * 標準の cron タスクに設定するタイムアウト秒数。
     * 実行に掛かる時間の目安が異なるため、タスクごとに変える。
     */
    protected $defaultRetryTimeouts = array(
        'Workflow'         => 3600,  // 1 時間
        'RecurringInvoice' => 86400, // 24 時間
        'SendReminder'     => 3600,  // 1 時間
        'MailScanner'      => 3600,  // 1 時間
        'Scheduled Import' => 21600, // 6 時間
        'ScheduleReports'  => 10800, // 3 時間
    );

    public function process() {
        $this->applyDatabaseChanges();
        $this->applyTo('config.inc.php');
    }

    // ------------------------------------------------------------ データベース

    /**
     * vtiger_cron_task に必要な列を追加し、既存タスクの初期値を整える。
     */
    public function applyDatabaseChanges() {
        $this->addColumns();
        $this->initializeRetryTimeouts();
        $this->initializeNextRunAt();
        $this->releaseStaleHeartbeats();
        $this->initializeScheduleType();
    }

    /**
     * 不足している列を追加する。既にある列は飛ばす。
     */
    protected function addColumns() {
        $db = PearDatabase::getInstance();

        $columns = array(
            // 実行中のまま終わらないタスクの判定に使う
            'retry_timeout'       => 'ALTER TABLE vtiger_cron_task ADD COLUMN retry_timeout INT DEFAULT 0',
            'next_run_at'         => 'ALTER TABLE vtiger_cron_task ADD COLUMN next_run_at INT(11) UNSIGNED DEFAULT 0',
            // 複数のサーバーで動かしたときに、どのサーバーが担当かを共有する
            'owner_host'          => 'ALTER TABLE vtiger_cron_task ADD COLUMN owner_host VARCHAR(255) DEFAULT NULL',
            'owner_pid'           => 'ALTER TABLE vtiger_cron_task ADD COLUMN owner_pid INT UNSIGNED DEFAULT 0',
            'last_heartbeat'      => 'ALTER TABLE vtiger_cron_task ADD COLUMN last_heartbeat INT UNSIGNED DEFAULT 0',
            // 実行タイミングの指定（周期／毎日／毎週／毎月）
            'schedule_type'       => "ALTER TABLE vtiger_cron_task ADD COLUMN schedule_type VARCHAR(16) NOT NULL DEFAULT 'interval'",
            'run_at_minutes'      => 'ALTER TABLE vtiger_cron_task ADD COLUMN run_at_minutes INT DEFAULT NULL',
            'run_on_weekdays'     => 'ALTER TABLE vtiger_cron_task ADD COLUMN run_on_weekdays VARCHAR(20) DEFAULT NULL',
            'run_on_day'          => 'ALTER TABLE vtiger_cron_task ADD COLUMN run_on_day TINYINT DEFAULT NULL',
            // 実行ログの保持世代数
            'log_retention_count' => 'ALTER TABLE vtiger_cron_task ADD COLUMN log_retention_count INT DEFAULT NULL',
        );

        $added = array();
        foreach ($columns as $columnName => $sql) {
            if ($this->hasColumn($columnName)) {
                continue;
            }
            $db->query($sql);
            $added[] = $columnName;
        }

        if (count($added) > 0) {
            $this->log(sprintf('vtiger_cron_task に %d 件の列を追加しました（%s）',
                    count($added), implode(', ', $added)));
        } else {
            $this->log('vtiger_cron_task の列は既に揃っています。');
        }
    }

    /**
     * 標準タスクのタイムアウトを設定する。
     *
     * Vtiger_Cron::register() は retry_timeout を設定しないため、既存タスクは 0 のままに
     * なっている。0 のままだと「経過時間 > retry_timeout」が常に成立し、実行中のタスクを
     * 二重起動してしまう。既に値が入っているものは運用者の設定として尊重する。
     */
    protected function initializeRetryTimeouts() {
        $db = PearDatabase::getInstance();

        $updated = 0;
        foreach ($this->defaultRetryTimeouts as $taskName => $timeout) {
            $result = $db->pquery('UPDATE vtiger_cron_task SET retry_timeout = ? WHERE name = ? AND retry_timeout = 0',
                    array($timeout, $taskName));
            $updated += intval($db->getAffectedRowCount($result));
        }

        if ($updated > 0) {
            $this->log(sprintf('%d 件のタスクにタイムアウトの初期値を設定しました', $updated));
        }
    }

    /**
     * 次回実行予定時刻が未設定のタスクを、それぞれの周期に応じた次のグリッドへ揃える。
     * 既に予定が入っているものは動かさない。
     */
    protected function initializeNextRunAt() {
        $db = PearDatabase::getInstance();

        $result = $db->pquery('SELECT id, name, frequency FROM vtiger_cron_task
                WHERE next_run_at IS NULL OR next_run_at = 0', array());
        $updated = 0;
        while ($row = $db->fetch_array($result)) {
            $nextRunAt = Vtiger_Cron::computeNextRunAt($row['frequency']);
            $db->pquery('UPDATE vtiger_cron_task SET next_run_at = ? WHERE id = ?',
                    array($nextRunAt, $row['id']));
            $this->log(sprintf('  %s の次回実行予定を %s に設定しました',
                    $row['name'], date('Y-m-d H:i:s', $nextRunAt)));
            $updated++;
        }

        if ($updated > 0) {
            $this->log(sprintf('%d 件のタスクの次回実行予定を初期化しました', $updated));
        }
    }

    /**
     * 移行時点で実行中のまま残っているタスクのハートビートを消す。
     *
     * どのサーバーが担当していたか分からないため、0 にしておくことで
     * タイムアウト経過後にいずれかのサーバーが引き継げるようにする。
     */
    protected function releaseStaleHeartbeats() {
        $db = PearDatabase::getInstance();

        $result = $db->pquery('UPDATE vtiger_cron_task SET last_heartbeat = 0 WHERE status = ?',
                array(Vtiger_Cron::$STATUS_RUNNING));
        $affected = intval($db->getAffectedRowCount($result));
        if ($affected > 0) {
            $this->log(sprintf('実行中のまま残っていた %d 件のタスクを引き継ぎ可能にしました', $affected));
        }
    }

    /**
     * 実行タイミングの種別が空のタスクを、従来どおりの周期実行に揃える。
     */
    protected function initializeScheduleType() {
        $db = PearDatabase::getInstance();

        $result = $db->pquery("UPDATE vtiger_cron_task SET schedule_type = ?
                WHERE schedule_type IS NULL OR schedule_type = ''",
                array(Vtiger_Cron::SCHEDULE_INTERVAL));
        $affected = intval($db->getAffectedRowCount($result));
        if ($affected > 0) {
            $this->log(sprintf('%d 件のタスクを周期実行として初期化しました', $affected));
        }
    }

    /**
     * 列が存在するか。
     *
     * @param string $columnName
     * @return boolean
     */
    protected function hasColumn($columnName) {
        $db = PearDatabase::getInstance();
        $result = $db->pquery('SHOW COLUMNS FROM vtiger_cron_task LIKE ?', array($columnName));
        return ($result && $db->num_rows($result) > 0);
    }

    // -------------------------------------------------------- 設定ファイル

    /**
     * 指定したファイルへ、未記載の設定キーだけを追記する。
     *
     * config.template.php は新規インストール時のひな形なので、既存環境の config.inc.php は
     * 更新されない。設定項目にはコード側の既定値があるため追記しなくても動作は変わらないが、
     * config.inc.php に現れないと運用者が設定の存在に気付けないため、ひな形と同じ内容を
     * 書き込んで新規インストールと同じ状態に揃える。
     *
     * 判定は設定キーごとに行い、既に書かれているキーには一切手を触れない。運用者が値を
     * 変更済みの設定を上書きしたり、同じキーを二重に書いたりしないため。すべてのキーが
     * 既にある場合はファイルを開くだけで終了する。
     *
     * ファイル名を引数で受け取れるようにしてあるのはテストのため。
     *
     * @param string $filename
     * @return array 追記したキーの一覧
     */
    public function applyTo($filename) {
        if (!file_exists($filename)) {
            $this->log($filename . ' が見つかりません。スキップします。');
            return array();
        }

        $contents = file_get_contents($filename);
        if ($contents === false) {
            throw new Exception($filename . ' の読み込みに失敗しました。');
        }

        // 未記載のキーだけを集める
        $missing = array();
        $present = array();
        foreach ($this->getSettings() as $setting) {
            if ($this->isSettingPresent($contents, $setting['key'])) {
                $present[] = '$' . $setting['key'];
            } else {
                $missing[] = $setting;
            }
        }

        if (count($present) > 0) {
            $this->log(sprintf('既に記載されているため対象外: %s', implode(', ', $present)));
        }
        if (count($missing) === 0) {
            $this->log('追記する設定はありません。');
            return array();
        }

        $newContents = $this->insertBlock($contents, $this->buildBlock($missing));

        // 書き戻しに失敗した場合に備えて元の内容を残す
        $backup = $filename . self::BACKUP_SUFFIX;
        if (!file_exists($backup) && file_put_contents($backup, $contents) === false) {
            throw new Exception($filename . ' のバックアップ作成に失敗しました。');
        }

        if (file_put_contents($filename, $newContents) === false) {
            throw new Exception($filename . ' の書き込みに失敗しました。'
                    . 'config.template.php のスケジューラー設定を手動で追記してください。');
        }

        $added = array();
        foreach ($missing as $setting) {
            $added[] = $setting['key'];
        }
        $this->log(sprintf('%s に %d 件の設定を追記しました（%s）。バックアップ: %s',
                $filename, count($added), '$' . implode(', $', $added), $backup));

        return $added;
    }

    /**
     * その設定キーが既にファイルに書かれているか。
     *
     * コメントアウトされた記載（例: 「// $cron_host_name = 'app01';」）も「記載済み」と
     * みなす。値を有効にするかどうかは運用者の判断であり、同じ説明を二重に書かないため。
     *
     * @param string $contents
     * @param string $key 先頭の $ を含まない変数名
     * @return boolean
     */
    public function isSettingPresent($contents, $key) {
        return preg_match('/\$' . preg_quote($key, '/') . '\s*=/', $contents) === 1;
    }

    /**
     * 追記する内容を組み立てる。
     *
     * 見出し（section）は、その見出しに属する設定を実際に書くときだけ出す。
     *
     * @param array $settings
     * @return string
     */
    protected function buildBlock($settings) {
        $lines = array('');
        $writtenSections = array();

        foreach ($settings as $setting) {
            if (isset($setting['section']) && !in_array($setting['section_id'], $writtenSections, true)) {
                foreach ($setting['section'] as $sectionLine) {
                    $lines[] = $sectionLine;
                }
                $lines[] = '';
                $writtenSections[] = $setting['section_id'];
            }
            foreach ($setting['lines'] as $line) {
                $lines[] = $line;
            }
            $lines[] = '';
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * ひな形と同じ並びになる位置へ追記する。
     *
     * @param string $contents
     * @param string $block
     * @return string
     */
    protected function insertBlock($contents, $block) {
        if (strpos($contents, self::ANCHOR) !== false) {
            // config.security.php の読み込みより前に置く（ひな形と同じ並びにする）
            return str_replace(self::ANCHOR, $block . self::ANCHOR, $contents);
        }
        if (preg_match('/\?>\s*$/', $contents)) {
            // 閉じタグがある場合はその直前へ
            return preg_replace('/\?>\s*$/', $block . "?>\n", $contents);
        }
        return rtrim($contents, "\r\n") . "\n\n" . $block;
    }

    /**
     * config.template.php に追加したスケジューラー設定と同じ内容。
     *
     * key   : 判定に使う変数名（先頭の $ は含まない）
     * lines : 書き込む行（説明のコメントを含む）
     * section / section_id : このキーを書くときだけ前に出す見出し
     *
     * @return array
     */
    protected function getSettings() {
        $multiServerSection = array(
            '// --- アプリケーションサーバーが複数台ある構成向けの設定 ---------------------------',
            '// 同じデータベースを共有する複数台で cron を動かしても、タスクが二重に実行されることは無い。',
            '// 実行権の獲得と振り分けはデータベース側のロックで排他制御している。',
            '//',
            '// 【重要】各サーバーの時刻を NTP で同期しておくこと。',
            '// 時刻の判定自体はデータベースの時計に揃えているが、ログの時刻表示がずれて調査しにくくなる。',
        );

        return array(
            array(
                'key' => 'cron_max_parallel',
                'lines' => array(
                    '// スケジューラー（vtigercron.php）が同時に実行する cron タスクの最大数。',
                    '// 1 タスクにつき 1 プロセスが起動するため、DB の最大接続数やサーバの負荷に合わせて調整する。',
                    '// 1 を指定すると並列実行を行わず、従来通り 1 プロセスで順番に実行する。',
                    '$cron_max_parallel = 4;',
                ),
            ),
            array(
                'key' => 'cron_serial_tasks',
                'lines' => array(
                    '// 互いに同時実行させたくない cron タスク名。ここに挙げたタスクは同時に 1 つまでしか実行しない。',
                    '// 指定していないタスクとは並列に実行されるため、他タスクの実行を止めることはない。',
                    "// 例: \$cron_serial_tasks = array('Workflow', 'RecurringInvoice');",
                    '$cron_serial_tasks = array();',
                ),
            ),
            array(
                'key' => 'cron_default_retry_timeout',
                'lines' => array(
                    '// retry_timeout が設定されていない cron タスクに適用するタイムアウト秒数。',
                    '// 実行中のまま停止したタスクを、この秒数を過ぎたら再実行できるものとして扱う。',
                    '$cron_default_retry_timeout = 3600;',
                ),
            ),
            array(
                'key' => 'cron_kill_timed_out',
                'lines' => array(
                    '// retry_timeout を過ぎても終わらない cron タスクのプロセスを強制終了するか。',
                    '// true にすると強制終了したうえでタスクを解放し、次回以降の実行を自動的に再開する。',
                    '// false にすると警告をログに出すだけで再実行もしない（原因調査を優先したい場合に使う）。',
                    '$cron_kill_timed_out = true;',
                ),
            ),
            array(
                'key' => 'cron_log_retention_count',
                'lines' => array(
                    '// 実行ログ（logs/cron/<タスク名>_<日付>.log）をタスクごとに残す世代数（ファイル数）。',
                    '// 新しいものをこの数だけ残し、それより古いファイルは自動で削除する。',
                    '// 0 を指定すると削除しない（無期限に残す）。',
                    '// タスクごとの指定はスケジューラー画面（システム設定）から行える。ここはその既定値。',
                    '$cron_log_retention_count = 30;',
                ),
            ),
            array(
                'key' => 'cron_heartbeat_timeout',
                'section_id' => 'multi_server',
                'section' => $multiServerSection,
                'lines' => array(
                    '// 担当サーバーが落ちたとみなすまでの秒数。',
                    '// 担当サーバーは自分が起動した子プロセスの生存を確認するたびに記録を更新する。',
                    '// この秒数を超えて更新が止まったら、他のサーバーがタスクを引き継ぐ。',
                    '// cron の起動間隔（1分）の数回分を見込んだ値にすること。短すぎると、',
                    '// 一時的に負荷が高いだけのサーバーからタスクを奪ってしまう。',
                    '$cron_heartbeat_timeout = 300;',
                ),
            ),
            array(
                'key' => 'cron_host_name',
                'section_id' => 'multi_server',
                'section' => $multiServerSection,
                'lines' => array(
                    '// このサーバーを識別する名前。未設定ならホスト名を使う。',
                    '// コンテナ等でホスト名が毎回変わる環境では、固定の名前を明示的に指定すること。',
                    "// \$cron_host_name = 'app01';",
                ),
            ),
        );
    }
}
