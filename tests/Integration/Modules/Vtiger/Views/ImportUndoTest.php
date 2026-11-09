<?php

declare(strict_types=1);

namespace Tests\Integration\Modules\Vtiger\Views;

use PearDatabase;
use PHPUnit\Framework\TestCase;
use Tests\Support\ImportUndoTestSupport;
use Vtiger_Import_View;
use Vtiger_Record_Model;
use Vtiger_Request;

require_once dirname(__DIR__, 4) . '/Support/ImportUndoTestSupport.php';

/**
 * インポート結果画面の「最新のインポートを取り消す」（#1967）
 *
 * Vtiger_Import_View::undoImport() が、完了済み（キュー status=4）のインポートでも
 * URL の importid から一時テーブル（vtiger_import_{userid}_{importid}）を特定し、
 * 取り込んだレコードを削除できることを確認する。
 */
final class ImportUndoTest extends TestCase
{
    use ImportUndoTestSupport;

    /** テストデータの目印（後始末で使う） */
    private const PREFIX = 'SPECTEST_UNDO_';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::requireTestDatabase();
        self::bootFrevoCrm();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = PearDatabase::getInstance();
        $this->loginAs(self::$importUserId);
    }

    protected function tearDown(): void
    {
        $this->dropCompletedImport();
        $result = $this->db->pquery(
            'SELECT accountid FROM vtiger_account WHERE accountname LIKE ?',
            [self::PREFIX . '%']
        );
        while ($row = $this->db->fetchByAssoc($result)) {
            $this->db->pquery('DELETE FROM vtiger_crmentity WHERE crmid = ?', [$row['accountid']]);
            $this->db->pquery('DELETE FROM vtiger_account WHERE accountid = ?', [$row['accountid']]);
        }
        parent::tearDown();
    }

    public function test_完了済みインポートを取り消すと取り込んだレコードが削除される(): void
    {
        $recordId = $this->createAccount('1');
        $this->createCompletedImport('Accounts', [$recordId]);

        $output = $this->runUndoImport('Accounts', static function (Vtiger_Request $request): void {
            (new Vtiger_Import_View())->undoImport($request);
        });

        $this->assertFalse(isRecordExists($recordId), '取り込んだレコードが削除されていない');
        $this->assertSame(1, $this->deletedCount($output), '削除件数が 1 と表示されていない');
    }

    private function createAccount(string $suffix): int
    {
        $recordModel = Vtiger_Record_Model::getCleanInstance('Accounts');
        $recordModel->set('mode', '');
        $recordModel->set('accountname', self::PREFIX . $suffix);
        $recordModel->set('assigned_user_id', self::$importUserId);
        $recordModel->save();

        return (int) $recordModel->getId();
    }
}
