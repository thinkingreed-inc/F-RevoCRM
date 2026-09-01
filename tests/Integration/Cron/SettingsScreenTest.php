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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
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
 * J. 管理画面（システム設定 > スケジューラ）— #1823
 *
 * 対象: Settings_CronTasks_Record_Model（save / getDisplayValue / getRuntimeState など）
 *
 *   J1  周期の変更で next_run_at が引き直される
 *   J2  毎日・毎週・毎月への変更が保存され、next_run_at が指定時刻になる
 *   J3  周期実行へ戻すと時刻・曜日・日の指定が消える
 *   J4  値が欠けている・範囲外の場合は周期実行にフォールバックする
 *   J5  タイムアウトの保存と表示（未設定は既定値＋注記）
 *   J6  ステータス表示が実行状態（DEAD など）を反映する
 *   J7  実行タイミング列の表示（周期／毎日／毎週／毎月／月末）
 *   J8  複数曜日の表示（選んだ曜日をすべて・曜日順）
 *   J9  最終開始日時・最終終了日時・次回実行予定を「日時」として表示する
 *   J10 周期の下限は 1 分（$MINIMUM_CRON_FREQUENCY があればその値）
 *   J11 15 分より短い周期を保存できる
 *
 * 表示の検証には vtiger の言語処理が必要になる。tests/Support/ のスタブが先に
 * 読み込まれているプロセスではそちらを使うため、実物と二重宣言にはならない。
 */
final class SettingsScreenTest extends TestCase
{
    use CronTestSupport;

    private string $taskName = '';
    private int $recordId = 0;

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
        if (!class_exists('Settings_CronTasks_Record_Model')) {
            self::markTestSkipped('Settings のクラスを読み込めなかった');
        }
        $this->prepareCronCurrentUser();
        $this->cleanUpCronTasks();
        // 表示系は vtiger の言語処理・日時処理を通り、そこに既存の警告があるため
        self::suppressCoreWarnings();

        $this->taskName = $this->makeTask('Ui', $this->fixtureHandler('noop1.service'));
        $this->recordId = $this->getColInt($this->taskName, 'id');
    }

    protected function tearDown(): void
    {
        self::restoreCoreWarnings();
        $this->cleanUpCron();
    }

    /**
     * 管理画面からの保存と同じ経路を通す
     *
     * @param array<string, mixed> $values
     */
    private function saveViaSettings(array $values): void
    {
        $record = Settings_CronTasks_Record_Model::getInstanceById($this->recordId, 'Settings:CronTasks');
        foreach ($values as $field => $value) {
            $record->set($field, $value);
        }
        $record->save();
    }

    private function record(): Settings_CronTasks_Record_Model
    {
        $this->clearCronInstanceCache();

        return Settings_CronTasks_Record_Model::getInstanceById($this->recordId, 'Settings:CronTasks');
    }

    public function test_J1_周期の変更で次回実行予定が引き直される(): void
    {
        $this->saveViaSettings([
            'frequency'      => 3600,
            'status'         => 1,
            'schedule_type'  => 'interval',
            'run_at_minutes' => '',
            'retry_timeout'  => 0,
        ]);

        self::assertSame(
            '00',
            date('i', $this->getColInt($this->taskName, 'next_run_at')),
            'J1 周期 3600 秒なら next_run_at が毎時 00 分になる'
        );
        self::assertNull($this->getCol($this->taskName, 'run_at_minutes'), 'J1 周期実行では run_at_minutes が NULL のまま');
    }

    public function test_J2_毎日実行へ変更できる(): void
    {
        $this->saveViaSettings([
            'frequency'      => 900,
            'status'         => 1,
            'schedule_type'  => 'daily',
            'run_at_minutes' => 3 * 60 + 30,
            'retry_timeout'  => 0,
        ]);

        self::assertSame('daily', $this->getColString($this->taskName, 'schedule_type'), 'J2 schedule_type が daily になる');
        self::assertSame('210', $this->getColString($this->taskName, 'run_at_minutes'), 'J2 run_at_minutes が保存される');
        self::assertSame(
            '03:30',
            date('H:i', $this->getColInt($this->taskName, 'next_run_at')),
            'J2 next_run_at が指定時刻（3:30）になる'
        );
        self::assertSame('86400', $this->getColString($this->taskName, 'frequency'), 'J2 周期は 1 日に揃えられる');
    }

    public function test_J2_毎週実行へ変更できる(): void
    {
        $this->saveViaSettings([
            'frequency'        => 900,
            'status'           => 1,
            'schedule_type'    => 'weekly',
            'run_at_minutes'   => 9 * 60,
            'run_on_weekdays'  => '5',
            'retry_timeout'    => 0,
        ]);

        self::assertSame('weekly', $this->getColString($this->taskName, 'schedule_type'), 'J2 毎週: schedule_type が weekly になる');
        self::assertSame('5', $this->getColString($this->taskName, 'run_on_weekdays'), 'J2 毎週: 曜日が保存される');

        $nextRunAt = $this->getColInt($this->taskName, 'next_run_at');
        self::assertSame('5', date('w', $nextRunAt), 'J2 毎週: next_run_at が金曜になる');
        self::assertSame('09:00', date('H:i', $nextRunAt), 'J2 毎週: next_run_at の時刻が 09:00');
        self::assertSame('604800', $this->getColString($this->taskName, 'frequency'), 'J2 毎週: 周期は 1 週間に揃えられる');
    }

    public function test_J2_毎月実行_月末_へ変更できる(): void
    {
        $this->saveViaSettings([
            'frequency'      => 900,
            'status'         => 1,
            'schedule_type'  => 'monthly',
            'run_at_minutes' => 2 * 60,
            'run_on_day'     => Vtiger_Cron::RUN_ON_LAST_DAY_OF_MONTH,
            'retry_timeout'  => 0,
        ]);

        self::assertSame('monthly', $this->getColString($this->taskName, 'schedule_type'), 'J2 毎月: schedule_type が monthly になる');
        self::assertSame('0', $this->getColString($this->taskName, 'run_on_day'), 'J2 毎月: 月末（0）が保存される');

        $nextRunAt = $this->getColInt($this->taskName, 'next_run_at');
        self::assertSame(date('t', $nextRunAt), date('j', $nextRunAt), 'J2 毎月: next_run_at が月末の日になる');
        self::assertSame('02:00', date('H:i', $nextRunAt), 'J2 毎月: next_run_at の時刻が 02:00');
        self::assertNull($this->getCol($this->taskName, 'run_on_weekdays'), 'J2 毎月: 曜日は消される');
    }

    public function test_J2_毎週で曜日を複数選択できる(): void
    {
        $this->saveViaSettings([
            'frequency'       => 900,
            'status'          => 1,
            'schedule_type'   => 'weekly',
            'run_at_minutes'  => 9 * 60,
            'run_on_weekdays' => [5, 1, 3, 3],
            'retry_timeout'   => 0,
        ]);

        self::assertSame('1,3,5', $this->getColString($this->taskName, 'run_on_weekdays'), 'J2 複数曜日: 重複を除き昇順で保存される');

        $nextRunAt = $this->getColInt($this->taskName, 'next_run_at');
        self::assertContains(
            (int) date('w', $nextRunAt),
            [1, 3, 5],
            'J2 複数曜日: next_run_at が指定した曜日のいずれかになる'
        );
        self::assertLessThanOrEqual(7 * 24 * 60 * 60, $nextRunAt - time(), 'J2 複数曜日: next_run_at が 1 週間以内になる');

        $this->saveViaSettings([
            'frequency'       => 900,
            'status'          => 1,
            'schedule_type'   => 'weekly',
            'run_at_minutes'  => 9 * 60,
            'run_on_weekdays' => '4,2',
            'retry_timeout'   => 0,
        ]);
        self::assertSame('2,4', $this->getColString($this->taskName, 'run_on_weekdays'), 'J2 複数曜日: カンマ区切りの文字列でも保存できる');
    }

    public function test_J3_周期実行へ戻すと時刻や曜日の指定が消える(): void
    {
        $this->saveViaSettings([
            'frequency'      => 900,
            'status'         => 1,
            'schedule_type'  => 'daily',
            'run_at_minutes' => 3 * 60,
            'retry_timeout'  => 0,
        ]);

        $this->saveViaSettings([
            'frequency'       => 900,
            'status'          => 1,
            'schedule_type'   => 'interval',
            'run_at_minutes'  => '',
            'run_on_weekdays' => '',
            'run_on_day'      => '',
            'retry_timeout'   => 0,
        ]);

        self::assertSame('interval', $this->getColString($this->taskName, 'schedule_type'), 'J3 周期実行に戻すと schedule_type が interval になる');
        self::assertNull($this->getCol($this->taskName, 'run_at_minutes'), 'J3 周期実行に戻すと run_at_minutes が消える');
        self::assertNull($this->getCol($this->taskName, 'run_on_day'), 'J3 周期実行に戻すと run_on_day が消える');
        self::assertSame('900', $this->getColString($this->taskName, 'frequency'), 'J3 周期が指定値に戻る');
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string}>
     */
    public static function invalidScheduleInputs(): array
    {
        return [
            '範囲外の時刻'       => [['schedule_type' => 'daily', 'run_at_minutes' => 5000], 'J4 範囲外の時刻は周期実行として扱う'],
            '毎週で曜日が無い'   => [['schedule_type' => 'weekly', 'run_at_minutes' => 540, 'run_on_weekdays' => ''], 'J4 毎週で曜日が無ければ周期実行として扱う'],
            '毎月で日が範囲外'   => [['schedule_type' => 'monthly', 'run_at_minutes' => 540, 'run_on_day' => 40], 'J4 毎月で日が範囲外なら周期実行として扱う'],
            '未知の種別'         => [['schedule_type' => 'bogus', 'run_at_minutes' => ''], 'J4 未知の種別は周期実行として扱う'],
        ];
    }

    /** @param array<string, mixed> $input */
    #[DataProvider('invalidScheduleInputs')]
    public function test_J4_値が欠けているか範囲外なら周期実行にフォールバックする(array $input, string $message): void
    {
        $this->saveViaSettings(array_merge(
            ['frequency' => 900, 'status' => 1, 'retry_timeout' => 0],
            $input
        ));

        self::assertSame('interval', $this->getColString($this->taskName, 'schedule_type'), $message);
    }

    public function test_J5_タイムアウトの保存と表示(): void
    {
        $this->saveViaSettings([
            'frequency'      => 900,
            'status'         => 1,
            'schedule_type'  => 'interval',
            'run_at_minutes' => '',
            'retry_timeout'  => 1800,
        ]);

        self::assertSame('1800', $this->getColString($this->taskName, 'retry_timeout'), 'J5 タイムアウトが保存される');
        self::assertSame('00:30', $this->record()->getDisplayValue('retry_timeout'), 'J5 タイムアウトの表示が 00:30 になる');

        $this->saveViaSettings([
            'frequency'      => 900,
            'status'         => 1,
            'schedule_type'  => 'interval',
            'run_at_minutes' => '',
            'retry_timeout'  => 0,
        ]);

        $display = $this->record()->getDisplayValue('retry_timeout');
        self::assertStringStartsWith('01:00', $display, 'J5 未設定なら既定値を表示する');
        self::assertStringContainsString('(', $display, 'J5 未設定なら「既定」の注記を添える');
    }

    public function test_J6_ステータス表示が実行状態を反映する(): void
    {
        $now = $this->dbNow();
        $this->setCols($this->taskName, [
            'status'         => Vtiger_Cron::$STATUS_RUNNING,
            'owner_host'     => FR_CronDispatcher::getHostName(),
            'owner_pid'      => 4194303,
            'laststart'      => $now - 10,
            'lastend'        => 0,
            'last_heartbeat' => $now,
        ]);

        $record = $this->record();
        self::assertSame('DEAD', $record->getRuntimeState(), 'J6 プロセスが無い実行中タスクの実行状態は DEAD');

        $display = $record->getDisplayValue('status');
        self::assertStringContainsString('label-danger', $display, 'J6 ステータス列が異常を強調表示する');
        self::assertStringNotContainsString(
            '>' . vtranslate('LBL_RUNNING', 'Settings:CronTasks') . '<',
            $display,
            'J6 ステータス列に「実行中」とだけ出すことはしない'
        );

        $this->setCols($this->taskName, ['status' => Vtiger_Cron::$STATUS_ENABLED]);
        self::assertStringContainsString(
            'label-success',
            $this->record()->getDisplayValue('status'),
            'J6 有効なタスクは通常表示'
        );
    }

    public function test_J7_実行タイミング列の表示(): void
    {
        $this->setCols($this->taskName, [
            'schedule_type'  => 'daily',
            'run_at_minutes' => 3 * 60 + 30,
            'frequency'      => 86400,
        ]);
        self::assertStringContainsString('03:30', $this->record()->getDisplayValue('frequency'), 'J7 毎日実行なら時刻を表示する');

        $this->setCols($this->taskName, [
            'schedule_type'   => 'weekly',
            'run_at_minutes'  => 9 * 60,
            'run_on_weekdays' => '1',
            'frequency'       => 604800,
        ]);
        $display = $this->record()->getDisplayValue('frequency');
        self::assertStringContainsString(vtranslate('LBL_MONDAY', 'Settings:CronTasks'), $display, 'J7 毎週実行なら曜日を表示する');
        self::assertStringContainsString('09:00', $display, 'J7 毎週実行なら時刻を表示する');

        $this->setCols($this->taskName, [
            'schedule_type'   => 'monthly',
            'run_at_minutes'  => 2 * 60,
            'run_on_weekdays' => null,
            'run_on_day'      => 15,
            'frequency'       => 2592000,
        ]);
        $display = $this->record()->getDisplayValue('frequency');
        self::assertStringContainsString('15', $display, 'J7 毎月実行なら日を表示する');
        self::assertStringContainsString('02:00', $display, 'J7 毎月実行なら時刻を表示する');

        $this->setCols($this->taskName, ['run_on_day' => Vtiger_Cron::RUN_ON_LAST_DAY_OF_MONTH]);
        self::assertStringContainsString(
            vtranslate('LBL_LAST_DAY_OF_MONTH', 'Settings:CronTasks'),
            $this->record()->getDisplayValue('frequency'),
            'J7 月末指定なら「月末」と表示する'
        );

        $this->setCols($this->taskName, [
            'schedule_type'   => 'interval',
            'run_at_minutes'  => null,
            'run_on_weekdays' => null,
            'run_on_day'      => null,
            'frequency'       => 900,
        ]);
        self::assertSame('00:15', $this->record()->getDisplayValue('frequency'), 'J7 周期実行なら従来どおり時:分を表示する');
    }

    public function test_J8_複数曜日は選んだ曜日をすべて曜日順で表示する(): void
    {
        $this->setCols($this->taskName, [
            'schedule_type'   => 'weekly',
            'run_at_minutes'  => 9 * 60,
            'run_on_weekdays' => '1,3,5',
            'run_on_day'      => null,
            'frequency'       => 604800,
        ]);

        $display = $this->record()->getDisplayValue('frequency');
        $monday = vtranslate('LBL_MONDAY', 'Settings:CronTasks');
        $wednesday = vtranslate('LBL_WEDNESDAY', 'Settings:CronTasks');
        $friday = vtranslate('LBL_FRIDAY', 'Settings:CronTasks');

        self::assertStringContainsString($monday, $display, 'J8 月曜が表示される');
        self::assertStringContainsString($wednesday, $display, 'J8 水曜が表示される');
        self::assertStringContainsString($friday, $display, 'J8 金曜が表示される');
        self::assertLessThan(strpos($display, $wednesday), strpos($display, $monday), 'J8 月曜が水曜より前に並ぶ');
        self::assertLessThan(strpos($display, $friday), strpos($display, $wednesday), 'J8 水曜が金曜より前に並ぶ');
    }

    public function test_J9_日時列は経過時間ではなく日時で表示する(): void
    {
        $timestamp = (int) mktime(14, 30, 0, 8, 20, 2026);
        $this->setCols($this->taskName, [
            'laststart'   => $timestamp,
            'lastend'     => $timestamp + 90,
            'next_run_at' => $timestamp + 3600,
        ]);

        $record = $this->record();
        foreach (['laststart' => '14:30', 'lastend' => '14:31', 'next_run_at' => '15:30'] as $field => $expectedTime) {
            $display = $record->getDisplayValue($field);
            self::assertStringContainsString('2026', $display, 'J9 ' . $field . ' に年が含まれる');
            self::assertStringContainsString('08', $display, 'J9 ' . $field . ' に月が含まれる');
            self::assertStringContainsString($expectedTime, $display, 'J9 ' . $field . ' に時刻 ' . $expectedTime . ' が含まれる');
        }

        $this->setCols($this->taskName, ['laststart' => 0, 'lastend' => 0, 'next_run_at' => 0]);
        $record = $this->record();
        self::assertSame('', $record->getDisplayValue('laststart'), 'J9 未実行なら最終開始日時は空欄');
        self::assertSame('', $record->getDisplayValue('next_run_at'), 'J9 未設定なら次回実行予定は空欄');
    }

    public function test_J10_周期の下限は1分(): void
    {
        $original = $GLOBALS['MINIMUM_CRON_FREQUENCY'] ?? null;
        unset($GLOBALS['MINIMUM_CRON_FREQUENCY']);

        try {
            $record = $this->record();
            self::assertSame(60, $record->getMinimumFrequency(), 'J10 下限は 1 分（15 分より短い周期を指定できる）');
            self::assertGreaterThanOrEqual($record->getMinimumFrequency(), 300, 'J10 5 分（300 秒）が下限を下回らない');

            // 明示的に設定されている場合はその値を尊重する
            $GLOBALS['MINIMUM_CRON_FREQUENCY'] = 15;
            self::assertSame(900, $record->getMinimumFrequency(), 'J10 $MINIMUM_CRON_FREQUENCY を設定した場合はその値を使う');
        } finally {
            if ($original === null) {
                unset($GLOBALS['MINIMUM_CRON_FREQUENCY']);
            } else {
                $GLOBALS['MINIMUM_CRON_FREQUENCY'] = $original;
            }
        }
    }

    public function test_J11_15分より短い周期を保存できる(): void
    {
        $this->saveViaSettings([
            'frequency'      => 300,
            'status'         => 1,
            'schedule_type'  => 'interval',
            'run_at_minutes' => '',
            'retry_timeout'  => 0,
        ]);

        self::assertSame('300', $this->getColString($this->taskName, 'frequency'), 'J11 5 分（300 秒）の周期を保存できる');
        self::assertSame(
            0,
            $this->getColInt($this->taskName, 'next_run_at') % 300,
            'J11 5 分周期の next_run_at が 5 分のグリッドに乗る'
        );
    }
}
