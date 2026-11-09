<?php

declare(strict_types=1);

namespace Tests\Integration\RecordVersion;

use RecordVersion_Config_Model;
use RecordVersion_Display_Model;
use RecordVersion_Revision_Model;
use RecordVersion_Util_Helper;
use Tests\Support\RecordVersionTestCase;
use Vtiger_Record_Model;

require_once dirname(__DIR__, 2) . '/Support/RecordVersionTestCase.php';

/**
 * 過去の版の表示（仕様 表C・表D）
 */
final class DisplayTest extends RecordVersionTestCase
{
    /** 今は存在しない項目 ID（当時あってその後削除した項目の代わり） */
    private const DELETED_FIELD_ID = 999999901;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableVersioning();
    }

    public function test_今もあり見られる項目は今の表示名と当時の値を出す_表Cルール1(): void
    {
        $recordId = $this->createPotential('表示');
        RecordVersion_Revision_Model::revise($recordId, '改版');
        $this->db->pquery('UPDATE vtiger_potential SET potentialname = ? WHERE potentialid = ?', [self::PREFIX . '改版後', $recordId]);

        $values = $this->displayedValues($this->snapshotFields($recordId));
        $this->assertSame(self::PREFIX . '表示', $values[vtranslate('Potential Name', self::MODULE)] ?? null, '当時の値を出す');
        $this->assertSame('1', $values[vtranslate('LBL_RECORD_VERSION', 'RecordVersion')] ?? null);
    }

    public function test_非表示にした項目は出さない_表Cルール3(): void
    {
        $recordId = $this->createPotential('非表示', ['nextstep' => '次の段取り']);
        RecordVersion_Revision_Model::revise($recordId, '改版');
        $fieldId = (int) $this->scalar("SELECT fieldid FROM vtiger_field WHERE tabid = ? AND fieldname = 'nextstep'", [$this->tabId]);
        $this->db->pquery('UPDATE vtiger_field SET presence = 1 WHERE fieldid = ?', [$fieldId]);
        try {
            \Vtiger_Cache::$cacheEnable = false;
            $values = $this->displayedValues($this->snapshotFields($recordId));
        } finally {
            $this->db->pquery('UPDATE vtiger_field SET presence = 2 WHERE fieldid = ?', [$fieldId]);
        }
        $this->assertNotContains('次の段取り', $values);
    }

    public function test_当時まだ無かった項目は出さない_表Cルール6(): void
    {
        $recordId = $this->createPotential('当時無い');
        RecordVersion_Revision_Model::revise($recordId, '改版');
        $fields = array_filter(
            $this->snapshotFields($recordId),
            fn (array $entry): bool => ($entry['name'] ?? '') !== 'potentialname'
        );
        $this->assertArrayNotHasKey(vtranslate('Potential Name', self::MODULE), $this->displayedValues($fields));
    }

    public function test_削除された項目は管理者にだけ当時の表示名と値で出す_表Cルール4と5(): void
    {
        $recordId = $this->createPotential('削除項目');
        RecordVersion_Revision_Model::revise($recordId, '改版');
        $fields = $this->snapshotFields($recordId);
        // 当時あって、その後削除した項目（今の vtiger_field に無い項目 ID）
        $fields[(string) self::DELETED_FIELD_ID] = [
            'fieldid' => self::DELETED_FIELD_ID,
            'name' => 'cf_spectest_deleted',
            'label' => 'SPECTEST Deleted',
            'label_ja' => 'SPECTEST 削除した項目',
            'label_en' => 'SPECTEST Deleted field',
            'uitype' => 1,
            'datatype' => 'string',
            'value' => '当時の値',
        ];

        $admin = (new RecordVersion_Display_Model('¥'))->buildFieldBlocks(self::MODULE, $fields);
        $this->assertCount(1, $admin['deleted'], '今もある項目は「削除された項目」に出さない');
        $expectedLabel = \Vtiger_Language_Handler::getLanguage() === 'ja_jp' ? 'SPECTEST 削除した項目' : 'SPECTEST Deleted field';
        $this->assertSame(['label' => $expectedLabel, 'value' => '当時の値'], $admin['deleted'][0], '当時の表示名と値で出す');

        $this->loginAs($this->getNonAdminUserId());
        $user = (new RecordVersion_Display_Model('¥'))->buildFieldBlocks(self::MODULE, $fields);
        $this->assertSame([], $user['deleted'], '管理者でない利用者には出さない');
    }

    public function test_削除された関連レコードは管理者にだけ出し今のレコードを開くは出さない_表D(): void
    {
        $contactId = (int) $this->scalar("SELECT crmid FROM vtiger_crmentity WHERE setype = 'Contacts' AND deleted = 0 ORDER BY crmid LIMIT 1");
        $relationId = (int) $this->scalar(
            'SELECT relation_id FROM vtiger_relatedlists WHERE tabid = ? AND related_tabid = ? LIMIT 1',
            [$this->tabId, getTabid('Contacts')]
        );
        RecordVersion_Config_Model::saveIncludedRelationIds($this->tabId, [$relationId]);
        $recordId = $this->createPotential('関連');
        $this->db->pquery('INSERT INTO vtiger_contpotentialrel (contactid, potentialid) VALUES (?, ?)', [$contactId, $recordId]);
        RecordVersion_Revision_Model::revise($recordId, '改版');

        $parent = Vtiger_Record_Model::getInstanceById($recordId, self::MODULE);
        $relation = $this->snapshotRelation($recordId, 0);

        $row = $this->findRow((new RecordVersion_Display_Model('¥'))->buildRelation($parent, $relation, 0, 1), $contactId);
        $this->assertNotNull($row, '今もあり見られる行は出す');
        $this->assertNotSame('', $row['current_url'], '「今のレコードを開く」を出す');

        $this->db->pquery('UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid = ?', [$contactId]);
        try {
            $row = $this->findRow((new RecordVersion_Display_Model('¥'))->buildRelation($parent, $relation, 0, 1), $contactId);
            $this->assertNotNull($row, '削除された行は管理者には出す');
            $this->assertSame('', $row['current_url'], '削除された行に「今のレコードを開く」は出さない');

            $this->loginAs($this->getNonAdminUserId());
            $built = (new RecordVersion_Display_Model('¥'))->buildRelation($parent, $relation, 0, 1);
            $this->assertNull($this->findRow($built, $contactId), '管理者でない利用者には削除された行を出さない');
        } finally {
            $this->db->pquery('UPDATE vtiger_crmentity SET deleted = 0 WHERE crmid = ?', [$contactId]);
            $this->db->pquery('DELETE FROM vtiger_contpotentialrel WHERE potentialid = ?', [$recordId]);
        }
    }

    /**
     * @param array<string, array<string, mixed>> $fields
     * @return array<string, string> 表示名 => 値（HTML のタグを除いたもの）
     */
    private function displayedValues(array $fields): array
    {
        $values = [];
        foreach ((new RecordVersion_Display_Model('¥'))->buildFieldBlocks(self::MODULE, $fields)['blocks'] as $block) {
            foreach ($block['fields'] as $field) {
                $values[$field['label']] = trim(strip_tags(html_entity_decode($field['value'])));
            }
        }
        return $values;
    }

    /**
     * @param array<string, mixed>|null $relation
     * @return array<string, mixed>|null
     */
    private function findRow(?array $relation, int $relatedId): ?array
    {
        foreach (RecordVersion_Util_Helper::toList($relation['rows'] ?? []) as $row) {
            $row = RecordVersion_Util_Helper::toArray($row);
            if (RecordVersion_Util_Helper::toInt($row['id'] ?? 0) === $relatedId) {
                return $row;
            }
        }
        return null;
    }
}
