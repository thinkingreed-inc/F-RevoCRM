<?php

namespace Tests\Integration\Documents;

use Documents_ComplianceChecker;
use Tests\Support\DocumentsTestCase;
use Vtiger_Record_Model;

require_once dirname(__DIR__, 3) . '/tests/Support/DocumentsTestCase.php';
require_once dirname(__DIR__, 3) . '/modules/Documents/utils/ComplianceChecker.php';

/**
 * 保存時の適合判定
 *
 * 対応する仕様書: docs/tests/Documents/TS-05_電帳法適合と監査ログ.md
 *
 * 初回登録の時点で適合判定が付くことを確認する。
 * 以前は ComplianceAPI を別途呼ぶ画面操作に依存していたため、
 * 新規登録直後は適合状態が空のままだった。
 */
final class ComplianceOnSaveTest extends DocumentsTestCase
{
    // ---- 初回登録で判定される -------------------------------------------

    public function test_TC_CM_100_102_保存区分が無ければ初回登録で不適合になる(): void
    {
        $notesId = $this->createDocument('ComplianceNoPreservation', [
            'document_category' => 'invoice',
        ]);

        $compliance = $this->complianceOf($notesId);
        $this->assertSame('non_compliant', $compliance['status'], 'TC-CM-100 初回登録で判定が付く');
        $this->assertStringContainsString(
            'LBL_ISSUE_NO_PRESERVATION_TYPE',
            (string) $compliance['notes'],
            'TC-CM-101 不適合理由に保存区分が含まれる'
        );
        $this->assertNotEmpty($compliance['checked_at'], 'TC-CM-102 判定日時が記録される');
    }

    public function test_TC_CM_103_104_スキャナ保存で解像度が空なら不適合になる(): void
    {
        $notesId = $this->createDocument('ComplianceNoResolution', [
            'document_category' => 'invoice',
            'preservation_type' => 'scanner',
        ]);

        $compliance = $this->complianceOf($notesId);
        $this->assertSame('non_compliant', $compliance['status'], 'TC-CM-103');
        $this->assertStringContainsString(
            'LBL_ISSUE_LOW_SCAN_RESOLUTION',
            (string) $compliance['notes'],
            'TC-CM-104 不適合理由に解像度が含まれる'
        );
    }

    public function test_TC_CM_105_解像度が要件未満なら不適合になる(): void
    {
        $notesId = $this->createDocument('ComplianceLowResolution', [
            'document_category' => 'invoice',
            'preservation_type' => 'scanner',
            'scan_resolution_dpi' => 199,
        ]);

        $this->assertSame('non_compliant', $this->complianceOf($notesId)['status'], 'TC-CM-105');
    }

    // ---- 再保存で変わらない ---------------------------------------------

    public function test_TC_CM_106_107_再保存しても判定は変わらない(): void
    {
        $notesId = $this->createDocument('ComplianceNoResolution', [
            'document_category' => 'invoice',
            'preservation_type' => 'scanner',
        ]);
        $before = $this->complianceOf($notesId);

        // 画面の「編集 → そのまま保存」と同じ
        $recordModel = Vtiger_Record_Model::getInstanceById($notesId, 'Documents');
        $recordModel->set('mode', 'edit');
        $recordModel->save();

        $after = $this->complianceOf($notesId);
        $this->assertSame($before['status'], $after['status'], 'TC-CM-106 判定は変わらない');
        $this->assertSame($before['notes'], $after['notes'], 'TC-CM-107 不適合理由も変わらない');
    }

    // ---- 電帳法対象外 ---------------------------------------------------

    public function test_TC_CM_108_書類区分が無ければ判定しない(): void
    {
        $notesId = $this->createDocument('ComplianceNotTarget');

        $status = $this->complianceOf($notesId)['status'];
        $this->assertTrue(
            $status === null || $status === '',
            'TC-CM-108 適合状態は空のまま: ' . var_export($status, true)
        );
    }

    // ---- 条件を満たすと適合に変わる -------------------------------------

    public function test_TC_CM_109_要件を満たすと保存時に適合へ変わる(): void
    {
        $notesId = $this->createDocument('ComplianceLowResolution', [
            'document_category' => 'invoice',
            'preservation_type' => 'scanner',
            'scan_resolution_dpi' => 199,
        ]);

        $category = $this->findCategoryWithoutRelation($notesId);
        if ($category === null) {
            $this->markTestSkipped('TC-CM-109 関連レコードが不要な書類区分が無い');
        }

        $recordModel = Vtiger_Record_Model::getInstanceById($notesId, 'Documents');
        $recordModel->set('mode', 'edit');
        $recordModel->set('document_category', $category);
        $recordModel->set('scan_resolution_dpi', 300);
        $recordModel->save();

        $this->assertSame('compliant', $this->complianceOf($notesId)['status'], 'TC-CM-109');
    }

    // ---- ヘルパ ---------------------------------------------------------

    /**
     * 保存されている適合状態と不適合理由を返す
     *
     * @return array{status:mixed,notes:mixed,checked_at:mixed}
     */
    private function complianceOf(int $notesId): array
    {
        $result = $this->db->pquery(
            'SELECT compliance_status, compliance_notes, compliance_checked_at
             FROM vtiger_notes WHERE notesid = ?',
            [$notesId]
        );
        $row = $this->db->query_result_rowdata($result, 0);

        return [
            'status' => $row['compliance_status'],
            'notes' => $row['compliance_notes'],
            'checked_at' => $row['compliance_checked_at'],
        ];
    }

    /**
     * 関連レコードの紐付けが不要な書類区分を探す
     *
     * 適合まで持っていくには関連レコードの条件も満たす必要があるため、
     * その条件が無い書類区分を選ぶ。
     */
    private function findCategoryWithoutRelation(int $notesId): ?string
    {
        foreach (['other', 'invoice'] as $candidate) {
            $related = Documents_ComplianceChecker::checkRelatedRecords($notesId, $candidate);
            if (!empty($related['has_related'])) {
                return $candidate;
            }
        }

        return null;
    }
}
