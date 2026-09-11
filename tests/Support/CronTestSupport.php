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

namespace Tests\Support;

use PearDatabase;
use ReflectionProperty;
use Vtiger_Cron;

/**
 * cron スケジューラー（#1823）のテストで使う共通処理。
 *
 * これらのテストは実際に vtiger_cron_task を読み書きし、プロセスも起動するため
 * tests/Integration/ に置く。テスト用タスク（FRTestCron_ で始まる名前）だけを
 * 登録・操作し、終了時に必ず削除する。既存の cron タスクの状態は変更しない。
 */
trait CronTestSupport
{
    /** テスト用タスク名の接頭辞。後始末の対象を識別する */
    public const PREFIX = 'FRTestCron_';

    /** 他サーバーを装うためのホスト名 */
    public const OTHER_HOST = 'fr-test-other-host';

    private ?PearDatabase $cronDb = null;

    /** @var array<int, resource> proc_open で起動した擬似プロセス */
    private array $cronProcesses = [];

    /**
     * vtiger 側のクラスを読み込む。
     *
     * tests/bootstrap.php が Vtiger_Loader のオートロードを外しているため明示的に読む。
     */
    public static function loadCronClasses(): void
    {
        $root = dirname(__DIR__, 2);
        require_once $root . '/include/utils/CommonUtils.php';
        require_once $root . '/vtlib/Vtiger/Cron.php';
        require_once $root . '/include/utils/CronDispatcher.php';
    }

    /**
     * 管理画面（Settings > スケジューラ）のモデルを読めるようにする。
     *
     * tests/bootstrap.php は PHPUnit のクラス探索で Vtiger 側の require が走らないよう
     * Vtiger_Loader のオートロードを外している。管理画面のモデルは継承チェーンが深く
     * 個別に require すると本体の構成に追従できないため、このテストの間だけ戻す。
     * PHPUnit のクラス探索は実行前に終わっているので影響しない。
     */
    protected static function enableVtigerAutoload(): void
    {
        $root = dirname(__DIR__, 2);
        require_once $root . '/includes/Loader.php';

        // includes/runtime/LanguageHandler.php はクラス宣言を条件で囲っていないため、
        // tests/Support/ のスタブが先に読み込まれているプロセスでは二重宣言で落ちる。
        // スタブも言語ファイルを実際に読むので、既に用意されていればそれを使う。
        if (!class_exists('Vtiger_Language_Handler', false)) {
            vimport('includes.runtime.EntryPoint');
        }

        // EntryPoint を読めなかった場合に足りないグローバル関数を補う
        require_once __DIR__ . '/CronGlobalFunctions.php';

        spl_autoload_register(['Vtiger_Loader', 'autoLoad']);
    }

    protected static function disableVtigerAutoload(): void
    {
        spl_autoload_unregister(['Vtiger_Loader', 'autoLoad']);
    }

    /**
     * 本体側（includes/ ・ include/）が出す既存の警告だけを黙らせる。
     *
     * 管理画面のモデルは vtiger の言語処理・日時処理を通るが、そこには未設定の配列キーを
     * 読む・変数名の綴り誤り（LanguageHandler.php の $moudle）といった既存の問題があり、
     * phpunit.xml の failOnWarning でテストが落ちてしまう。これらはコア相当のファイルで
     * このテストの対象ではないため、発生元のパスで判定して除外する。
     *
     * テスト対象（modules/Settings/CronTasks/ など）の警告はそのまま PHPUnit へ渡すので、
     * 自分たちのコードの問題は見落とさない。
     */
    protected static function suppressCoreWarnings(): void
    {
        set_error_handler(static function (int $number, string $message, string $file = ''): bool {
            foreach (['/includes/', '/include/', '/libraries/', '/vtlib/'] as $coreDirectory) {
                if (strpos($file, $coreDirectory) !== false) {
                    return true; // 処理済みとして PHPUnit へ渡さない
                }
            }

            return false; // 通常のエラーハンドラへ委譲する
        }, E_WARNING | E_NOTICE | E_DEPRECATED);
    }

    protected static function restoreCoreWarnings(): void
    {
        restore_error_handler();
    }

    /**
     * 言語ファイルの読み込みキャッシュを先に温める。
     *
     * includes/runtime/LanguageHandler.php は self::$fileExists[$file] を
     * 未設定のまま読むため、言語ファイルごとに初回だけ「Undefined array key」の
     * 警告が出る（本体側の既存の問題）。phpunit.xml は failOnWarning を有効にしており
     * テストが落ちてしまうため、初回の読み込みだけここで済ませる。
     *
     * @param array<int, string> $modules
     */
    protected static function primeLanguageCache(array $modules = ['Settings:CronTasks', 'Vtiger']): void
    {
        if (!function_exists('vtranslate')) {
            return;
        }

        set_error_handler(static fn (): bool => true, E_WARNING | E_NOTICE | E_DEPRECATED);
        try {
            foreach ($modules as $module) {
                // 戻り値は使わない。言語ファイルを読ませること（＝キャッシュの生成）が目的
                $warmed = vtranslate('LBL_STATUS', $module);
                unset($warmed);
            }
        } finally {
            restore_error_handler();
        }
    }

    protected function cronDb(): PearDatabase
    {
        $db = $this->cronDb;
        if (!$db instanceof PearDatabase) {
            $db = PearDatabase::getInstance();
            $this->cronDb = $db;
        }

        return $db;
    }

    /**
     * テスト用DB に接続できない場合はテストを飛ばす。
     *
     * Integration テストは DB が無いと成立しない。CI では DB を用意していないため、
     * 失敗ではなくスキップとして扱う。
     */
    protected function requireCronDatabase(): void
    {
        try {
            $result = $this->cronDb()->pquery('SELECT 1 AS ok FROM vtiger_cron_task LIMIT 1', []);
        } catch (\Throwable $e) {
            $this->markTestSkipped('テスト用DBに接続できないため実行しない: ' . $e->getMessage());
        }
        if ($result === false) {
            $this->markTestSkipped('テスト用DBに vtiger_cron_task がないため実行しない');
        }
    }

    /**
     * 日時の表示（書式・タイムゾーン）はログイン中のユーザーに依存するため用意する。
     *
     * 管理ユーザーの読み込みは vtiger 側（log4php / VTCacheUtils）が未定義の配列キーや
     * 変数を触るため PHP の警告が出る。phpunit.xml は failOnWarning を有効にしており、
     * 本体側の既存の警告でテストが落ちてしまうため、この呼び出しの間だけ黙らせる。
     * 抑止はこの 1 か所に閉じてあり、テスト対象（cron スケジューラー）側の警告は拾う。
     */
    protected function prepareCronCurrentUser(): void
    {
        if (!empty($GLOBALS['current_user']) || !class_exists('Users')) {
            return;
        }

        set_error_handler(static fn (): bool => true, E_WARNING | E_NOTICE | E_DEPRECATED);
        try {
            $adminUser = \Users::getActiveAdminUser();
        } finally {
            restore_error_handler();
        }

        // vglobal() も結局 $GLOBALS へ入れるだけなので、ここでは直接入れる
        $GLOBALS['current_user'] = $adminUser;
    }

    /** 起動した擬似プロセスとテスト用タスクを片付ける */
    protected function cleanUpCron(): void
    {
        foreach ($this->cronProcesses as $process) {
            if (is_resource($process)) {
                @proc_terminate($process, 9);
                @proc_close($process);
            }
        }
        $this->cronProcesses = [];

        $this->cleanUpCronTasks();
    }

    protected function cleanUpCronTasks(): void
    {
        $db = $this->cronDb();
        $result = $db->pquery('SELECT name FROM vtiger_cron_task WHERE name LIKE ?', [self::PREFIX . '%']);
        if ($result === false) {
            return;
        }
        while ($row = $db->fetch_array($result)) {
            Vtiger_Cron::deregister($row['name']);
        }
    }

    /** テスト用タスクを登録して名前を返す */
    protected function makeTask(string $suffix, string $handler, int $frequency = 900, int $status = 1): string
    {
        $name = self::PREFIX . $suffix;
        Vtiger_Cron::deregister($name);
        Vtiger_Cron::register($name, $handler, $frequency, 'Home', $status, 0, 'FRTest task');

        return $name;
    }

    /**
     * DB の内容から Vtiger_Cron を読み直す。
     *
     * Vtiger_Cron はコンストラクタで自分をプロセス内キャッシュへ登録し、getInstance() は
     * キャッシュがあれば DB を読まない。テストでは列を直接書き換えて状態を作るため、
     * キャッシュを毎回捨てて確実に DB から読み直す。
     */
    protected function reload(string $name): Vtiger_Cron
    {
        $this->clearCronInstanceCache();

        return Vtiger_Cron::getInstance($name);
    }

    /** Vtiger_Cron のプロセス内キャッシュを捨てる（新しいリクエストを模す） */
    protected function clearCronInstanceCache(): void
    {
        $property = new ReflectionProperty(Vtiger_Cron::class, 'instanceCache');
        $property->setValue(null, []);
    }

    /**
     * 列を直接書き換えて任意の状態を作る。
     *
     * @param array<string, mixed> $columns
     */
    protected function setCols(string $name, array $columns): void
    {
        $assignments = [];
        $params = [];
        foreach ($columns as $column => $value) {
            $assignments[] = $column . ' = ?';
            $params[] = $value;
        }
        $params[] = $name;
        $this->cronDb()->pquery(
            'UPDATE vtiger_cron_task SET ' . implode(', ', $assignments) . ' WHERE name = ?',
            $params
        );
    }

    /** @return mixed */
    protected function getCol(string $name, string $column)
    {
        $db = $this->cronDb();
        $result = $db->pquery('SELECT ' . $column . ' AS value FROM vtiger_cron_task WHERE name = ?', [$name]);

        return $db->query_result($result, 0, 'value');
    }

    /** 列の値を文字列で取り出す */
    protected function getColString(string $name, string $column): string
    {
        $value = $this->getCol($name, $column);

        return is_scalar($value) ? (string) $value : '';
    }

    /** 列の値を整数で取り出す */
    protected function getColInt(string $name, string $column): int
    {
        $value = $this->getCol($name, $column);

        return is_scalar($value) ? (int) $value : 0;
    }

    /** データベースの現在時刻（判定と同じ基準を使う） */
    protected function dbNow(): int
    {
        $db = $this->cronDb();
        $result = $db->pquery('SELECT UNIX_TIMESTAMP() AS now', []);
        $now = $db->query_result($result, 0, 'now');

        return is_scalar($now) ? (int) $now : 0;
    }

    /**
     * このタスクの子プロセスに見えるプロセスを起動する。
     *
     * 実際に cron タスクを動かすと副作用が出るため、コマンドラインだけを
     * 子プロセスと同じ形（vtigercron.php ... --service=<名前>）にした sleep を使う。
     */
    protected function startFakeTaskProcess(string $taskName, int $seconds = 120): int
    {
        $command = sprintf(
            'exec %s -d xdebug.mode=off -r %s vtigercron.php --service=%s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg('sleep(' . $seconds . ');'),
            escapeshellarg($taskName)
        );
        $pipes = [];
        $process = @proc_open($command, [], $pipes);
        if (!is_resource($process)) {
            return 0;
        }
        $this->cronProcesses[] = $process;
        $status = proc_get_status($process);
        // /proc に現れるまで少し待つ
        for ($i = 0; $i < 50 && !file_exists('/proc/' . $status['pid'] . '/cmdline'); $i++) {
            usleep(20000);
        }

        return (int) $status['pid'];
    }

    /** 無関係なプロセスを起動して PID を返す（PID 再利用の検証用） */
    protected function startUnrelatedProcess(int $seconds = 120): int
    {
        $command = sprintf(
            'exec %s -d xdebug.mode=off -r %s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg('sleep(' . $seconds . ');')
        );
        $pipes = [];
        $process = @proc_open($command, [], $pipes);
        if (!is_resource($process)) {
            return 0;
        }
        $this->cronProcesses[] = $process;

        return (int) proc_get_status($process)['pid'];
    }

    /**
     * プロセスが生きているか。
     *
     * SIGTERM で終了させても、親（このテストプロセス）が回収するまではゾンビとして
     * /proc に残る。/proc の有無だけでは終了を判定できないため状態まで見る。
     */
    protected function isPidAlive(int $pid): bool
    {
        if ($pid <= 0) {
            return false;
        }
        $stat = @file_get_contents('/proc/' . $pid . '/stat');
        if ($stat === false) {
            return false;
        }
        // "<pid> (<comm>) <state> ..." の state。Z はゾンビ（既に終了している）
        if (preg_match('/\)\s+(\S)/', $stat, $matches)) {
            return $matches[1] !== 'Z';
        }

        return true;
    }

    /** プロセスが終了するまで待つ */
    protected function waitForPidToExit(int $pid, int $attempts = 100): void
    {
        for ($i = 0; $i < $attempts && $this->isPidAlive($pid); $i++) {
            usleep(50000);
        }
    }

    /**
     * vtigercron.php を CLI で実行して出力と終了コードを返す。
     *
     * vtigercron.php を直接起動すると開発用DB へ接続してしまうため、
     * テスト用DB を指す入口（run_vtigercron.php）を経由する。
     *
     * @return array{output: string, status: int}
     */
    protected function runCli(string $arguments): array
    {
        $command = sprintf(
            '%s -d xdebug.mode=off -f tests/Support/cron/run_vtigercron.php -- %s 2>&1',
            escapeshellarg(PHP_BINARY),
            $arguments
        );
        $output = [];
        $status = 0;
        exec($command, $output, $status);

        return ['output' => implode("\n", $output), 'status' => (int) $status];
    }

    /**
     * スタブのハンドラ（.service）の、プロジェクトルートからの相対パス。
     *
     * 実物の cron ハンドラの代わりにテスト対象へ実行させる小さなスクリプト。
     */
    protected function stubHandler(string $fileName): string
    {
        return 'tests/Support/cron/' . $fileName;
    }
}
