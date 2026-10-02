<?php

namespace Tests\Integration\Documents;

use AppException;
use Documents_FolderPermission;
use Tests\Support\DocumentsTestCase;
use Vtiger_Record_Model;

require_once dirname(__DIR__, 3) . '/tests/Support/DocumentsTestCase.php';

/**
 * フォルダ権限による入口のガード
 *
 * 対応する仕様書: docs/tests/Documents/TS-08_フォルダ権限.md
 *
 * ダウンロード・プレビュー・詳細など、ドキュメントIDを直接受け取る入口は
 * Documents_FolderPermission の判定を通す。判定そのもの（can*）ではなく、
 * 「通さない入口が無いか」を確かめる。
 */
final class FolderPermissionGuardTest extends DocumentsTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once dirname(__DIR__, 3) . '/modules/Documents/utils/FolderPermission.php';
    }

    // ---- 参照権限 -------------------------------------------------------

    public function test_TC_FP_201_参照できないドキュメントは例外で止まる(): void
    {
        $notesId = $this->documentInFolderWithPermissions([]);
        $this->loginAs($this->generalUserId());

        $this->expectException(AppException::class);
        Documents_FolderPermission::assertDocumentAccess($notesId);
    }

    public function test_TC_FP_202_参照できるドキュメントは通る(): void
    {
        $notesId = $this->documentInFolderWithPermissions([['view', 'everyone', null]]);
        $this->loginAs($this->generalUserId());

        Documents_FolderPermission::assertDocumentAccess($notesId);
        $this->addToAssertionCount(1);
    }

    public function test_TC_FP_203_管理者はフォルダ権限が無くても通る(): void
    {
        $notesId = $this->documentInFolderWithPermissions([]);

        Documents_FolderPermission::assertDocumentAccess($notesId);
        $this->addToAssertionCount(1);
    }

    // ---- 編集権限 -------------------------------------------------------

    public function test_TC_FP_204_参照のみのフォルダは編集で止まる(): void
    {
        $notesId = $this->documentInFolderWithPermissions([['view', 'everyone', null]]);
        $this->loginAs($this->generalUserId());

        // 参照は通る
        Documents_FolderPermission::assertDocumentAccess($notesId);

        $this->expectException(AppException::class);
        Documents_FolderPermission::assertDocumentEditable($notesId);
    }

    public function test_TC_FP_205_編集できるフォルダは編集も通る(): void
    {
        $notesId = $this->documentInFolderWithPermissions([['edit', 'everyone', null]]);
        $this->loginAs($this->generalUserId());

        Documents_FolderPermission::assertDocumentEditable($notesId);
        $this->addToAssertionCount(1);
    }

    // ---- リクエスト単位のガード -----------------------------------------

    public function test_TC_FP_206_リクエストの参照ガードが効く(): void
    {
        $notesId = $this->documentInFolderWithPermissions([]);
        $this->loginAs($this->generalUserId());

        $this->expectException(AppException::class);
        Documents_FolderPermission::checkRequestAccess($this->request(['record' => $notesId]));
    }

    public function test_TC_FP_207_リクエストの編集ガードが効く(): void
    {
        $notesId = $this->documentInFolderWithPermissions([['view', 'everyone', null]]);
        $this->loginAs($this->generalUserId());

        $this->expectException(AppException::class);
        Documents_FolderPermission::checkRequestEdit($this->request(['record' => $notesId]));
    }

    public function test_TC_FP_208_ドキュメントIDが無いリクエストは素通りする(): void
    {
        $this->loginAs($this->generalUserId());

        // 新規作成の画面・保存はここでは止めない（保存先の判定は保存時に行う）
        Documents_FolderPermission::checkRequestAccess($this->request([]));
        Documents_FolderPermission::checkRequestEdit($this->request([]));
        $this->addToAssertionCount(2);
    }

    // ---- ダウンロード ---------------------------------------------------

    public function test_TC_FP_209_ダウンロードはモデルの側でも止まる(): void
    {
        $notesId = $this->documentInFolderWithPermissions([]);
        $this->loginAs($this->generalUserId());

        // 入口（アクション）を通さず直接呼んでも止まること
        $recordModel = Vtiger_Record_Model::getInstanceById($notesId, 'Documents');

        $this->expectException(AppException::class);
        $recordModel->downloadFile();
    }

    // ---- 権限行が1件も無いフォルダ ---------------------------------------

    public function test_TC_FP_220_権限行が無いフォルダは作成者なら参照・変更できる(): void
    {
        $userId = $this->generalUserId();
        $folderId = $this->createFolderOwnedBy($userId);
        $notesId = $this->createDocument('NoPermDoc', ['folderid' => $folderId]);
        $this->loginAs($userId);

        $this->assertTrue(
            Documents_FolderPermission::canAccessFolder($folderId),
            'TC-FP-220 作成者はフォルダを参照できる'
        );
        $this->assertTrue(
            Documents_FolderPermission::canEditFolder($folderId),
            'TC-FP-220 作成者はフォルダを変更できる'
        );

        // 中のドキュメントの編集・削除・移動も通る
        Documents_FolderPermission::assertDocumentEditable($notesId);
        $this->addToAssertionCount(1);
    }

    public function test_TC_FP_221_権限行が無いフォルダでも作成者以外は参照できない(): void
    {
        $folderId = $this->createFolderOwnedBy($this->otherUserId());
        $notesId = $this->createDocument('NoPermOtherDoc', ['folderid' => $folderId]);
        $this->loginAs($this->generalUserId());

        $this->expectException(AppException::class);
        Documents_FolderPermission::assertDocumentAccess($notesId);
    }

    public function test_TC_FP_222_権限行が無いフォルダでも作成者に権限設定は与えない(): void
    {
        $userId = $this->generalUserId();
        $folderId = $this->createFolderOwnedBy($userId);
        $this->loginAs($userId);

        $this->assertFalse(
            Documents_FolderPermission::canManageFolderPermissions($folderId),
            'TC-FP-222 権限設定までは与えない'
        );
        // 管理者だけ null（すべて対象）になるため、一般ユーザーでは配列が返る
        $ownedFolderIds = Documents_FolderPermission::getOwnedFolderIds($userId);
        $editableFolderIds = Documents_FolderPermission::getEditableFolderIds($userId);
        $this->assertIsArray($ownedFolderIds);
        $this->assertIsArray($editableFolderIds);

        $this->assertNotContains(
            $folderId,
            $ownedFolderIds,
            'TC-FP-222 オーナーのフォルダ一覧には入らない'
        );
        $this->assertContains(
            $folderId,
            $editableFolderIds,
            'TC-FP-222 変更できるフォルダ一覧には入る'
        );
    }

    public function test_TC_FP_223_権限行があれば作成者でもその内容で判断する(): void
    {
        $userId = $this->generalUserId();
        $folderId = $this->createFolderOwnedBy($userId);
        $this->setFolderPermissions($folderId, [['view', 'everyone', null]]);
        $notesId = $this->createDocument('ViewOnlyOwnDoc', ['folderid' => $folderId]);
        $this->loginAs($userId);

        Documents_FolderPermission::assertDocumentAccess($notesId);

        $this->expectException(AppException::class);
        Documents_FolderPermission::assertDocumentEditable($notesId);
    }

    public function test_TC_FP_224_一覧の条件でも作成者にだけ見える(): void
    {
        $userId = $this->generalUserId();
        $folderId = $this->createFolderOwnedBy($userId);
        $notesId = $this->createDocument('NoPermListedDoc', ['folderid' => $folderId]);

        $this->loginAs($userId);
        $this->assertTrue(
            $this->isVisibleInList($notesId),
            'TC-FP-224 作成者には一覧の条件でも見える'
        );

        $this->loginAs($this->otherUserId());
        $this->assertFalse(
            $this->isVisibleInList($notesId),
            'TC-FP-224 作成者以外には見えない'
        );
    }

    // ---- エクスポート用の条件 -------------------------------------------

    public function test_TC_FP_210_値を埋め込んだ条件でも同じ結果になる(): void
    {
        $this->documentInFolderWithPermissions([]);
        $this->documentInFolderWithPermissions([['view', 'everyone', null]]);
        $this->loginAs($this->generalUserId());

        $condition = Documents_FolderPermission::buildAccessibleCondition();
        $embedded = Documents_FolderPermission::buildAccessibleConditionSql();

        $this->assertNotSame('', $embedded, '一般ユーザーには条件が付く');
        $this->assertSame(
            $this->countDocuments($condition['sql'], $condition['params']),
            $this->countDocuments($embedded, []),
            'TC-FP-210 エクスポート用（値を埋め込んだ条件）でも件数が一致する'
        );
    }

    // ---- 補助 -----------------------------------------------------------

    /**
     * 指定した権限だけを持つフォルダを作り、その中のドキュメントIDを返す
     *
     * @param array<int,array{0:string,1:string,2:string|int|null}> $permissions
     */
    private function documentInFolderWithPermissions(array $permissions): int
    {
        $folderId = $this->createFolder('GuardFolder');
        $this->setFolderPermissions($folderId, $permissions);

        return $this->createDocument('GuardDoc', ['folderid' => $folderId]);
    }

    /**
     * 一覧の絞り込み条件で、そのドキュメントが見えるか
     */
    private function isVisibleInList(int $notesId): bool
    {
        $condition = Documents_FolderPermission::buildAccessibleCondition();
        $result = $this->db->pquery(
            'SELECT vtiger_notes.notesid FROM vtiger_notes
             INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
             WHERE vtiger_crmentity.deleted = 0 AND vtiger_notes.notesid = ?' . $condition['sql'],
            array_merge([$notesId], $condition['params'])
        );
        $this->assertNotFalse($result, 'クエリが実行できる');

        return $this->db->num_rows($result) > 0;
    }

    /**
     * 条件を付けてドキュメント件数を数える
     *
     * @param array<int,mixed> $params
     */
    private function countDocuments(string $conditionSql, array $params): int
    {
        $result = $this->db->pquery(
            'SELECT COUNT(*) AS cnt FROM vtiger_notes
             INNER JOIN vtiger_crmentity ON vtiger_crmentity.crmid = vtiger_notes.notesid
             WHERE vtiger_crmentity.deleted = 0' . $conditionSql,
            $params
        );
        $this->assertNotFalse($result, 'クエリが実行できる');

        return (int) $this->db->query_result($result, 0, 'cnt');
    }

    /**
     * 指定ユーザーが作成した、権限行が1件も無いフォルダを作る
     */
    private function createFolderOwnedBy(int $userId): int
    {
        $folderId = $this->createFolder('NoPermFolder');
        $this->db->pquery(
            'UPDATE vtiger_attachmentsfolder SET createdby = ? WHERE folderid = ?',
            [$userId, $folderId]
        );
        // 作成時に権限行が入る実装に備えて空にしておく
        $this->setFolderPermissions($folderId, []);

        return $folderId;
    }

    /**
     * 管理者ではないユーザーのID（いなければテストを飛ばす）
     */
    private function generalUserId(): int
    {
        return $this->generalUserIdAt(0);
    }

    /**
     * generalUserId() とは別の一般ユーザーのID（いなければテストを飛ばす）
     */
    private function otherUserId(): int
    {
        return $this->generalUserIdAt(1);
    }

    /**
     * 管理者ではないユーザーを順に取る
     */
    private function generalUserIdAt(int $index): int
    {
        $result = $this->db->pquery(
            "SELECT id FROM vtiger_users
             WHERE is_admin = 'off' AND deleted = 0 AND status = 'Active' ORDER BY id",
            []
        );
        if ($result === false || $this->db->num_rows($result) <= $index) {
            $this->markTestSkipped('一般ユーザーが足りないため飛ばします');
        }

        return (int) $this->db->query_result($result, $index, 'id');
    }
}
