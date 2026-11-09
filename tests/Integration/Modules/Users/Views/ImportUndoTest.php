<?php

declare(strict_types=1);

namespace Tests\Integration\Modules\Users\Views;

use CRMEntity;
use PearDatabase;
use PHPUnit\Framework\TestCase;
use Tests\Support\ImportUndoTestSupport;
use Users_Import_View;
use Users_Record_Model;
use Vtiger_Request;

require_once dirname(__DIR__, 4) . '/Support/ImportUndoTestSupport.php';

/**
 * ユーザーのインポート結果画面の「最新のインポートを取り消す」（#1967）
 *
 * Users_Import_View の取り消しが、完了済み（キュー status=4）のインポートでも
 * URL の importid から一時テーブルを特定し、取り込んだユーザーを削除できることを確認する。
 */
final class ImportUndoTest extends TestCase
{
    use ImportUndoTestSupport;

    /** テストデータの目印（後始末で使う） */
    private const PREFIX = 'spectest_undo_';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::requireTestDatabase();
        self::bootFrevoCrm();
    }

    /** @var array<string,string> 退避した権限ファイル（パス => 内容） */
    private array $savedPrivilegeFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = PearDatabase::getInstance();
        $this->savedPrivilegeFiles = $this->backupPrivilegeFiles();
        $this->loginAs(self::$importUserId);
    }

    protected function tearDown(): void
    {
        try {
            $this->dropCompletedImport();
            $result = $this->db->pquery('SELECT id FROM vtiger_users WHERE user_name LIKE ?', [self::PREFIX . '%']);
            while ($row = $this->db->fetchByAssoc($result)) {
                Users_Record_Model::deleteUserPermanently((int) $row['id'], self::$importUserId);
            }
        } finally {
            $this->restorePrivilegeFiles($this->savedPrivilegeFiles);
        }
        parent::tearDown();
    }

    /**
     * user_privileges/ の権限ファイルを退避する
     *
     * ユーザーの保存は、作ったユーザー以外の分も含めて権限ファイルを作り直す。
     * user_privileges/ は開発・E2E 環境と共有で、tests/bootstrap.php の DB 切替では守れないため、
     * テストの前後で丸ごと退避・復元する。
     *
     * @return array<string,string>
     */
    private function backupPrivilegeFiles(): array
    {
        $files = [];
        foreach ($this->privilegeFilePaths() as $path) {
            $files[$path] = (string) file_get_contents($path);
        }

        return $files;
    }

    /** @param array<string,string> $files backupPrivilegeFiles() の戻り値 */
    private function restorePrivilegeFiles(array $files): void
    {
        foreach ($this->privilegeFilePaths() as $path) {
            if (!array_key_exists($path, $files)) {
                unlink($path);
            }
        }
        foreach ($files as $path => $contents) {
            file_put_contents($path, $contents);
        }
    }

    /** @return list<string> */
    private function privilegeFilePaths(): array
    {
        $paths = glob(dirname(__DIR__, 5) . '/user_privileges/*_privileges_*.php');

        return $paths === false ? [] : $paths;
    }

    public function test_完了済みインポートを取り消すと取り込んだユーザーが削除される(): void
    {
        $userId = $this->createUser('1');
        $this->createCompletedImport('Users', [$userId]);

        $output = $this->runUndoImport('Users', static function (Vtiger_Request $request): void {
            (new Users_Import_View())->process($request);
        });

        $this->assertSame(0, $this->countUsers($userId), '取り込んだユーザーが削除されていない');
        $this->assertSame(1, $this->deletedCount($output), '削除件数が 1 と表示されていない');
    }

    private function createUser(string $suffix): int
    {
        $user = CRMEntity::getInstance('Users');
        $user->column_fields['user_name'] = self::PREFIX . $suffix;
        $user->column_fields['last_name'] = 'SpecTestUndo' . $suffix;
        $user->column_fields['email1'] = self::PREFIX . $suffix . '@example.com';
        $user->column_fields['roleid'] = 'H4';
        $user->column_fields['status'] = 'Active';
        $user->column_fields['is_admin'] = 'off';
        $user->column_fields['user_password'] = 'Passw0rd-Spec';
        $user->column_fields['confirm_password'] = 'Passw0rd-Spec';
        $user->save('Users');

        return (int) $user->id;
    }

    private function countUsers(int $userId): int
    {
        $result = $this->db->pquery('SELECT COUNT(*) AS cnt FROM vtiger_users WHERE id = ?', [$userId]);

        return (int) $this->db->query_result($result, 0, 'cnt');
    }
}
