<?php

declare(strict_types=1);
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.1
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  F-RevoCRM Open Source
 * The Initial Developer of the Original Code is F-RevoCRM.
 * Portions created by thinkingreed are Copyright (C) F-RevoCRM.
 * All Rights Reserved.
 ************************************************************************************/

namespace Tests\Integration\Cron;

use FR_CronDispatcher;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Settings_CronTasks_Record_Model;
use Tests\Support\CronTestSupport;
use Vtiger_Cron;

require_once dirname(__DIR__, 2) . '/Support/CronTestSupport.php';

$cronTestRoot = dirname(__DIR__, 3);

require_once $cronTestRoot . '/include/database/PearDatabase.php';
require_once $cronTestRoot . '/include/utils/CommonUtils.php';
require_once $cronTestRoot . '/vtlib/Vtiger/Cron.php';
require_once $cronTestRoot . '/include/utils/CronDispatcher.php';

/**
 * M. 補助関数・設定・整合性（DB / 管理画面クラスを使う範囲）— #1823
 *
 *   M3  getLogFile がログディレクトリを作る（副作用）
 *   M4  getEffectiveRetryTimeout（タスク値 > 既定値。0 は未設定扱い）
 *   M6  countRunning（名前の絞り込み・ハートビート途絶は数えない）
 *   M7  acquireLock / releaseLock（取得・解放・再取得）
 *   M8  recordChildPid（担当・PID・ハートビートの記録）（副作用）
 *   M9  hasStaleHeartbeat / hasExceededRetryTimeout / isOwnedByThisHost の境界
 *   M10 describe が返す配列の形
 *   M13 担当サーバー名のサニタイズと REMOTE / STALE の表示
 *   M15 一覧に出す列・編集対象の項目が DB に存在する
 *   M16 編集画面の選択肢（時・分・曜日・日）
 *   M17 実行ログ画面が不正なレコードを拒否する（異常系）
 *   M18 ログ保持世代数の表示（既定・指定・無期限）
 */
final class DispatcherHelpersTest extends TestCase
{
    use CronTestSupport;

    private string $host = '';

    public static function setUpBeforeClass(): void
    {
        self::enableVtigerAutoload();
        self::primeLanguageCache();
    }

    public static function tearDownAfterClass(): void
    {
        self::disableVtigerAutoload();
    }

    protected function setUp(): void
    {
        $this->requireCronDatabase();
        $this->prepareCronCurrentUser();
        $this->cleanUpCronTasks();
        self::suppressCoreWarnings();
        $this->host = FR_CronDispatcher::getHostName();
    }

    protected function tearDown(): void
    {
        self::restoreCoreWarnings();
        $this->cleanUpCron();
    }

    /**
     * protected メソッドを呼ぶ
     *
     * @param array<int, mixed> $arguments
     * @return mixed
     */
    private function invokeProtected(object $object, string $method, array $arguments = [])
    {
        $target = (new ReflectionClass($object))->getMethod($method);

        return $target->invokeArgs($object, $arguments);
    }

    /**
     * protected メソッドを呼び、配列として受け取る
     *
     * @param array<int, mixed> $arguments
     * @return array<int|string, mixed>
     */
    private function invokeProtectedArray(object $object, string $method, array $arguments = []): array
    {
        $result = $this->invokeProtected($object, $method, $arguments);

        return is_array($result) ? $result : [];
    }

    /**
     * protected メソッドを呼び、文字列として受け取る
     *
     * @param array<int, mixed> $arguments
     */
    private function invokeProtectedString(object $object, string $method, array $arguments = []): string
    {
        $result = $this->invokeProtected($object, $method, $arguments);

        return is_scalar($result) ? (string) $result : '';
    }

    /**
     * 選択肢の配列から value を取り出す
     *
     * @param array<int|string, mixed> $choices
     * @return mixed
     */
    private function choiceValue(array $choices, int $index)
    {
        $choice = $choices[$index] ?? null;

        return is_array($choice) ? ($choice['value'] ?? null) : null;
    }

    public function test_M3_getLogFile_がログディレクトリを用意する(): void
    {
        $name = $this->makeTask('LogDir', $this->fixtureHandler('noop1.service'));
        $directory = FR_CronDispatcher::getLogDirectory();

        // 空であれば「無い状態から作られる」ことまで確認できる
        $movedAside = false;
        if (is_dir($directory) && count(glob($directory . DIRECTORY_SEPARATOR . '*') ?: []) === 0) {
            @rmdir($directory);
            $movedAside = true;
        }

        $logFile = FR_CronDispatcher::getLogFile($this->reload($name));

        self::assertDirectoryExists($directory, 'M3 getLogFile がログディレクトリを用意する');
        self::assertSame($directory, dirname($logFile), 'M3 返すパスはディレクトリ配下のファイル');
        if ($movedAside) {
            self::assertDirectoryExists($directory, 'M3 無い状態から作られる');
        }
    }

    public function test_M4_実効的なタイムアウト(): void
    {
        $name = $this->makeTask('Timeout', $this->fixtureHandler('noop2.service'));

        $this->setCols($name, ['retry_timeout' => 120]);
        self::assertSame(120, FR_CronDispatcher::getEffectiveRetryTimeout($this->reload($name)), 'M4 タスクに値があればそれを使う');

        $this->setCols($name, ['retry_timeout' => 0]);
        self::assertSame(
            FR_CronDispatcher::getDefaultRetryTimeout(),
            FR_CronDispatcher::getEffectiveRetryTimeout($this->reload($name)),
            'M4 タスクが 0 なら既定値を使う'
        );
    }

    public function test_M9_タイムアウト超過の境界(): void
    {
        $name = $this->makeTask('Timeout', $this->fixtureHandler('noop2.service'));
        $now = $this->dbNow();

        $this->setCols($name, [
            'retry_timeout'  => 100,
            'laststart'      => $now - 100,
            'lastend'        => 0,
            'last_heartbeat' => 0,
            'owner_host'     => $this->host,
        ]);
        self::assertFalse(
            FR_CronDispatcher::hasExceededRetryTimeout($this->reload($name)),
            'M9 境界: 経過がちょうどタイムアウトなら超過ではない'
        );

        $this->setCols($name, ['laststart' => $now - 101]);
        self::assertTrue(FR_CronDispatcher::hasExceededRetryTimeout($this->reload($name)), 'M9 タイムアウトを 1 秒超えたら超過');

        $this->setCols($name, ['laststart' => 0]);
        self::assertFalse(FR_CronDispatcher::hasExceededRetryTimeout($this->reload($name)), 'M9 未実行なら超過とみなさない');
    }

    public function test_M9_ハートビート途絶の境界(): void
    {
        $name = $this->makeTask('Timeout', $this->fixtureHandler('noop2.service'));
        $now = $this->dbNow();
        $heartbeatTimeout = FR_CronDispatcher::getHeartbeatTimeout();

        $this->setCols($name, ['last_heartbeat' => $now - $heartbeatTimeout]);
        self::assertFalse(FR_CronDispatcher::hasStaleHeartbeat($this->reload($name)), 'M9 境界: ちょうど猶予なら途絶とみなさない');

        $this->setCols($name, ['last_heartbeat' => $now - ($heartbeatTimeout + 1)]);
        self::assertTrue(FR_CronDispatcher::hasStaleHeartbeat($this->reload($name)), 'M9 猶予を 1 秒超えたら途絶');
    }

    public function test_M9_担当ホストの判定(): void
    {
        $name = $this->makeTask('Timeout', $this->fixtureHandler('noop2.service'));

        $this->setCols($name, ['owner_host' => $this->host]);
        self::assertTrue(FR_CronDispatcher::isOwnedByThisHost($this->reload($name)), 'M9 担当が自ホストなら true');

        $this->setCols($name, ['owner_host' => self::OTHER_HOST]);
        self::assertFalse(FR_CronDispatcher::isOwnedByThisHost($this->reload($name)), 'M9 担当が他ホストなら false');

        $this->setCols($name, ['owner_host' => '']);
        self::assertFalse(FR_CronDispatcher::isOwnedByThisHost($this->reload($name)), 'M9 担当が空なら false');
    }

    public function test_M8_子プロセスの_PID_を記録する(): void
    {
        $name = $this->makeTask('Timeout', $this->fixtureHandler('noop2.service'));
        $this->setCols($name, ['owner_host' => '', 'owner_pid' => 0, 'last_heartbeat' => 0]);

        FR_CronDispatcher::recordChildPid($this->reload($name));

        self::assertSame($this->host, $this->getColString($name, 'owner_host'), 'M8 担当ホストを記録する');
        self::assertSame((string) getmypid(), $this->getColString($name, 'owner_pid'), 'M8 自分の PID を記録する');
        self::assertGreaterThan(0, $this->getColInt($name, 'last_heartbeat'), 'M8 ハートビートを記録する');
    }

    public function test_M6_実行中の件数(): void
    {
        $name = $this->makeTask('Count', $this->fixtureHandler('noop3.service'));
        $now = $this->dbNow();
        $this->setCols($name, [
            'status'         => Vtiger_Cron::$STATUS_RUNNING,
            'owner_host'     => $this->host,
            'retry_timeout'  => 3600,
            'laststart'      => $now - 10,
            'lastend'        => 0,
            'last_heartbeat' => $now,
        ]);

        self::assertSame(1, FR_CronDispatcher::countRunning([$name]), 'M6 名前で絞り込んで数えられる');
        self::assertSame(0, FR_CronDispatcher::countRunning(['FRTestCron_NoSuchTask']), 'M6 該当しない名前なら 0');

        $this->setCols($name, ['last_heartbeat' => $now - (FR_CronDispatcher::getHeartbeatTimeout() + 10)]);
        self::assertSame(0, FR_CronDispatcher::countRunning([$name]), 'M6 ハートビートが途絶えたものは数えない');

        $this->setCols($name, ['status' => Vtiger_Cron::$STATUS_ENABLED]);
    }

    public function test_M7_名前付きロック(): void
    {
        $dispatcher = new FR_CronDispatcher();

        self::assertTrue($dispatcher->acquireLock(), 'M7 ロックを取得できる');
        $dispatcher->releaseLock();
        self::assertTrue($dispatcher->acquireLock(), 'M7 解放後に再取得できる');
        $dispatcher->releaseLock();
        self::assertNull($dispatcher->releaseLock(), 'M7 解放を 2 回呼んでも壊れない');
    }

    public function test_M10_describe_が返す配列の形(): void
    {
        $name = $this->makeTask('Describe', $this->fixtureHandler('noop4.service'));

        $rows = FR_CronDispatcher::describe([$this->reload($name)]);

        self::assertCount(1, $rows, 'M10 タスク 1 件につき 1 行返す');
        $expectedKeys = ['name', 'state', 'laststart', 'nextrunat', 'elapsed', 'host', 'pid', 'timeout', 'frequency'];
        self::assertSame([], array_diff($expectedKeys, array_keys($rows[0])), 'M10 返す配列のキーが揃っている');
        self::assertSame($name, $rows[0]['name'], 'M10 タスク名が入る');
        self::assertSame('IDLE', $rows[0]['state'], 'M10 実行中でなければ IDLE');
    }

    public function test_M13_担当サーバー名のサニタイズと表示(): void
    {
        $name = $this->makeTask('Display', $this->fixtureHandler('noop5.service'));
        $id = $this->getColInt($name, 'id');
        $now = $this->dbNow();

        $this->setCols($name, [
            'status'         => Vtiger_Cron::$STATUS_RUNNING,
            'owner_host'     => 'app01<script>x</script>',
            'owner_pid'      => 12345,
            'retry_timeout'  => 3600,
            'laststart'      => $now - 10,
            'lastend'        => 0,
            'last_heartbeat' => $now,
        ]);
        $this->clearCronInstanceCache();
        $record = Settings_CronTasks_Record_Model::getInstanceById($id, 'Settings:CronTasks');

        self::assertSame('REMOTE', $record->getRuntimeState(), 'M13 実行状態は REMOTE');
        $display = $record->getDisplayValue('status');
        self::assertStringContainsString('app01', $display, 'M13 サーバー名を表示に添える');
        self::assertStringNotContainsString('<script>', $display, 'M13 記号は取り除く（HTML を混ぜない）');

        // サニタイズ自体を固定する。表示側の strip_tags でも HTML は消えるが、
        // タグ以外の記号（空白・記号）はここで落とす必要がある。
        // なお読み出し時に & や " は HTML 実体参照へ変換されるため、
        // ここでは変換の影響を受けない文字だけで確かめる。
        $this->setCols($name, ['owner_host' => 'app01; rm -rf /x']);
        $this->clearCronInstanceCache();
        $dirty = Settings_CronTasks_Record_Model::getInstanceById($id, 'Settings:CronTasks');
        self::assertSame('app01rm-rfx', $this->invokeProtectedString($dirty, 'getSafeOwnerHost'), 'M13 ホスト名から記号と空白を落とす');

        $this->setCols($name, ['owner_host' => 'app-01.example.com']);
        $this->clearCronInstanceCache();
        $clean = Settings_CronTasks_Record_Model::getInstanceById($id, 'Settings:CronTasks');
        self::assertSame('app-01.example.com', $this->invokeProtectedString($clean, 'getSafeOwnerHost'), 'M13 通常のホスト名はそのまま通す');

        // ハートビート途絶
        $this->setCols($name, [
            'owner_host'     => self::OTHER_HOST,
            'last_heartbeat' => $now - (FR_CronDispatcher::getHeartbeatTimeout() + 10),
        ]);
        // getRuntimeState() は Vtiger_Cron::getInstance() を通るためプロセス内キャッシュが効く。
        // 実際の画面は 1 リクエストにつき 1 回しか読まないので、新しいリクエストを模して捨てる。
        $this->clearCronInstanceCache();
        $stale = Settings_CronTasks_Record_Model::getInstanceById($id, 'Settings:CronTasks');

        self::assertSame('STALE', $stale->getRuntimeState(), 'M13 ハートビート途絶なら STALE');
        self::assertStringContainsString('label-warning', $stale->getDisplayValue('status'), 'M13 STALE は注意として強調する');
    }

    public function test_M18_ログ保持世代数の表示(): void
    {
        $name = $this->makeTask('Display', $this->fixtureHandler('noop5.service'));
        $id = $this->getColInt($name, 'id');

        $this->setCols($name, ['log_retention_count' => null]);
        $this->clearCronInstanceCache();
        $record = Settings_CronTasks_Record_Model::getInstanceById($id, 'Settings:CronTasks');
        self::assertStringStartsWith(
            (string) FR_CronDispatcher::getLogRetentionCount(),
            $record->getDisplayValue('log_retention_count'),
            'M18 未設定なら既定値と注記を出す'
        );

        $this->setCols($name, ['log_retention_count' => 5]);
        $this->clearCronInstanceCache();
        $record = Settings_CronTasks_Record_Model::getInstanceById($id, 'Settings:CronTasks');
        self::assertSame('5', $record->getDisplayValue('log_retention_count'), 'M18 指定した世代数をそのまま出す');

        $this->setCols($name, ['log_retention_count' => 0]);
        $this->clearCronInstanceCache();
        $record = Settings_CronTasks_Record_Model::getInstanceById($id, 'Settings:CronTasks');
        self::assertSame(
            vtranslate('LBL_LOG_RETENTION_UNLIMITED', 'Settings:CronTasks'),
            $record->getDisplayValue('log_retention_count'),
            'M18 0 は無期限と出す'
        );
    }

    public function test_M15_一覧の列と編集対象の項目が_DB_に存在する(): void
    {
        $moduleModel = \Settings_CronTasks_Module_Model::getInstance('Settings:CronTasks');
        self::assertInstanceOf(\Settings_CronTasks_Module_Model::class, $moduleModel, 'M15 モジュールモデルを取得できる');

        // PearDatabase は連想キーを小文字にして返す
        $db = $this->cronDb();
        $columns = [];
        $result = $db->pquery('SHOW COLUMNS FROM vtiger_cron_task', []);
        while ($row = $db->fetch_array($result)) {
            $columns[] = $row['field'];
        }

        self::assertSame(
            [],
            array_diff(array_keys($moduleModel->listFields), $columns),
            'M15 一覧の列がすべて DB に存在する'
        );
        self::assertSame(
            [],
            array_diff($moduleModel->getEditableFieldsList(), $columns),
            'M15 編集対象の項目がすべて DB に存在する'
        );
    }

    public function test_M16_編集画面の選択肢(): void
    {
        $view = new \Settings_CronTasks_EditAjax_View();

        $hours = $this->invokeProtectedArray($view, 'getHourChoices');
        self::assertCount(24, $hours, 'M16 時の候補は 24 個');
        self::assertSame(['00', '23'], [$hours[0] ?? null, $hours[23] ?? null], 'M16 時は 2 桁で並ぶ');

        $weekdays = $this->invokeProtectedArray($view, 'getWeekdayChoices');
        self::assertCount(7, $weekdays, 'M16 曜日の候補は 7 個');
        self::assertSame(0, $this->choiceValue($weekdays, 0), 'M16 曜日は日曜（0）から始まる');

        $days = $this->invokeProtectedArray($view, 'getDayChoices');
        self::assertCount(32, $days, 'M16 日の候補は月末 + 31 個');
        self::assertSame(0, $this->choiceValue($days, 0), 'M16 先頭は月末（0）');

        // 分は 5 分刻み。5 分刻みでない値が設定されている場合はその値も候補に含める
        $name = $this->makeTask('Choices', $this->fixtureHandler('noop1.service'));
        $id = $this->getColInt($name, 'id');

        $this->setCols($name, ['schedule_type' => Vtiger_Cron::SCHEDULE_DAILY, 'run_at_minutes' => 3 * 60]);
        $this->clearCronInstanceCache();
        $record = Settings_CronTasks_Record_Model::getInstanceById($id, 'Settings:CronTasks');
        self::assertCount(12, $this->invokeProtectedArray($view, 'getMinuteChoices', [$record]), 'M16 分の候補は 5 分刻みで 12 個');

        $this->setCols($name, ['run_at_minutes' => 3 * 60 + 7]);
        $this->clearCronInstanceCache();
        $record = Settings_CronTasks_Record_Model::getInstanceById($id, 'Settings:CronTasks');
        $minutes = $this->invokeProtectedArray($view, 'getMinuteChoices', [$record]);
        self::assertCount(13, $minutes, 'M16 5 分刻みでない現在値を候補に足す');
        self::assertContains('07', $minutes, 'M16 現在値（07）が候補に含まれる');
    }

    public function test_M17_実行ログ画面が不正なレコードを拒否する(): void
    {
        // 存在しないレコードでは Record_Model が false を返し、画面は例外にする
        self::assertFalse(
            Settings_CronTasks_Record_Model::getInstanceById(0, 'Settings:CronTasks'),
            'M17 存在しないレコードはモデルを取得できない'
        );
        // 継承チェーンを実際にたどって確かめる（リテラル同士の is_subclass_of は静的に畳まれる）
        self::assertContains(
            'Settings_Vtiger_Index_View',
            array_keys(class_parents(\Settings_CronTasks_LogAjax_View::class) ?: []),
            'M17 実行ログ画面は管理者向けの基底クラスを継承する'
        );
        self::assertGreaterThan(0, \Settings_CronTasks_LogAjax_View::DISPLAY_LINES, 'M17 表示行数に上限がある');
    }
}
