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

namespace Tests\Integration\Migration;

use Leads_ConvertLeadAssignee_Model;
use Migration20260929112019_AddLeadConvertAssignCurrentUserParameter as ParameterMigration;
use PearDatabase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * add_lead_convert_assign_current_user_parameter マイグレーション — #1864
 *
 * 対象: setup/migration/scripts/20260929112019_add_lead_convert_assign_current_user_parameter.php
 *       modules/Leads/models/ConvertLeadAssignee.php の getDefaultAssignedUserId()
 *
 *   1  パラメータが無ければ既定値 false で追加する
 *   2  2 回実行しても重複登録しない（冪等性）
 *   3  登録済みの値（true）を上書きしない
 *   4  登録された値が true ならログインユーザー、false ならリードの担当を初期値にする
 *
 * 検証前の LEAD_CONVERT_ASSIGN_CURRENT_USER の行を退避し、終わったら元に戻す。
 *
 * マイグレーションの基底クラスが includes/runtime/LanguageHandler.php を実物で読み込み、
 * tests/Support/ のスタブと同名クラスになるため、独立したプロセスで動かす。
 * getParameterValue() は値をプロセス内でキャッシュするため、その点でも分離が必要。
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class LeadConvertAssignCurrentUserParameterTest extends TestCase
{
    private const KEY = 'LEAD_CONVERT_ASSIGN_CURRENT_USER';

    /**
     * 検証前に登録されていた行。無ければ null
     *
     * @var array<string, mixed>|null
     */
    private ?array $originalRow = null;

    private ?PearDatabase $db = null;

    private function db(): PearDatabase
    {
        $db = $this->db;
        if (!$db instanceof PearDatabase) {
            $db = PearDatabase::getInstance();
            $this->db = $db;
        }

        return $db;
    }

    protected function setUp(): void
    {
        // 分離した子プロセス側で読み込む。setUpBeforeClass は親プロセスで動くため
        // ここで読まないと、スタブを持つ親で二重宣言になる。
        // 本番のマイグレーション実行と同じく Vtiger_Loader のオートロードを効かせる
        // （tests/bootstrap.php が外しているため。Settings_Parameters_Record_Model の親クラスの解決に要る）
        $root = dirname(__DIR__, 3);
        require_once $root . '/includes/Loader.php';
        spl_autoload_register(['Vtiger_Loader', 'autoLoad']);
        require_once $root
            . '/setup/migration/scripts/20260929112019_add_lead_convert_assign_current_user_parameter.php';

        try {
            $result = $this->db()->pquery('SELECT 1 AS ok FROM vtiger_parameters LIMIT 1', []);
        } catch (\Throwable $e) {
            self::markTestSkipped('テスト用DBに接続できないため実行しない: ' . $e->getMessage());
        }
        if ($result === false) {
            self::markTestSkipped('テスト用DBに vtiger_parameters がないため実行しない');
        }

        $result = $this->db()->pquery('SELECT `key`, `value`, `description` FROM vtiger_parameters WHERE `key` = ?', [self::KEY]);
        if ($this->db()->num_rows($result) > 0) {
            $this->originalRow = $this->db()->fetchByAssoc($result, -1, false);
        }
        $this->deleteParameter();
    }

    protected function tearDown(): void
    {
        if ($this->db === null) {
            return;
        }
        $this->deleteParameter();
        if ($this->originalRow !== null) {
            $this->db()->pquery(
                'INSERT INTO vtiger_parameters(`key`, `value`, `description`) VALUES(?, ?, ?)',
                [$this->originalRow['key'], $this->originalRow['value'], $this->originalRow['description']]
            );
        }
    }

    public function testAddsParameterWithFalseByDefault(): void
    {
        $this->runMigration();

        $this->assertSame(['false'], $this->storedValues());
        $description = $this->storedDescription();
        $this->assertNotSame('', $description, '説明文が入っていること');
        $this->assertStringNotContainsString(
            'LBL_SETUP_PARAMETER_MESSAGE',
            $description,
            '説明文が翻訳されていること（言語ファイルのキーが登録されていること）'
        );
    }

    public function testRunningTwiceDoesNotDuplicate(): void
    {
        $this->runMigration();
        $this->runMigration();

        $this->assertSame(['false'], $this->storedValues());
    }

    public function testDoesNotOverwriteExistingValue(): void
    {
        $this->insertParameter('true');

        $this->runMigration();

        $this->assertSame(['true'], $this->storedValues());
    }

    public function testCurrentUserIsDefaultWhenStoredValueIsTrue(): void
    {
        $this->insertParameter('true');

        $this->assertSame('1', Leads_ConvertLeadAssignee_Model::getDefaultAssignedUserId('5', '1'));
    }

    public function testLeadAssigneeIsDefaultWhenStoredValueIsFalse(): void
    {
        $this->runMigration();

        $this->assertSame('5', Leads_ConvertLeadAssignee_Model::getDefaultAssignedUserId('5', '1'));
    }

    public function testLeadAssigneeIsDefaultWhenParameterIsMissing(): void
    {
        $this->assertSame('5', Leads_ConvertLeadAssignee_Model::getDefaultAssignedUserId('5', '1'));
    }

    private function runMigration(): void
    {
        $migration = new ParameterMigration();

        // 説明文の vtranslate() でコア（includes/runtime/LanguageHandler.php 等）が
        // 未定義変数の警告を出す。本機能と無関係なコア由来の警告だけを握りつぶし、
        // それ以外は PHPUnit のハンドラへ渡す。
        $previous = null;
        $previous = set_error_handler(
            static function (int $errno, string $errstr, string $errfile = '', int $errline = 0) use (&$previous): bool {
                $coreFiles = ['/includes/runtime/LanguageHandler.php', '/include/database/PearDatabase.php'];
                foreach ($coreFiles as $coreFile) {
                    if (str_ends_with($errfile, $coreFile)) {
                        return true;
                    }
                }

                return is_callable($previous) ? (bool) $previous($errno, $errstr, $errfile, $errline) : false;
            }
        );

        // log() は標準出力に書くため、テストの出力として扱われないよう捨てる
        ob_start();
        try {
            $migration->process();
        } finally {
            ob_end_clean();
            restore_error_handler();
        }
    }

    private function insertParameter(string $value): void
    {
        $this->db()->pquery(
            'INSERT INTO vtiger_parameters(`key`, `value`, `description`) VALUES(?, ?, ?)',
            [self::KEY, $value, 'test']
        );
    }

    private function deleteParameter(): void
    {
        $this->db()->pquery('DELETE FROM vtiger_parameters WHERE `key` = ?', [self::KEY]);
    }

    /**
     * @return list<string>
     */
    private function storedValues(): array
    {
        $result = $this->db()->pquery('SELECT `value` FROM vtiger_parameters WHERE `key` = ?', [self::KEY]);
        $values = [];
        while ($row = $this->db()->fetchByAssoc($result, -1, false)) {
            $values[] = (string) $row['value'];
        }

        return $values;
    }

    private function storedDescription(): string
    {
        $result = $this->db()->pquery('SELECT `description` FROM vtiger_parameters WHERE `key` = ?', [self::KEY]);

        return (string) $this->db()->query_result($result, 0, 'description');
    }
}
