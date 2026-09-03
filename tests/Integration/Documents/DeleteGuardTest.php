<?php

namespace Tests\Integration\Documents;

use AppException;
use RecycleBin_Module_Model;
use Tests\Support\DocumentsTestCase;
use Vtiger_Record_Model;

require_once dirname(__DIR__, 3) . '/tests/Support/DocumentsTestCase.php';

/**
 * 電帳法対象ドキュメントの削除禁止
 *
 * 対応する仕様書: docs/tests/Documents/TS-05_電帳法適合と監査ログ.md
 *
 * 電帳法対象（書類区分あり）のドキュメントは保存義務があるため、
 * ごみ箱への移動も、ごみ箱からの完全削除も許可しない。
 */
final class DeleteGuardTest extends DocumentsTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        require_once dirname(__DIR__, 3) . '/modules/RecycleBin/models/Module.php';
    }

    // ---- ごみ箱への移動 -------------------------------------------------

    public function test_TC_CM_120_121_電帳法対象はごみ箱へ移動できない(): void
    {
        $notesId = $this->createComplianceDocument('DeleteGuardCompliance');

        try {
            $this->trashDocument($notesId);
            $this->fail('TC-CM-120 例外が投げられなかった');
        } catch (AppException $e) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame(
            0,
            $this->deletedFlagOf($notesId),
            'TC-CM-121 ごみ箱へ移動されていない（deleted=0 のまま）'
        );
    }

    public function test_TC_CM_122_123_電帳法対象外はごみ箱へ移動できる(): void
    {
        $notesId = $this->createDocument('DeleteGuardNormal');

        $this->trashDocument($notesId);

        $this->assertSame(1, $this->deletedFlagOf($notesId), 'TC-CM-122/123 deleted=1 になる');
    }

    // ---- 完全削除 -------------------------------------------------------

    public function test_TC_CM_124_電帳法対象はごみ箱から完全削除できない(): void
    {
        $notesId = $this->createTrashedComplianceDocument('DeleteGuardInTrash');

        (new RecycleBin_Module_Model())->deleteRecords([$notesId]);

        $this->assertSame(1, $this->deletedFlagOf($notesId), 'TC-CM-124 残っている');
    }

    public function test_TC_CM_125_127_削除対象から電帳法対象を除く(): void
    {
        $inTrash = $this->createTrashedComplianceDocument('DeleteGuardInTrash');
        $normal = $this->createDocument('DeleteGuardNormal');
        $this->trashDocument($normal);

        $this->assertSame(
            [],
            RecycleBin_Module_Model::excludeNonDeletableRecords([$inTrash]),
            'TC-CM-125 電帳法対象は除かれる'
        );
        $this->assertSame(
            [$normal],
            RecycleBin_Module_Model::excludeNonDeletableRecords([$normal]),
            'TC-CM-126 電帳法対象外は残る'
        );
        $this->assertSame(
            [$normal],
            RecycleBin_Module_Model::excludeNonDeletableRecords([$inTrash, $normal]),
            'TC-CM-127 混在時は電帳法対象だけを除く'
        );
    }

    public function test_TC_CM_128_129_ごみ箱を空にしても電帳法対象は残る(): void
    {
        $inTrash = $this->createTrashedComplianceDocument('DeleteGuardInTrash');
        $normal = $this->createDocument('DeleteGuardNormal');
        $this->trashDocument($normal);

        (new RecycleBin_Module_Model())->emptyRecycleBin();

        $this->assertSame(1, $this->deletedFlagOf($inTrash), 'TC-CM-128 電帳法対象は残る');
        $this->assertNull($this->deletedFlagOf($normal), 'TC-CM-129 電帳法対象外は消える');
    }

    // ---- 削除の記録 -----------------------------------------------------

    public function test_TC_CM_140_141_削除が監査ログに記録される(): void
    {
        // 詳細画面からの削除も一覧の一括削除も Vtiger_Record_Model::delete() を通る。
        // 記録を呼び出し側に任せていた頃は、呼んでいない経路の削除が履歴に残らなかった
        $notesId = $this->createDocument('DeleteGuardAuditLog');

        $this->trashDocument($notesId);

        $this->assertSame(1, $this->deleteLogCountOf($notesId), 'TC-CM-140 経路によらず記録される');
        $this->assertStringContainsString(
            'DeleteGuardAuditLog',
            $this->deleteLogDetailOf($notesId),
            'TC-CM-141 削除時点のタイトルが残る'
        );
    }

    public function test_TC_CM_142_削除がブロックされた場合は記録しない(): void
    {
        $notesId = $this->createComplianceDocument('DeleteGuardCompliance');

        try {
            $this->trashDocument($notesId);
        } catch (AppException $e) {
            // 拒否されるのが期待どおり
        }

        $this->assertSame(0, $this->deleteLogCountOf($notesId), 'TC-CM-142');
    }

    // ---- ヘルパ ---------------------------------------------------------

    /** 電帳法対象（書類区分あり）のドキュメントを作る */
    private function createComplianceDocument(string $suffix): int
    {
        return $this->createDocument($suffix, [
            'document_category' => 'invoice',
            'preservation_type' => 'electronic_transaction',
        ]);
    }

    /** ごみ箱にある電帳法対象のドキュメントを作る（過去に移動されたものを再現） */
    private function createTrashedComplianceDocument(string $suffix): int
    {
        $notesId = $this->createDocument($suffix, [
            'document_category' => 'receipt',
            'preservation_type' => 'scanner',
        ]);
        $this->db->pquery('UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid = ?', [$notesId]);

        return $notesId;
    }

    /** ごみ箱へ移動する（一覧・詳細・一括削除はいずれもこの経路） */
    private function trashDocument(int $notesId): void
    {
        Vtiger_Record_Model::getInstanceById($notesId, 'Documents')->delete();
    }

    /**
     * vtiger_crmentity.deleted を返す
     *
     * @return int|null 1 ならごみ箱。レコードが消えていれば null
     */
    private function deletedFlagOf(int $notesId): ?int
    {
        $result = $this->db->pquery(
            'SELECT deleted FROM vtiger_crmentity WHERE crmid = ?',
            [$notesId]
        );
        if ($result === false || $this->db->num_rows($result) === 0) {
            return null;
        }

        return (int) $this->db->query_result($result, 0, 'deleted');
    }

    /** delete の監査ログ件数 */
    private function deleteLogCountOf(int $notesId): int
    {
        $result = $this->db->pquery(
            "SELECT COUNT(*) AS cnt FROM vtiger_notes_audit_log
             WHERE notesid = ? AND action_type = 'delete'",
            [$notesId]
        );

        return ($result === false) ? 0 : (int) $this->db->query_result($result, 0, 'cnt');
    }

    /** 最後に記録された delete ログの action_detail */
    private function deleteLogDetailOf(int $notesId): string
    {
        $result = $this->db->pquery(
            "SELECT action_detail FROM vtiger_notes_audit_log
             WHERE notesid = ? AND action_type = 'delete'
             ORDER BY audit_id DESC LIMIT 1",
            [$notesId]
        );
        if ($result === false || $this->db->num_rows($result) === 0) {
            return '';
        }

        return (string) $this->db->query_result($result, 0, 'action_detail');
    }
}
