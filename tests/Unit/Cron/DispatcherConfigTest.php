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

namespace Tests\Unit\Cron;

use FR_CronDispatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

$dispatcherConfigTestRoot = dirname(__DIR__, 3);
require_once $dispatcherConfigTestRoot . '/include/utils/CronDispatcher.php';

/**
 * M. 補助関数・設定・整合性（DB を使わない範囲）— #1823
 *
 * 対象: FR_CronDispatcher の設定読み出しと、参照している言語ラベルの整合性
 *
 *   M1  getPhpBinary の決定順（設定 > 実行中の PHP）
 *   M2  getRootDirectory（末尾セパレータなし・vtigercron.php がある場所）
 *   M5  getSerialTaskNames（配列でない設定・未設定は空配列）
 *   M11 ログディレクトリが無い場合は削除しない（異常系）
 *   M12 tailLogFile が存在しないファイル・ディレクトリを安全に扱う（異常系）
 *   M14 参照している言語ラベルが ja_jp / en_us に揃っている
 */
final class DispatcherConfigTest extends TestCase
{
    /**
     * Vtiger 共通の言語ファイルで定義されているラベル。
     * このモジュールの言語ファイルには無くてよい。
     */
    private const COMMON_LABELS = [
        'LBL_STATUS', 'LBL_MINUTES', 'LBL_HOURS', 'LBL_DRAG', 'LBL_EDIT_RECORD',
        'LBL_PERMISSION_DENIED', 'JS_VALUE_SHOULD_NOT_BE_LESS_THAN', 'JS_MINUTES',
    ];

    private function projectRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['cron_php_binary'], $GLOBALS['cron_serial_tasks']);
    }

    public function test_M1_PHP_バイナリの決定順(): void
    {
        $GLOBALS['cron_php_binary'] = '/opt/php/bin/php';
        self::assertSame('/opt/php/bin/php', FR_CronDispatcher::getPhpBinary(), 'M1 設定があればその値を使う');

        unset($GLOBALS['cron_php_binary']);
        self::assertSame(PHP_BINARY, FR_CronDispatcher::getPhpBinary(), 'M1 設定が無ければ実行中の PHP を使う');
    }

    public function test_M2_インストールディレクトリ(): void
    {
        $root = FR_CronDispatcher::getRootDirectory();

        self::assertSame(rtrim($root, '/\\'), $root, 'M2 末尾にセパレータを付けない');
        self::assertFileExists($root . DIRECTORY_SEPARATOR . 'vtigercron.php', 'M2 vtigercron.php がある場所を指す');
    }

    public function test_M5_直列指定タスクの設定(): void
    {
        $GLOBALS['cron_serial_tasks'] = ['Workflow'];
        self::assertSame(['Workflow'], FR_CronDispatcher::getSerialTaskNames(), 'M5 配列の設定はそのまま使う');

        $GLOBALS['cron_serial_tasks'] = 'Workflow';
        self::assertSame([], FR_CronDispatcher::getSerialTaskNames(), 'M5 配列でない設定は空配列として扱う');

        unset($GLOBALS['cron_serial_tasks']);
        self::assertSame([], FR_CronDispatcher::getSerialTaskNames(), 'M5 未設定なら空配列');
    }

    public function test_M11_ログディレクトリが無ければ削除しない(): void
    {
        // 実際の logs/cron は触らず、ログの無い一時ディレクトリを root として見せる
        $original = $GLOBALS['root_directory'] ?? null;
        $emptyRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'frtest_cron_root_' . getmypid();
        if (!is_dir($emptyRoot)) {
            mkdir($emptyRoot, 0700, true);
        }

        try {
            $GLOBALS['root_directory'] = $emptyRoot;
            self::assertDirectoryDoesNotExist(FR_CronDispatcher::getLogDirectory(), 'M11 前提: ログディレクトリが存在しない');
            self::assertSame([], FR_CronDispatcher::pruneLogFiles([]), 'M11 ログディレクトリが無ければ削除しない');
            self::assertSame([], FR_CronDispatcher::pruneLogFilesOncePerDay([]), 'M11 1 日 1 回の呼び出しでも何もしない');
        } finally {
            if ($original === null) {
                unset($GLOBALS['root_directory']);
            } else {
                $GLOBALS['root_directory'] = $original;
            }
            @rmdir($emptyRoot);
        }
    }

    public function test_M12_存在しないファイルを安全に扱う(): void
    {
        $directory = $this->projectRoot();

        $missing = FR_CronDispatcher::tailLogFile($directory . DIRECTORY_SEPARATOR . 'no_such.log');
        self::assertSame('', $missing['content'], 'M12 存在しないファイルは空を返す');
        self::assertSame(0, $missing['size'], 'M12 存在しないファイルはサイズ 0');

        self::assertSame(
            '',
            FR_CronDispatcher::tailLogFile($directory)['content'],
            'M12 ディレクトリを渡しても空を返す'
        );
    }

    /**
     * @return array<int, array{0: string}>
     */
    public static function languages(): array
    {
        return [['ja_jp'], ['en_us']];
    }

    /**
     * 画面・モデル・JS が参照しているラベルが言語ファイルに揃っているか。
     * 追加した項目のラベルを入れ忘れるとキー名がそのまま画面に出てしまう。
     */
    #[DataProvider('languages')]
    public function test_M14_参照している言語ラベルが揃っている(string $language): void
    {
        $root = $this->projectRoot();
        $sources = array_merge(
            glob($root . '/layouts/v7/modules/Settings/CronTasks/*.tpl') ?: [],
            glob($root . '/modules/Settings/CronTasks/models/*.php') ?: [],
            glob($root . '/modules/Settings/CronTasks/views/*.php') ?: [],
            glob($root . '/public/layouts/v7/modules/Settings/CronTasks/resources/*.js') ?: []
        );

        $referenced = [];
        foreach ($sources as $source) {
            $contents = (string) file_get_contents($source);
            if (preg_match_all("/'((?:LBL|JS)_[A-Z0-9_]+)'/", $contents, $matches)) {
                foreach ($matches[1] as $label) {
                    $referenced[$label] = true;
                }
            }
        }
        $referenced = array_keys($referenced);
        self::assertGreaterThan(20, count($referenced), 'M14 参照しているラベルを収集できる');

        $languageStrings = [];
        require $root . '/languages/' . $language . '/Settings/CronTasks.php';

        $missing = [];
        foreach ($referenced as $label) {
            // Vtiger 共通の言語ファイルにあるものは対象外
            if (in_array($label, self::COMMON_LABELS, true)) {
                continue;
            }
            if (!isset($languageStrings[$label])) {
                $missing[] = $label;
            }
        }

        self::assertSame([], $missing, 'M14 ' . $language . ' に不足しているラベルが無い');
    }
}
