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

namespace Tests\Integration\Import;

use Import_Data_Action;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\DocumentsTestCase;
use WebServiceErrorCode;
use WebServiceException;

require_once dirname(__DIR__, 3) . '/tests/Support/DocumentsTestCase.php';

/**
 * インポートの失敗・スキップ理由 (#1905)
 *
 * 理由は Import_Data_Action のプロパティに1件だけ保持し、updateImportStatus() が
 * 一時テーブルに理由列があるときだけ書き込む。理由列の有無で挙動が分かれること、
 * 各分岐が仕様の理由（言語キー）を設定すること、行をまたいで理由が残らないことを確かめる。
 *
 * 検証用の一時テーブルは IMPORT_ID の1本だけを作り、tearDown で消す。
 */
final class ImportFailureReasonTest extends DocumentsTestCase
{
    /** 既存のインポート履歴と衝突しない ID */
    private const IMPORT_ID = 990001;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once dirname(__DIR__, 3) . '/modules/Import/actions/Data.php';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->dropTable();
    }

    protected function tearDown(): void
    {
        $this->dropTable();
        parent::tearDown();
    }

    private function user(): \Users
    {
        $user = $GLOBALS['current_user'];
        if (!$user instanceof \Users) {
            throw new \RuntimeException('実行ユーザーが設定されていません');
        }

        return $user;
    }

    private function tableName(): string
    {
        return \Import_Utils_Helper::getDbTableName($this->user(), self::IMPORT_ID);
    }

    private function dropTable(): void
    {
        $this->db->pquery('DROP TABLE IF EXISTS ' . $this->tableName(), []);
    }

    private function createTable(bool $withReasonColumn): void
    {
        $columns = 'id INT PRIMARY KEY AUTO_INCREMENT, status INT DEFAULT 0, recordid INT, potentialname VARCHAR(100)';
        if ($withReasonColumn) {
            $columns .= ', import_failure_reason TEXT';
        }
        $this->db->pquery(
            'CREATE TABLE ' . $this->tableName() . ' (' . $columns . ') ENGINE=MyISAM DEFAULT CHARSET=utf8mb4',
            []
        );
        $this->db->pquery('INSERT INTO ' . $this->tableName() . ' (potentialname) VALUES (?)', ['SPECTEST_IMPORT']);
    }

    private function newAction(string $module): Import_Data_Action
    {
        return new Import_Data_Action([
            'id' => self::IMPORT_ID,
            'module' => $module,
            'field_mapping' => [],
            'merge_type' => null,
            'merge_fields' => null,
            'default_values' => null,
            'lineitem_currency_id' => null,
            'paging' => false,
        ], $this->user());
    }

    private function meta(string $module): object
    {
        return vtws_getModuleHandlerFromName($module, $this->user())->getMeta();
    }

    /** 言語キーの文言（%s は任意の1語以上）に合致するか */
    private function assertReasonMatches(string $languageKey, ?string $reason): void
    {
        $this->assertNotNull($reason, $languageKey . ' の理由が設定されていません');
        $template = vtranslate($languageKey, 'Import');
        $pattern = '/^' . str_replace('%s', '.+', preg_quote($template, '/')) . '$/u';
        $this->assertMatchesRegularExpression($pattern, $reason, $languageKey);
    }

    private function reasonInTable(): ?string
    {
        $result = $this->db->pquery('SELECT import_failure_reason FROM ' . $this->tableName() . ' WHERE id = 1', []);

        return $this->db->query_result($result, 0, 'import_failure_reason');
    }

    // ---- transformForImport() の理由 ---------------------------------------

    /**
     * @return array<string, array{string, array<string,string>, string}>
     */
    public static function transformReasonProvider(): array
    {
        return [
            'R01 必須の選択肢項目が空で既定値も無い' => [
                'Potentials', ['sales_stage' => ''], 'LBL_IMPORT_REASON_MANDATORY_EMPTY',
            ],
            'R02 選択肢に存在しない値' => [
                'Potentials', ['sales_stage' => 'SPECTEST_NO_SUCH_VALUE'], 'LBL_IMPORT_REASON_PICKLIST_NOT_EXIST',
            ],
            'R03 変換後に必須項目が空' => [
                // closingdate は日付型の必須項目で、既定値を補う対象外のため空のまま残る
                'Potentials', ['potentialname' => 'SPECTEST_R03', 'sales_stage' => 'Prospecting'], 'LBL_IMPORT_REASON_MANDATORY_EMPTY',
            ],
        ];
    }

    /**
     * @param array<string,string> $fieldData
     */
    #[DataProvider('transformReasonProvider')]
    public function test_transformForImportが失敗理由を設定して_nullを返す(string $module, array $fieldData, string $languageKey): void
    {
        $this->createTable(true);
        $action = $this->newAction($module);

        $result = $action->transformForImport($fieldData, $this->meta($module), []);

        $this->assertNull($result);
        $this->assertReasonMatches($languageKey, $action->failureReason);
    }

    public function test_R08_参照が複数レコードに一致したら理由を設定して例外を再送出する(): void
    {
        $this->createTable(true);
        $action = $this->newAction('Contacts');
        $cache = ['Accounts' => [
            ['accountid' => 1, 'accountname' => 'SPECTEST_DUP'],
            ['accountid' => 2, 'accountname' => 'SPECTEST_DUP'],
        ]];

        try {
            $action->transformForImport(['account_id' => 'Accounts::::accountname====SPECTEST_DUP'], $this->meta('Contacts'), $cache);
            $this->fail('ImportException が再送出されていません');
        } catch (\ImportException $e) {
            $this->assertReasonMatches('LBL_IMPORT_REASON_REFERENCE_MULTIPLE', $action->failureReason);
        }
    }

    public function test_理由列が無いテーブルでは理由を保持しない(): void
    {
        $this->createTable(false);
        $action = $this->newAction('Potentials');

        $action->transformForImport(['sales_stage' => 'SPECTEST_NO_SUCH_VALUE'], $this->meta('Potentials'), []);

        $this->assertNull($action->failureReason);
    }

    // ---- 活動・見積系の importRecord() -------------------------------------

    public function test_R10_活動の開始日が空なら必須未入力の理由を設定する(): void
    {
        $this->createTable(true);
        $action = $this->newAction('Calendar');

        $result = $action->importRecord(['activitytype' => 'Task', 'date_start' => '0000-00-00'], 'create');

        $this->assertNull($result);
        $this->assertReasonMatches('LBL_IMPORT_REASON_MANDATORY_EMPTY', $action->failureReason);
    }

    public function test_R13_明細行の製品が見つからなければ理由を設定する(): void
    {
        $this->createTable(true);
        $action = $this->newAction('Quotes');
        $focus = \CRMEntity::getInstance('Quotes');
        $lineItems = [['productid' => 'Products::::productname====SPECTEST_NO_SUCH_PRODUCT', 'quantity' => '1']];

        $result = $focus->importRecord($action, ['subject' => 'SPECTEST_R13'], $lineItems, []);

        $this->assertNull($result);
        $this->assertReasonMatches('LBL_IMPORT_REASON_LINEITEM_PRODUCT_NOT_FOUND', $action->failureReason);
    }

    // ---- 例外の分類（R04〜R06）---------------------------------------------

    /**
     * @return array<string, array{string, string}>
     */
    public static function exceptionReasonProvider(): array
    {
        return [
            'R04 権限エラー' => ['ACCESSDENIED', 'LBL_IMPORT_REASON_ACCESS_DENIED'],
            'R05 参照先の種類が不正' => ['REFERENCEINVALID', 'LBL_IMPORT_REASON_REFERENCE_INVALID'],
            'R06 その他のWebサービス例外' => ['DATABASEQUERYERROR', 'LBL_IMPORT_REASON_SAVE_ERROR'],
        ];
    }

    #[DataProvider('exceptionReasonProvider')]
    public function test_保存時の例外を種類別の理由にする(string $errorCodeName, string $languageKey): void
    {
        $this->createTable(true);
        $action = $this->newAction('Accounts');
        $secret = 'SQLSTATE[HY000] SPECTEST_SECRET_MESSAGE';

        $action->setFailureReasonFromException(new WebServiceException(WebServiceErrorCode::${$errorCodeName}, $secret));

        $this->assertReasonMatches($languageKey, $action->failureReason);
        $this->assertStringNotContainsString($secret, (string) $action->failureReason, '例外メッセージは理由に含めない');
    }

    public function test_R06_Webサービス以外の例外もその他の保存エラーにする(): void
    {
        $this->createTable(true);
        $action = $this->newAction('Accounts');

        $action->setFailureReasonFromException(new \RuntimeException('SPECTEST_SECRET_MESSAGE'));

        $this->assertReasonMatches('LBL_IMPORT_REASON_SAVE_ERROR', $action->failureReason);
    }

    public function test_保存時の例外は先に設定された理由を上書きしない(): void
    {
        $this->createTable(true);
        $action = $this->newAction('Potentials');
        $action->setFailureReason('LBL_IMPORT_REASON_PICKLIST_NOT_EXIST', 'Sales Stage', 'Potentials');

        $action->setFailureReasonFromException(new WebServiceException(WebServiceErrorCode::$MANDFIELDSMISSING, 'SPECTEST_SECRET_MESSAGE'));

        $this->assertReasonMatches('LBL_IMPORT_REASON_PICKLIST_NOT_EXIST', $action->failureReason);
    }

    // ---- updateImportStatus() ----------------------------------------------

    /**
     * ステータス値は Import_Data_Action::$IMPORT_RECORD_* と同じ（プロバイダはクラス読み込み前に評価されるため数値で持つ）
     *
     * @return array<string, array{int}>
     */
    public static function failedStatusProvider(): array
    {
        return [
            '失敗' => [5],
            'スキップ' => [2],
        ];
    }

    #[DataProvider('failedStatusProvider')]
    public function test_理由列があれば失敗とスキップで理由を書く(int $status): void
    {
        $this->createTable(true);
        $action = $this->newAction('Potentials');
        $action->setFailureReason('LBL_IMPORT_REASON_SAVE_ERROR');

        $action->updateImportStatus(1, ['id' => null, 'status' => $status]);

        $this->assertSame(vtranslate('LBL_IMPORT_REASON_SAVE_ERROR', 'Import'), $this->reasonInTable());
    }

    #[DataProvider('failedStatusProvider')]
    public function test_C8_理由が未設定のまま失敗やスキップになればR99を書く(int $status): void
    {
        $this->createTable(true);
        $action = $this->newAction('Potentials');

        $action->updateImportStatus(1, ['id' => null, 'status' => $status]);

        $this->assertSame(vtranslate('LBL_IMPORT_REASON_UNKNOWN', 'Import'), $this->reasonInTable());
    }

    /**
     * @return array<string, array{int}>
     */
    public static function succeededStatusProvider(): array
    {
        return [
            '作成' => [1],
            '更新' => [3],
            'マージ' => [4],
        ];
    }

    #[DataProvider('succeededStatusProvider')]
    public function test_C7_成功した行には理由を書かない(int $status): void
    {
        $this->createTable(true);
        $action = $this->newAction('Potentials');
        // 前の行の理由が残っていても成功行には書かない
        $action->setFailureReason('LBL_IMPORT_REASON_SAVE_ERROR');

        $action->updateImportStatus(1, ['id' => null, 'status' => $status]);

        $this->assertNull($this->reasonInTable());
    }

    #[DataProvider('failedStatusProvider')]
    #[DataProvider('succeededStatusProvider')]
    public function test_C10_理由列が無いテーブルではエラーにならず状態だけ更新する(int $status): void
    {
        $this->createTable(false);
        $action = $this->newAction('Potentials');
        $action->failureReason = 'SPECTEST_IGNORED';

        $action->updateImportStatus(1, ['id' => null, 'status' => $status]);

        $result = $this->db->pquery('SELECT status FROM ' . $this->tableName() . ' WHERE id = 1', []);
        $this->assertSame($status, (int) $this->db->query_result($result, 0, 'status'));
    }

    public function test_C13_理由が設定されなかった行に前の行の理由を引き継がない(): void
    {
        $this->createTable(true);
        $this->db->pquery('INSERT INTO ' . $this->tableName() . ' (potentialname) VALUES (?)', ['SPECTEST_IMPORT_2']);
        $action = $this->newAction('Potentials');

        // 1行目: 理由あり。createRecords() の各行の先頭と同じく、2行目の前に初期化する
        $action->setFailureReason('LBL_IMPORT_REASON_PICKLIST_NOT_EXIST', 'Sales Stage', 'Potentials');
        $action->updateImportStatus(1, ['id' => null, 'status' => Import_Data_Action::$IMPORT_RECORD_FAILED]);
        $action->failureReason = null;
        // 2行目: 理由なしで失敗
        $action->updateImportStatus(2, ['id' => null, 'status' => Import_Data_Action::$IMPORT_RECORD_FAILED]);

        $result = $this->db->pquery('SELECT import_failure_reason FROM ' . $this->tableName() . ' WHERE id = 2', []);
        $this->assertSame(
            vtranslate('LBL_IMPORT_REASON_UNKNOWN', 'Import'),
            $this->db->query_result($result, 0, 'import_failure_reason')
        );
    }

    // ---- getImportDetails() ------------------------------------------------

    public function test_詳細は理由列があるときだけ理由を載せる(): void
    {
        $this->createTable(true);
        $this->db->pquery(
            'UPDATE ' . $this->tableName() . ' SET status = ?, import_failure_reason = ? WHERE id = 1',
            [Import_Data_Action::$IMPORT_RECORD_FAILED, '<b>SPECTEST_REASON</b>']
        );

        $details = Import_Data_Action::getImportDetails($this->user(), 'Potentials', self::IMPORT_ID);

        $this->assertTrue($details['hasFailureReason']);
        // fetchByAssoc() が to_html を通すため、テンプレートでエスケープしなくてもタグとして解釈されない
        $this->assertSame('&lt;b&gt;SPECTEST_REASON&lt;/b&gt;', $details['failed'][0]->get('import_failure_reason'));
    }

    public function test_C3_理由列が無ければ詳細に理由の情報を載せない(): void
    {
        $this->createTable(false);
        $this->db->pquery(
            'UPDATE ' . $this->tableName() . ' SET status = ? WHERE id = 1',
            [Import_Data_Action::$IMPORT_RECORD_FAILED]
        );

        $details = Import_Data_Action::getImportDetails($this->user(), 'Potentials', self::IMPORT_ID);

        $this->assertFalse($details['hasFailureReason']);
        $this->assertNull($details['failed'][0]->get('import_failure_reason'));
    }
}
