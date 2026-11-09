<?php

declare(strict_types=1);

namespace Tests\Integration\RecordVersion;

use RecordVersion_Revision_Model;
use RecycleBin_Module_Model;
use Tests\Support\RecordVersionTestCase;

require_once dirname(__DIR__, 2) . '/Support/RecordVersionTestCase.php';

/**
 * 削除したときの過去の版（仕様 動作定義 No.23）
 */
final class PermanentDeleteTest extends RecordVersionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableVersioning();
    }

    public function test_ごみ箱に入れても戻しても過去の版は残る(): void
    {
        $recordId = $this->createPotential('ごみ箱');
        RecordVersion_Revision_Model::revise($recordId, '改版');
        $this->db->pquery('UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid = ?', [$recordId]);
        $this->assertSame(1, $this->versionCount($recordId));

        // ごみ箱の「復元」と同じ処理（RecycleBin_Module_Model::restore() が呼ぶ CRMEntity::restore()）
        \CRMEntity::getInstance(self::MODULE)->restore(self::MODULE, $recordId);
        $this->assertSame(1, $this->versionCount($recordId));
        $this->assertSame(2, $this->currentVersion($recordId));
    }

    public function test_ごみ箱から完全に削除すると過去の版も削除する(): void
    {
        $recordId = $this->createPotential('完全削除');
        RecordVersion_Revision_Model::revise($recordId, '改版');
        $this->db->pquery('UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid = ?', [$recordId]);

        (new RecycleBin_Module_Model())->deleteRecords([$recordId]);
        $this->assertSame(0, $this->versionCount($recordId));
    }

    public function test_ごみ箱に入っていないレコードの過去の版は消さない(): void
    {
        $recordId = $this->createPotential('ごみ箱の外');
        RecordVersion_Revision_Model::revise($recordId, '改版');

        (new RecycleBin_Module_Model())->deleteRecords([$recordId]);
        $this->assertSame(1, $this->versionCount($recordId));
    }
}
