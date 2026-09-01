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
use Tests\Support\CronTestSupport;

require_once dirname(__DIR__, 2) . '/Support/CronTestSupport.php';

/**
 * K. 実行ログの閲覧とローテーション — #1823
 *
 * 対象: FR_CronDispatcher の getLatestLogFile() / findLogFiles() / tailLogFile()
 *       / getLogFileName() / pruneLogFiles() / pruneLogFilesOncePerDay()
 *
 *   K1  ログが無い状態を安全に扱う
 *   K2  最新の日付のログが選ばれる／一覧は新しい順
 *   K3  指定した行数だけ末尾から取り出す（境界含む）
 *   K4  バイト上限で切り詰める。ファイル全体のサイズは元のまま報告する
 *   K5  タスク名の記号はファイル名に持ち込まない
 *   K6  保持世代数を超えた古いログを削除する（既定 30）
 *   K7  保持世代数 0 は削除しない
 *   K8  ログの命名に合わないファイルには手を出さない
 *   K9  1 日 1 回だけ走る
 *   K10 タスクごとの指定が既定値より優先される
 *   K11 対応するタスクが無いログは既定値で扱う
 *   K12 タスクごとに独立して数える
 */
final class ExecutionLogTest extends TestCase
{
    use CronTestSupport;

    private string $directory = '';

    /** @var array<int, string> テスト中に作ったログファイル */
    private array $written = [];

    public static function setUpBeforeClass(): void
    {
        self::loadCronClasses();
    }

    protected function setUp(): void
    {
        $this->requireCronDatabase();
        $this->cleanUpCronTasks();
        $this->directory = FR_CronDispatcher::getLogDirectory();
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['cron_log_retention_count']);
        foreach ($this->written as $file) {
            @unlink($file);
        }
        $this->written = [];
        $this->cleanUpCron();
    }

    /**
     * 指定した日付のログを作る。前段の残骸に影響されないよう、作る前に消す。
     *
     * @param array<int, string> $dates
     * @return array<string, string> 日付 => パス
     */
    private function makeLogs(string $base, array $dates): array
    {
        foreach (glob($this->directory . DIRECTORY_SEPARATOR . $base . '_*.log') ?: [] as $existing) {
            @unlink($existing);
        }
        $files = [];
        foreach ($dates as $date) {
            $file = $this->directory . DIRECTORY_SEPARATOR . $base . '_' . $date . '.log';
            file_put_contents($file, "x\n");
            $files[$date] = $file;
            $this->written[] = $file;
        }

        return $files;
    }

    public function test_K1_ログが無い状態を安全に扱う(): void
    {
        $name = $this->makeTask('Log', $this->fixtureHandler('noop1.service'));
        $task = $this->reload($name);
        foreach (FR_CronDispatcher::findLogFiles($task, 10) as $existing) {
            @unlink($existing);
        }

        self::assertNull(FR_CronDispatcher::getLatestLogFile($task), 'K1 ログが無ければ最新ログは null');

        $empty = FR_CronDispatcher::tailLogFile(null);
        self::assertSame('', $empty['content'], 'K1 ファイル未指定なら空の内容を返す');
        self::assertFalse($empty['truncated'], 'K1 ファイル未指定なら切り詰めは報告しない');
    }

    public function test_K2_最新の日付のログが選ばれる(): void
    {
        $name = $this->makeTask('Log', $this->fixtureHandler('noop1.service'));
        $task = $this->reload($name);

        $older = $this->directory . DIRECTORY_SEPARATOR . FR_CronDispatcher::getLogFileName($task, '20260101');
        $newer = $this->directory . DIRECTORY_SEPARATOR . FR_CronDispatcher::getLogFileName($task, '20260102');
        file_put_contents($older, "old line\n");
        file_put_contents($newer, "new line\n");
        $this->written[] = $older;
        $this->written[] = $newer;

        self::assertSame($newer, FR_CronDispatcher::getLatestLogFile($task), 'K2 日付が新しいログが選ばれる');
        self::assertSame([$newer, $older], FR_CronDispatcher::findLogFiles($task, 10), 'K2 ログ一覧は新しい順');
    }

    public function test_K3_指定した行数だけ末尾から取り出す(): void
    {
        $name = $this->makeTask('Log', $this->fixtureHandler('noop1.service'));
        $task = $this->reload($name);
        $file = $this->directory . DIRECTORY_SEPARATOR . FR_CronDispatcher::getLogFileName($task, '20260102');
        $this->written[] = $file;

        $lines = [];
        for ($i = 1; $i <= 50; $i++) {
            $lines[] = 'line ' . $i;
        }
        file_put_contents($file, implode("\n", $lines) . "\n");

        $tail = FR_CronDispatcher::tailLogFile($file, 10);
        self::assertSame(implode("\n", array_slice($lines, -10)), $tail['content'], 'K3 末尾 10 行だけを取り出す');
        self::assertTrue($tail['truncated'], 'K3 行数で切り詰めたことを報告する');

        $tail = FR_CronDispatcher::tailLogFile($file, 50);
        self::assertFalse($tail['truncated'], 'K3 境界: ちょうど全行なら切り詰めない');
        self::assertSame(implode("\n", $lines), $tail['content'], 'K3 境界: 全行が取れる');
    }

    public function test_K4_バイト上限で切り詰める(): void
    {
        $name = $this->makeTask('Log', $this->fixtureHandler('noop1.service'));
        $task = $this->reload($name);
        $file = $this->directory . DIRECTORY_SEPARATOR . FR_CronDispatcher::getLogFileName($task, '20260102');
        $this->written[] = $file;

        file_put_contents($file, str_repeat("0123456789abcdef\n", 4096)); // 約 68KB

        $tail = FR_CronDispatcher::tailLogFile($file, 100000, 1024);
        self::assertTrue($tail['truncated'], 'K4 上限バイトを超えたら切り詰めを報告する');
        self::assertLessThanOrEqual(1024, strlen($tail['content']), 'K4 読み出す量が上限に収まる');
        self::assertSame(filesize($file), $tail['size'], 'K4 ファイル全体のサイズは元のまま報告する');
    }

    public function test_K5_タスク名の記号はファイル名に持ち込まない(): void
    {
        $trickyName = $this->makeTask('../../etc/passwd Log', $this->fixtureHandler('noop2.service'));
        $fileName = FR_CronDispatcher::getLogFileName($this->reload($trickyName), '20260101');

        self::assertSame(0, preg_match('#[^A-Za-z0-9_\-\.]#', $fileName), 'K5 ファイル名に記号やパス区切りが残らない');
        self::assertStringNotContainsString('..', $fileName, 'K5 ファイル名に .. が残らない');
    }

    public function test_K6_K8_保持世代数を超えた古いログを削除する(): void
    {
        self::assertSame(30, FR_CronDispatcher::getLogRetentionCount(), 'K6 保持世代数の既定は 30');

        $GLOBALS['cron_log_retention_count'] = 3;
        $pruneName = $this->makeTask('Prune', $this->fixtureHandler('noop4.service'));
        $base = FR_CronDispatcher::getLogBaseName($this->reload($pruneName));

        $dates = ['20260101', '20260102', '20260103', '20260104', '20260105'];
        $files = $this->makeLogs($base, $dates);

        // ログの命名に合わないファイル（削除対象にしない）
        $stray = $this->directory . DIRECTORY_SEPARATOR . $base . '_notadate.log';
        $strayText = $this->directory . DIRECTORY_SEPARATOR . $base . '_20200101.txt';
        $strayKept = $this->directory . DIRECTORY_SEPARATOR . $base . '_20200101_backup.log';
        foreach ([$stray, $strayText, $strayKept] as $file) {
            file_put_contents($file, "x\n");
            $this->written[] = $file;
        }

        $removed = FR_CronDispatcher::pruneLogFiles([$this->reload($pruneName)]);

        self::assertFileDoesNotExist($files['20260101'], 'K6 保持世代数を超えた古いログを削除する');
        self::assertFileDoesNotExist($files['20260102'], 'K6 保持世代数を超えた古いログを削除する');
        self::assertFileExists($files['20260103'], 'K6 境界: 保持数に収まるログは残す');
        self::assertFileExists($files['20260104'], 'K6 境界: 保持数に収まるログは残す');
        self::assertFileExists($files['20260105'], 'K6 境界: 保持数に収まるログは残す');
        self::assertCount(2, $removed, 'K6 削除件数を返す');

        self::assertFileExists($stray, 'K8 日付でないファイル名には手を出さない');
        self::assertFileExists($strayText, 'K8 .log 以外の拡張子には手を出さない');
        self::assertFileExists($strayKept, 'K8 日付が末尾でないファイルには手を出さない');
    }

    public function test_K7_保持世代数0なら削除しない(): void
    {
        $GLOBALS['cron_log_retention_count'] = 0;
        $pruneName = $this->makeTask('Prune', $this->fixtureHandler('noop4.service'));
        $base = FR_CronDispatcher::getLogBaseName($this->reload($pruneName));
        $files = $this->makeLogs($base, ['20260101', '20260102', '20260103', '20260104', '20260105']);

        self::assertSame([], FR_CronDispatcher::pruneLogFiles([$this->reload($pruneName)]), 'K7 保持世代数 0 なら削除しない');
        self::assertFileExists($files['20260101'], 'K7 保持世代数 0 ならファイルが残る');
    }

    public function test_K10_タスクごとの指定が既定値より優先される(): void
    {
        $GLOBALS['cron_log_retention_count'] = 30;
        $pruneName = $this->makeTask('Prune', $this->fixtureHandler('noop4.service'));
        $base = FR_CronDispatcher::getLogBaseName($this->reload($pruneName));
        $dates = ['20260101', '20260102', '20260103', '20260104', '20260105'];

        $this->setCols($pruneName, ['log_retention_count' => 2]);
        $files = $this->makeLogs($base, $dates);
        FR_CronDispatcher::pruneLogFiles([$this->reload($pruneName)]);

        self::assertFileDoesNotExist($files['20260103'], 'K10 タスク側の指定（2 世代）が使われる');
        self::assertFileExists($files['20260104'], 'K10 タスク側の指定（2 世代）が使われる');
        self::assertFileExists($files['20260105'], 'K10 タスク側の指定（2 世代）が使われる');

        // タスク側で 0（無期限）を指定した場合
        $this->setCols($pruneName, ['log_retention_count' => 0]);
        $this->makeLogs($base, $dates);
        self::assertSame([], FR_CronDispatcher::pruneLogFiles([$this->reload($pruneName)]), 'K10 タスク側の 0 は無期限として扱う');

        // 未設定に戻すと既定値に従う
        $this->setCols($pruneName, ['log_retention_count' => null]);
        self::assertSame(
            30,
            FR_CronDispatcher::getEffectiveLogRetentionCount($this->reload($pruneName)),
            'K10 未設定なら既定値を使う'
        );
    }

    public function test_K11_対応するタスクが無いログも既定値で削除される(): void
    {
        $GLOBALS['cron_log_retention_count'] = 1;
        $pruneName = $this->makeTask('Prune', $this->fixtureHandler('noop4.service'));

        $orphanFiles = $this->makeLogs('FRTestCronOrphan', ['20260101', '20260102']);
        FR_CronDispatcher::pruneLogFiles([$this->reload($pruneName)]);

        self::assertFileDoesNotExist($orphanFiles['20260101'], 'K11 タスクの無いログも既定値で削除される');
        self::assertFileExists($orphanFiles['20260102'], 'K11 保持数に収まる分は残る');
    }

    public function test_K12_タスクごとに独立して数える(): void
    {
        $GLOBALS['cron_log_retention_count'] = 2;
        $pruneName = $this->makeTask('Prune', $this->fixtureHandler('noop4.service'));
        $otherName = $this->makeTask('PruneOther', $this->fixtureHandler('noop5.service'));
        $base = FR_CronDispatcher::getLogBaseName($this->reload($pruneName));
        $otherBase = FR_CronDispatcher::getLogBaseName($this->reload($otherName));

        $filesA = $this->makeLogs($base, ['20260101', '20260102', '20260103']);
        $filesB = $this->makeLogs($otherBase, ['20260101', '20260102']);

        FR_CronDispatcher::pruneLogFiles([$this->reload($pruneName), $this->reload($otherName)]);

        self::assertFileDoesNotExist($filesA['20260101'], 'K12 一方のタスクは 2 世代に絞られる');
        self::assertFileExists($filesA['20260102'], 'K12 一方のタスクは 2 世代に絞られる');
        self::assertFileExists($filesA['20260103'], 'K12 一方のタスクは 2 世代に絞られる');
        self::assertFileExists($filesB['20260101'], 'K12 他方のタスクは 2 世代なので残る');
        self::assertFileExists($filesB['20260102'], 'K12 他方のタスクは 2 世代なので残る');
    }

    public function test_K9_1日1回だけ走る(): void
    {
        $stamp = $this->directory . DIRECTORY_SEPARATOR . FR_CronDispatcher::LOG_PRUNE_STAMP;
        $originalStamp = is_file($stamp) ? (string) file_get_contents($stamp) : null;
        @unlink($stamp);

        $GLOBALS['cron_log_retention_count'] = 1;
        $pruneName = $this->makeTask('Prune', $this->fixtureHandler('noop4.service'));
        $base = FR_CronDispatcher::getLogBaseName($this->reload($pruneName));

        try {
            $files = $this->makeLogs($base, ['20260101', '20260102']);
            $firstRun = FR_CronDispatcher::pruneLogFilesOncePerDay([$this->reload($pruneName)]);
            self::assertNotEmpty($firstRun, 'K9 1 回目は削除される');
            self::assertFileDoesNotExist($files['20260101'], 'K9 1 回目は削除される');
            self::assertFileExists($stamp, 'K9 実行した日が記録される');
            self::assertSame(date('Ymd'), trim((string) file_get_contents($stamp)), 'K9 実行した日が記録される');

            $files = $this->makeLogs($base, ['20260101', '20260102']);
            self::assertSame(
                [],
                FR_CronDispatcher::pruneLogFilesOncePerDay([$this->reload($pruneName)]),
                'K9 同じ日の 2 回目は走らない'
            );
            self::assertFileExists($files['20260101'], 'K9 2 回目では削除されない');
        } finally {
            if ($originalStamp === null) {
                @unlink($stamp);
            } else {
                file_put_contents($stamp, $originalStamp);
            }
        }
    }
}
