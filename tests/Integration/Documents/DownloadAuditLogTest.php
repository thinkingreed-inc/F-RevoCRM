<?php

namespace Tests\Integration\Documents;

use Tests\Support\DocumentsTestCase;
use Vtiger_Record_Model;

require_once dirname(__DIR__, 3) . '/tests/Support/DocumentsTestCase.php';

/**
 * ダウンロードの記録（変更履歴／監査ログ）
 *
 * 対応する仕様書: docs/tests/Documents/TS-05_電帳法適合と監査ログ.md
 *
 * 記録は経路ごとの呼び出しに任せず、ファイルを送り出す
 * Documents_Record_Model::downloadFile() で残す。呼び出し側に任せていた頃は、
 * 詳細画面・一覧からのダウンロード（action=DownloadFile）が履歴に出なかった。
 */
final class DownloadAuditLogTest extends DocumentsTestCase
{
    /**
     * テストで作った添付ファイルのID（後始末で消す）
     *
     * @var array<int,int>
     */
    private array $attachmentIds = [];

    protected function tearDown(): void
    {
        foreach ($this->attachmentIds as $attachmentsId) {
            $this->db->pquery(
                'DELETE FROM vtiger_seattachmentsrel WHERE attachmentsid = ?',
                [$attachmentsId]
            );
            $this->db->pquery(
                'DELETE FROM vtiger_attachments WHERE attachmentsid = ?',
                [$attachmentsId]
            );
            $this->db->pquery(
                'DELETE FROM vtiger_crmentity WHERE crmid = ?',
                [$attachmentsId]
            );
        }
        $this->attachmentIds = [];
        parent::tearDown();
    }

    public function test_TC_CM_150_ダウンロードが記録される(): void
    {
        $notesId = $this->createDocumentWithAttachment('DownloadAuditLog');

        $recordModel = Vtiger_Record_Model::getInstanceById($notesId, 'Documents');
        $recordModel->downloadFile();

        $this->assertSame(
            1,
            $this->downloadLogCountOf($notesId),
            'TC-CM-150 ダウンロードが監査ログに残る'
        );
    }

    public function test_TC_CM_151_URLのドキュメントは記録しない(): void
    {
        // 外部URLはファイルを送り出さないため、記録する対象が無い
        $notesId = $this->createDocument('DownloadAuditLogExternal');

        $recordModel = Vtiger_Record_Model::getInstanceById($notesId, 'Documents');
        $recordModel->downloadFile();

        $this->assertSame(0, $this->downloadLogCountOf($notesId), 'TC-CM-151');
    }

    // ---- ヘルパ ---------------------------------------------------------

    /**
     * 添付ファイル付き（filelocationtype = I）のドキュメントを作る
     *
     * 実ファイルは置かない。downloadFile() は記録したあとに送出へ進み、
     * ファイルが無ければ何も出力せずに終わるため、テストの出力は汚れない。
     */
    private function createDocumentWithAttachment(string $suffix): int
    {
        $notesId = $this->createDocument($suffix, [
            'filelocationtype' => 'I',
            'filename' => $suffix . '.pdf',
            'filestatus' => 1,
        ]);

        // 添付ファイルのIDは crmentity のIDを使う（vtiger_attachments は crmid を参照する）
        $attachmentsId = $this->db->getUniqueID('vtiger_crmentity');
        $this->db->pquery(
            'INSERT INTO vtiger_crmentity (crmid, setype, description, createdtime, modifiedtime, smcreatorid, smownerid, deleted)
             VALUES (?, ?, ?, ?, ?, ?, ?, 0)',
            [
                $attachmentsId,
                'Documents Attachment',
                '',
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s'),
                self::ADMIN_USER_ID,
                self::ADMIN_USER_ID,
            ]
        );

        $this->db->pquery(
            'INSERT INTO vtiger_attachments (attachmentsid, name, description, type, path, storedname)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $attachmentsId,
                $suffix . '.pdf',
                '',
                'application/pdf',
                'storage/spec-test-missing/',
                $suffix . '.pdf',
            ]
        );
        $this->db->pquery(
            'INSERT INTO vtiger_seattachmentsrel (crmid, attachmentsid) VALUES (?, ?)',
            [$notesId, $attachmentsId]
        );
        $this->attachmentIds[] = $attachmentsId;

        return $notesId;
    }

    /** ダウンロードの記録件数 */
    private function downloadLogCountOf(int $notesId): int
    {
        $result = $this->db->pquery(
            "SELECT COUNT(*) AS cnt FROM vtiger_notes_audit_log
             WHERE notesid = ? AND action_type = 'download'",
            [$notesId]
        );

        return ($result === false) ? 0 : (int) $this->db->query_result($result, 0, 'cnt');
    }
}
