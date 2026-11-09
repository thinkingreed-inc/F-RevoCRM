<?php

declare(strict_types=1);

namespace Tests\Integration\RecordVersion;

use RecordVersion_Revision_Model;
use RecordVersion_Version_Model;
use Tests\Support\RecordVersionTestCase;

require_once dirname(__DIR__, 2) . '/Support/RecordVersionTestCase.php';

/**
 * 改版の処理（仕様 表B、動作定義 No.6・No.25）
 */
final class RevisionTest extends RecordVersionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableVersioning();
    }

    public function test_改版理由が空なら改版しない_表Bルール1(): void
    {
        $recordId = $this->createPotential('理由なし');
        $result = RecordVersion_Revision_Model::revise($recordId, "  \n ");
        $this->assertFalse($result['success']);
        $this->assertSame(vtranslate('LBL_REASON_REQUIRED', 'RecordVersion'), $result['message']);
        $this->assertSame(1, $this->currentVersion($recordId));
        $this->assertSame(0, $this->versionCount($recordId));
    }

    public function test_改版理由が1000文字を超えたら改版しない(): void
    {
        $recordId = $this->createPotential('理由が長い');
        $this->assertFalse(RecordVersion_Revision_Model::revise($recordId, str_repeat('あ', 1001))['success']);
        $this->assertTrue(RecordVersion_Revision_Model::revise($recordId, str_repeat('あ', 1000))['success'], '1,000 文字ちょうどは改版できる');
    }

    public function test_改版すると今の内容を過去の版に保存し版番号を上げ更新履歴に1件残す_表Bルール4(): void
    {
        $recordId = $this->createPotential('改版', ['amount' => 120000]);
        $result = RecordVersion_Revision_Model::revise($recordId, '金額を見直すため');

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['version_no']);
        $this->assertSame(2, $this->currentVersion($recordId));

        $version = RecordVersion_Version_Model::getByNumber($recordId, 1);
        $this->assertNotNull($version, '改版する前の版（版 1）を保存する');
        $this->assertSame('金額を見直すため', $version->getReason());
        $fields = $this->snapshotFieldsByName($recordId);
        $this->assertSame(self::PREFIX . '改版', $fields['potentialname']['value']);
        $this->assertSame('1', $fields['record_version']['value'], '過去の版の「版」は改版する前の版番号');
        $this->assertSame('Potential Name', $fields['potentialname']['label']);
        $this->assertNotSame('', $fields['potentialname']['label_ja'], '日本語の表示名も残す');
        $this->assertNotSame('', $fields['potentialname']['label_en'], '英語の表示名も残す');
        $this->assertArrayHasKey('display', $fields['related_to'], '関連項目は当時の名前も残す');
        $this->assertArrayHasKey('display', $fields['assigned_user_id'], '担当は当時の名前も残す');

        $this->assertSame(1, $this->versionChangeLogCount($recordId), '更新履歴には版の変更を 1 件だけ記録する');
        $change = $this->scalar(
            "SELECT CONCAT(d.prevalue, ':', d.postvalue) FROM vtiger_modtracker_basic b JOIN vtiger_modtracker_detail d ON d.id = b.id
             WHERE b.crmid = ? AND b.status = '0' AND d.fieldname = 'record_version'",
            [$recordId]
        );
        $this->assertSame('1:2', $change, '「版 1→2」と記録する');
    }

    public function test_ダイアログを開いたあとにほかの利用者が改版していたら改版しない_表Bルール2(): void
    {
        $recordId = $this->createPotential('同時');
        $this->assertTrue(RecordVersion_Revision_Model::revise($recordId, '先に改版', 1)['success']);

        $result = RecordVersion_Revision_Model::revise($recordId, 'あとから改版', 1);
        $this->assertFalse($result['success']);
        $this->assertSame(vtranslate('LBL_ALREADY_REVISED', 'RecordVersion'), $result['message']);
        $this->assertSame(2, $this->currentVersion($recordId));
        $this->assertSame(1, $this->versionCount($recordId));
    }

    public function test_改版の前のイベントで止める理由が返れば改版しない_表Bルール3(): void
    {
        $this->registerTestHandler(RecordVersion_Revision_Model::EVENT_BEFORE, 'RecordVersionTestStopHandler');
        $recordId = $this->createPotential('止める');

        $result = RecordVersion_Revision_Model::revise($recordId, '止められる');
        $this->assertFalse($result['success']);
        $this->assertSame(\RecordVersionTestStopHandler::MESSAGE, $result['message'], '返った理由をそのままメッセージにする');
        $this->assertSame(1, $this->currentVersion($recordId));
        $this->assertSame(0, $this->versionCount($recordId));
    }

    public function test_改版のあとのイベントで失敗しても改版は取り消さない(): void
    {
        $this->registerTestHandler(RecordVersion_Revision_Model::EVENT_AFTER, 'RecordVersionTestFailingAfterHandler');
        $recordId = $this->createPotential('あとで失敗');

        $result = RecordVersion_Revision_Model::revise($recordId, '後処理が失敗する');
        $this->assertTrue($result['success']);
        $this->assertSame(2, $this->currentVersion($recordId));
        $this->assertSame(1, $this->versionCount($recordId));
    }

    public function test_途中で失敗したら過去の版も版番号も変えない(): void
    {
        $recordId = $this->createPotential('途中で失敗');
        // 同じ版番号の過去の版を先に入れておき、保存の途中で一意制約に当たるようにする
        RecordVersion_Version_Model::insert(
            $recordId,
            self::MODULE,
            1,
            '先にある版',
            self::ADMIN_USER_ID,
            '2026-01-01 00:00:00',
            RecordVersion_Version_Model::encodeSnapshot(['fields' => []])
        );
        $dieOnError = $this->db->dieOnError;
        $this->db->dieOnError = false;
        try {
            $result = RecordVersion_Revision_Model::revise($recordId, '失敗する改版');
        } finally {
            $this->db->dieOnError = $dieOnError;
        }

        $this->assertFalse($result['success']);
        $this->assertSame(1, $this->currentVersion($recordId), '版番号は変えない');
        $this->assertSame(1, $this->versionCount($recordId), '過去の版は増えない');
        $this->assertSame(0, $this->versionChangeLogCount($recordId), '更新履歴にも残さない');
    }

    public function test_過去の版がDBに保存できない大きさなら改版しない_動作定義No6(): void
    {
        $recordId = $this->createPotential('大きすぎる');
        RecordVersion_Revision_Model::$maxSnapshotBytes = 10;
        try {
            $result = RecordVersion_Revision_Model::revise($recordId, '保存できない大きさ');
        } finally {
            RecordVersion_Revision_Model::$maxSnapshotBytes = null;
        }
        $this->assertFalse($result['success']);
        $this->assertSame(vtranslate('LBL_TOO_MANY_RELATED_RECORDS', 'RecordVersion'), $result['message']);
        $this->assertSame(1, $this->currentVersion($recordId), '版番号は変えない');
        $this->assertSame(0, $this->versionCount($recordId), '過去の版は保存しない');
        $this->assertSame(0, $this->versionChangeLogCount($recordId), '更新履歴にも残さない');
    }

    public function test_メモリが足りなくなりそうなら改版しない_動作定義No6(): void
    {
        $recordId = $this->createPotential('メモリ不足');
        \RecordVersion_Snapshot_Model::$memoryLimitBytes = 1;
        try {
            $result = RecordVersion_Revision_Model::revise($recordId, 'メモリが足りない');
        } finally {
            \RecordVersion_Snapshot_Model::$memoryLimitBytes = null;
        }
        $this->assertFalse($result['success']);
        $this->assertSame(vtranslate('LBL_TOO_MANY_RELATED_RECORDS', 'RecordVersion'), $result['message']);
        $this->assertSame(1, $this->currentVersion($recordId));
        $this->assertSame(0, $this->versionCount($recordId));
    }

    public function test_ほかの機能から改版者を指定して呼べる(): void
    {
        $recordId = $this->createPotential('改版者指定');
        $userId = $this->getNonAdminUserId();

        $result = RecordVersion_Revision_Model::revise($recordId, '別の機能から', null, $userId, false);
        $this->assertTrue($result['success']);
        $version = RecordVersion_Version_Model::getByNumber($recordId, 1);
        $this->assertNotNull($version);
        $this->assertSame($userId, $version->getRevisedById());
        $this->assertSame(getUserFullName($userId), $version->getRevisedByName());
    }

    public function test_削除されたレコードは改版しない(): void
    {
        $recordId = $this->createPotential('削除済み');
        $this->db->pquery('UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid = ?', [$recordId]);
        $this->assertFalse(RecordVersion_Revision_Model::revise($recordId, 'ごみ箱の中')['success']);
    }
}
